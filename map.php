<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
/**
 * Institute Map — Main Entry Point
 * Server: Apache/Nginx + PHP 7.4+
 * Usage: Place in web root and open in browser
 */

// Load room data for inline fallback (works even without JS fetch)
$dataFile = __DIR__ . '/data/rooms.json';
$mapData  = file_exists($dataFile) ? file_get_contents($dataFile) : '{}';

// Config
$siteName  = 'Building B';
$institute = 'Institute of Technology';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($siteName) ?> — Interactive Map</title>
  <meta name="description" content="Interactive floor map for <?= htmlspecialchars($institute) ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Inline JSON fallback so map works even if PHP-fetch fails -->
  <script>window.MAP_DATA = <?= $mapData ?>;</script>
</head>
<body>

<!-- ══════════════════════════════════════════════
     TOP BAR
═══════════════════════════════════════════════ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="bg-teal-500 p-2 rounded-lg text-white shadow-lg shadow-teal-500/20 mr-3">
      <i class="fas fa-layer-group text-lg"></i>
    </div>
    <div>
      <div class="topbar-name">MIT NEVIGATOR</div>
      <div class="topbar-sub"><?= htmlspecialchars($institute) ?></div>
    </div>
  </div>

  <div class="topbar-right">

    <span class="text-teal-400 font-medium text-xs mr-4"><i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['user']['email']) ?></span>
    <a href="api/auth.php?action=logout" class="text-red-400 hover:text-red-300 transition-colors text-xs font-medium mr-6"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
    <!-- Search -->
    <div class="search-box" style="position:relative">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" id="searchInput" placeholder="Search rooms… (Ctrl+F)">
      <!-- Search dropdown -->
      <div id="searchResults" style="
        display:none; position:absolute; top:calc(100% + 6px); left:0; right:0;
        background:#1C2E42; border:1px solid rgba(255,255,255,.14); border-radius:10px;
        z-index:200; max-height:340px; overflow-y:auto; box-shadow:0 8px 30px rgba(0,0,0,.5)">
      </div>
    </div>

    <!-- Emergency -->
    <a class="emergency-btn" href="#" onclick="alert('Contact Security: +91-XXXX-XXXXXX\nEmergency: 112'); return false;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2L1 21h22L12 2zm0 3.5 8.5 15H3.5L12 5.5zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z"/>
      </svg>
      Emergency Exit
    </a>
  </div>
</header>

<!-- ══════════════════════════════════════════════
     MAIN LAYOUT
