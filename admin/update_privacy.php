<?php
declare(strict_types=1);

/**
 * admin/update_privacy.php
 * Save the privacy policy content (file-storage friendly).
 * - Expects POST with 'content' (or 'privacy') field and CSRF token.
 * - Persists to /pages/privacy.html (adjust path below if your reader uses another file).
 */

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$wants_json = (($_SERVER['HTTP_ACCEPT'] ?? '') && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

function respond_json(array $p): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if (empty($_SESSION['authed'])) {
  if ($wants_json) respond_json(['ok'=>false,'error'=>'Not authenticated.']);
  header('Location: /admin/login.php');
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  if ($wants_json) respond_json(['ok'=>false,'error'=>'Method not allowed.']);
  http_response_code(405);
  exit('Method not allowed.');
}

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  if ($wants_json) respond_json(['ok'=>false,'error'=>'CSRF validation failed.']);
  http_response_code(403);
  exit('CSRF failed.');
}

$content = (string)($_POST['content'] ?? $_POST['privacy'] ?? '');
$content = trim($content);

// Choose/write target file
$ROOT        = project_root();
$PAGES_DIR   = $ROOT . '/pages';
$TARGET_FILE = $PAGES_DIR . '/privacy.html';

if (!is_dir($PAGES_DIR)) @mkdir($PAGES_DIR, 0775, true);

// Basic HTML skeleton if empty; otherwise store as-is (assume HTML)
if ($content === '') {
  $content = "<h1>Privacy Policy</h1>\n<p>Last updated: ".date('Y-m-d')."</p>\n<p>(No content provided.)</p>\n";
}

$ok = @file_put_contents($TARGET_FILE, $content);
if ($ok === false) {
  if ($wants_json) respond_json(['ok'=>false,'error'=>'Failed to write privacy file.']);
  header('Location: /admin/privacy.php?ok=0');
  exit;
}
@chmod($TARGET_FILE, 0664);

if ($wants_json) {
  respond_json(['ok'=>true, 'file'=>str_replace($ROOT, '', $TARGET_FILE)]);
}

// Non-JSON: go back to the editor
header('Location: /admin/privacy.php?ok=1');
exit;
