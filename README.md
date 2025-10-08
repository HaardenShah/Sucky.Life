sucky.life — Plug & Play

Inside-joke site with a lightweight flat-file CMS (“Egg Manager”), visual hotspot placement, and dramatic tear physics.

What’s included

Homepage (index.html)

Hero image + visceral screech audio.

Play/Stop toggle (not just mute).

Tear rain that only runs while audio is actually playing & unmuted; cursor repels tears.

Animated modal (iframe) for eggs with smooth open/close transitions.

Dynamic hotspots loaded from the server (no hardcoding in HTML).

Eggs

/eggs/egg.php – displays an egg from JSON.

/eggs/data/*.json – each egg’s content + position (pos_left vw, pos_top vh).

/eggs/list.php – JSON endpoint the homepage uses to render hotspots.

Admin (no-code content editing)

/admin/index.php – create/edit/delete/rename eggs, drag-and-drop or paste-to-upload images (auto-WebP).

Visual Editor /admin/visual.php – loads your homepage in an iframe; pick an egg, click to place it; saves pos_left/pos_top into JSON.

First-run password flow (default sucky-life → forced change at login).

/admin/password.json – stores the hash (updated via the UI).

/admin/save_position.php – API to store egg positions.

Quick Start

Upload everything to your PHP-enabled host.

Ensure these folders are writable by PHP:

assets/uploads/

eggs/data/

First login

Visit /admin

Password: sucky-life

You’ll be prompted to set a new password (stored in /admin/password.json).

Add content

In Admin → “Create new egg” → fill Title/Caption/Body → upload/paste image → Save.

Open Visual Editor → choose your egg → click on the preview to place → Save Position.

Replace placeholders

assets/friend.jpg – your hero image.

assets/screech.mp3 – your audio.

Everyday Use

Open an egg: click its hotspot on the homepage; animated modal opens an iframe with the egg page.

Reposition: Admin → Visual Editor → pick egg → click page → Save Position.

Edit content: Admin → pick egg → change fields → Save.

Rename or delete: Admin sidebar buttons (rename updates JSON filename; delete removes JSON but keeps any uploaded images).

Direct link to an egg: https://yourdomain/?egg=slug (auto-opens the modal to that egg).

File Map
/index.html                         # Homepage (audio, tears, dynamic hotspots, animated modal, play/stop)
/assets/friend.jpg                  # Replace with your image
/assets/screech.mp3                 # Replace with your audio
/assets/uploads/                    # Uploaded images (auto-WebP preferred)

/eggs/egg.php                       # Public egg renderer
/eggs/list.php                      # NEW: JSON feed with egg positions/titles
/eggs/data/*.json                   # Flat JSON for each egg (content + pos_left/pos_top)

/admin/index.php                    # CMS UI (+ link to Visual Editor)
/admin/visual.php                   # NEW: Visual placement editor
/admin/save_position.php            # NEW: Saves pos_left/pos_top
/admin/save.php                     # Saves egg content + handles image uploads (auto-WebP)
/admin/rename.php                   # Rename egg slug
/admin/delete.php                   # Delete egg JSON
/admin/util.php                     # Helpers (slugify, list/save eggs, WebP conversion)
/admin/config.php                   # Paths + first-run init
/admin/password.json                # Password store (auto-managed by UI; do not hand-edit)

Tech Notes

Positions are viewport-relative:

pos_left (vw), pos_top (vh). This keeps hotspots consistent across screen sizes.

Auto-WebP conversion:

Requires PHP GD with WebP. If missing, uploads are saved in original format.

Security

Change the default password immediately (we force this on first login).

Optionally add HTTP Basic Auth to /admin.

Back up eggs/data/*.json regularly (that’s your content).

Performance

WebP keeps images light; prefer it when possible.

Home hotspots are loaded via eggs/list.php (small JSON).

GitHub Workflow

Make edits locally (or copy/paste the provided files).

Commit and push:

git add .
git commit -m "sucky.life: visual editor, dynamic hotspots, animated modal, play/stop tears"
git push origin main


Deploy to your host (or wire CI/CD to rsync/FTP).

Troubleshooting

I don’t see my hotspots

Make sure each egg you expect to see has pos_left and pos_top saved in its JSON. Use Admin → Visual Editor to place.

Images don’t convert to WebP

Your PHP GD may lack WebP; file saves in original format. That’s fine; site will still work.

Can’t upload

Ensure assets/uploads/ is writable (e.g., perm 775).

Password problems

Password is managed by /admin/password.json. Use Admin → Change password. If locked out (last resort), delete password.json to restore default sucky-life at next login (then you’ll be forced to set a new one).

Accessibility

Add descriptive ALT text for each egg image in Admin to help screen readers.

Buttons have ARIA labels; modal can be closed with Esc.