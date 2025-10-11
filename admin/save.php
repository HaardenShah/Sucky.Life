<?php
declare(strict_types=1);

/**
 * admin/save.php (SQLite-backed)
 * Create/update an egg. Accepts URL fields and optional uploads.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('default_charset','UTF-8');

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

/* ---------- Upload helpers (same behavior as file version) ---------- */

function ensure_dir(string $path): void {
  if (!is_dir($path)) @mkdir($path, 0775, true);
}
function unique_filename(string $dir, string $base, string $ext=''): string {
  $base = preg_replace('~[^a-zA-Z0-9_-]+~', '-', $base) ?: 'file';
  $ext  = $ext ? ('.'.ltrim($ext,'.')) : '';
  $i=0; do {
    $p = $dir.'/'.$base.($i?'-'.$i:'').$ext;
    $i++;
  } while (file_exists($p));
  return $p;
}
function ext_from_mime(string $mime, string $fallback='bin'): string {
  static $map = [
    'image/webp'=>'webp','image/png'=>'png','image/jpeg'=>'jpg','image/jpg'=>'jpg','image/gif'=>'gif','image/svg+xml'=>'svg',
    'audio/mpeg'=>'mp3','audio/mp4'=>'m4a','audio/wav'=>'wav','audio/ogg'=>'ogg','audio/webm'=>'weba',
    'video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogv',
  ];
  return $map[$mime] ?? $fallback;
}
function handle_upload(string $type, string $field, string $uploadsDir, string $uploadsUrl): ?string {
  if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$field];
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return null;
  if (!is_uploaded_file($f['tmp_name'])) return null;

  $mime = mime_content_type($f['tmp_name']) ?: '';
  $ok = match($type){
    'image' => str_starts_with($mime,'image/'),
    'audio' => str_starts_with($mime,'audio/'),
    'video' => str_starts_with($mime,'video/'),
    default => false
  };
  if (!$ok) return null;

  $orig = (string)($f['name'] ?? '');
  $base = pathinfo($orig, PATHINFO_FILENAME) ?: $type.'-'.date('Ymd-His');
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION) ?: ext_from_mime($mime, $type==='image'?'webp':($type==='audio'?'mp3':'mp4')));

  ensure_dir($uploadsDir);
  $destAbs = unique_filename($uploadsDir, $base, $ext);
  if (!@move_uploaded_file($f['tmp_name'], $destAbs)) return null;
  @chmod($destAbs, 0644);

  $rel = ltrim(str_replace('\\','/', substr($destAbs, strlen($uploadsDir))), '/');
  return rtrim($uploadsUrl,'/').'/'.$rel;
}

/* ---------- Paths for uploads ---------- */
$ROOT        = realpath(__DIR__.'/..') ?: dirname(__DIR__);
$UPLOADS_DIR = $ROOT . '/assets/uploads';
$UPLOADS_URL = '/assets/uploads';
ensure_dir($UPLOADS_DIR);

/* ---------- Inputs ---------- */
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

/* ---------- File uploads (override if present) ---------- */
$image_url = handle_upload('image','image_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $image_url_in;
$audio_url = handle_upload('audio','audio_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $audio_url_in;
$video_url = handle_upload('video','video_file',$UPLOADS_DIR,$UPLOADS_URL) ?? $video_url_in;

/* ---------- Compose egg ---------- */
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

/* ---------- Persist via repo ---------- */
try {
  $saved = repo()->save($egg);
  echo json_encode(['ok'=>true,'slug'=>$saved['slug'],'data'=>$saved], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}