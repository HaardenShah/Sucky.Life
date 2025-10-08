Perfect timing — the project’s now cohesive enough for a professional, developer-friendly `README.md`.
Here’s a full, cleaned-up version that reflects all recent structural, UX, and security updates.

---

## 🥲 **sucky.life**

A lovingly over-engineered inside-joke website for our friend group.
Built to immortalize every tragicomic moment — complete with hidden “eggs”, visceral crying audio, interactive tears, and a dead-serious admin panel.

---

### 🧱 **Overview**

`sucky.life` is a PHP-based humor site featuring:

* A dramatic **screech button** with looping audio and animated tears.
* **Hidden easter eggs** scattered across the homepage.
* A sleek, modal-based viewer for each egg (images, videos, and audio supported).
* A secure **admin panel** with full CRUD (create, read, update, delete) control for eggs.
* A first-time **setup wizard** for easy configuration.
* Drag-and-drop media uploads with automatic `.webp` conversion.
* Visual egg placement via a live preview editor.

It’s built for shared hosting, no frameworks required — just drop it in and go.

---

### ⚙️ **Installation**

1. **Upload all files** to your hosting root or a subdirectory.
2. Visit the site in your browser.

   * The **setup wizard** appears automatically the first time.
3. Enter:

   * **Site name**
   * **Domain**
   * **Admin password** (you’ll be prompted to change it from the default)
4. That’s it — the wizard creates all required config and data folders.

---

### 🔐 **Security**

* Passwords are stored as **SHA-256 hashes** in `/admin/password.json`.
* All admin actions require authentication.
* Direct access to internal files (e.g. `eggs/data/*.json`) is blocked.
* Optionally hide drafts from the public by toggling in `/eggs/list.php`:

  ```php
  const HIDE_DRAFTS = true;
  ```
* Uploaded media is sanitized, renamed, and converted to `.webp` for safety and speed.

---

### 🧠 **Project Structure**

```
sucky.life/
│
├── index.php                 → Main landing page (screech + tears + egg loader)
├── assets/
│   ├── site.js               → Screech logic, tears animation, egg modals
│   ├── style.css             → Shared visual styles
│   └── media/                → Static images, icons, audio
│
├── admin/
│   ├── index.php             → Main admin dashboard
│   ├── login.php             → Admin authentication
│   ├── setup.php             → One-time site configuration wizard
│   ├── util.php              → Reusable backend helpers
│   ├── save.php              → Handles egg creation / updates
│   ├── delete.php            → Removes an egg
│   ├── config.php            → Paths and globals
│   ├── password.json         → Hashed admin password (auto-generated)
│   └── data/                 → Internal configs and logs
│
├── eggs/
│   ├── data/                 → Each egg is a JSON file (content + metadata)
│   ├── list.php              → Public API listing all visible eggs
│   ├── egg.php               → Modal renderer (polished “glass card”)
│   └── uploads/              → Uploaded images, videos, and audio
│
└── README.md                 → You’re reading this.
```

---

### 🎨 **Design Features**

* **Glass card modal:** Subtle gradients, soft borders, and top color ribbon.
* **Smooth animations:** Apple-like transitions for modals and interactions.
* **Responsive scaling:** Layout adapts to all viewports (390px → 1280px+).
* **Optimized assets:** Automatic `.webp` conversion and lazy loading.
* **Accessibility:** Keyboard navigation + ARIA roles for interactive elements.

---

### 🧰 **Admin Features**

#### 🖼️ Egg Manager

* Add, rename, update, or delete eggs.
* Drag-and-drop images or videos.
* Assign **custom audio** per egg.
* Toggle **draft mode** to hide an egg from public view.
* Live-preview the homepage in an iframe.
* Click to visually **place** an egg — it saves the viewport coordinates automatically.

#### ⚙️ System Settings

* Change password securely from inside the panel.
* Auto-logout on inactivity.
* Setup screen runs only once (then locks itself).

---

### 🧩 **Egg JSON Schema**

Each egg is stored as a `.json` file in `/eggs/data/`:

```json
{
  "slug": "boneless-mystique",
  "title": "Boneless Mystique",
  "caption": "He swore it was boneless. Reader, it was not.",
  "body": "Some bites crunch. Others crunch back.",
  "image": "/eggs/uploads/boneless.webp",
  "video": "",
  "audio": "/eggs/uploads/chicken-scream.mp3",
  "pos_left": 32.5,
  "pos_top": 61.2,
  "draft": false
}
```

---

### 🧑‍💻 **Developer Notes**

* No database — everything is **file-driven**.
* Uses plain PHP + vanilla JS for simplicity and portability.
* Designed to work on **shared hosting** (Apache, Nginx, or LiteSpeed).
* `.htaccess` is optional — the host typically handles rewrites and MIME types.
* All code is **UTF-8** safe (no broken emoji or character encoding issues).
* Editing HTML/CSS/JS is safe — every major section is commented.

---

### 🪄 **Recent Major Updates**

| Area                   | Description                                                              |
| ---------------------- | ------------------------------------------------------------------------ |
| 🧭 **Setup Wizard**    | Added full site initialization flow with password creation.              |
| 🛠️ **Admin Panel**    | Modularized (split into login, index, save, delete). Easier maintenance. |
| 🎨 **Egg Modals**      | Rebuilt with glass-card design, proper aspect ratios, better typography. |
| 💾 **Data Handling**   | Switched to flexible egg discovery with fallback paths.                  |
| 🔊 **Audio System**    | Mute/unmute button only visible when screech plays.                      |
| 💧 **Tears Animation** | Cursor repels tears dynamically; synchronized to audio playback.         |
| 🔐 **Security Layer**  | Password hashing, JSON validation, and draft visibility toggle.          |
| 🪶 **Performance**     | `.webp` conversion + lazy loading + reduced JS CPU loops.                |

---

### 🚀 **Future Enhancements**

* Optional **multi-user** admin support.
* Media compression queue for heavy uploads.
* Egg clustering mode (for thematic grouping).
* Public “random egg” shuffle button.

---

### ❤️ **Credits**

Built by a bunch of friends who take jokes too seriously.
Dedicated to *that one guy* whose life is just so… sucky. 💀

---

Would you like me to append a **“Deployment & Hosting Tips”** section too (covering permissions, SSL setup, and PHP version recommendations)? It would make the README fully production-ready.
