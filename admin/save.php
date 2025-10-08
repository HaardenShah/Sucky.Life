<?php
/**
 * admin/save.php — Persist an egg + handle uploads + position updates
 * - Requires admin session
 * - CSRF protected (skipped for quiet autosave only)
 * - Fields: title, caption, alt, body, draft, published_at, pos_left, pos_top
 * - Media: image_url|image_file, audio_url|audio_file, video_url|video_file
 * - Image pipeline: responsive WebP variants (-640/-1024/-1600) if GD+WebP
 * - Video: poster JPG via ffmpeg (if available)
 * - Audio: loudness normalization via ffmpeg (if available)
 * - Response: JSON { ok: true, slug }
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/config.php';
require __DIR__ . '/util.php'; // for load_egg(), etc. (slugify fallback below)

if (empty($_SESSION['authed'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'forbidden']);
  exit;
}

/* ---------- CSRF (allow autosave to skip strict check) ---------- */
$isAutosave = isset($_POST['autosave']);
if (!$isAutosave) { require_csrf(); }

/* ---------- Inputs ---------- */
$slug   = trim($_POST['slug'] ?? '');
$title  = trim($_POST['title'] ?? '');
$caption= trim($_POST['caption'] ?? '');
$alt    = trim($_POST['alt'] ?? '');
$body   = $_POST['body'] ?? '';
$draft  = isset($_POST['draft']);
$published_at = trim($_POST['published_at'] ?? '');

$pos_left = isset($_POST['pos_left']) ? floatval($_POST['pos_left']) : null;
$pos_top  = isset($_POST['pos_top'])  ? floatval($_POST['pos_top'])  : null;

$image_url = trim($_POST['image_url'] ?? '');
$audio_url = trim($_POST['audio_url'] ?? '');
$video_url = trim($_POST['video_url'] ?? '');

/* ---------- Paths ---------- */
$DATA_DIR = __DIR__ . '/../eggs/data';
$UP_DIR   = __DIR__ . '/../assets/uploads';
@mkdir($DATA_DIR, 0775, true);
@mkdir($UP_DIR,   0775, true);

