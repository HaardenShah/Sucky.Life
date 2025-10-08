# sucky.life — Inside-Joke Engine

A plug-and-play meme site for your crew: animated homepage, silky modal “eggs”, per-egg images/audio/video, and a full-screen visual editor that places hotspots with pixel-perfect accuracy (via vw/vh). No code edits required after install.

## Feature Snapshot

* **First-run Setup Wizard**
  Friendly, animated screen to set **Site Name**, **Domain**, and **Admin Password**.
* **Homepage drama**

  * Big **Play/Stop** button for the screech (not just mute)
  * **Tears** fall only while audio is playing; mouse **repels** droplets
  * Smooth, Apple-y **modal** with iframe for each inside joke (“egg”)
  * **Deep link**: `/?egg=slug` opens straight into that egg
* **Eggs (inside jokes)**

  * Content lives in flat **JSON** files (no DB)
  * Each egg supports **image**, **audio**, and **video** (MP4/WebM recommended)
  * Captions + rich story body (basic HTML allowed)
* **Admin (no-code)**

  * Create / edit / delete / rename eggs
  * **Drag-drop** image uploads (auto-WebP when supported)
  * Optional **audio** and **video** upload or **URL**
  * **Media Picker** with previews (images/audio/video) from `/assets/uploads`
  * **Full-screen Visual Editor** overlays your homepage, clicks save positions in **vw/vh**—accurate on every device
  * “Center” and grid overlay; animated UI; keyboard accessible
* **Security & UX niceties**

  * Password stored hashed; first-run requires setting one
  * Admin session-gated endpoints
  * Autoplay safe: main audio only plays after interaction

---

## Quick Start (zero config)

1. **Upload all files** to a PHP-enabled host.
2. Ensure these directories are **writable** by PHP:

   * `assets/uploads/`
   * `eggs/data/`
3. Visit your domain → the **Setup Wizard** appears.
   Set **Site Name**, **Domain**, and **Admin Password** → **Finish**.
4. You’ll land in **Admin**. Create your first egg, then open the **Visual Editor** to place it on the page.

> Already had an older version? We now serve the homepage from `index.php`. You can keep an old `index.html` around, but it’s unused.

---

## Typical Flow

* **Add egg** → fill title/caption/story → upload image (or pick from uploads via Media Picker).
* **Optional media** → add per-egg **audio** and/or **video**. Preview right in the editor.
* **Place it** → Visual Editor → click where it belongs → **Save position**.
* **Test** → on homepage, click the hotspot → modal opens the egg page with your image/video/audio.

---

## File Map

```
/index.php                         # Homepage (dynamic hotspots, play/stop audio, tears, modal, deep-link support)
├─ assets/
│  ├─ friend.jpg                  # Replace with your hero image
│  ├─ screech.mp3                 # Replace with your screech audio
│  └─ uploads/                    # All uploaded media (images/audio/video)
├─ eggs/
│  ├─ egg.php                     # Public egg renderer (image/caption/body + audio/video player)
│  ├─ list.php                    # JSON feed of eggs (slug, title, positions) for homepage
│  └─ data/*.json                 # One JSON per egg (content + pos_left/pos_top in vw/vh)
└─ admin/
   ├─ setup.php                   # First-run wizard (site name/domain/password)
   ├─ index.php                   # CMS UI (create/edit/rename/delete + media picker)
   ├─ visual.php                  # Full-screen visual placement editor
   ├─ save.php                    # Persists egg content (image/audio/video, URLs or uploads)
   ├─ save_position.php           # Persists vw/vh position for a given egg
   ├─ media_list.php              # Returns uploads (images/audio/video) with metadata
   ├─ rename.php                  # Rename egg slug
   ├─ delete.php                  # Delete egg
   ├─ util.php                    # Helpers (slugify, list/load/save eggs, WebP conversion)
   ├─ config.php                  # Paths + session + setup state
   ├─ site.json                   # (generated) {site_name, domain, first_run:false, ...}
   └─ password.json               # (generated) {hash, updated_at}
```

---

## Data Model (egg JSON)

Example `eggs/data/usb.json`:

