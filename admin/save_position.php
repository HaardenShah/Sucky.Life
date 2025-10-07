<?php
require __DIR__.'/util.php';
header('Content-Type: application/json');
if(empty($_SESSION['authed'])){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$slug = slugify($_POST['slug'] ?? '');
$left = isset($_POST['pos_left']) ? floatval($_POST['pos_left']) : null;
$top  = isset($_POST['pos_top'])  ? floatval($_POST['pos_top'])  : null;

if(!$slug || !is_finite($left) || !is_finite($top)){
  echo json_encode(['ok'=>false, 'error'=>'Bad parameters']); exit;
}

$egg = load_egg($slug) ?: [];
$egg['pos_left'] = $left;
$egg['pos_top']  = $top;

if(save_egg($slug, $egg)){
  echo json_encode(['ok'=>true]);
} else {
  echo json_encode(['ok'=>false, 'error'=>'Write failed']);
}
