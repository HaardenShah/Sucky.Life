<?php
require __DIR__.'/config.php';
if (empty($_SESSION['authed'])) { http_response_code(403); exit('Forbidden'); }

$SITE_JSON = __DIR__.'/site.json';
$site = file_exists($SITE_JSON) ? (json_decode(file_get_contents($SITE_JSON), true) ?: []) : [];

$gate_on = isset($_POST['gate']) ? true : false;
$pw      = trim($_POST['pw'] ?? '');

$site['visitor_gate_on'] = $gate_on;

// If a new password was provided, (re)hash it
if ($pw !== '') {
  $site['visitor_password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
  // Optional: invalidate current visitor sessions by changing a version token
  // $site['visitor_gate_version'] = time();
}

file_put_contents($SITE_JSON, json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
header('Location: /admin/privacy.php');