```json
{
  "title": "USB of Doom",
  "caption": "He swore it was ‘just charging’",
  "alt": "Close-up of a cursed USB stick",
  "body": "<p>Short story with <strong>light HTML</strong>.</p>",
  "image": "/assets/uploads/usb.webp",
  "audio": "/assets/uploads/usb-laugh.mp3",
  "video": "/assets/uploads/usb-fail.mp4",
  "pos_left": 62.4,
  "pos_top": 41.8
}
```

* Positions are **viewport-relative**:

  * `pos_left` = vw (0–100)
  * `pos_top`  = vh (0–100)

---

## Media Guidelines

* **Images**: any common format; auto-converted to **WebP** when GD supports it; otherwise original is kept.
* **Audio**: mp3, m4a/aac, wav, ogg/oga, webm (no transcoding).
* **Video**: mp4, webm, ogg/ogv, mov, m4v (no transcoding).

  * For best cross-browser playback: **MP4 (H.264/AAC)** or **WebM (VP9/Opus)**.

> If you want automatic transcoding or thumbnails, we can add an FFmpeg step later.

---

## Admin: Visual Editor (accuracy notes)

* Opens the homepage in an iframe that **fills the screen**.
* Clicks are converted to **vw/vh** using the actual `window.innerWidth/innerHeight` of the loaded page.
* The editor overlays a marker + HUD with the exact saved values and an optional grid.
* “Center” button drops the marker at 50vw / 50vh.

---

## Deep Links

Open a specific egg directly:

```
https://yourdomain/?egg=slug
```

The homepage auto-opens the modal with that egg.

---

## Accessibility

* Provide **ALT text** for each egg image.
* Buttons have ARIA labels; modal supports **Esc** to close.
* Video and audio players use native controls for keyboard/screen reader support.

---

## Performance Tips

* Prefer **WebP** for images (automatic when possible).
* Keep video sizes sane (short clips; compress if needed).
* Hotspots are loaded once via a tiny JSON (`eggs/list.php`).

---

## Security

* Password is hashed (PHP `password_hash`) in `admin/password.json`.
* Protect `/admin` further with HTTP Basic Auth if you want extra belt-and-suspenders.
* If you forget the password, as a last resort delete `admin/password.json` and re-run the setup (you’ll set a new one).

---

## Deploy / Update

1. Copy new files up (or use Git/CI).
2. Ensure `assets/uploads/` and `eggs/data/` remain **writable**.
3. No migrations needed—flat files keep your content.

> Moving from a much older build? Rename your root `index.html` to `index.php` (or just keep it—our `index.php` is what gets served).

---

## Troubleshooting

* **Admin shows header only (blank body)**
  Ensure you’ve uploaded the full `admin/index.php` (not the earlier stub) and that `eggs/data/` exists.
* **Hotspots don’t show**
  The egg must have both `pos_left` and `pos_top`. Use the **Visual Editor** to save a position.
* **Uploads fail**
  Check folder permissions on `assets/uploads/`.
* **WebP not generated**
  Your PHP GD may lack WebP support—images will stay in original format (that’s fine).
* **Video won’t play on iOS**
  Re-encode to **H.264/AAC MP4** or **WebM**; MOV/OGV isn’t universally supported.

---

## Dev Notes (for the curious)

* No DB, just **flat JSON** + **uploads** folder.
* Homepage effects:

  * **Play/Stop** toggles the main audio loop
  * Tears spawn only when audio is **playing & unmuted**
  * **Mute** toggles without stopping tears logic (tears stop if muted)
  * Cursor **repels** active droplets
  * Modal open/close uses CSS transitions; the iframe src resets on close.
* Visual Editor communicates via `postMessage` (`egg-editor-click` → vw/vh), so placements stay consistent across devices.

---

## Roadmap Ideas (if you want v2 spice)

* Snap-to-grid (hold **Shift** to snap to 1vw / 1vh).
* Trash/restore (“Recycle Bin” for deleted eggs).
* Per-egg autoplay-muted video, or poster thumbnails.
* Optional FFmpeg pipeline for transcoding and generating GIF/thumbnail previews.
