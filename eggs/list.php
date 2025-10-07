<?php
header('Content-Type: application/json');
$dir = __DIR__ . '/data';
$out = [];
foreach (glob($dir.'/*.json') as $f) {
  $slug = basename($f, '.json');
  $data = json_decode(file_get_contents($f), true) ?: [];
  $out[] = [
    'slug' => $slug,
    'title' => $data['title'] ?? '',
    'caption' => $data['caption'] ?? '',
    // positions stored as percentages of viewport (vw/vh)
    'pos_left' => isset($data['pos_left']) ? floatval($data['pos_left']) : null,
    'pos_top'  => isset($data['pos_top'])  ? floatval($data['pos_top'])  : null,
  ];
}
echo json_encode($out, JSON_UNESCAPED_SLASHES);