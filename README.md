# Building B — Interactive Institute Map

A full-stack interactive floor map application for Building B.

## Tech Stack
- **Frontend**: HTML5, CSS3 (CSS Variables, Grid, Flexbox), Vanilla JavaScript (ES6+)
- **Backend**: PHP 8+ (API endpoint)
- **Data**: JSON (rooms database)
- **No external libraries** — zero dependencies, works offline

---

## Project Structure

```
institute-map/
├── index.php               ← Main page (entry point)
├── api/
│   └── rooms.php           ← REST API: GET /api/rooms.php
├── assets/
│   ├── css/
│   │   └── style.css       ← All styles
│   ├── js/
│   │   └── app.js          ← Frontend application
│   └── images/             ← ★ PUT YOUR FLOOR PLAN IMAGES HERE ★
│       ├── ground_floor.jpg
│       ├── first_floor.jpg
│       ├── second_floor.jpg
│       └── third_floor.jpg
├── data/
│   └── rooms.json          ← All room data (edit this to add rooms)
└── README.md
```

---

## Setup Instructions

### 1. Server Requirements
- PHP 7.4 or newer
- Apache / Nginx / XAMPP / WAMP / Laragon

### 2. Place Your Floor Plan Images
Copy your floor plan images into `assets/images/`:
```
assets/images/ground_floor.jpg
assets/images/first_floor.jpg
assets/images/second_floor.jpg
assets/images/third_floor.jpg
```

### 3. Start Server

**XAMPP / WAMP:**
1. Copy the `institute-map/` folder into `htdocs/`
2. Open: `http://localhost/institute-map/`

**PHP Built-in Server (development):**
```bash
cd institute-map
php -S localhost:8080
# Open http://localhost:8080
```

**Apache vhost:**
```apache
<VirtualHost *:80>
    ServerName map.yoursite.com
    DocumentRoot /var/www/institute-map
    <Directory /var/www/institute-map>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Adjusting Room Overlay Positions

Room zones are defined in `data/rooms.json` as **percentage coordinates** relative to each floor image:

```json
{
  "id": "BGF-01",
  "name": "Room Main Comp Dept",
  "area": "78.0",
  "type": "office",
  "top":    12.5,   ← % from top of image
  "left":   6.5,    ← % from left of image
  "width":  24.0,   ← % width
  "height": 22.5,   ← % height
  "capacity": 40,
  "contact": "Dept. Office",
  "description": "..."
}
```

### How to calibrate coordinates:
1. Open your floor plan image in any image editor (or even a browser)
2. Note the image pixel dimensions (e.g., 800 × 1100 px)
3. Find where each room starts/ends in pixels
4. Convert to percentages: `left% = (room_x / image_width) * 100`
5. Update `data/rooms.json` — changes take effect immediately (no restart needed)

---

## API Reference

```
GET /api/rooms.php              → All floors data
GET /api/rooms.php?floor=ground → Single floor (ground/first/second/third)
GET /api/rooms.php?search=lab   → Search across all floors
```

Example response:
```json
{
  "floor": "ground",
  "data": {
    "label": "Ground Floor",
    "image": "assets/images/ground_floor.jpg",
    "rooms": [ { "id": "BGF-01", "name": "...", ... } ]
  }
}
```

---

## Room Types

| Type       | Color     | Used For                     |
|------------|-----------|------------------------------|
| lab        | Blue      | Laboratories                 |
| office     | Green     | Administrative offices       |
| hall       | Amber     | Halls, multipurpose centres  |
| classroom  | Purple    | Lecture rooms, tutorials     |
| common     | Cyan      | Lobbies, waiting areas       |
| utility    | Red       | Electrical, pantry, WC       |
| server     | Orange    | Server / IT rooms            |
| stairs     | Gray      | Staircase, lift              |
| exit       | Pink      | Emergency exits              |

---

## Keyboard Shortcuts

| Key     | Action              |
|---------|---------------------|
| Ctrl+F  | Focus search        |
| Escape  | Close panel / clear |
| +       | Zoom in             |
| -       | Zoom out            |
| 0       | Reset zoom          |

---

## Adding New Floors

1. Add floor plan image to `assets/images/`
2. Edit `data/rooms.json` — add a new key under `"floors"`:
```json
"fourth": {
  "label": "Fourth Floor",
  "image": "assets/images/fourth_floor.jpg",
  "rooms": [ ... ]
}
```
3. The UI updates automatically — no code changes needed.

---

## Customisation

**Change colours** → Edit CSS variables at top of `assets/css/style.css`

**Change institution name** → Edit `$siteName` / `$institute` in `index.php`

**Add room details** → Add fields to `rooms.json` and update `showInfoPanel()` in `app.js`
