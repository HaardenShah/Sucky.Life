<?php
require __DIR__.'/config.php';
require __DIR__.'/util.php';

header('Content-Type: application/json');

if(empty($_SESSION['authed'])){
  http_response_code(403);
  echo json_encode(['items'=>[], 'error'=>'Forbidden']); exit;
}

$items = [];
$allowed_img = ['jpg','jpeg','png','gif','webp'];
$allowed_audio = ['mp3','m4a','aac','wav','ogg','oga','webm'];

$dir = $UPLOAD_DIR;
$base = $BASE_UPLOAD_URL;

if(is_dir($dir)){
  foreach (scandir($dir) as $f){
    if($f === '.' || $f === '..') continue;
    $path = $dir . '/' . $f;
    if(!is_file($path)) continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $type = in_array($ext, $allowed_img) ? 'image' : (in_array($ext, $allowed_audio) ? 'audio' : null);
    if(!$type) continue;

    $url = rtrim($base,'/') . '/' . rawurlencode($f);
    $st = @stat($path);
    $items[] = [
      'name'  => $f,
      'url'   => $url,
      'type'  => $type,
      'size'  => $st ? (int)$st['size'] : null,
      'mtime' => $st ? (int)$st['mtime'] : null
    ];
  }
}

// sort newest first by default
usort($items, function($a,$b){ return ($b['mtime']??0) <=> ($a['mtime']??0); });

echo json_encode(['items'=>$items], JSON_UNESCAPED_SLASHES);