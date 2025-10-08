<?php
/**
 * admin/config.php — shared config, sessions, site settings
 * - Loads site.json, visitor gate flags
 * - Starts hardened session
 * - Provides CSRF helpers + simple rate limiting
 */
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset','UTF-8');

/* Session hardening */
@ini_set('session.use_strict_mode', 1);
@ini_set('session.cookie_httponly', 1);
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') { @ini_set('session.cookie_secure', 1); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* Site settings */
$SITE_JSON   = __DIR__ . '/site.json';
$PWD_JSON    = __DIR__ . '/password.json';
$SITE_NAME   = 'sucky.life';
$SITE_DOMAIN = '';
$NEEDS_SETUP = !file_exists($SITE_JSON) || !file_exists($PWD_JSON);

$GATE_ON   = false;
$GATE_HASH = '';

if (!$NEEDS_SETUP) {
  $site = json_decode(@file_get_contents($SITE_JSON), true) ?: [];
  $SITE_NAME   = $site['site_name'] ?? $SITE_NAME;
  $SITE_DOMAIN = $site['domain']    ?? $SITE_DOMAIN;
  $GATE_ON     = (bool)($site['visitor_gate_on'] ?? false);
  $GATE_HASH   = $site['visitor_password_hash'] ?? '';
}

/* CSRF */
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES).'">'; }
function require_csrf(): void { $ok = !empty($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']); if (!$ok) { http_response_code(400); exit('Bad CSRF'); } }

/* Simple rate limiting */
function rate_limit(string $key, int $limit, int $windowSec): bool {
  $now = time();
  $_SESSION['rl'] = $_SESSION['rl'] ?? [];
  $bucket = $_SESSION['rl'][$key] ?? ['t'=>$now,'n'=>0];
  if (($now - $bucket['t']) > $windowSec) { $bucket = ['t'=>$now,'n'=>0]; }
  $bucket['n']++;
  $_SESSION['rl'][$key] = $bucket;
  return $bucket['n'] <= $limit;
}