/* ---------- Helpers ---------- */
if (!function_exists('slugify')) {
  function slugify($s) {
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('~[^a-zA-Z0-9]+~', '-', $s);
    $s = strtolower(trim($s, '-'));
    return $s ?: bin2hex(random_bytes(4));
  }
}
function web_path($abs){ $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'); return str_replace($root, '', $abs); }

function move_upload($field, array $allowedTypes) {
  global $UP_DIR;
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $tmp  = $_FILES[$field]['tmp_name'];
  $name = $_FILES[$field]['name'];
  $mime = mime_content_type($tmp) ?: '';
  $type = strtok($mime, '/'); // image|audio|video|...
  if (!in_array($type, $allowedTypes, true)) return null;

  $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $base = slugify(pathinfo($name, PATHINFO_FILENAME));
  $safe = $base . '-' . substr(bin2hex(random_bytes(4)),0,6) . '.' . $ext;
  $dest = $UP_DIR . '/' . $safe;

  if (!@move_uploaded_file($tmp, $dest)) return null;
  return '/assets/uploads/' . $safe;
}

function has_gd_webp(){ return function_exists('imagewebp') && function_exists('imagecreatetruecolor'); }
function make_responsive_webp_variants($imageWebPath){
  if (!has_gd_webp()) return [];
  $abs = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $imageWebPath;
  if (!is_file($abs)) return [];
  $info = @getimagesize($abs); if (!$info) return [];
  [$w,$h,$type] = $info;

  switch ($type) {
    case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($abs); break;
    case IMAGETYPE_PNG:  $src = @imagecreatefrompng($abs); break;
    case IMAGETYPE_WEBP: $src = @imagecreatefromwebp($abs); break;
    default: $src = null;
  }
  if (!$src) return [];
  $sizes = [640,1024,1600]; $out = []; $pi = pathinfo($abs);
  foreach($sizes as $s){
    $ratio=$h/$w; $nw=$s; $nh=(int)round($s*$ratio);
    $dst=imagecreatetruecolor($nw,$nh);
    if ($type===IMAGETYPE_PNG){ imagealphablending($dst,false); imagesavealpha($dst,true); }
    imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
    $webpAbs=$pi['dirname'].'/'.$pi['filename'].'-'.$s.'.webp';
    @imagewebp($dst,$webpAbs,80); imagedestroy($dst);
    if (is_file($webpAbs)) $out[] = web_path($webpAbs);
  }
  imagedestroy($src);
  return $out;
}

function has_ffmpeg(){ $o=@shell_exec('which ffmpeg 2>/dev/null'); return !empty($o); }
function ffmpeg_poster($videoWebPath){
  if (!has_ffmpeg()) return null;
  $abs = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $videoWebPath;
  if (!is_file($abs)) return null;
  $pi  = pathinfo($abs);
  $out = $pi['dirname'].'/'.$pi['filename'].'-poster.jpg';
  @shell_exec('ffmpeg -y -i '.escapeshellarg($abs).' -ss 00:00:01.000 -vframes 1 '.escapeshellarg($out).' 2>/dev/null');
  return is_file($out) ? web_path($out) : null;
}
function ffmpeg_loudnorm($audioWebPath){
  if (!has_ffmpeg()) return null;
  $abs = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $audioWebPath;
  if (!is_file($abs)) return null;
  $pi  = pathinfo($abs);
  $out = $pi['dirname'].'/'.$pi['filename'].'-norm.mp3';
  @shell_exec('ffmpeg -y -i '.escapeshellarg($abs).' -filter:a loudnorm=I=-14:TP=-1.5:LRA=11 -ar 44100 '.escapeshellarg($out).' 2>/dev/null');
  return is_file($out) ? web_path($out) : null;
}

/* ---------- Slug ---------- */
if ($slug === '' && $title !== '') $slug = slugify($title);
if ($slug === '')                 $slug = bin2hex(random_bytes(4));

/* ---------- Move uploads (if any) ---------- */
$upImage = move_upload('image_file', ['image']);
$upAudio = move_upload('audio_file', ['audio']);
$upVideo = move_upload('video_file', ['video']);

if ($upImage) $image_url = $upImage;
if ($upAudio) $audio_url = $upAudio;
if ($upVideo) $video_url = $upVideo;

/* ---------- Media post-processing ---------- */
$variants = [];
if ($image_url) { $variants = make_responsive_webp_variants($image_url); }
$poster = '';
if ($video_url) { $poster = ffmpeg_poster($video_url) ?: ''; }
if ($audio_url) { $norm = ffmpeg_loudnorm($audio_url); if ($norm) $audio_url = $norm; }

/* ---------- Load existing egg (if any) ---------- */
$FILE = $DATA_DIR . '/' . $slug . '.json';
$egg  = is_file($FILE) ? (json_decode(@file_get_contents($FILE), true) ?: []) : [];

/* ---------- Apply updates ---------- */
if ($title !== '')   $egg['title'] = $title;
if ($caption !== '') $egg['caption'] = $caption;
if ($alt !== '')     $egg['alt'] = $alt;
if ($body !== '')    $egg['body'] = $body;

if ($image_url !== '') $egg['image'] = $image_url;
if ($video_url !== '') $egg['video'] = $video_url;
if ($poster !== '')    $egg['poster'] = $poster;
if ($audio_url !== '') $egg['audio']  = $audio_url;

if (!empty($variants)) $egg['image_variants'] = $variants;

if ($published_at !== '') $egg['published_at'] = $published_at;
$egg['draft'] = $draft;

/* Position updates from Visual Editor */
if ($pos_left !== null) $egg['pos_left'] = $pos_left;
if ($pos_top  !== null) $egg['pos_top']  = $pos_top;

/* ---------- Persist ---------- */
if (!@file_put_contents($FILE, json_encode($egg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
  echo json_encode(['ok'=>false, 'error'=>'write_failed']);
  exit;
}

/* ---------- Respond ---------- */
echo json_encode(['ok' => true, 'slug' => $slug]);