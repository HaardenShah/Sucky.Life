<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/../admin/config.php';
if ($NEEDS_SETUP) { http_response_code(307); header('Location: /admin/setup.php'); echo json_encode(['error'=>'setup_required']); exit; }
if (!empty($GATE_ON) && $GATE_ON === true && empty($_SESSION['visitor_ok'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$DATA_DIR = __DIR__ . '/data';
$out = [];

if (is_dir($DATA_DIR)) {
  foreach (glob($DATA_DIR . '/*.json') as $file) {
    $slug = basename($file, '.json');
    $row  = json_decode(@file_get_contents($file), true) ?: [];
    $out[] = [
      'slug'     => $slug,
      'title'    => $row['title']   ?? $slug,
      'caption'  => $row['caption'] ?? '',
      'pos_left' => isset($row['pos_left']) ? floatval($row['pos_left']) : null,
      'pos_top'  => isset($row['pos_top'])  ? floatval($row['pos_top'])  : null,
      'draft'    => !empty($row['draft'])
    ];
  }
  usort($out, fn($a,$b)=> (strcasecmp($a['title']??'', $b['title']??'')) ?: strcasecmp($a['slug'],$b['slug']));
}
echo json_encode($out, JSON_UNESCAPED_SLASHES);
