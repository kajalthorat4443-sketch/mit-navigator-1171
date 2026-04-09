/**
 * Institute Map — Frontend Application
 * Handles: floor switching, room selection, search, zoom, tooltips
 */

const App = (() => {

  /* ─────────────────────── State ─────────────────────── */
  let allData = null;
  let curFloor = 'ground';
  let selRoom = null;
  let zoomLevel = 1;
  let searchMode = false;
  let roomIndex = {};
  let routePlan = null;
  let activeStageEl = null;
  let miniMapRaf = null;

  const ZOOM_MIN = 0.6, ZOOM_MAX = 2.0, ZOOM_STEP = 0.15;
  const FLOOR_ORDER = ['ground', 'first', 'second', 'third'];

  const TYPE_META = {
    lab: { label: 'Laboratory', icon: '🔬', color: '#3B82F6' },
    office: { label: 'Office', icon: '🏢', color: '#10B981' },
    hall: { label: 'Hall', icon: '🎭', color: '#F59E0B' },
    classroom: { label: 'Classroom', icon: '📚', color: '#8B5CF6' },
    utility: { label: 'Utility', icon: '⚙️', color: '#EF4444' },
    common: { label: 'Common Area', icon: '🪑', color: '#06B6D4' },
    stairs: { label: 'Stairs / Lift', icon: '🪜', color: '#64748B' },
    server: { label: 'Server Room', icon: '🖥️', color: '#F97316' },
    exit: { label: 'Exit', icon: '🚪', color: '#EC4899' },
  };

  function buildImageCandidates(imagePath) {
    const raw = String(imagePath || '').trim();
    if (!raw) return [];

    const list = [raw];
    const add = candidate => {
      if (candidate && !list.includes(candidate)) list.push(candidate);
    };

    add(raw.replace('_floor.', '_flour.'));
    add(raw.replace('_flour.', '_floor.'));

    if (/\.jpe?g$/i.test(raw)) {
      add(raw.replace(/\.jpe?g$/i, '.svg'));
      add(raw.replace('_floor.', '_flour.').replace(/\.jpe?g$/i, '.svg'));
    } else if (/\.svg$/i.test(raw)) {
      add(raw.replace(/\.svg$/i, '.jpg'));
      add(raw.replace('_flour.', '_floor.').replace(/\.svg$/i, '.jpg'));
    }

    return list;
  }

  function probeImageSource(path, done) {
    let settled = false;
    const finish = result => {
      if (settled) return;
      settled = true;
      done(result);
    };

    const img = new Image();
    img.onload = () => finish({
      path,
      width: img.naturalWidth || 760,
      height: img.naturalHeight || 860,
      hasImage: true
    });
    img.onerror = () => finish(null);
    img.src = path;

    if (img.complete && img.naturalWidth) {
      finish({
        path,
        width: img.naturalWidth || 760,
        height: img.naturalHeight || 860,
        hasImage: true
      });
    }
  }

  function loadFirstAvailableImage(candidates, done) {
    const queue = Array.isArray(candidates) ? candidates.filter(Boolean) : [];

    const tryNext = idx => {
      if (idx >= queue.length) {
        done({ path: '', width: 760, height: 860, hasImage: false });
        return;
      }

      probeImageSource(queue[idx], result => {
        if (result?.hasImage) {
          done(result);
          return;
        }
        tryNext(idx + 1);
      });
    };

    tryNext(0);
  }

  /* ─────────────────────── Init ─────────────────────── */
  function init() {
    loadData();
    bindSearch();
    bindZoom();
    bindKeyboard();
    bindRouteFinder();
    bindMiniMap();
    initVoiceNav();
  }

  /* ─────────────────────── Voice Navigation ───────────────── */
  let recognition;
  let isListening = false;

  function initVoiceNav() {
    const btn = document.getElementById('voiceNavBtn');
    if (!btn) return;

    const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRec) {
      btn.style.opacity = '0.5';
      btn.title = 'Voice navigation is only supported in Chrome or Edge.';
      btn.addEventListener('click', () => {
        showToast('Voice navigation is not supported in this browser. Please use Chrome or Edge.');
      });
      return;
    }

    recognition = new SpeechRec();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'en-US';

    recognition.onstart = () => {
      isListening = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Listening...</span>';
      btn.style.backgroundColor = 'rgba(239, 68, 68, 0.15)';
      btn.style.color = '#EF4444';
      btn.style.borderColor = 'rgba(239, 68, 68, 0.3)';
      showToast('Listening... Please say your destination.');
    };

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript.toLowerCase();
      console.log('Voice Input:', transcript);
      processVoiceCommand(transcript);
    };

    recognition.onerror = (event) => {
      console.error('Speech recognition error', event.error);
      showToast('Error listening to voice command.');
      resetVoiceBtn();
    };

    recognition.onend = () => {
      resetVoiceBtn();
    };

    btn.addEventListener('click', () => {
      if (isListening) {
        recognition.stop();
      } else {
        recognition.start();
      }
    });
  }

  function resetVoiceBtn() {
    isListening = false;
    const btn = document.getElementById('voiceNavBtn');
    if (btn) {
      btn.innerHTML = '<i class="fas fa-microphone"></i> <span>Voice Navigation</span>';
      btn.style.backgroundColor = 'rgba(59, 130, 246, 0.15)';
      btn.style.color = '#60A5FA';
      btn.style.borderColor = 'rgba(59, 130, 246, 0.3)';
    }
  }

  // Add your free Google Gemini API key here for better voice recognition
  // Get one free at: https://aistudio.google.com/app/apikey
  const GEMINI_API_KEY = 'AIzaSyAGrHsiuQiRdEI6xHLHJLMbVOZBQF0DOGk';

  async function processVoiceCommand(command) {
    if (!allData) return;

    if (GEMINI_API_KEY) {
      showToast("AI is processing your command...");

      const roomList = [];
      Object.entries(allData.floors).forEach(([fKey, fData]) => {
        fData.rooms.forEach(room => {
          roomList.push({ id: room.id, name: room.name, floor: fKey });
        });
      });

      const prompt = `You are a helpful navigation assistant for an interactive map.
Input voice command: "${command}"

Available rooms in JSON array:
${JSON.stringify(roomList)}

Task: Identify the user's intended "START" room and "DESTINATION" room from the voice command.
- If the user explicitly mentions a starting point (e.g., "starting lab one", "from BGF01"), find its corresponding ID.
- If the user explicitly mentions a destination (e.g., "destination point is lab 2", "go to BGF02"), find its corresponding ID.
- If only one room is mentioned, it is the DESTINATION. The START should be null.
- Pay attention to numbers, phrases, and misspellings (e.g., "bgf01" instead of "BGF-01").
Return ONLY a valid JSON object in this exact format with NO markdown wrapping:
{
  "startMatchId": "ROOM_ID_OR_NULL",
  "endMatchId": "ROOM_ID_OR_NULL"
}`;

      try {
        const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            contents: [{ parts: [{ text: prompt }] }],
            generationConfig: { temperature: 0.1 }
          })
        });

        if (!response.ok) throw new Error("API Request Failed");
        const data = await response.json();
        const text = data.candidates[0].content.parts[0].text.trim().replace(/^```json/i, '').replace(/```$/i, '').trim();
        const parsed = JSON.parse(text);

        let startMatch = null;
        let endMatch = null;

        roomList.forEach(r => {
          if (r.id === parsed.startMatchId) startMatch = { room: r, floor: r.floor };
          if (r.id === parsed.endMatchId) endMatch = { room: r, floor: r.floor };
        });

        if (endMatch) {
          if (startMatch && startMatch.room.id !== endMatch.room.id) {
            speakText(`Routing from ${startMatch.room.name || startMatch.room.id} to ${endMatch.room.name || endMatch.room.id}`);
            setRoutePoint('start', startMatch.floor, startMatch.room.id);
            setRoutePoint('end', endMatch.floor, endMatch.room.id);
          } else {
            showToast(`Voice target: ${endMatch.room.name || endMatch.room.id}`);
            const startSel = document.getElementById('routeStart');
            if (!startSel || !startSel.value) {
              setRoutePoint('end', endMatch.floor, endMatch.room.id);
              speakText(`Destination set to ${endMatch.room.name || endMatch.room.id}. Please select a starting point.`);
            } else {
              setRoutePoint('end', endMatch.floor, endMatch.room.id);
            }
          }
        } else {
          speakText("Sorry, I couldn't understand which room you meant.");
          showToast("Room not found by AI.");
        }
        return; // Skip fallback
      } catch (err) {
        console.error("Gemini Parsing Error", err);
        showToast("AI processing failed, falling back to local search...");
      }
    }

    // --- Local Fallback Parser ---
    let matchedRooms = [];

    // Simple heuristic: If words like "from", "start", "starting" are spoken, find what comes after
    const hasFrom = command.includes("from ") || command.includes("start ") || command.includes("starting ");
    const hasDest = command.includes("destination ");

    // Sort rooms by descending name length to match longer names first ("Main Computer Dept" before "Main")
    const allRoomsFlat = [];
    Object.entries(allData.floors).forEach(([fKey, fData]) => {
      fData.rooms.forEach(room => allRoomsFlat.push({ room, floor: fKey }));
    });
    allRoomsFlat.sort((a, b) => b.room.name.length - a.room.name.length);

    allRoomsFlat.forEach(({ room, floor }) => {
      const idLower = room.id.toLowerCase();
      const nameLower = room.name.toLowerCase();
      const cmdClean = command.replace(/[\s-]/g, '');
      const idClean = idLower.replace(/[\s-]/g, '');

      const matchPosId = command.indexOf(idLower);
      const matchPosName = nameLower.length > 3 ? command.indexOf(nameLower) : -1;
      const matchPosClean = cmdClean.indexOf(idClean);

      if (matchPosId > -1 || matchPosName > -1 || matchPosClean > -1) {
        // Prevent matching sub-words if a larger room already matched at this spot
        if (!matchedRooms.find(m => m.room.id === room.id)) {
          // Heuristic: determine index in the original string to know which was spoken first
          const firstIdx = Math.max(matchPosId, matchPosName, command.replace(/[\s-]/g, ' ').indexOf(idClean));
          matchedRooms.push({ room, floor, idx: firstIdx });

          // Replace the matched part in the command so we don't double-match substrings
          if (matchPosName > -1) command = command.replace(nameLower, '---');
          if (matchPosId > -1) command = command.replace(idLower, '---');
        }
      }
    });

    if (matchedRooms.length > 0) {
      if (matchedRooms.length >= 2) {
        matchedRooms.sort((a, b) => a.idx - b.idx);

        let startMatch = null;
        let endMatch = null;

        if (hasFrom || hasDest) {
          // If they said "starting lab 1 and destination lab 2" -> starts with the "starting" one (earlier found)
          // Even if they just said "destination is lab 2 and starting is lab 1" -> usually the "destination" word comes first and grabs the next match

          // Simplest heuristic: check exact location of "start", "from", "destination", "to"
          const toIdx = command.indexOf(" to");
          const destIdx = command.indexOf("destination");
          const fromIdx = command.indexOf("from ");
          const startIdx = command.indexOf("start");

          const endWordIdx = Math.max(toIdx, destIdx);
          const startWordIdx = Math.max(fromIdx, startIdx);

          if (startWordIdx > -1 && startWordIdx > endWordIdx && endWordIdx > -1) {
            // Format: "destination lab 2 starting lab 1"
            startMatch = matchedRooms[1];
            endMatch = matchedRooms[0];
          } else {
            // Format: "starting lab 1 destination lab 2" or implicitly assumed
            startMatch = matchedRooms[0];
            endMatch = matchedRooms[1];
          }
        } else {
          // Example: "Navigate BGF-01 BGF-02"
          // Assume destination comes last natively
          startMatch = matchedRooms[0];
          endMatch = matchedRooms[1];
        }

        speakText(`Routing from ${startMatch.room.name || startMatch.room.id} to ${endMatch.room.name || endMatch.room.id}`);
        setRoutePoint('start', startMatch.floor, startMatch.room.id);
        setRoutePoint('end', endMatch.floor, endMatch.room.id);
      } else {
        const target = matchedRooms[0];
        showToast(`Voice target: ${target.room.name || target.room.id}`);

        const startSel = document.getElementById('routeStart');
        if (!startSel || !startSel.value) {
          setRoutePoint('end', target.floor, target.room.id);
          speakText(`Destination set to ${target.room.name || target.room.id}. Please select a starting point.`);
        } else {
          setRoutePoint('end', target.floor, target.room.id);
        }
      }
    } else {
      speakText("Sorry, I couldn't find that room.");
      showToast("Room not found in voice command.");
    }
  }

  function speakText(text) {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    window.speechSynthesis.speak(utterance);
  }

  /* ─────────────────────── Data Load ─────────────────── */
  function loadData() {
    fetch(`api/rooms.php?t=${Date.now()}`, { cache: 'no-store' })
      .then(r => r.json())
      .then(data => {
        allData = data;
        buildRoomIndex();
        buildFloorTabs();
        buildRouteSelectors();
        renderFloor('ground');
        updateStats();
        setRouteStatus('Choose start and destination to draw route.');
        renderRouteFloorNav();
      })
      .catch(() => {
        // Fallback: try loading inline data from window
        if (window.MAP_DATA) {
          allData = window.MAP_DATA;
          buildRoomIndex();
          buildFloorTabs();
          buildRouteSelectors();
          renderFloor('ground');
          updateStats();
          setRouteStatus('Choose start and destination to draw route.');
          renderRouteFloorNav();
        }
      });
  }

  /* ─────────────────────── Floor Tabs ─────────────────── */
  function buildFloorTabs() {
    const container = document.getElementById('floorTabs');
    if (!container || !allData) return;
    container.innerHTML = '';

    const labels = { ground: 'Ground', first: '1st Floor', second: '2nd Floor', third: '3rd Floor' };
    const nums = { ground: 'G', first: '1', second: '2', third: '3' };

    Object.entries(allData.floors).forEach(([key, floor]) => {
      const btn = document.createElement('button');
      btn.className = 'floor-tab' + (key === curFloor ? ' active' : '');
      btn.dataset.floor = key;
      btn.innerHTML = `
        <span class="ft-num">${nums[key] || key[0].toUpperCase()}</span>
        <span>${labels[key] || floor.label}</span>
        <span class="ft-count">${floor.rooms.length}</span>`;
      btn.addEventListener('click', () => switchFloor(key));
      container.appendChild(btn);
    });
  }

  function switchFloor(floorKey) {
    curFloor = floorKey;
    selRoom = null;
    closeInfoPanel();
    clearSearch();
    buildFloorTabs();
    renderFloor(floorKey);
    updateStats();
  }

  /* ─────────────────────── Map Render ─────────────────── */
  function renderFloor(floorKey) {
    if (!allData) return;
    const floor = allData.floors[floorKey];
    if (!floor) return;

    // Update banner
    const banner = document.getElementById('floorBannerName');
    const bannerSub = document.getElementById('floorBannerSub');
    if (banner) banner.textContent = floor.label;
    if (bannerSub) bannerSub.textContent = `${floor.rooms.length} rooms  ·  Building B`;

    // Build map
    const wrapper = document.getElementById('mapWrapper');
    wrapper.innerHTML = '';

    const container = document.createElement('div');
    container.className = 'map-container';
    container.id = 'mapContainer';
    container.style.transform = `scale(${zoomLevel})`;

    const stage = document.createElement('div');
    stage.className = 'map-stage';
    activeStageEl = stage;

    // SVG overlay — sized from source image dimensions
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'map-overlay');
    svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

    stage.appendChild(svg);
    container.appendChild(stage);
    wrapper.appendChild(container);

    // Tooltip element
    ensureTooltip();

    // Draw zones once floor image dimensions are known
    const draw = (W, H, hasImage, imagePath) => {
      const routeFocused = Array.isArray(routePlan?.floorPath) && routePlan.floorPath.includes(floorKey);
      stage.classList.toggle('no-image', !hasImage);
      stage.classList.toggle('route-focus', routeFocused);
      stage.style.backgroundImage = hasImage && imagePath ? `url("${imagePath}")` : 'none';
      stage.style.aspectRatio = `${W} / ${H}`;

      renderMiniMapFloor(floor, W, H, hasImage, imagePath);

      svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
      svg.setAttribute('width', '100%');
      svg.setAttribute('height', '100%');
      svg.innerHTML = '';

      floor.rooms.forEach(room => {
        const x = (room.left / 100) * W;
        const y = (room.top / 100) * H;
        const w = (room.width / 100) * W;
        const h = (room.height / 100) * H;

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('class', `room-zone${selRoom?.id === room.id ? ' selected' : ''}`);
        rect.setAttribute('data-id', room.id);
        rect.setAttribute('data-type', room.type);
        rect.setAttribute('x', x); rect.setAttribute('y', y);
        rect.setAttribute('width', w); rect.setAttribute('height', h);
        rect.setAttribute('rx', 4); rect.setAttribute('ry', 4);

        // Room ID label
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('class', 'room-label');
        text.setAttribute('x', x + w / 2);
        text.setAttribute('y', y + h / 2);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('dominant-baseline', 'middle');
        text.setAttribute('font-size', Math.max(7, Math.min(11, w / 7)));
        text.setAttribute('font-weight', '700');
        text.setAttribute('font-family', 'Segoe UI, system-ui, sans-serif');
        text.setAttribute('fill', '#fff');
        text.setAttribute('opacity', '0');
        text.setAttribute('pointer-events', 'none');
        text.setAttribute('paint-order', 'stroke');
        text.setAttribute('stroke', 'rgba(0,0,0,0.6)');
        text.setAttribute('stroke-width', '3');
        text.textContent = room.id;

        rect.addEventListener('mouseenter', e => onRoomHover(e, room, text));
        rect.addEventListener('mouseleave', () => onRoomLeave(text));
        rect.addEventListener('click', () => onRoomClick(room));

        g.appendChild(rect);
        g.appendChild(text);

        if (room.door) {
          const door = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
          door.setAttribute('class', 'room-door-marker');
          door.setAttribute('cx', (room.door.left / 100) * W);
          door.setAttribute('cy', (room.door.top / 100) * H);
          door.setAttribute('r', '4');
          door.setAttribute('fill', '#f97316');
          door.setAttribute('stroke', '#fff');
          door.setAttribute('stroke-width', '1');
          door.setAttribute('pointer-events', 'none');
          g.appendChild(door);
        }

        svg.appendChild(g);
      });

      drawRouteLayer(svg, floorKey, W, H);
      rerenderZones();
      renderRouteFloorNav();
      queueMiniMapViewportUpdate();
    };

    loadFirstAvailableImage(buildImageCandidates(floor.image), result => {
      draw(result.width || 760, result.height || 860, !!result.hasImage, result.path || '');
    });
  }

  /* ─────────────────────── Room Interactions ─────────────── */
  function onRoomHover(e, room, textEl) {
    textEl.setAttribute('opacity', '0.9');
    showTooltip(e, room);
  }

  function onRoomLeave(textEl) {
    textEl.setAttribute('opacity', '0');
    hideTooltip();
  }

  function onRoomClick(room) {
    if (selRoom?.id === room.id) {
      selRoom = null;
      closeInfoPanel();
      rerenderZones();
      return;
    }
    selRoom = room;
    showInfoPanel(room);
    rerenderZones();
    scrollToRoom(room);
  }

  function rerenderZones() {
    const startId = routePlan?.start?.room?.id;
    const endId = routePlan?.end?.room?.id;
    const startFloor = routePlan?.start?.floorKey;
    const endFloor = routePlan?.end?.floorKey;

    document.querySelectorAll('.room-zone').forEach(rect => {
      rect.classList.toggle('selected', rect.dataset.id === selRoom?.id);
      rect.classList.toggle(
        'route-point',
        (rect.dataset.id === startId && curFloor === startFloor) ||
        (rect.dataset.id === endId && curFloor === endFloor)
      );
    });

    renderMiniMapRoute();
    renderMiniMapPins();
  }

  function scrollToRoom(room) {
    const rect = document.querySelector(`[data-id="${room.id}"]`);
    if (rect) rect.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
  }

  /* ─────────────────────── Info Panel ─────────────────── */
  function showInfoPanel(room) {
    const panel = document.getElementById('infoPanel');
    const content = document.getElementById('infoPanelContent');
    if (!panel || !content) return;

    const meta = TYPE_META[room.type] || { label: room.type, icon: '📍', color: '#64748B' };
    const floor = allData.floors[curFloor];

    content.innerHTML = `
      <div class="info-header">
        <button class="info-close" onclick="App.closeInfoPanel()" title="Close">✕</button>
        <div class="info-type-badge type-${room.type}">
          <span class="info-type-dot"></span>
          ${meta.label}
        </div>
        <div class="info-id">${room.id}</div>
        <div class="info-name">${room.name}</div>
      </div>
      <div class="info-body">
        ${room.description ? `<div class="info-desc">${room.description}</div>` : ''}
        <div class="info-grid">
          <div class="info-row">
            <span class="info-lbl">Floor</span>
            <span class="info-val">${floor?.label || curFloor}</span>
          </div>
          <div class="info-row">
            <span class="info-lbl">Building</span>
            <span class="info-val">${allData.building || 'Building B'}</span>
          </div>
          ${room.area ? `
          <div class="info-row">
            <span class="info-lbl">Area</span>
            <span class="info-val">${room.area} sq.mt.</span>
          </div>` : ''}
          ${room.capacity ? `
          <div class="info-row">
            <span class="info-lbl">Capacity</span>
            <span class="info-val">${room.capacity} persons</span>
          </div>` : ''}
          ${room.contact ? `
          <div class="info-row">
            <span class="info-lbl">Contact</span>
            <span class="info-val">${room.contact}</span>
          </div>` : ''}
          <div class="info-row">
            <span class="info-lbl">Type</span>
            <span class="info-val">${meta.label}</span>
          </div>
        </div>
        <button class="locate-btn" onclick="App.locateRoom('${room.id}')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
          </svg>
          Highlight on map
        </button>
        <div class="route-shortcuts">
          <button class="locate-btn route-shortcut-btn" onclick="App.setRoutePoint('start','${curFloor}','${room.id}')">Set as start</button>
          <button class="locate-btn route-shortcut-btn" onclick="App.setRoutePoint('end','${curFloor}','${room.id}')">Set as destination</button>
        </div>
      </div>`;

    panel.classList.add('open');
  }

  function closeInfoPanel() {
    const panel = document.getElementById('infoPanel');
    if (panel) panel.classList.remove('open');
    selRoom = null;
    rerenderZones();
  }

  function locateRoom(id) {
    const rect = document.querySelector(`[data-id="${id}"]`);
    if (!rect) return;
    rect.classList.add('highlighted');
    rect.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
    showToast(`📍 ${id} located on map`);
    setTimeout(() => rect.classList.remove('highlighted'), 3000);
  }

  /* ─────────────────────── Mini Map ─────────────────── */
  function bindMiniMap() {
    const wrapper = document.getElementById('mapWrapper');
    wrapper?.addEventListener('scroll', queueMiniMapViewportUpdate, { passive: true });
    window.addEventListener('resize', queueMiniMapViewportUpdate);
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function queueMiniMapViewportUpdate() {
    if (miniMapRaf) return;
    miniMapRaf = window.requestAnimationFrame(() => {
      miniMapRaf = null;
      updateMiniMapViewport();
    });
  }

  function renderMiniMapFloor(floor, width, height, hasImage, imagePath) {
    const stage = document.getElementById('miniMapStage');
    const caption = document.getElementById('miniMapCaption');
    if (!stage) return;

    stage.classList.toggle('no-image', !hasImage);
    stage.classList.toggle('route-focus', Array.isArray(routePlan?.floorPath) && routePlan.floorPath.includes(curFloor));
    stage.style.backgroundImage = hasImage && imagePath ? `url("${imagePath}")` : 'none';
    stage.style.aspectRatio = `${width} / ${height}`;

    if (caption) caption.textContent = `${floor.label} overview`;
    renderMiniMapRoute();
    renderMiniMapPins();
    queueMiniMapViewportUpdate();
  }

  function renderMiniMapRoute() {
    const routeSvg = document.getElementById('miniMapRoute');
    if (!routeSvg) return;

    routeSvg.innerHTML = '';
    if (!routePlan?.segments?.length) return;

    const floorSegments = routePlan.segments.filter(seg => seg.floor === curFloor && Array.isArray(seg.points) && seg.points.length >= 2);
    floorSegments.forEach(seg => {
      const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
      polyline.setAttribute('class', 'mini-map-route-path');
      polyline.setAttribute('points', seg.points.map(p => `${p.left},${p.top}`).join(' '));
      routeSvg.appendChild(polyline);
    });
  }

  function appendMiniMapPin(container, left, top, cls) {
    const pin = document.createElement('span');
    pin.className = `mini-map-pin ${cls}`;
    pin.style.left = `${left}%`;
    pin.style.top = `${top}%`;
    container.appendChild(pin);
  }

  function renderMiniMapPins() {
    const pins = document.getElementById('miniMapPins');
    if (!pins) return;

    pins.innerHTML = '';

    if (selRoom) {
      appendMiniMapPin(
        pins,
        selRoom.left + (selRoom.width / 2),
        selRoom.top + (selRoom.height / 2),
        'selected'
      );
    }

    if (!routePlan?.markers) return;
    routePlan.markers
      .filter(marker => marker.floor === curFloor)
      .forEach(marker => {
        appendMiniMapPin(pins, marker.left, marker.top, marker.kind);
      });
  }

  function updateMiniMapViewport() {
    const viewport = document.getElementById('miniMapViewport');
    const wrapper = document.getElementById('mapWrapper');
    if (!viewport || !wrapper || !activeStageEl) return;

    const wrapRect = wrapper.getBoundingClientRect();
    const stageRect = activeStageEl.getBoundingClientRect();
    if (!stageRect.width || !stageRect.height) return;

    const ix1 = Math.max(wrapRect.left, stageRect.left);
    const iy1 = Math.max(wrapRect.top, stageRect.top);
    const ix2 = Math.min(wrapRect.right, stageRect.right);
    const iy2 = Math.min(wrapRect.bottom, stageRect.bottom);

    const visW = Math.max(0, ix2 - ix1);
    const visH = Math.max(0, iy2 - iy1);

    let wPct = clamp((visW / stageRect.width) * 100, 0, 100);
    let hPct = clamp((visH / stageRect.height) * 100, 0, 100);
    wPct = Math.max(wPct, 8);
    hPct = Math.max(hPct, 8);

    let xPct = clamp(((ix1 - stageRect.left) / stageRect.width) * 100, 0, 100);
    let yPct = clamp(((iy1 - stageRect.top) / stageRect.height) * 100, 0, 100);
    xPct = clamp(xPct, 0, 100 - wPct);
    yPct = clamp(yPct, 0, 100 - hPct);

    viewport.style.left = `${xPct}%`;
    viewport.style.top = `${yPct}%`;
    viewport.style.width = `${wPct}%`;
    viewport.style.height = `${hPct}%`;
  }

  /* ─────────────────────── Route Finder ─────────────────── */
  function bindRouteFinder() {
    document.getElementById('findRouteBtn')?.addEventListener('click', findRouteFromInputs);
    document.getElementById('clearRouteBtn')?.addEventListener('click', () => clearRoute(true));
  }

  function roomRefKey(floorKey, roomId) {
    return `${floorKey}|${roomId}`;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function buildRoomIndex() {
    roomIndex = {};
    if (!allData?.floors) return;

    Object.entries(allData.floors).forEach(([floorKey, floorData]) => {
      floorData.rooms.forEach(room => {
        const key = roomRefKey(floorKey, room.id);
        roomIndex[key] = {
          key,
          floorKey,
          floorLabel: floorData.label,
          room
        };
      });
    });
  }

  function buildRouteSelectors() {
    const startSel = document.getElementById('routeStart');
    const endSel = document.getElementById('routeEnd');
    if (!startSel || !endSel || !allData?.floors) return;

    const prevStart = startSel.value;
    const prevEnd = endSel.value;

    const options = [];
    Object.entries(allData.floors).forEach(([floorKey, floorData]) => {
      const roomOpts = floorData.rooms.map(room => {
        const key = roomRefKey(floorKey, room.id);
        return `<option value="${escapeHtml(key)}">${escapeHtml(room.id)} - ${escapeHtml(room.name)}</option>`;
      }).join('');
      options.push(`<optgroup label="${escapeHtml(floorData.label)}">${roomOpts}</optgroup>`);
    });

    startSel.innerHTML = `<option value="">Select start room</option>${options.join('')}`;
    endSel.innerHTML = `<option value="">Select destination room</option>${options.join('')}`;

    if (roomIndex[prevStart]) startSel.value = prevStart;
    if (roomIndex[prevEnd]) endSel.value = prevEnd;
  }

  function resolveRoomRef(value) {
    return roomIndex[value] || null;
  }

  function setRouteStatus(message, type = 'neutral') {
    const status = document.getElementById('routeStatus');
    if (!status) return;

    status.classList.remove('route-ok', 'route-error');
    if (type === 'ok') status.classList.add('route-ok');
    if (type === 'error') status.classList.add('route-error');
    status.textContent = message;
  }

  function getFloorLabel(floorKey) {
    return allData?.floors?.[floorKey]?.label || floorKey;
  }

  function renderRouteFloorNav() {
    const nav = document.getElementById('routeFloorNav');
    if (!nav) return;

    const floorPath = routePlan?.floorPath;
    if (!Array.isArray(floorPath) || floorPath.length <= 1) {
      nav.innerHTML = '';
      nav.style.display = 'none';
      return;
    }

    nav.innerHTML = '';
    nav.style.display = 'flex';

    floorPath.forEach((floorKey, idx) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'route-floor-btn' + (floorKey === curFloor ? ' active' : '');
      btn.textContent = getFloorLabel(floorKey);
      btn.addEventListener('click', () => jumpToRouteFloor(floorKey));
      nav.appendChild(btn);

      if (idx < floorPath.length - 1) {
        const sep = document.createElement('span');
        sep.className = 'route-floor-sep';
        sep.textContent = '->';
        nav.appendChild(sep);
      }
    });
  }

  function jumpToRouteFloor(floorKey) {
    const floorPath = routePlan?.floorPath;
    if (!Array.isArray(floorPath) || !floorPath.includes(floorKey)) return;
    if (floorKey === curFloor) return;
    switchFloor(floorKey);
  }

  function clearRoute(clearSelections = false) {
    routePlan = null;

    if (clearSelections) {
      const startSel = document.getElementById('routeStart');
      const endSel = document.getElementById('routeEnd');
      if (startSel) startSel.value = '';
      if (endSel) endSel.value = '';
    }

    setRouteStatus('Choose start and destination to draw route.');
    renderRouteFloorNav();
    renderFloor(curFloor);
  }

  function setRoutePoint(kind, floorKey, roomId) {
    const target = kind === 'start' ? document.getElementById('routeStart') : document.getElementById('routeEnd');
    if (!target) return;

    const key = roomRefKey(floorKey, roomId);
    if (!roomIndex[key]) return;

    target.value = key;
    showToast(`${kind === 'start' ? 'Start' : 'Destination'} set: ${roomId}`);

    const startSel = document.getElementById('routeStart');
    const endSel = document.getElementById('routeEnd');
    if (startSel?.value && endSel?.value) {
      findRouteFromInputs();
    }
  }

  function findRouteFromInputs() {
    const startSel = document.getElementById('routeStart');
    const endSel = document.getElementById('routeEnd');
    if (!startSel || !endSel) return;

    const startRef = resolveRoomRef(startSel.value);
    const endRef = resolveRoomRef(endSel.value);

    if (!startRef || !endRef) {
      setRouteStatus('Please select both start and destination rooms.', 'error');
      return;
    }

    if (startRef.key === endRef.key) {
      setRouteStatus('Start and destination cannot be the same room.', 'error');
      return;
    }

    const plan = computeRoute(startRef, endRef);
    if (!plan) {
      setRouteStatus('Route could not be generated for these rooms.', 'error');
      speakText('Route could not be generated for these rooms.');
      return;
    }

    routePlan = plan;
    setRouteStatus(plan.message, 'ok');
    renderRouteFloorNav();
    showToast(`Route ready: ${startRef.room.id} -> ${endRef.room.id}`);
    speakText(plan.message);

    if (plan.preferredFloor !== curFloor) {
      switchFloor(plan.preferredFloor);
    } else {
      renderFloor(curFloor);
    }
  }

  function getFloorTraversal(startFloor, endFloor) {
    const existingFloors = Object.keys(allData?.floors || {});
    const ordered = FLOOR_ORDER.filter(f => existingFloors.includes(f));
    existingFloors.forEach(f => {
      if (!ordered.includes(f)) ordered.push(f);
    });

    const startIdx = ordered.indexOf(startFloor);
    const endIdx = ordered.indexOf(endFloor);
    if (startIdx < 0 || endIdx < 0) return null;

    const step = startIdx <= endIdx ? 1 : -1;
    const path = [];
    for (let i = startIdx; ; i += step) {
      path.push(ordered[i]);
      if (i === endIdx) break;
    }
    return path;
  }

  function getPreferredConnector(floorKey) {
    const floor = allData?.floors?.[floorKey];
    if (!floor?.rooms?.length) return null;

    const connectors = floor.rooms.filter(room => room.type === 'stairs');
    if (!connectors.length) return null;

    return connectors.find(r => /stair/i.test(`${r.id} ${r.name}`)) || connectors[0];
  }

  function getRoomCenter(room) {
    if (room.door) {
      return {
        left: room.door.left,
        top: room.door.top
      };
    }
    return {
      left: room.left + (room.width / 2),
      top: room.top + (room.height / 2)
    };
  }

  function samePoint(a, b) {
    return Math.abs(a.left - b.left) < 0.01 && Math.abs(a.top - b.top) < 0.01;
  }

  function createSegmentPoints(start, end, floorKey, startRoomId, endRoomId) {
    const floor = allData?.floors?.[floorKey];
    if (!floor || !floor.rooms) {
      return [start, { left: end.left, top: start.top }, end];
    }

    // 1. Check for Main Floor Path Backbone
    if (floor.mainPath && Array.isArray(floor.mainPath) && floor.mainPath.length >= 2) {
      function snapToPolyline(point, polyline) {
        let minDist = Infinity;
        let closestPt = null;
        let segIdx = -1;

        for (let i = 0; i < polyline.length - 1; i++) {
          const A = polyline[i];
          const B = polyline[i + 1];

          const dx = B.left - A.left;
          const dy = B.top - A.top;
          const lenSq = dx * dx + dy * dy;

          let t = 0;
          if (lenSq !== 0) {
            t = ((point.left - A.left) * dx + (point.top - A.top) * dy) / lenSq;
            t = Math.max(0, Math.min(1, t));
          }

          const projX = A.left + t * dx;
          const projY = A.top + t * dy;

          const dist = Math.hypot(point.left - projX, point.top - projY);

          if (dist < minDist) {
            minDist = dist;
            closestPt = { left: projX, top: projY };
            segIdx = i;
          }
        }
        return { pt: closestPt, segIdx: segIdx };
      }

      const startSnap = snapToPolyline(start, floor.mainPath);
      const endSnap = snapToPolyline(end, floor.mainPath);

      let pathLine = [start, startSnap.pt];

      const i1 = startSnap.segIdx;
      const i2 = endSnap.segIdx;

      if (i1 < i2) {
        for (let i = i1 + 1; i <= i2; i++) {
          pathLine.push(floor.mainPath[i]);
        }
      } else if (i1 > i2) {
        for (let i = i1; i >= i2 + 1; i--) {
          pathLine.push(floor.mainPath[i]);
        }
      }

      pathLine.push(endSnap.pt);
      pathLine.push(end);

      return pathLine;
    }

    function getCost(x, y) {
      if (x < -2 || x > 102 || y < -2 || y > 102) return 99999;
      for (let i = 0; i < floor.rooms.length; i++) {
        const r = floor.rooms[i];
        if (x >= r.left - 0.5 && x <= r.left + r.width + 0.5 && y >= r.top - 0.5 && y <= r.top + r.height + 0.5) {
          if (r.id === startRoomId || r.id === endRoomId || r.type === 'stairs' || r.type === 'exit') continue;
          return 999;
        }
      }
      return 1;
    }

    const startX = Math.round(start.left);
    const startY = Math.round(start.top);
    const endX = Math.round(end.left);
    const endY = Math.round(end.top);

    const openSet = [{ x: startX, y: startY, g: 0, f: 0, dir: null, prev: null }];
    const gScore = new Map();
    gScore.set(startX + ',' + startY, 0);

    let closestNode = openSet[0];
    let closestDist = Math.hypot(startX - endX, startY - endY);

    const dirs = [[0, 1], [1, 0], [0, -1], [-1, 0]];

    let iters = 0;
    while (openSet.length > 0 && iters < 3000) {
      iters++;
      openSet.sort((a, b) => a.f - b.f);
      const curr = openSet.shift();

      if (Math.abs(curr.x - endX) <= 1 && Math.abs(curr.y - endY) <= 1) {
        closestNode = curr;
        break;
      }

      for (let i = 0; i < 4; i++) {
        const nx = curr.x + dirs[i][0] * 2;
        const ny = curr.y + dirs[i][1] * 2;
        const key = nx + ',' + ny;

        const cost = getCost(nx, ny);
        if (cost > 100) continue;

        const turnPen = (curr.dir !== null && curr.dir !== i) ? 5 : 0;
        const tg = curr.g + cost * 2 + turnPen;

        if (!gScore.has(key) || tg < gScore.get(key)) {
          gScore.set(key, tg);
          const h = Math.abs(nx - endX) + Math.abs(ny - endY);
          const newNode = { x: nx, y: ny, g: tg, f: tg + h, dir: i, prev: curr };
          openSet.push(newNode);
          if (h < closestDist) { closestDist = h; closestNode = newNode; }
        }
      }
    }

    const pts = [];
    let p = closestNode;
    while (p) {
      pts.push({ left: p.x, top: p.y });
      p = p.prev;
    }
    pts.reverse();

    const simplified = [start];
    if (pts.length > 1) {
      let lastDx = 0, lastDy = 0;
      for (let i = 0; i < pts.length - 1; i++) {
        const dx = Math.sign(pts[i + 1].left - pts[i].left);
        const dy = Math.sign(pts[i + 1].top - pts[i].top);
        if (dx !== lastDx || dy !== lastDy) {
          simplified.push(pts[i]);
          lastDx = dx; lastDy = dy;
        }
      }
      simplified.push(pts[pts.length - 1]);
    }
    simplified.push(end);
    return simplified;
  }

  function computeRoute(startRef, endRef) {
    const startCenter = getRoomCenter(startRef.room);
    const endCenter = getRoomCenter(endRef.room);

    const markers = [
      { floor: startRef.floorKey, left: startCenter.left, top: startCenter.top, kind: 'start', label: startRef.room.id },
      { floor: endRef.floorKey, left: endCenter.left, top: endCenter.top, kind: 'end', label: endRef.room.id }
    ];

    if (startRef.floorKey === endRef.floorKey) {
      return {
        preferredFloor: startRef.floorKey,
        floorPath: [startRef.floorKey],
        segments: [{ floor: startRef.floorKey, points: createSegmentPoints(startCenter, endCenter, startRef.floorKey, startRef.room.id, endRef.room.id) }],
        markers,
        start: startRef,
        end: endRef,
        message: `${startRef.floorLabel}: ${startRef.room.id} to ${endRef.room.id}. Follow the cyan line.`
      };
    }

    const traversal = getFloorTraversal(startRef.floorKey, endRef.floorKey);
    if (!traversal) return null;

    const connectorStops = [];
    for (const floorKey of traversal) {
      const connectorRoom = getPreferredConnector(floorKey);
      if (!connectorRoom) return null;

      const center = getRoomCenter(connectorRoom);
      connectorStops.push({ floorKey, room: connectorRoom, center });
      markers.push({ floor: floorKey, left: center.left, top: center.top, kind: 'connector', label: connectorRoom.id });
    }

    const firstStop = connectorStops[0];
    const lastStop = connectorStops[connectorStops.length - 1];

    return {
      preferredFloor: startRef.floorKey,
      floorPath: traversal,
      segments: [
        { floor: startRef.floorKey, points: createSegmentPoints(startCenter, firstStop.center, startRef.floorKey, startRef.room.id, firstStop.room.id) },
        { floor: endRef.floorKey, points: createSegmentPoints(lastStop.center, endCenter, endRef.floorKey, lastStop.room.id, endRef.room.id) }
      ],
      markers,
      start: startRef,
      end: endRef,
      message: `Use stairs from ${startRef.floorLabel} to ${endRef.floorLabel}. Use top floor buttons to navigate.`
    };
  }

  function drawRouteLayer(svg, floorKey, width, height) {
    if (!routePlan) return;

    const floorSegments = routePlan.segments.filter(seg => seg.floor === floorKey);
    const floorMarkers = routePlan.markers.filter(m => m.floor === floorKey);
    if (!floorSegments.length && !floorMarkers.length) return;

    const layer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    layer.setAttribute('class', 'route-layer');

    floorSegments.forEach(seg => {
      if (!seg.points || seg.points.length < 2) return;
      const pointsAttr = seg.points
        .map(p => `${(p.left / 100) * width},${(p.top / 100) * height}`)
        .join(' ');

      const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
      polyline.setAttribute('class', 'route-path');
      polyline.setAttribute('points', pointsAttr);
      layer.appendChild(polyline);
    });

    floorMarkers.forEach(marker => {
      const cx = (marker.left / 100) * width;
      const cy = (marker.top / 100) * height;

      const node = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      node.setAttribute('class', `route-node route-node-${marker.kind}`);
      node.setAttribute('cx', cx);
      node.setAttribute('cy', cy);
      node.setAttribute('r', marker.kind === 'connector' ? '5.5' : '6.5');
      layer.appendChild(node);

      if (marker.kind === 'connector') {
        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        label.setAttribute('class', 'route-node-label');
        label.setAttribute('x', cx + 8);
        label.setAttribute('y', cy - 8);
        label.textContent = marker.label;
        layer.appendChild(label);
      }
    });

    svg.appendChild(layer);
  }

  /* ─────────────────────── Search ─────────────────────── */
  function bindSearch() {
    const input = document.getElementById('searchInput');
    if (!input) return;
    let debounce;
    input.addEventListener('input', () => {
      clearTimeout(debounce);
      debounce = setTimeout(() => doSearch(input.value.trim()), 200);
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') { clearSearch(); input.blur(); }
    });
  }

  function doSearch(q) {
    const resultBox = document.getElementById('searchResults');
    if (!resultBox || !allData) return;

    if (!q || q.length < 2) {
      resultBox.style.display = 'none';
      searchMode = false;
      return;
    }

    searchMode = true;
    const ql = q.toLowerCase();
    const hits = [];

    Object.entries(allData.floors).forEach(([fKey, fData]) => {
      fData.rooms.forEach(room => {
        if (
          room.id.toLowerCase().includes(ql) ||
          room.name.toLowerCase().includes(ql) ||
          room.type.toLowerCase().includes(ql)
        ) hits.push({ room, fKey, fLabel: fData.label });
      });
    });

    if (!hits.length) {
      resultBox.innerHTML = `<div style="padding:14px;text-align:center;font-size:12px;color:var(--text-3)">No rooms found for "${q}"</div>`;
    } else {
      resultBox.innerHTML = hits.slice(0, 12).map(({ room, fKey, fLabel }) => `
        <div class="search-result-item" onclick="App.goToRoom('${fKey}','${room.id}')">
          <div class="sri-id">${room.id}</div>
          <div class="sri-name">${room.name}</div>
          <div class="sri-fl">${fLabel}</div>
        </div>`).join('');
    }
    resultBox.style.display = 'block';
  }

  function clearSearch() {
    const input = document.getElementById('searchInput');
    const results = document.getElementById('searchResults');
    if (input) input.value = '';
    if (results) results.style.display = 'none';
    searchMode = false;
  }

  function goToRoom(floorKey, roomId) {
    clearSearch();
    if (floorKey !== curFloor) {
      curFloor = floorKey;
      buildFloorTabs();
      renderFloor(floorKey);
      updateStats();
      
      // Wait for render then select
      setTimeout(() => selectAndHighlight(roomId), 300);
    } else {
      selectAndHighlight(roomId);
    }
  }

  function selectAndHighlight(roomId) {
    const floor = allData.floors[curFloor];
    if (!floor) return;
    const room = floor.rooms.find(r => r.id === roomId);
    if (!room) return;
    selRoom = room;
    showInfoPanel(room);
    rerenderZones();
    const rect = document.querySelector(`[data-id="${roomId}"]`);
    if (rect) {
      rect.classList.add('highlighted');
      rect.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
      setTimeout(() => rect.classList.remove('highlighted'), 3000);
    }
  }

  /* ─────────────────────── Zoom ───────────────────────── */
  
  function bindZoom() {
    document.getElementById('zoomIn')?.addEventListener('click', () => setZoom(zoomLevel + ZOOM_STEP));
    document.getElementById('zoomOut')?.addEventListener('click', () => setZoom(zoomLevel - ZOOM_STEP));
    document.getElementById('zoomReset')?.addEventListener('click', () => setZoom(1));
  }

  function setZoom(level) {
    zoomLevel = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, parseFloat(level.toFixed(2))));
    const container = document.getElementById('mapContainer');
    if (container) container.style.transform = `scale(${zoomLevel})`;
    document.getElementById('zoomLabel').textContent = Math.round(zoomLevel * 100) + '%';
    queueMiniMapViewportUpdate();
  }

  /* ─────────────────────── Keyboard ───────────────────── */
 
  function bindKeyboard() {
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') { closeInfoPanel(); clearSearch(); }
      if (e.key === '+' || e.key === '=') setZoom(zoomLevel + ZOOM_STEP);
      if (e.key === '-') setZoom(zoomLevel - ZOOM_STEP);
      if (e.key === '0') setZoom(1);
      if (e.ctrlKey && e.key === 'f') { e.preventDefault(); document.getElementById('searchInput')?.focus(); }
    });
  }

  /* ─────────────────────── Tooltip ───────────────────── */
  
  function ensureTooltip() {
    if (!document.getElementById('roomTooltip')) {
      const tt = document.createElement('div');
      tt.id = 'roomTooltip';
      tt.className = 'room-tooltip';
      document.body.appendChild(tt);
    }
  }

  function showTooltip(e, room) {
    const tt = document.getElementById('roomTooltip');
    if (!tt) return;
    const meta = TYPE_META[room.type] || {};
    tt.innerHTML = `<div class="room-tooltip-id">${room.id}</div><div class="room-tooltip-name">${room.name}${room.area ? ` · ${room.area} sq.mt.` : ''}</div>`;
    tt.style.left = (e.clientX + 14) + 'px';
    tt.style.top = (e.clientY - 10) + 'px';
    tt.classList.add('show');
  }

  function hideTooltip() {
    document.getElementById('roomTooltip')?.classList.remove('show');
  }

  /* ─────────────────────── Stats ───────────────────────── */
  
  function updateStats() {
    if (!allData) return;
    const floor = allData.floors[curFloor];
    const total = floor?.rooms.length || 0;
    const labs = floor?.rooms.filter(r => r.type === 'lab').length || 0;
    const offices = floor?.rooms.filter(r => r.type === 'office').length || 0;

    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    el('statTotal', total);
    el('statLabs', labs);
    el('statOffices', offices);
    el('statFloor', allData.floors[curFloor]?.label || '—');
  }

  /* ─────────────────────── Toast ───────────────────────── */
  
  function showToast(msg) {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.className = 'toast';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

 
 /* ─────────────────────── Public API ─────────────────── */

 return { init, closeInfoPanel, locateRoom, goToRoom, switchFloor, setRoutePoint, clearRoute };
})();

document.addEventListener('DOMContentLoaded', App.init);
