<?php
/**
 * admin/config.php — shared config, hardened sessions, paths, CSRF, rate limiting
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

/* ---------- Absolute paths (no surprises with symlinks) ---------- */
$ADMIN_DIR = __DIR__;
$ROOT_DIR  = dirname($ADMIN_DIR);
$SITE_JSON = $ADMIN_DIR . '/site.json';
$PWD_JSON  = $ADMIN_DIR . '/password.json';

/* ---------- Session hardening ---------- */
if (!headers_sent()) {
  @ini_set('session.use_strict_mode', 1);
  @ini_set('session.cookie_httponly', 1);
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    @ini_set('session.cookie_secure', 1);
  }
}
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ---------- Site settings + first-run flag ---------- */
$SITE_NAME   = 'sucky.life';
$SITE_DOMAIN = '';
$NEEDS_SETUP = !is_file($SITE_JSON) || !is_file($PWD_JSON);

$GATE_ON   = false;
$GATE_HASH = '';

if (!$NEEDS_SETUP) {
  $site = json_decode(@file_get_contents($SITE_JSON), true) ?: [];
  $SITE_NAME   = $site['site_name'] ?? $SITE_NAME;
  $SITE_DOMAIN = $site['domain']    ?? $SITE_DOMAIN;
  $GATE_ON     = (bool)($site['visitor_gate_on'] ?? false);
  $GATE_HASH   = $site['visitor_password_hash'] ?? '';
}

/* ---------- CSRF helpers ---------- 
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_input(): string {
  return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES).'">';
}
function require_csrf(): void {
  $ok = !empty($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
  if (!$ok) { http_response_code(400); exit('Bad CSRF'); }
}
*/

/* ---------- Simple rate limiting (per session) ---------- */
function rate_limit(string $key, int $limit, int $windowSec): bool {
  $now = time();
  $_SESSION['rl'] = $_SESSION['rl'] ?? [];
  $bucket = $_SESSION['rl'][$key] ?? ['t'=>$now,'n'=>0];
  if (($now - $bucket['t']) > $windowSec) { $bucket = ['t'=>$now,'n'=>0]; }
  $bucket['n']++;
  $_SESSION['rl'][$key] = $bucket;
  return $bucket['n'] <= $limit;
}

/* ---------- Atomic JSON write helper ---------- */
function write_json_atomic(string $path, array $data): bool {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  if (!is_writable($dir)) return false;
  $tmp = tempnam($dir, 'tmp');
  if ($tmp === false) return false;
  $bytes = @file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
  if ($bytes === false) { @unlink($tmp); return false; }
  $ok = @rename($tmp, $path);
  if (!$ok) { @unlink($tmp); return false; }
  @chmod($path, 0644);
  return true;
}