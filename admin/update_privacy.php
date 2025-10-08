<?php
require __DIR__.'/config.php';
if (empty($_SESSION['authed'])) { http_response_code(403); exit('Forbidden'); }
require_csrf();

$SITE_JSON = __DIR__.'/site.json';
$site = file_exists($SITE_JSON) ? (json_decode(file_get_contents($SITE_JSON), true) ?: []) : [];

$gate_on = isset($_POST['gate']) ? true : false;
$pw      = trim($_POST['pw'] ?? '');

$site['visitor_gate_on'] = $gate_on;
if ($pw !== '') { $site['visitor_password_hash'] = password_hash($pw, PASSWORD_DEFAULT); }

file_put_contents($SITE_JSON, json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
header('Location: /admin/privacy.php');