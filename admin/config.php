<?php
// Paths
$DATA_DIR = __DIR__ . '/../eggs/data';
$UPLOAD_DIR = __DIR__ . '/../assets/uploads';
$BASE_UPLOAD_URL = '/assets/uploads';
$PWD_FILE = __DIR__ . '/password.json';

if(!is_dir($DATA_DIR)) mkdir($DATA_DIR, 0775, true);
if(!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0775, true);

// Initialize password store if missing
if(!file_exists($PWD_FILE)){
  $default = password_hash('sucky-life', PASSWORD_DEFAULT);
  $payload = ['hash' => $default, 'first_run' => true, 'updated_at' => time()];
  file_put_contents($PWD_FILE, json_encode($payload));
}

// Load password store
$pwd = json_decode(file_get_contents($PWD_FILE), true);
$ADMIN_PASSWORD_HASH = $pwd['hash'] ?? password_hash('sucky-life', PASSWORD_DEFAULT);
$FIRST_RUN = !empty($pwd['first_run']);

session_start();