═══════════════════════════════════════════════ -->
<div class="main">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">

    <!-- Floor selector -->
    <div class="sidebar-section">
      <div class="sidebar-title">Floors</div>
      <div class="floor-tabs" id="floorTabs">
        <!-- Populated by JS -->
        <div style="padding:8px 0;font-size:12px;color:var(--text-3)">Loading…</div>
      </div>
    </div>

    <!-- Stats -->
    <div class="sidebar-stats">
      <div class="sidebar-title" style="margin-bottom:8px">Floor Stats</div>
      <div class="stat-row"><span class="stat-lbl">Floor</span><span class="stat-val" id="statFloor">—</span></div>
      <div class="stat-row"><span class="stat-lbl">Total rooms</span><span class="stat-val" id="statTotal">—</span></div>
      <div class="stat-row"><span class="stat-lbl">Labs</span><span class="stat-val" id="statLabs">—</span></div>
      <div class="stat-row"><span class="stat-lbl">Offices</span><span class="stat-val" id="statOffices">—</span></div>
    </div>

    <!-- Route planner -->
    <div class="sidebar-section route-section">
      <div class="sidebar-title">Route Finder</div>

      <label class="route-lbl" for="routeStart">Start</label>
      <select id="routeStart" class="route-select">
        <option value="">Select start room</option>
      </select>

      <label class="route-lbl" for="routeEnd">Destination</label>
      <select id="routeEnd" class="route-select">
        <option value="">Select destination room</option>
      </select>

      <div class="route-actions">
        <button class="route-btn route-btn-primary" id="findRouteBtn" type="button">Find Route</button>
        <button class="route-btn" id="clearRouteBtn" type="button">Clear</button>
      </div>

      <div class="route-actions" style="margin-top: 8px;">
        <button class="route-btn" id="voiceNavBtn" type="button" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; background: rgba(59, 130, 246, 0.15); color: #60A5FA; border-color: rgba(59, 130, 246, 0.3);">
          <i class="fas fa-microphone"></i> <span>Voice Navigation</span>
        </button>
      </div>

      <div class="route-status" id="routeStatus">Choose start and destination to draw route.</div>
    </div>

    <!-- Legend -->
    <div class="sidebar-section" style="border-bottom:none;flex:1;overflow-y:auto">
      <div class="sidebar-title">Legend</div>
      <div class="legend-list">
        <div class="legend-item"><div class="legend-dot" style="background:#3B82F6"></div>Laboratory</div>
        <div class="legend-item"><div class="legend-dot" style="background:#10B981"></div>Office</div>
        <div class="legend-item"><div class="legend-dot" style="background:#F59E0B"></div>Hall / Centre</div>
        <div class="legend-item"><div class="legend-dot" style="background:#8B5CF6"></div>Classroom</div>
        <div class="legend-item"><div class="legend-dot" style="background:#06B6D4"></div>Common Area</div>
        <div class="legend-item"><div class="legend-dot" style="background:#EF4444"></div>Utility</div>
        <div class="legend-item"><div class="legend-dot" style="background:#F97316"></div>Server Room</div>
        <div class="legend-item"><div class="legend-dot" style="background:#64748B"></div>Stairs / Lift</div>
        <div class="legend-item"><div class="legend-dot" style="background:#EC4899"></div>Exit</div>
      </div>

      <div style="margin-top:14px;font-size:11px;color:var(--text-3);line-height:1.6">
        <strong style="color:var(--text-2)">Tip:</strong><br>
        Click any room to see details.<br>
        Hover for quick info.<br>
        Use <kbd style="background:rgba(255,255,255,.1);padding:1px 4px;border-radius:3px">Ctrl+F</kbd> to search.
      </div>
    </div>

  </aside>

  <!-- ── Map Canvas ── -->
  <div class="canvas-area">

    <!-- Floor banner + zoom controls -->
    <div class="floor-banner">
      <div class="floor-banner-meta">
        <div class="floor-banner-name" id="floorBannerName">Ground Floor</div>
        <div class="floor-banner-sub"  id="floorBannerSub">Loading…</div>
        <div class="route-floor-nav" id="routeFloorNav"></div>
      </div>
      <div class="zoom-controls">
        <button class="zoom-btn" id="zoomOut" title="Zoom out (-)">−</button>
        <div style="padding:0 6px;font-size:11px;color:var(--text-3);display:flex;align-items:center;min-width:38px;justify-content:center" id="zoomLabel">100%</div>
        <button class="zoom-btn" id="zoomIn"  title="Zoom in (+)">+</button>
        <button class="zoom-btn" id="zoomReset" title="Reset zoom (0)" style="font-size:11px;width:auto;padding:0 8px">Fit</button>
      </div>
    </div>

    <!-- Map image + overlay -->
    <div class="map-wrapper" id="mapWrapper">
      <div style="color:var(--text-3);font-size:13px;margin-top:40px">Loading floor plan…</div>
    </div>

    <div class="mini-map-card" id="miniMapCard">
      <div class="mini-map-title">Overview Map</div>
      <div class="mini-map-stage" id="miniMapStage">
        <svg class="mini-map-route" id="miniMapRoute" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
        <div class="mini-map-pins" id="miniMapPins"></div>
        <div class="mini-map-viewport" id="miniMapViewport"></div>
      </div>
      <div class="mini-map-caption" id="miniMapCaption">Current floor</div>
    </div>

  </div>

  <!-- ── Info Panel ── -->
  <aside class="info-panel" id="infoPanel">
    <div class="info-inner" id="infoPanelContent">
      <!-- Filled by JS when a room is clicked -->
    </div>
  </aside>

</div><!-- .main -->

<!-- ══════════════════════════════════════════════
     Scripts
═══════════════════════════════════════════════ -->
<script src="assets/js/app.js"></script>

</body>
</html>
