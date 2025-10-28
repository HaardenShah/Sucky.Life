<?php
require_once 'config.php';

// Check if setup is complete
if (!isSetupComplete()) {
    header('Location: /admin/setup.php');
    exit;
}

$config = getConfig();

// Check if site password is enabled
if (isset($config['site_password_enabled']) && $config['site_password_enabled']) {
    if (!isset($_SESSION['site_access']) || $_SESSION['site_access'] !== true) {
        header('Location: /gate.php');
        exit;
    }
}

// Get published eggs only
$eggs = getAllEggs(true, false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_name'] ?? 'sucky.life'); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <div class="hero" <?php if (!empty($config['hero_image'])): ?>style="background-image: url('<?php echo htmlspecialchars($config['hero_image']); ?>');"<?php endif; ?>>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($config['hero_text'] ?? 'When the universe has it out for you'); ?></h1>
            <button id="screech-btn" class="screech-button" aria-label="Unleash the screech">
                Press here to unleash the screech
            </button>
            <div id="audio-controls" class="audio-controls hidden">
                <button id="mute-btn" class="control-btn" aria-label="Mute">
                    <span class="mute-icon">🔊</span>
                </button>
                <button id="stop-btn" class="control-btn" aria-label="Stop">
                    <span class="stop-icon">⏹</span>
                </button>
            </div>
        </div>
    </div>

    <div id="tears-container" class="tears-container"></div>

    <!-- Hidden hotspots -->
    <?php foreach ($eggs as $egg): ?>
        <?php
            $left = isset($egg['pos_left']) ? (float)$egg['pos_left'] : 50;
            $top = isset($egg['pos_top']) ? (float)$egg['pos_top'] : 50;
            $left = max(0, min(100, $left));
            $top = max(0, min(100, $top));
        ?>
        <div class="egg-hotspot" 
             data-slug="<?php echo htmlspecialchars($egg['slug']); ?>"
             style="left: <?php echo $left; ?>vw; top: <?php echo $top; ?>vh;"
             role="button"
             tabindex="0"
             aria-label="<?php echo htmlspecialchars($egg['title'] ?? 'Hidden egg'); ?>">
            <span class="egg-tooltip"><?php echo htmlspecialchars($egg['title'] ?? 'Hidden egg'); ?></span>
        </div>
    <?php endforeach; ?>

    <!-- Modal -->
    <div id="egg-modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-backdrop"></div>
        <div class="modal-card">
            <button class="modal-close" aria-label="Close modal">&times;</button>
            <div id="modal-content" class="modal-content">
                <!-- Dynamic content loaded here -->
            </div>
        </div>
    </div>

    <audio id="screech-audio" loop>
        <source src="/assets/audio/screech.mp3" type="audio/mpeg">
    </audio>

    <script>
        window.eggsData = <?php echo json_encode($eggs); ?>;
    </script>
    <script src="/assets/js/main.js"></script>
</body>
</html>