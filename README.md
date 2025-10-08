# sucky.life

A gloriously over-the-top meme site for your friend group.
Main page: your friend’s dramatic portrait + visceral screech (loop), falling “tears” that dodge the cursor, and **hidden easter eggs** that pop open in animated iframes with their own media (image / video / audio).

Admin panel lets you add/edit eggs, drag-drop media, place hotspots visually, and manage a simple visitor password gate.

> ⚠️ **No direct egg links.** Eggs only open inside the homepage modal. Even if someone copies the iframe URL, it 404s outside the session.

---

## ✨ Features

### Public site

* **Hero + screech**: click “Make it extra sucky” (or the curly-lip text) to start/stop the looped screech.

  * Mute/unmute button **only shows while playing**.
  * Tears spawn **only while audio is playing and unmuted**, and subtly repel from the cursor.
* **Silky modal**: eggs open in a glassy, animated iframe; backdrop is blurred; ESC/click outside closes it.
* **Hotspots**: subtle floating dots over the hero image; hover shows a label; click opens the egg.
* **Visitor gate (optional)**: password prompt before anyone can view the site.

### Eggs

* Each egg can have:

  * **Image** (plus automatic responsive WebP variants when supported)
  * **Video** (auto poster thumbnail if ffmpeg is available)
  * **Audio** (per-egg sound; normalized to -14 LUFS if ffmpeg is available)
  * **Title, caption, alt, body (HTML)**
  * **Draft** flag (hidden from visitors)
  * **Position** (vw/vh on the hero)
* **No direct linking**: the egg iframe requires a per-session **view token**; outside the modal/session it won’t load.

### Admin UX

* **Visual hotspot editor**: full-screen live preview, click anywhere to place;
  **Grid toggle**, **snap to 5vw/5vh**, **breakpoint previews** (390 / 768 / 1280), **undo/redo** (last 10 placements).
* **Drag-drop uploads** with preview pickers for images, audio, and video.
* **Autosave** every ~10s (silent) and **Draft/Publish** fields.
* **Search & filter** (including “drafts only”).
* **Visitor gate manager** to turn gating on/off and set the visitor password.

### Security & privacy

* **Session-bound view token**: eggs only load when launched from the homepage.
* **CSRF** on admin POST endpoints.
* **Rate-limiting** on admin login and visitor gate.
* **Hardened sessions** (httponly; secure when HTTPS).
* Optional crawler block via `robots.txt` (included).

> On shared hosting, many security headers are managed by the platform. This project **doesn’t require** `.htaccess`. If you later self-host, you can add CSP/headers at the server level.

---

## 🚀 Quick start

1. **Upload everything** to your host (root of your subdomain or site directory).
2. Visit your domain → you’ll see the **Welcome Setup**:

   * Set **Site name**, **Domain**, and **Admin password**.
3. You’ll be redirected to **/admin/login.php**. Sign in.
4. In Admin, create your first **egg**, set **Draft** off when ready, and place it using **Visual Editor**.
5. Optional: enable the **Visitor Gate** in Admin → Privacy if you want a site password.

---

## 🗂️ Project structure

```
/assets/            Public assets (hero image “friend.jpg”, uploads, audio)
/assets/uploads/    User uploads (images, videos, audio)
/eggs/egg.php       Renders a single egg in the modal (iframe)
/eggs/list.php      JSON list of eggs for hotspots
/eggs/data/         JSON files per egg (title/caption/media/position/etc.)
/admin/             Admin panel, setup, login, and helpers
  config.php        Shared config, sessions, CSRF, rate limit, paths
  setup.php         First-run setup (creates site.json + password.json)
  login.php         Admin sign-in
  index.php         Admin UI (egg list/editor/media pickers)
  save.php          Save egg + uploads + media post-processing
  visual.php        Full-screen visual hotspot placer
  util.php          Helper functions
index.php           Homepage
robots.txt          Disallow all (you can change this later)
```

