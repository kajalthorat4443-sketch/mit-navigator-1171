<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map Admin - Upload & Highlight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-panel: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #14b8a6;
            --accent-hover: #0d9488;
            --border: #334155;
            --danger: #ef4444;
            --success: #10b981;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 350px;
            background-color: var(--bg-panel);
            border-right: 1px solid var(--border);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .main-content {
            flex: 1;
            position: relative;
            overflow: auto;
            background-color: var(--bg-dark);
            background-image: radial-gradient(at 0% 0%, rgba(20, 184, 166, 0.1) 0px, transparent 50%), radial-gradient(at 50% 0%, #0f172a 0px, transparent 50%);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }

        h1, h2, h3 {
            margin: 0 0 10px 0;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }

        input, select, textarea {
            background-color: var(--bg-dark);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
        }

        button {
            background-color: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: var(--accent-hover);
        }
        
        button.danger {
            background-color: var(--danger);
        }

        button.success {
            background-color: var(--success);
        }

        #map-container {
            position: relative;
            transform-origin: top left;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            background: #fff; /* For transparent PNG/SVGs */
        }

        #map-image {
            display: block;
            pointer-events: none; /* Let canvas handle events */
            max-width: none;
        }

        #draw-canvas {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
        }

        .room-item {
            background: var(--bg-dark);
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .room-item-title {
            font-weight: 600;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        #toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-panel);
            color: var(--text-main);
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            z-index: 9999;
        }
        
        #toast.show {
            opacity: 1;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="header-bar border-slate-700">
        <div class="flex items-center space-x-2">
            <div class="bg-teal-500 p-1.5 rounded text-white shadow-lg shadow-teal-500/20">
                <i class="fas fa-layer-group text-sm"></i>
            </div>
            <h2 class="text-base font-bold text-white m-0">Map Admin</h2>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="map.php" class="text-slate-400 hover:text-white transition-colors text-xs font-medium"><i class="fas fa-map mr-1"></i> View</a>
            <a href="api/auth.php?action=logout" class="text-red-400 hover:text-red-300 transition-colors text-xs font-medium"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
        </div>
    </div>

    <!-- Step 0: Existing Floors -->
    <div class="control-group" id="step0">
        <h3>Current Floors</h3>
        <div id="existing-floors-list" style="display: flex; flex-direction: column; gap: 8px;">
            <div style="font-size: 13px; color: var(--text-muted);">Loading floors...</div>
        </div>
    </div>

    <!-- Step 1: Upload Image -->
    <div class="control-group" id="step1">
        <h3>1. Upload Floor Plan</h3>
        <label for="image-upload">Select JPG, PNG, or SVG</label>
        <input type="file" id="image-upload" accept=".jpg,.jpeg,.png,.svg">
        <button id="btn-upload">Upload Image</button>
    </div>

    <!-- Step 2: Floor Details (Hidden initially) -->
    <div class="control-group" id="step2" style="display: none;">
        <h3>2. Floor Details</h3>
        <label>Floor Key (e.g., ground, first)</label>
        <input type="text" id="floor-key" placeholder="e.g., ground" required>
        
        <label>Floor Label</label>
        <input type="text" id="floor-label" placeholder="e.g., Ground Floor" required>
        
        <input type="hidden" id="image-path">
    </div>

    <!-- Step 3: Draw & Add Rooms -->
    <div class="control-group" id="step3" style="display: none;">
        <h3>3. Draw Highlight & Add Room</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Click and drag on the image to draw a blue highlight box.</p>
        
        <form id="room-form" style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Top (%)</label>
                    <input type="text" id="r-top" readonly>
                </div>
                <div style="flex: 1;">
                    <label>Left (%)</label>
                    <input type="text" id="r-left" readonly>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Width (%)</label>
                    <input type="text" id="r-width" readonly>
                </div>
                <div style="flex: 1;">
                    <label>Height (%)</label>
                    <input type="text" id="r-height" readonly>
                </div>
            </div>

            <label>Room ID</label>
            <input type="text" id="r-id" placeholder="e.g., BGF-01" required>

            <label>Name</label>
            <input type="text" id="r-name" placeholder="e.g., Main Comp Dept" required>

            <label>Type</label>
            <select id="r-type">
                <option value="office">Office</option>
                <option value="lab">Lab</option>
                <option value="classroom">Classroom</option>
                <option value="hall">Hall / Common</option>
                <option value="stairs">Stairs / Lift</option>
                <option value="exit">Exit</option>
                <option value="server">Server Room</option>
                <option value="utility">Utility / Washroom</option>
            </select>

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Area (sq.m)</label>
                    <input type="text" id="r-area" placeholder="e.g., 78.0">
                </div>
                <div style="flex: 1;">
                    <label>Capacity</label>
                    <input type="number" id="r-capacity" placeholder="e.g., 40">
                </div>
            </div>

            <label>Description</label>
            <textarea id="r-desc" rows="2" placeholder="Brief description"></textarea>

            <button type="submit">Add Room to List</button>
        </form>
    </div>

    <!-- Step 4: Main Floor Path -->
    <div class="control-group" id="step4" style="display: none;">
        <h3>4. Main Floor Path</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Draw a single continuous line representing the main walkable corridors. 1) Start Drawing, 2) Click to add points, 3) Finish.</p>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="button" id="btn-draw-path" style="flex: 1; background: #64748b;">Start Drawing Path</button>
            <button type="button" id="btn-finish-path" class="success" style="flex: 1; display: none;">Finish Path</button>
            <button type="button" id="btn-clear-path" class="danger" style="flex: 1;">Clear Path</button>
        </div>
        <div id="path-status" style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">Points: 0</div>
    </div>

    <!-- Step 5: Finalize -->
    <div class="control-group" id="step5" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>5. Added Rooms</h3>
            <span id="room-count" style="font-size: 12px; background: var(--accent); padding: 2px 6px; border-radius: 10px; color: #fff;">0</span>
        </div>
        <div id="room-list" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column;">
            <!-- Rooms appear here -->
        </div>

        <button id="btn-save" class="success" style="margin-top: 10px; width: 100%;">Save Floor Data to Map</button>
    </div>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['email'] === 'themorevaibhav@gmail.com'): ?>
    <!-- Step 6: User Management -->
    <div class="control-group" id="step6">
        <h3>User Management</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 10px 0;">Create new administrator or standard user profiles.</p>
        <form id="create-user-form" style="display: flex; flex-direction: column; gap: 8px;">
            <label>Email</label>
            <input type="email" id="new-user-email" placeholder="e.g., user@example.com" required>
            
            <label>Password</label>
            <input type="password" id="new-user-password" placeholder="••••••••" required minlength="6">
            
            <label>Role</label>
            <select id="new-user-role">
                <option value="user">Standard User</option>
                <option value="admin">Administrator</option>
            </select>
            
            <button type="submit" id="btn-create-user" style="margin-top: 5px;">Create User</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<div class="main-content" id="map-scroll-area">
    <div id="map-container" style="display: none;">
        <img id="map-image" src="" alt="Floor Plan Map">
        <canvas id="draw-canvas"></canvas>
    </div>
