<?php
require_once '../config.php';
requireAuth();

$config = getConfig();
$eggs = getAllEggs(true, true); // Get all eggs (published and drafts)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/theme-toggle.js"></script>
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <div class="admin-nav-content">
            <h1 class="admin-brand"><?php echo htmlspecialchars($config['site_name']); ?></h1>
            <div class="admin-nav-links">
                <a href="/admin/index.php" class="nav-link active">Dashboard</a>
                <a href="/admin/settings.php" class="nav-link">Settings</a>
                <a href="/admin/logout.php" class="nav-link">Logout</a>
                <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
                    <span class="theme-toggle-slider"></span>
                </button>
            </div>
        </div>
    </nav>

    <main class="admin-main">
        <div class="admin-header">
            <h2>Easter Eggs</h2>
            <a href="/admin/egg-new.php" class="btn btn-primary">+ New Egg</a>
        </div>

        <?php if (empty($eggs)): ?>
            <div class="empty-state">
                <div class="empty-icon">🥚</div>
                <h3>No eggs yet</h3>
                <p>Create your first hidden easter egg to get started!</p>
                <a href="/admin/egg-new.php" class="btn btn-primary">Create First Egg</a>
            </div>
        <?php else: ?>
            <div class="eggs-grid">
                <?php foreach ($eggs as $egg): ?>
                    <div class="egg-card <?php echo (isset($egg['draft']) && $egg['draft']) ? 'draft' : ''; ?>">
                        <div class="egg-card-header">
                            <h3 class="egg-card-title"><?php echo htmlspecialchars($egg['title']); ?></h3>
                            <?php if (isset($egg['draft']) && $egg['draft']): ?>
                                <span class="badge badge-draft">Draft</span>
                            <?php else: ?>
                                <span class="badge badge-published">Published</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($egg['caption'])): ?>
                            <p class="egg-card-caption"><?php echo htmlspecialchars($egg['caption']); ?></p>
                        <?php endif; ?>

                        <div class="egg-card-meta">
                            <span>Position: <?php echo round($egg['pos_left'] ?? 50, 1); ?>vw, <?php echo round($egg['pos_top'] ?? 50, 1); ?>vh</span>
                        </div>

                        <div class="egg-card-actions">
                            <a href="/admin/egg-edit.php?slug=<?php echo urlencode($egg['slug']); ?>" class="btn btn-sm">Edit</a>
                            <a href="/admin/egg-place.php?slug=<?php echo urlencode($egg['slug']); ?>" class="btn btn-sm">Place</a>
                            <button onclick="deleteEgg('<?php echo htmlspecialchars($egg['slug']); ?>')" class="btn btn-sm btn-danger">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function deleteEgg(slug) {
            if (!confirm('Are you sure you want to delete this egg? This cannot be undone.')) {
                return;
            }

            fetch('/admin/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_egg',
                    slug: slug,
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete egg'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    </script>
</body>
</html>