**Config/runtime files** (created at first run):

* `admin/site.json` — site name, domain, visitor gate settings
* `admin/password.json` — admin password hash (bcrypt)

---

## 🛠️ Requirements

* PHP 8.x recommended
* **Optional:**

  * **GD with WebP** support → auto responsive WebP image variants
  * **ffmpeg** → video poster frames + audio loudness normalization

The site runs fine without GD/ffmpeg; those features are opportunistic.

---

## 🧭 Admin guide

### Creating & editing eggs

* Go to **/admin/** → “New Egg”
* Fill out fields (Title, Caption, Alt, Body)
* Add media: upload or pick from the **Media Picker** (with previews and audio play button)
* **Draft** on = hidden from visitors; off = visible
* Click **Visual Editor** to place the hotspot (click on the hero to set position)

### Visual Editor goodies

* **Grid** toggles a 5vw/5vh guide
* **Snap** clamps positions to the grid
* **390/768/1280** quick layout previews
* **Undo/Redo** remembers your last 10 placements

### Visitor gate (optional)

* Admin → Privacy → toggle on/off and set a password
* Visitors must enter that password once per session

---

## 🔊 Audio behavior

* The **main screech** loops once started. You can **stop** it via the main button and **mute/unmute** while it’s playing.

  * Mute/unmute button **only appears while playing**.
  * Tears fall **only when playing and unmuted**.
* Each **egg** can have its own audio (played on demand in the egg modal).

> Mobile browsers often require a first tap before audio can start. The UI handles this gracefully.

---

## 🔒 Link policy (no direct egg URLs)

* Eggs **must** be opened via the homepage modal.
* The iframe request includes a session **view token**; without it, the egg page returns **404**.
* This keeps the “hidden easter eggs” mechanic intact: you have to find them on the page.

---

## 🧑‍⚕️ Troubleshooting

* **Can’t log in after setup**

  * Make sure `admin/password.json` exists and is writable by PHP.
  * If needed, use the admin password reset flow (we recommend removing any temporary reset files after use).
* **Uploads not appearing**

  * Ensure `assets/uploads/` is writable (typical perms: 755/775 for folders).
* **Video has no thumbnail**

  * ffmpeg likely isn’t available on your host; the video still plays, just without a poster image.
* **Images don’t generate WebP variants**

  * Your PHP GD might not have WebP enabled. It’s optional.

---

## 🔐 Security notes (shared hosting)

* Many shared hosts inject security headers (CSP, X-Frame-Options, etc.) automatically.
* This project does **not** rely on `.htaccess`. If you move to a VPS or custom server, add headers at the web server level.
* `robots.txt` is included to **Disallow: /** by default; flip it if you decide to open the site up.

---

## 🧳 Backup / migrate

* Copy the whole folder, plus:

  * `admin/site.json`
  * `admin/password.json`
  * `eggs/data/*.json`
  * `assets/uploads/*`
* Drop onto the new host; first visit should **not** trigger setup if those files are present and readable.

---

## 📦 .gitignore (recommended)

If you’re committing this repo publicly, ignore secrets and heavy content:

```
# runtime secrets
admin/site.json
admin/password.json
admin/.unlock

# user content
eggs/data/*.json
assets/uploads/*

# temp logs/cache
logs/*
*.log
.cache/
```

---

## 🗒️ Changelog (recent highlights)

* Security: session view token for egg iframes; CSRF on admin endpoints; rate limiting on login and gate
* Admin UX: Visual Editor (grid/snap/breakpoints/undo), autosave, draft/publish, media picker with previews
* Media pipeline: responsive WebP variants (if GD/WebP), video poster thumbnails, audio loudness normalization
* Public: mute button only visible during playback; tears tied to audio state; silky modal animations

---

## License

Do whatever you want with it inside your friend group’s chaotic good jurisdiction. 😄

---
