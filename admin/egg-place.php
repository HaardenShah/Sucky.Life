<?php
require_once '../config.php';
requireAuth();

$config = getConfig();
$slug = $_GET['slug'] ?? '';
$egg = getEgg($slug);

if (!$egg) {
    header('Location: /admin/index.php');
    exit;
}

// Get all other eggs to show their positions
$allEggs = getAllEggs(true, true); // Include both published and drafts
$otherEggs = array_filter($allEggs, function($e) use ($slug) {
    return $e['slug'] !== $slug && isset($e['pos_left']) && isset($e['pos_top']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Egg: <?php echo htmlspecialchars($egg['title']); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        body {
            margin: 0;
            overflow: hidden;
            user-select: none;
        }

        .placement-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
            pointer-events: none;
        }

        .placement-controls {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 2rem;
            z-index: 100001;
            pointer-events: all;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .placement-title {
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
        }

        .placement-position {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            font-family: 'Courier New', monospace;
        }

        .placement-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .placement-btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .placement-btn-save:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .placement-btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .placement-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .placement-marker {
            position: fixed;
            width: 60px;
            height: 60px;
            background: rgba(102, 126, 234, 0.3);
            border: 3px solid #667eea;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 100002;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
            }
            50% {
                box-shadow: 0 0 40px rgba(102, 126, 234, 0.9);
            }
        }

        .placement-marker::after {
            content: '<?php echo substr(htmlspecialchars($egg['title']), 0, 1); ?>';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .save-success {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            padding: 2rem 3rem;
            border-radius: 20px;
            text-align: center;
            z-index: 100003;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .save-success.show {
            opacity: 1;
            pointer-events: all;
        }

        .save-success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: scaleUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes scaleUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .save-success-text {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .existing-egg-marker {
            position: fixed;
            width: 50px;
            height: 50px;
            background: rgba(255, 107, 107, 0.2);
            border: 2px solid rgba(255, 107, 107, 0.6);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            pointer-events: all;
            cursor: help;
            z-index: 100001;
            box-shadow: 0 0 10px rgba(255, 107, 107, 0.3);
            transition: all 0.3s;
        }

        .existing-egg-marker:hover {
            background: rgba(255, 107, 107, 0.3);
            border-color: rgba(255, 107, 107, 0.8);
            box-shadow: 0 0 20px rgba(255, 107, 107, 0.5);
            z-index: 100003;
        }

        .existing-egg-marker::after {
            content: attr(data-title);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .existing-egg-tooltip {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .existing-egg-marker:hover .existing-egg-tooltip {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Show the actual homepage in the background -->
    <div class="hero" <?php if (!empty($config['hero_image'])): ?>style="background-image: url('<?php echo htmlspecialchars($config['hero_image']); ?>');"<?php endif; ?>>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($config['hero_text'] ?? 'When the universe has it out for you'); ?></h1>
        </div>
    </div>

    <!-- Placement UI -->
    <div class="placement-overlay">
        <div class="placement-controls">
            <span class="placement-title">Click anywhere to place: <?php echo htmlspecialchars($egg['title']); ?></span>
            <span class="placement-position" id="position-display">
                <?php echo round($egg['pos_left'] ?? 50, 1); ?>vw, <?php echo round($egg['pos_top'] ?? 50, 1); ?>vh
            </span>
            <?php if (count($otherEggs) > 0): ?>
                <span class="placement-position" style="color: rgba(255, 107, 107, 0.9);">
                    <?php echo count($otherEggs); ?> other egg<?php echo count($otherEggs) !== 1 ? 's' : ''; ?> (red markers)
                </span>
            <?php endif; ?>
            <button class="placement-btn placement-btn-save" id="save-btn">Save</button>
            <button class="placement-btn placement-btn-cancel" onclick="window.location.href='/admin/egg-edit.php?slug=<?php echo urlencode($slug); ?>'">Cancel</button>
        </div>

        <div class="placement-marker" id="marker" style="left: <?php echo $egg['pos_left'] ?? 50; ?>vw; top: <?php echo $egg['pos_top'] ?? 50; ?>vh;"></div>

        <!-- Show existing eggs -->
        <?php foreach ($otherEggs as $otherEgg): ?>
            <div class="existing-egg-marker"
                 style="left: <?php echo $otherEgg['pos_left']; ?>vw; top: <?php echo $otherEgg['pos_top']; ?>vh;"
                 data-title="<?php echo substr(htmlspecialchars($otherEgg['title']), 0, 1); ?>">
                <div class="existing-egg-tooltip"><?php echo htmlspecialchars($otherEgg['title']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="save-success" id="save-success">
        <div class="save-success-icon">✓</div>
        <div class="save-success-text">Position saved!</div>
    </div>

    <script>
        const slug = <?php echo json_encode($slug); ?>;
        const csrfToken = <?php echo json_encode(generateCSRFToken()); ?>;
        const marker = document.getElementById('marker');
        const positionDisplay = document.getElementById('position-display');
        const saveBtn = document.getElementById('save-btn');
        const saveSuccess = document.getElementById('save-success');

        let currentLeft = <?php echo $egg['pos_left'] ?? 50; ?>;
        let currentTop = <?php echo $egg['pos_top'] ?? 50; ?>;

        // Click to place
        document.body.addEventListener('click', (e) => {
            // Ignore clicks on controls and existing egg markers
            if (e.target.closest('.placement-controls') || e.target.closest('.existing-egg-marker')) {
                return;
            }

            const vw = (e.clientX / window.innerWidth) * 100;
            const vh = (e.clientY / window.innerHeight) * 100;

            currentLeft = Math.max(0, Math.min(100, vw));
            currentTop = Math.max(0, Math.min(100, vh));

            marker.style.left = currentLeft + 'vw';
            marker.style.top = currentTop + 'vh';

            positionDisplay.textContent = `${currentLeft.toFixed(1)}vw, ${currentTop.toFixed(1)}vh`;
        });

        // Save position
        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch('/admin/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_position',
                        slug: slug,
                        pos_left: currentLeft,
                        pos_top: currentTop,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    saveSuccess.classList.add('show');
                    setTimeout(() => {
                        window.location.href = '/admin/egg-edit.php?slug=' + encodeURIComponent(slug);
                    }, 1500);
                } else {
                    alert('Error: ' + (data.error || 'Failed to save position'));
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        });

        // Prevent text selection
        document.addEventListener('selectstart', (e) => {
            e.preventDefault();
        });
    </script>
</body>
</html>