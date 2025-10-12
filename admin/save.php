<?php
declare(strict_types=1);

/**
 * admin/save.php — FILE STORAGE VERSION
 * Creates/updates an egg JSON file. Accepts URL fields and optional uploads.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('default_charset','UTF-8');

require __DIR__ . '/config.php';
require __DIR__ . '/util.php';

if (empty($_SESSION['authed'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Not authenticated.']);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed.']);
  exit;
}
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'CSRF validation failed.']);
  exit;
}

/* Upload paths */
$ROOT        = project_root();
$UPLOADS_DIR = $ROOT . '/assets/uploads';
$UPLOADS_URL = '/assets/uploads';
ensure_dir($UPLOADS_DIR);

/* Inputs */
$incomingSlug = trim((string)($_POST['slug'] ?? ''));
$title        = trim((string)($_POST['title'] ?? ''));
$caption      = trim((string)($_POST['caption'] ?? ''));
$alt          = trim((string)($_POST['alt'] ?? ''));
$body         = (string)($_POST['body'] ?? '');
$draft        = !empty($_POST['draft']);
$published_at = trim((string)($_POST['published_at'] ?? ''));

$image_url_in = trim((string)($_POST['image_url'] ?? ''));
$audio_url_in = trim((string)($_POST['audio_url'] ?? ''));
$video_url_in = trim((string)($_POST['video_url'] ?? ''));

/* Handle uploads (override URL fields if files present) */
$image_url = handle_upload('image','image_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $image_url_in;
$audio_url = handle_upload('audio','audio_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $audio_url_in;
$video_url = handle_upload('video','video_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $video_url_in;

/* Compose egg */
$egg = [
  'slug'         => $incomingSlug,
  'title'        => $title,
  'caption'      => $caption,
  'alt'          => $alt,
  'body'         => $body,
  'image'        => $image_url,
  'audio'        => $audio_url,
  'video'        => $video_url,
  'draft'        => $draft ? 1 : 0,
];
if ($published_at !== '') $egg['published_at'] = $published_at;

/* Save */
try {
  $saved = save_egg($egg);
  echo json_encode(['ok'=>true,'slug'=>$saved['slug'],'data'=>$saved], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
