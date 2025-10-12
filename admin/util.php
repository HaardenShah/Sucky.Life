<?php
declare(strict_types=1);

/**
 * admin/util.php — FILE STORAGE VERSION
 * - Provides CSRF helpers (only if not already defined in config.php)
 * - Provides file-backed functions used across admin + public:
 *     list_eggs(), load_egg(), save_egg(), rename_egg(), delete_egg()
 * - JSON lives at /eggs/data/{slug}.json
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* -------- CSRF (guarded so we don't redeclare if config.php defines them) -------- */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
  }
}
if (!function_exists('csrf_input')) {
  function csrf_input(): string {
    $v = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf" value="'.$v.'">';
  }
}

/* -------- Paths -------- */
function project_root(): string {
  return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}
function eggs_data_dir(): string {
  $dir = project_root() . '/eggs/data';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return $dir;
}

/* -------- Slug utils -------- */
function slugify(string $s): string {
  $s = trim(mb_strtolower($s, 'UTF-8'));
  $s = preg_replace('~[^\\pL\\pN]+~u', '-', $s) ?? '';
  $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
  $s = preg_replace('~[^-a-z0-9]+~', '', $s) ?? '';
  $s = trim($s, '-');
  return $s !== '' ? $s : 'egg';
}
function unique_slug_file(string $base): string {
  $base = slugify($base);
  $dir  = eggs_data_dir();
  $i = 0;
  do {
    $candidate = $base . ($i ? "-$i" : '');
    if (!file_exists("$dir/$candidate.json")) return $candidate;
    $i++;
  } while (true);
}

/* -------- File-backed API used by templates and endpoints -------- */

/** @return string[] slugs (sorted by modified desc) */
function list_eggs(): array {
  $dir = eggs_data_dir();
  $files = glob($dir . '/*.json') ?: [];
  // sort by mtime desc
  usort($files, fn($a,$b)=> filemtime($b) <=> filemtime($a));
  return array_map(function($p){
    return basename($p, '.json');
  }, $files);
}

/** @return array|null */
function load_egg(string $slug): ?array {
  $slug = slugify($slug);
  $path = eggs_data_dir() . "/$slug.json";
  if (!is_file($path)) return null;
  $json = file_get_contents($path);
  if ($json === false) return null;
  $data = json_decode($json, true);
  return is_array($data) ? $data : null;
}

/** @return array saved egg */
function save_egg(array $egg): array {
  $dir = eggs_data_dir();
  @mkdir($dir, 0775, true);

  // Determine slug
  $incoming = trim((string)($egg['slug'] ?? ''));
  if ($incoming === '') {
    $base = trim((string)($egg['title'] ?? '')) ?: ('egg-' . date('Ymd-His'));
    $slug = unique_slug_file($base);
  } else {
    $slug = slugify($incoming);
    // If new and collides, suffix it
    if (!is_file("$dir/$slug.json")) {
      // fine
    }
  }

  // Compose final payload
  $payload = [
    'slug'   => $slug,
    'title'  => (string)($egg['title'] ?? $slug),
    'caption'=> (string)($egg['caption'] ?? ''),
    'alt'    => (string)($egg['alt'] ?? ''),
    'body'   => (string)($egg['body'] ?? ''),   // HTML allowed; escape on render
    'image'  => (string)($egg['image'] ?? ''),
    'audio'  => (string)($egg['audio'] ?? ''),
    'video'  => (string)($egg['video'] ?? ''),
    'draft'  => !empty($egg['draft']) ? 1 : 0,
  ];
  if (!empty($egg['published_at'])) $payload['published_at'] = (string)$egg['published_at'];

  $ok = @file_put_contents("$dir/$slug.json", json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  if ($ok === false) {
    throw new RuntimeException('Failed to write egg JSON.');
  }
  @chmod("$dir/$slug.json", 0664);
  return $payload;
}

/** Rename slug file; returns final new slug (may be suffixed) or false */
function rename_egg(string $slug, string $new): string|false {
  $dir = eggs_data_dir();
  $slug = slugify($slug);
  $new  = slugify($new);
  $src = "$dir/$slug.json";
  if (!is_file($src)) return false;

  // Avoid collisions
  $final = $new;
  $i = 0;
  while (is_file("$dir/$final.json")) {
    $i++; $final = $new . "-$i";
  }

  if (!@rename($src, "$dir/$final.json")) return false;

  // Update slug inside JSON
  $data = load_egg($final) ?? [];
  $data['slug'] = $final;
  @file_put_contents("$dir/$final.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

  return $final;
}

/** Delete by slug */
function delete_egg(string $slug): bool {
  $path = eggs_data_dir() . '/' . slugify($slug) . '.json';
  return is_file($path) ? @unlink($path) : false;
}

/* -------- Upload helpers used by save.php (kept here for reuse) -------- */
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
