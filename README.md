\# sucky.life — Plug \& Play



Inside-joke site with a lightweight flat-file CMS (“Egg Manager”), visual hotspot placement, and dramatic tear physics.



\## What’s included



\* \*\*Homepage (`index.html`)\*\*



&nbsp; \* Hero image + \*\*visceral screech\*\* audio.

&nbsp; \* \*\*Play/Stop toggle\*\* (not just mute).

&nbsp; \* \*\*Tear rain\*\* that only runs while audio is actually playing \& unmuted; cursor repels tears.

&nbsp; \* \*\*Animated modal\*\* (iframe) for eggs with smooth open/close transitions.

&nbsp; \* \*\*Dynamic hotspots\*\* loaded from the server (no hardcoding in HTML).



\* \*\*Eggs\*\*



&nbsp; \* `/eggs/egg.php` – displays an egg from JSON.

&nbsp; \* `/eggs/data/\*.json` – each egg’s content + position (`pos\_left` vw, `pos\_top` vh).

&nbsp; \* `/eggs/list.php` – JSON endpoint the homepage uses to render hotspots.



\* \*\*Admin (no-code content editing)\*\*



&nbsp; \* `/admin/index.php` – create/edit/delete/rename eggs, drag-and-drop or paste-to-upload images (auto-WebP).

&nbsp; \* \*\*Visual Editor\*\* `/admin/visual.php` – loads your homepage in an iframe; pick an egg, click to place it; saves `pos\_left`/`pos\_top` into JSON.

&nbsp; \* First-run \*\*password flow\*\* (default `sucky-life` → forced change at login).

&nbsp; \* `/admin/password.json` – stores the hash (updated via the UI).

&nbsp; \* `/admin/save\_position.php` – API to store egg positions.



\## Quick Start



1\. \*\*Upload everything\*\* to your PHP-enabled host.

2\. Ensure these folders are \*\*writable\*\* by PHP:



&nbsp;  \* `assets/uploads/`

&nbsp;  \* `eggs/data/`

3\. \*\*First login\*\*



&nbsp;  \* Visit `/admin`

&nbsp;  \* Password: `sucky-life`

&nbsp;  \* You’ll be prompted to set a new password (stored in `/admin/password.json`).

4\. \*\*Add content\*\*



&nbsp;  \* In Admin → “Create new egg” → fill Title/Caption/Body → upload/paste image → \*\*Save\*\*.

&nbsp;  \* Open \*\*Visual Editor\*\* → choose your egg → click on the preview to place → \*\*Save Position\*\*.

5\. \*\*Replace placeholders\*\*



&nbsp;  \* `assets/friend.jpg` – your hero image.

&nbsp;  \* `assets/screech.mp3` – your audio.



\## Everyday Use



\* \*\*Open an egg\*\*: click its hotspot on the homepage; animated modal opens an iframe with the egg page.

\* \*\*Reposition\*\*: Admin → Visual Editor → pick egg → click page → Save Position.

\* \*\*Edit content\*\*: Admin → pick egg → change fields → \*\*Save\*\*.

\* \*\*Rename or delete\*\*: Admin sidebar buttons (rename updates JSON filename; delete removes JSON but keeps any uploaded images).

\* \*\*Direct link to an egg\*\*: `https://yourdomain/?egg=slug` (auto-opens the modal to that egg).



\## File Map



```

/index.html                         # Homepage (audio, tears, dynamic hotspots, animated modal, play/stop)

/assets/friend.jpg                  # Replace with your image

/assets/screech.mp3                 # Replace with your audio

/assets/uploads/                    # Uploaded images (auto-WebP preferred)



/eggs/egg.php                       # Public egg renderer

/eggs/list.php                      # NEW: JSON feed with egg positions/titles

/eggs/data/\*.json                   # Flat JSON for each egg (content + pos\_left/pos\_top)



/admin/index.php                    # CMS UI (+ link to Visual Editor)

/admin/visual.php                   # NEW: Visual placement editor

/admin/save\_position.php            # NEW: Saves pos\_left/pos\_top

/admin/save.php                     # Saves egg content + handles image uploads (auto-WebP)

/admin/rename.php                   # Rename egg slug

/admin/delete.php                   # Delete egg JSON

/admin/util.php                     # Helpers (slugify, list/save eggs, WebP conversion)

/admin/config.php                   # Paths + first-run init

/admin/password.json                # Password store (auto-managed by UI; do not hand-edit)

```



\## Tech Notes



\* \*\*Positions are viewport-relative\*\*:



&nbsp; \* `pos\_left` (vw), `pos\_top` (vh). This keeps hotspots consistent across screen sizes.

\* \*\*Auto-WebP\*\* conversion:



&nbsp; \* Requires PHP GD with WebP. If missing, uploads are saved in original format.

\* \*\*Security\*\*



&nbsp; \* Change the default password immediately (we force this on first login).

&nbsp; \* Optionally add HTTP Basic Auth to `/admin`.

&nbsp; \* Back up `eggs/data/\*.json` regularly (that’s your content).

\* \*\*Performance\*\*



&nbsp; \* WebP keeps images light; prefer it when possible.

&nbsp; \* Home hotspots are loaded via `eggs/list.php` (small JSON).



\## GitHub Workflow



1\. Make edits locally (or copy/paste the provided files).

2\. Commit and push:



&nbsp;  ```bash

&nbsp;  git add .

&nbsp;  git commit -m "sucky.life: visual editor, dynamic hotspots, animated modal, play/stop tears"

&nbsp;  git push origin main

&nbsp;  ```

3\. Deploy to your host (or wire CI/CD to rsync/FTP).



\## Troubleshooting



\* \*\*I don’t see my hotspots\*\*



&nbsp; \* Make sure each egg you expect to see has `pos\_left` and `pos\_top` saved in its JSON. Use Admin → \*\*Visual Editor\*\* to place.

\* \*\*Images don’t convert to WebP\*\*



&nbsp; \* Your PHP GD may lack WebP; file saves in original format. That’s fine; site will still work.

\* \*\*Can’t upload\*\*



&nbsp; \* Ensure `assets/uploads/` is writable (e.g., perm 775).

\* \*\*Password problems\*\*



&nbsp; \* Password is managed by `/admin/password.json`. Use Admin → \*\*Change password\*\*. If locked out (last resort), delete `password.json` to restore default `sucky-life` at next login (then you’ll be forced to set a new one).



\## Accessibility



\* Add descriptive \*\*ALT text\*\* for each egg image in Admin to help screen readers.

\* Buttons have ARIA labels; modal can be closed with \*\*Esc\*\*.

