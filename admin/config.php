<?php
// Core paths
$DATA_DIR = __DIR__ . '/../eggs/data';
$UPLOAD_DIR = __DIR__ . '/../assets/uploads';
$BASE_UPLOAD_URL = '/assets/uploads';

// Config files
$SITE_FILE = __DIR__ . '/site.json';
$PWD_FILE  = __DIR__ . '/password.json';

if(!is_dir($DATA_DIR))  @mkdir($DATA_DIR, 0775, true);
if(!is_dir($UPLOAD_DIR))@mkdir($UPLOAD_DIR, 0775, true);

session_start();

// ---- Load site config ----
$SITE_NAME   = 'sucky.life';
$SITE_DOMAIN = 'sucky.life';
$NEEDS_SETUP = false;

if(file_exists($SITE_FILE)){
  $site = json_decode(file_get_contents($SITE_FILE), true);
  if(is_array($site)){
    $SITE_NAME   = $site['site_name'] ?? $SITE_NAME;
    $SITE_DOMAIN = $site['domain']    ?? $SITE_DOMAIN;
    if(!empty($site['first_run'])) $NEEDS_SETUP = true;
  } else {
    $NEEDS_SETUP = true;
  }
} else {
  $NEEDS_SETUP = true;
}

// ---- Load password ----
$ADMIN_PASSWORD_HASH = null;
if(file_exists($PWD_FILE)){
  $pwd = json_decode(file_get_contents($PWD_FILE), true);
  if(is_array($pwd) && !empty($pwd['hash'])){
    $ADMIN_PASSWORD_HASH = $pwd['hash'];
  } else {
    $NEEDS_SETUP = true;
  }
} else {
  $NEEDS_SETUP = true;
}