</div>

<div id="toast">Message goes here</div>

<script>
    // State
    let rooms = [];
    let mainPath = []; // Array of {left, top} percentage points
    let currentRect = null; // {x, y, w, h} in pixels
    let isDrawing = false;
    let isDrawingMainPath = false;
    let isDrawingDoorFor = null;
    let currentMainPathPixels = []; // Array of pixel {x, y} points

    let startX = 0;
    let startY = 0;

    // Elements
    const imgUpload = document.getElementById('image-upload');
    const btnUpload = document.getElementById('btn-upload');
    const mapContainer = document.getElementById('map-container');
    const mapImage = document.getElementById('map-image');
    const canvas = document.getElementById('draw-canvas');
    const ctx = canvas.getContext('2d');
    
    // Inputs
    const iTop = document.getElementById('r-top');
    const iLeft = document.getElementById('r-left');
    const iWidth = document.getElementById('r-width');
    const iHeight = document.getElementById('r-height');

    // Init Admin Panel
    async function loadExistingFloors() {
        const listContainer = document.getElementById('existing-floors-list');
        try {
            const res = await fetch('api/rooms.php');
            const data = await res.json();
            
            if (data && data.floors) {
                listContainer.innerHTML = '';
                Object.keys(data.floors).forEach(floorKey => {
                    const floor = data.floors[floorKey];
                    const div = document.createElement('div');
                    div.className = 'room-item';
                    div.innerHTML = `
                        <div>
                            <div class="room-item-title">${floor.label}</div>
                            <div style="font-size: 11px; color: var(--text-muted);">${floorKey} • ${floor.rooms ? floor.rooms.length : 0} rooms</div>
                        </div>
                        <div style="display: flex; gap: 4px;">
                            <button type="button" style="padding: 4px 8px; font-size: 11px; background: var(--border);" onclick="editFloor('${floorKey}')">Edit</button>
                            <button type="button" class="danger" style="padding: 4px 8px; font-size: 11px;" onclick="deleteFloor('${floorKey}')">Delete</button>
                        </div>
                    `;
                    listContainer.appendChild(div);
                });
                // Store fetched data globally for quick "edit" access
                window._allFloorsCache = data.floors;
                
                if (Object.keys(data.floors).length === 0) {
                    listContainer.innerHTML = '<div style="font-size: 13px; color: var(--text-muted);">No floors found.</div>';
                }
            }
        } catch(e) {
            listContainer.innerHTML = '<div style="font-size: 13px; color: var(--danger);">Failed to load floors.</div>';
        }
    }

    window.deleteFloor = async function(floorKey) {
        if (!confirm('Are you sure you want to permanently delete the floor "' + floorKey + '" and all its rooms?')) return;
        
        try {
            const res = await fetch('api/delete_floor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ floorKey })
            });
            const data = await res.json();
            
            if(data.success) {
                showToast(data.message);
                loadExistingFloors();
            } else {
                showToast(data.error || 'Failed to delete floor', true);
            }
        } catch(e) {
            showToast('Network error while deleting floor', true);
        }
    };

    window.editFloor = function(floorKey) {
        if (!window._allFloorsCache || !window._allFloorsCache[floorKey]) return;
        const floor = window._allFloorsCache[floorKey];
        
        // Populate step 2
        document.getElementById('floor-key').value = floorKey;
        document.getElementById('floor-label').value = floor.label;
        document.getElementById('image-path').value = floor.image;
        
        // Populate rooms array
        rooms = Array.isArray(floor.rooms) ? JSON.parse(JSON.stringify(floor.rooms)) : [];
        mainPath = Array.isArray(floor.mainPath) ? JSON.parse(JSON.stringify(floor.mainPath)) : [];
        
        // Show sections
        document.getElementById('step2').style.display = 'flex';
        document.getElementById('step3').style.display = 'flex';
        document.getElementById('step4').style.display = 'flex';
        document.getElementById('step5').style.display = 'flex';
        
        updateRoomList();
        updatePathStatus();
        
        // Load image to trigger canvas setup
        loadImage(floor.image);
        showToast('Floor loaded for editing.');
        // Scroll to step 2 visually
        document.getElementById('step2').scrollIntoView({behavior: 'smooth'});
    };

    // Load floors on startup
    loadExistingFloors();

    function showToast(msg, isError = false) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.style.borderLeft = isError ? "4px solid var(--danger)" : "4px solid var(--success)";
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    // 1. Upload Image
    btnUpload.addEventListener('click', async () => {
        const file = imgUpload.files[0];
        if (!file) return showToast('Please select a file first.', true);

        const formData = new FormData();
        formData.append('image', file);

        btnUpload.innerText = 'Uploading...';
        btnUpload.disabled = true;

        try {
            const res = await fetch('api/upload_image.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                showToast('Image uploaded successfully!');
                document.getElementById('image-path').value = data.path;
                loadImage(data.path);
                
                document.getElementById('step2').style.display = 'flex';
                document.getElementById('step3').style.display = 'flex';
                document.getElementById('step4').style.display = 'flex';
                document.getElementById('step5').style.display = 'flex';
            } else {
                showToast(data.error || 'Upload failed', true);
            }
        } catch (e) {
            showToast('Network error during upload', true);
        } finally {
            btnUpload.innerText = 'Upload Image';
            btnUpload.disabled = false;
        }
    });

    // Load Image and Init Canvas
    function loadImage(src) {
        mapImage.onload = () => {
            mapContainer.style.display = 'block';
            mapContainer.style.width = mapImage.naturalWidth + 'px';
            mapContainer.style.height = mapImage.naturalHeight + 'px';
            
            canvas.width = mapImage.naturalWidth;
            canvas.height = mapImage.naturalHeight;
            
            redrawCanvas();
        };
        mapImage.src = src + '?t=' + Date.now(); // Cache bust
    }

    // Canvas Events for Drawing
    canvas.addEventListener('mousedown', (e) => {
        const rect = canvas.getBoundingClientRect();
        // Calculate true scale in case CSS resizes it
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const x = (e.clientX - rect.left) * scaleX;
        const y = (e.clientY - rect.top) * scaleY;

        if (isDrawingMainPath) {
            currentMainPathPixels.push({x, y});
            redrawCanvas();
            return;
        }

        if (isDrawingDoorFor !== null) {
            const pLeft = (x / canvas.width) * 100;
            const pTop = (y / canvas.height) * 100;
            rooms[isDrawingDoorFor].door = { left: parseFloat(pLeft.toFixed(2)), top: parseFloat(pTop.toFixed(2)) };
            isDrawingDoorFor = null;
            redrawCanvas();
            showToast('Door location set!');
            return;
        }

        startX = x;
        startY = y;
        isDrawing = true;
        currentRect = null;
    });

    canvas.addEventListener('mousemove', (e) => {
        if (isDrawingMainPath) return; // Route draws only on click
        if (!isDrawing) return;

        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;

        const currX = (e.clientX - rect.left) * scaleX;
        const currY = (e.clientY - rect.top) * scaleY;

        currentRect = {
            x: Math.min(startX, currX),
            y: Math.min(startY, currY),
            w: Math.abs(currX - startX),
            h: Math.abs(currY - startY)
        };

        redrawCanvas();
    });

    canvas.addEventListener('mouseup', () => {
        if (isDrawingMainPath) return;
        isDrawing = false;
        if (currentRect && currentRect.w > 5 && currentRect.h > 5) {
            updateCoordinateInputs();
        } else {
            currentRect = null;
            redrawCanvas();
        }
    });
    
    canvas.addEventListener('mouseleave', () => {
        if (!isDrawingMainPath) {
            isDrawing = false;
        }
    });

    function updateCoordinateInputs() {
        if (!currentRect) return;
        const imgW = mapImage.naturalWidth;
        const imgH = mapImage.naturalHeight;

        // Convert to percentage
        const pLeft = (currentRect.x / imgW) * 100;
        const pTop = (currentRect.y / imgH) * 100;
        const pWidth = (currentRect.w / imgW) * 100;
        const pHeight = (currentRect.h / imgH) * 100;

        iLeft.value = pLeft.toFixed(2);
        iTop.value = pTop.toFixed(2);
        iWidth.value = pWidth.toFixed(2);
        iHeight.value = pHeight.toFixed(2);
    }

    function redrawCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw existing rooms
        ctx.fillStyle = 'rgba(20, 184, 166, 0.2)'; // Teal
        ctx.strokeStyle = '#14b8a6';
        ctx.lineWidth = 2;

        const imgW = mapImage.naturalWidth;
        const imgH = mapImage.naturalHeight;

        rooms.forEach(r => {
            const rx = (r.left / 100) * imgW;
            const ry = (r.top / 100) * imgH;
            const rw = (r.width / 100) * imgW;
            const rh = (r.height / 100) * imgH;
            
            ctx.fillRect(rx, ry, rw, rh);
            ctx.strokeRect(rx, ry, rw, rh);
            
            // Draw label
            ctx.fillStyle = '#fff';
            ctx.font = '14px Inter';
            ctx.fillText(r.id, rx + 5, ry + 20);
            
            // Draw door if exists
            if (r.door) {
                const dx = (r.door.left / 100) * imgW;
                const dy = (r.door.top / 100) * imgH;
                ctx.fillStyle = '#f97316'; // Orange door indicator
                ctx.beginPath();
                ctx.arc(dx, dy, 5, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1;
                ctx.stroke();
            }
            
            ctx.fillStyle = 'rgba(20, 184, 166, 0.2)'; // reset
        });

        // Draw current rect
        if (currentRect) {
            ctx.fillStyle = 'rgba(239, 68, 68, 0.3)'; // Redish for current drawing
            ctx.strokeStyle = '#ef4444';
            ctx.fillRect(currentRect.x, currentRect.y, currentRect.w, currentRect.h);
            ctx.strokeRect(currentRect.x, currentRect.y, currentRect.w, currentRect.h);
        }

        // Draw saved main path
        if (mainPath.length > 0 && !isDrawingMainPath) {
            ctx.beginPath();
            ctx.strokeStyle = '#2dd4bf'; // Teal
            ctx.lineWidth = 4;
            ctx.setLineDash([5, 5]);
            
            mainPath.forEach((pt, idx) => {
                const px = (pt.left / 100) * imgW;
                const py = (pt.top / 100) * imgH;
                if (idx === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            });
            ctx.stroke();
            ctx.setLineDash([]); // Reset
        }

        // Draw current actively drawn route
        if (currentMainPathPixels.length > 0) {
            ctx.beginPath();
            ctx.strokeStyle = '#f59e0b'; // Amber
            ctx.lineWidth = 4;
            currentMainPathPixels.forEach((pt, idx) => {
                if (idx === 0) ctx.moveTo(pt.x, pt.y);
                else ctx.lineTo(pt.x, pt.y);
            });
            ctx.stroke();

            // Draw handles at points
            ctx.fillStyle = '#f59e0b';
            currentMainPathPixels.forEach(pt => {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 5, 0, Math.PI * 2);
                ctx.fill();
            });
        }
    }

    // Form Submit (Add Room)
    document.getElementById('room-form').addEventListener('submit', (e) => {
        e.preventDefault();
        
        if (!iTop.value || !iLeft.value) {
            return showToast('Please draw a highlight box on the map first.', true);
        }

        const room = {
            id: document.getElementById('r-id').value.trim(),
            name: document.getElementById('r-name').value.trim(),
            type: document.getElementById('r-type').value,
            top: parseFloat(iTop.value),
            left: parseFloat(iLeft.value),
            width: parseFloat(iWidth.value),
            height: parseFloat(iHeight.value),
            area: document.getElementById('r-area').value.trim() || null,
            capacity: document.getElementById('r-capacity').value.trim() || null,
            contact: null,
            description: document.getElementById('r-desc').value.trim() || null
        };

        rooms.push(room);
        currentRect = null; // Clear drawn rect
        redrawCanvas();
        updateRoomList();
        
        // Clear form (keep some defaults)
        document.getElementById('r-id').value = '';
        document.getElementById('r-name').value = '';
        iTop.value = '';
        iLeft.value = '';
        iWidth.value = '';
        iHeight.value = '';
        
        // Increment ID naturally if it ended in a number
        const lastId = room.id;
        const match = lastId.match(/^(.*?)(\d+)$/);
        if (match) {
            const num = parseInt(match[2], 10) + 1;
            const padded = num.toString().padStart(match[2].length, '0');
            document.getElementById('r-id').value = match[1] + padded;
        }
    });

    function updateRoomList() {
        const list = document.getElementById('room-list');
        document.getElementById('room-count').innerText = rooms.length;
        list.innerHTML = '';
        
        rooms.forEach((r, idx) => {
            const div = document.createElement('div');
            div.className = 'room-item';
            div.innerHTML = `
                <div>
                    <div class="room-item-title">${r.id}</div>
                    <div style="font-size: 11px; color: var(--text-muted);">${r.name}${r.door ? ' <span style="color:#f97316;">🚪 Door Set</span>' : ''}</div>
                </div>
                <div style="display: flex; gap: 4px;">
                    <button type="button" style="padding: 4px 8px; font-size: 11px; background: #f59e0b;" onclick="setRoomDoor(${idx})">🚪 Set Door</button>
                    <button type="button" style="padding: 4px 8px; font-size: 11px; background: var(--border);" onclick="editRoom(${idx})">Edit</button>
                    <button type="button" class="danger" style="padding: 4px 8px; font-size: 11px;" onclick="removeRoom(${idx})">Del</button>
                </div>
            `;
            list.appendChild(div);
        });

    }

    window.setRoomDoor = function(idx) {
        if (!rooms[idx]) return;
        isDrawingDoorFor = idx;
        showToast('Click anywhere on the map to place the door for room ' + rooms[idx].id);
    };

    // --- Main Path UI ---
    function updatePathStatus() {
        document.getElementById('path-status').innerText = `Points: ${mainPath.length}`;
    }
    
    document.getElementById('btn-draw-path')?.addEventListener('click', () => {
        isDrawingMainPath = true;
        currentMainPathPixels = [];
        document.getElementById('btn-draw-path').style.display = 'none';
        document.getElementById('btn-finish-path').style.display = 'block';
        showToast('Path drawing ON. Click points on the map along the central hallway.');
    });

    document.getElementById('btn-finish-path')?.addEventListener('click', () => {
        isDrawingMainPath = false;
        document.getElementById('btn-finish-path').style.display = 'none';
        document.getElementById('btn-draw-path').style.display = 'block';
        
        if (currentMainPathPixels.length > 1) {
            const imgW = mapImage.naturalWidth;
            const imgH = mapImage.naturalHeight;
            mainPath = currentMainPathPixels.map(p => ({
                left: parseFloat(((p.x / imgW) * 100).toFixed(2)),
                top: parseFloat(((p.y / imgH) * 100).toFixed(2))
            }));
            
            updatePathStatus();completely
            showToast('Main path set !');
        } else {
            showToast('Path not saved. Need at least 2 points.', true);
        }
        
        currentMainPathPixels = [];
        redrawCanvas();
    });

    document.getElementById('btn-clear-path')?.addEventListener('click', () => {
        mainPath = [];
        updatePathStatus();
        redrawCanvas();
        showToast('Path cleared.');
    });

    window.removeRoom = function(idx) {
        rooms.splice(idx, 1);
        updateRoomList();
        redrawCanvas();
    };

    window.editRoom = function(idx) {
        const r = rooms[idx];
        
        document.getElementById('r-id').value = r.id || '';
        document.getElementById('r-name').value = r.name || '';
        document.getElementById('r-type').value = r.type || 'office';
        iTop.value = r.top !== undefined ? r.top : '';
        iLeft.value = r.left !== undefined ? r.left : '';
        iWidth.value = r.width !== undefined ? r.width : '';
        iHeight.value = r.height !== undefined ? r.height : '';
        document.getElementById('r-area').value = r.area || '';
        document.getElementById('r-capacity').value = r.capacity || '';
        document.getElementById('r-desc').value = r.description || '';
        
        // Remove from list so a "re-add" updates it instead of duplicating
        removeRoom(idx);
        
        showToast('Room moved to editor. Tweak details or redraw its box, then click Add.');
    };

    // Save Floor to JSON
    document.getElementById('btn-save').addEventListener('click', async () => {
        const floorKey = document.getElementById('floor-key').value.trim();
        const floorLabel = document.getElementById('floor-label').value.trim();
        const imagePath = document.getElementById('image-path').value;

        if (!floorKey || !floorLabel || !imagePath) {
            return showToast('Complete Floor Details and Upload an Image first.', true);
        }

        if (rooms.length === 0) {
            if (!confirm('You have not added any rooms. Save an empty floor?')) return;
        }

        const payload = {
            floorKey,
            label: floorLabel,
            image: imagePath,
            rooms: rooms,
            mainPath: mainPath
        };

        const btn = document.getElementById('btn-save');
        btn.innerText = 'Saving...';
        btn.disabled = true;

        try {
            const res = await fetch('api/save_floor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message);
                loadExistingFloors();
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'none';
                document.getElementById('step4').style.display = 'none';
                document.getElementById('step5').style.display = 'none';
                mapContainer.style.display = 'none';
                rooms = [];
                mainPath = [];
                updateRoomList();
                updatePathStatus();
            } else {
                showToast(data.error || 'Failed to save', true);
            }
        } catch (e) {
            showToast('Network error while saving', true);
        } finally {
            btn.innerText = 'Save Floor Data to Map';
            btn.disabled = false;
        }
    });

    // Create User API
    document.getElementById('create-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('new-user-email').value.trim();
        const password = document.getElementById('new-user-password').value;
        const role = document.getElementById('new-user-role').value;

        if (!email || !password || !role) {
            return showToast('Please fill all fields to create a user.', true);
        }

        const btn = document.getElementById('btn-create-user');
        btn.innerText = 'Creating...';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            formData.append('role', role);

            const res = await fetch('api/auth.php?action=create_user', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                showToast('User created successfully!');
                document.getElementById('create-user-form').reset();
            } else {
                showToast(data.error || 'Failed to create user', true);
            }
        } catch (err) {
            showToast('Network error while creating user', true);
        } finally {
            btn.innerText = 'Create User';
            btn.disabled = false;
        }
    });
</script>

</body>
</html>
