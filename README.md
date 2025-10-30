# 🥚 sucky.life

> **Inside-Joke Website Platform**  
> When the universe has it out for you, document it with style.

![Version](https://img.shields.io/badge/version-2.0-purple?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)

A polished, drama-filled platform for creating hidden easter egg websites. Perfect for friend groups, private galleries, and chaotic inside jokes.

---

## ✨ What's New in v2.0

- 🔒 **Enhanced Security**: CSRF protection, session timeouts, file locking
- 🎨 **Hero Customization**: Custom background images and text
- 📁 **Improved File Handling**: Size limits, type validation, WebP conversion
- 🌓 **Dark Mode**: Light/dark theme toggle in admin panel
- ⚡ **Performance**: Optimized uploads and concurrent access handling
- 🛡️ **Hardened Protection**: Security headers, input validation, position clamping

---

## 📦 What's Included

**Complete Website Bundle** (~25KB compressed)
- 20+ PHP files (public site + admin panel)
- Responsive CSS stylesheets (light/dark mode)
- Vanilla JavaScript (tears, hotspots, media picker)
- Configuration & security files
- Full documentation

---

## 🚀 Quick Start

```bash
# 1. Extract to web hosting root
tar -xzf sucky-life-website.tar.gz

# 2. Set permissions
chmod -R 755 data/

# 3. Visit your domain
# Auto-redirects to setup wizard

# 4. Complete 4-field setup
# - Site name
# - Domain
# - Admin password
# - Confirm password

# 5. Add screech audio
# Upload MP3 to /assets/audio/screech.mp3

# 6. Login and create your first egg!
```

**That's it!** No database, no dependencies, no complex configuration.

---

## 🎯 Key Features

### Public Site

- ✅ **Dramatic Hero Section** with customizable text and background
- ✅ **Unleash the Screech** button (loops audio with controls)
- ✅ **Animated Tears** that repel from cursor
- ✅ **Hidden Easter Egg Hotspots** placed anywhere on screen
- ✅ **Glass-Morphism Modals** with media + rich content
- ✅ **Optional Password Gate** for privacy

### Admin Panel

- ✅ **Animated Setup Wizard** (first-run experience)
- ✅ **Visual Egg Placement Tool** (click to position)
- ✅ **Rich Content Editor** (HTML support)
- ✅ **Drag-and-Drop Media Uploads** (images, videos, audio)
- ✅ **Automatic WebP Conversion** (image optimization)
- ✅ **Draft System** (hide eggs until ready)
- ✅ **Light/Dark Theme Toggle**
- ✅ **Hero Customization** (text + background image)
- ✅ **Settings Panel** (site config + password management)

---

## 💻 Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 7.4+ with file-based JSON storage |
| **Frontend** | Vanilla JavaScript (zero frameworks) |
| **Styling** | CSS3 with backdrop-filter effects |
| **Security** | Bcrypt passwords, CSRF tokens, session management |
| **Images** | Automatic WebP conversion via GD |
| **Storage** | File-based (no database required) |

---

## 📁 File Structure

```
/your-web-root/
├── admin/                  # Admin panel (8 files)
│   ├── api.php            # AJAX endpoints with CSRF protection
│   ├── index.php          # Dashboard
│   ├── login.php          # Admin login
│   ├── setup.php          # First-run wizard
│   ├── settings.php       # Site configuration
│   ├── egg-new.php        # Create egg
│   ├── egg-edit.php       # Edit egg content
│   ├── egg-place.php      # Visual placement tool
│   └── logout.php         # Session cleanup
├── assets/
│   ├── css/
│   │   ├── main.css       # Public site styles
│   │   └── admin.css      # Admin panel styles (light/dark)
│   ├── js/
│   │   ├── main.js        # Tears, hotspots, modal logic
│   │   ├── admin-editor.js # Media picker, uploads
│   │   └── theme-toggle.js # Dark mode switcher
│   └── audio/
│       └── screech.mp3    # ⚠️ Required audio file
├── data/                  # Auto-created storage
│   ├── index.php          # Security protection (403)
│   ├── config.json        # Site configuration
│   ├── error.log          # Error logging
│   ├── eggs/              # Egg JSON files
│   └── uploads/           # User-uploaded media
├── config.php             # Core functions & security
├── index.php              # Homepage
├── gate.php               # Password protection
├── INSTALLATION.md        # Setup guide
└── README.md              # This file
```

---

## ⚙️ Server Requirements

- **PHP**: 7.4 or higher
- **Extensions**: GD (for WebP conversion)
- **Permissions**: Writable `/data` directory
- **Optional**: Pretty URLs (mod_rewrite)

**Check your environment:**
```bash
php -v                    # Check PHP version
php -m | grep gd          # Verify GD extension
ls -la data/              # Check permissions
```

---

## 🔒 Security Features

| Feature | Implementation |
|---------|---------------|
| **Password Hashing** | Bcrypt (PASSWORD_DEFAULT) |
| **CSRF Protection** | Tokens on all state changes |
| **Session Security** | HttpOnly cookies, strict mode |
| **File Locking** | Prevents race conditions |
| **Session Timeout** | 30-minute inactivity limit |
| **Input Validation** | File types, sizes, positions |
| **Output Escaping** | htmlspecialchars() everywhere |
| **Security Headers** | X-Frame-Options, CSP, etc. |
| **Upload Limits** | 2MB max file size |
| **Directory Protection** | index.php blocks browsing |

---

## 📝 Usage Workflow

### Creating Your First Egg

1. **Login** at `yourdomain.com/admin/login.php`
2. **Click "New Egg"** on dashboard
3. **Enter a title** (e.g., "The Coffee Incident")
4. **Add content**:
   - Caption (optional italic text)
   - Body (supports HTML: `<p>`, `<strong>`, `<em>`, etc.)
   - Alt text (accessibility)
5. **Upload media** (optional):
   - Image (auto-converts to WebP)
   - Video (alternative to image)
   - Audio (plays in modal)
6. **Click "Place on Site"**:
   - Click anywhere to position hotspot
   - Save placement
7. **Uncheck "Draft"** to publish
8. **Test on homepage!**

### Customizing Your Site

**Settings → Site Settings:**
- Change site name
- Update domain
- Customize hero text
- Upload hero background image
- Enable/disable password gate
- Set visitor password

**Settings → Change Admin Password:**
- Enter current password
- Set new password (min 8 chars)
- Confirm new password

---

## 🎨 Design Philosophy

> "Minimalist Apple-adjacent polish, chaotic on purpose"

- **Dark gradients** and glass morphism
- **Smooth animations** with spring physics
- **Premium feel** with playful interactions
- **Clean UI**, hidden complexity
- **Accessibility-first** (ARIA labels, keyboard nav)

---

## 🎭 Perfect For

- 👥 **Friend Group Inside Jokes**
- 📸 **Private Photo/Video Galleries**
- 📖 **Interactive Storytelling**
- 🎉 **Event Scrapbooks**
- 🕵️ **Dramatic Easter Egg Hunts**
- 💭 **Memory Collections**
- 🎨 **Creative Projects**

---

## 🔧 Troubleshooting

### Setup page won't load
- Verify all files extracted properly
- Check web server is running PHP 7.4+
- Ensure `config.php` is readable

### Can't save configuration
```bash
# Fix permissions
chmod -R 755 data/
chown -R www-data:www-data data/  # Linux/Apache
chown -R _www:_www data/          # macOS
```

### Images won't upload
```bash
# Check PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Verify GD extension
php -m | grep gd
```

### Screech button does nothing
- Upload audio file to `/assets/audio/screech.mp3`
- Check browser console for errors
- Verify file format is MP3

### Tears are laggy
```javascript
// Edit /assets/js/main.js around line 78
// Increase interval from 150ms to 300ms
tearInterval = setInterval(() => {
    if (isPlaying && !isMuted) {
        createTear();
    }
}, 300); // Changed from 150
```

### Session timeout too short/long
```php
// Edit /config.php around line 56
// Change timeout value (in seconds)
$timeout = 3600; // 1 hour instead of 30 minutes
```

### Can't access /data directory (403)
**Good!** This is intentional security. The `/data/index.php` file prevents direct access to your JSON files and uploads.

---

## 🛡️ Security Best Practices

### Immediately After Installation

1. ✅ Change the default admin password
2. ✅ Set strong, unique passwords (min 12 chars)
3. ✅ Enable site password gate if privacy is needed
4. ✅ Verify `/data` directory returns 403 error

### Regular Maintenance

- 🔄 Keep PHP and server software updated
- 💾 Backup `/data` directory regularly
- 📊 Monitor `/data/error.log` for issues
- 🔍 Review uploaded files periodically
- 🔐 Rotate passwords every 6 months

### Production Deployment

```bash
# Disable error display
# Add to config.php after session_start():
ini_set('display_errors', 0);
error_reporting(0);

# Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', DATA_PATH . '/error.log');
```

---

## 📚 Documentation Files

| File | Description |
|------|-------------|
| `README.md` | Complete documentation (this file) |
| `INSTALLATION.md` | Step-by-step setup guide |
| `FEATURES.md` | Detailed feature overview |

---

## 🎨 Customization Tips

### Change Color Scheme

Edit `/assets/css/main.css`:
```css
/* Update gradient colors */
background: linear-gradient(135deg, #YOUR_COLOR_1 0%, #YOUR_COLOR_2 100%);

/* Update button colors */
.screech-button {
    background: linear-gradient(135deg, #YOUR_COLOR_3 0%, #YOUR_COLOR_4 100%);
}
```

### Adjust Tear Behavior

Edit `/assets/js/main.js`:
```javascript
// Line ~78: Change tear frequency
tearInterval = setInterval(() => {
    createTear();
}, 150); // Lower = more tears

// Line ~87: Change tear speed
const duration = 2 + Math.random() * 2; // Adjust duration

// Line ~131: Change repulsion
const repelRadius = 100; // Larger = wider effect
const repelForce = 50;   // Larger = stronger push
```

### Add Custom Fonts

Add to `/assets/css/main.css`:
```css
@import url('https://fonts.googleapis.com/css2?family=Your+Font&display=swap');

body {
    font-family: 'Your Font', -apple-system, BlinkMacSystemFont, sans-serif;
}
```

---

## 🚀 Performance Tips

### For Large Media Libraries

1. **Use WebP**: Already automatic for images
2. **Compress videos**: Use HandBrake or FFmpeg before uploading
3. **Limit file sizes**: Reduce max upload size in `admin/api.php`
4. **Clean old uploads**: Periodically remove unused media

### For Many Eggs

The file-based storage handles ~1000 eggs efficiently. Beyond that, consider:
- Implementing pagination in admin dashboard
- Adding search/filter functionality
- Migrating to SQLite for better performance

---

## 🔄 Backup & Restore

### Backup
```bash
# Backup everything
tar -czf sucky-life-backup-$(date +%Y%m%d).tar.gz data/

# Backup just eggs and config
tar -czf eggs-backup-$(date +%Y%m%d).tar.gz data/eggs/ data/config.json
```

### Restore
```bash
# Restore full backup
tar -xzf sucky-life-backup-20250101.tar.gz

# Restore specific files
tar -xzf eggs-backup-20250101.tar.gz
```

### Automated Backups
```bash
# Add to crontab for daily backups at 2 AM
0 2 * * * cd /path/to/site && tar -czf backups/backup-$(date +\%Y\%m\%d).tar.gz data/
```

---

## 🐛 Known Limitations

- **Concurrent editing**: While file locking prevents corruption, two admins editing the same egg simultaneously may overwrite changes
- **No version history**: Egg changes are permanent (backup before major edits)
- **Single admin account**: Only one admin user supported (use strong password!)
- **No mobile placement**: Egg placement tool requires desktop/laptop for precision
- **Audio format**: Only MP3 supported for screech audio

---

## 🗺️ Roadmap (Potential Future Features)

- [ ] Multiple admin accounts with roles
- [ ] Egg revision history
- [ ] Media library management (delete unused files)
- [ ] Export/import eggs
- [ ] Themes/templates
- [ ] Mobile-friendly placement tool
- [ ] Egg categories/tags
- [ ] Search functionality
- [ ] Analytics (egg views, clicks)
- [ ] SQLite migration path

---

## 💡 Tips & Tricks

### Audio Recommendations

**Best sources for screech audio:**
- [Freesound.org](https://freesound.org) (royalty-free sound effects)
- Record your own dramatic scream
- Use animal sounds (peacock screech works great!)
- Mix multiple sounds for unique effect

**Audio specs:**
- Format: MP3
- Bitrate: 128-192 kbps (balance quality/size)
- Duration: 3-10 seconds (loops automatically)
- Volume: Normalize to -14 LUFS

### Content Ideas

**Great egg content:**
- Story moments with photos/videos
- Voice messages or audio clips
- Memes with context
- Recipe cards
- Quotes with attribution
- Timeline events
- Behind-the-scenes content
- Reaction compilations

### Placement Strategy

**Effective hotspot placement:**
- Cluster related eggs together
- Hide eggs in unexpected places
- Use visual cues (subtle hints in background)
- Vary position: corners, edges, center
- Test on different screen sizes
- Leave some easy to find, others challenging

---

## 🤝 Contributing

This is a personal project template, but feel free to:
- Fork and customize for your needs
- Share improvements and bug fixes
- Create themes or extensions
- Submit detailed bug reports

---

## 📄 License

MIT License - Feel free to use, modify, and distribute.

**Attribution appreciated but not required.**

---

## 🙏 Credits

Built with:
- ❤️ Love for friends who've seen some stuff
- ☕ Too much coffee
- 🎭 A flair for the dramatic
- 🔧 Vanilla tech (PHP + JavaScript + CSS)

Special thanks to:
- Everyone who's experienced a "sucky.life" moment
- Inside joke creators everywhere
- The friend groups who make life memorable

---

## 🆘 Support

### Need Help?

1. Check `INSTALLATION.md` for setup issues
2. Review `FEATURES.md` for feature details  
3. Search this README for your specific issue
4. Check `/data/error.log` for error messages

### Found a Bug?

Please include:
- PHP version (`php -v`)
- Error message from `/data/error.log`
- Steps to reproduce
- Expected vs actual behavior

---

## 🎉 Final Words

**May your inside jokes echo through eternity!** 🥚✨

Built for moments that are too good not to document, too weird to explain, and too precious to forget. Whether it's documenting coffee disasters, immortalizing group chat legends, or creating an interactive memory book, sucky.life is here for your chaotic, beautiful, dramatic moments.

Now go forth and create some digital mayhem! 🚀

---

```
┌──────────────────────────────────────────────────────────┐
│                    sucky.life v2.0                       │
│         Inside-Joke Website Platform                     │
│                                                          │
│   When the universe has it out for you,                  │
│   document it with style.                                │
│                                                          │
│   PHP + Vanilla JS + File-Based Storage                  │
│   No Database • No Frameworks • No Complexity            │
└──────────────────────────────────────────────────────────┘
```

**Version 2.0** | **PHP 7.4+** | **Zero Dependencies** | **Production Ready**