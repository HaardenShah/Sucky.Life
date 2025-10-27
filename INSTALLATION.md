# sucky.life Installation Guide

## Quick Start

1. **Extract the archive** to your web hosting root directory
2. **Set permissions** on the data directory:
   ```bash
   chmod -R 755 data/
   ```
3. **Visit your domain** - you'll auto-redirect to setup
4. **Complete setup wizard**:
   - Site name (e.g., "sucky.life")
   - Domain (e.g., "sucky.life" or "example.com")
   - Admin password (min 8 chars)
5. **Add screech audio**: Upload your MP3 to `/assets/audio/screech.mp3`

## First Steps After Installation

1. **Login** at `yourdomain.com/admin/login.php`
2. **Create your first egg**:
   - Dashboard → "New Egg"
   - Enter a title
   - Add content in the editor
   - Upload media (optional)
   - Click "Place on Site" to position
   - Uncheck "draft" to publish
3. **Test the site**: Visit homepage and click the screech button!

## Server Requirements

- PHP 7.4+
- GD extension (for WebP conversion)
- Writable `/data` directory

## File Structure After Extraction

```
/your-web-root/
├── admin/              Admin panel files
├── assets/            CSS, JS, and media
│   ├── css/
│   ├── js/
│   └── audio/         ⚠️ Add screech.mp3 here
├── data/              Storage (auto-created)
│   ├── eggs/          Egg JSON files
│   └── uploads/       User uploads
├── config.php
├── index.php
└── gate.php
```

## Optional: Enable Site Password

1. Go to Admin → Settings
2. Check "Enable site password"
3. Set a password for visitors
4. Save settings

Visitors will now need this password to access the site.

## Adding Your Screech Audio

The site needs an audio file at `/assets/audio/screech.mp3`:

1. Find or record a dramatic screech/cry sound
2. Convert to MP3 format
3. Upload to `/assets/audio/screech.mp3`
4. Test by clicking the screech button on homepage

**Tip**: Search for "dramatic scream sound effect" on royalty-free sites like Freesound.org

## Troubleshooting

### Setup page won't load
- Check that all files extracted properly
- Verify web server is running PHP

### Can't save configuration
- Ensure `/data` directory exists and is writable
- Try: `chmod -R 755 data/`

### Images won't upload
- Check PHP upload limits in php.ini
- Verify GD extension is installed: `php -m | grep gd`
- Ensure `/data/uploads` is writable

### Screech button does nothing
- Add audio file at `/assets/audio/screech.mp3`
- Check browser console for errors
- Verify audio file format is MP3

### Tears are laggy
- Edit `/assets/js/main.js`
- Line ~78: Increase interval from 150 to 300ms
- Fewer tears = better performance

## Security Notes

- Admin password is hashed with bcrypt
- Change default password immediately
- Enable site password gate for privacy
- Keep PHP and server updated
- Backup `/data` directory regularly

## Need Help?

Check README.md in the archive for detailed documentation.

---

Built with drama, polish, and vanilla tech. Enjoy your inside-joke empire! 🥚