<?php
/**
 * eggs/list.php — Robust public list of eggs
 *
 * What this does:
 * - Searches multiple likely data directories for *.json egg files.
 * - Coerces pos_left / pos_top to numbers (handles "34.2" strings).
 * - Sanitizes positions into 0..100 range.
 * - Includes drafts for now (so you can verify hotspots). Set HIDE_DRAFTS = true to hide again.
 * - Skips invalid / incomplete files gracefully.
 *
 * Output: JSON array like:
 * [
 *   { "slug":"my-egg", "title":"...", "caption":"...", "pos_left":34.2, "pos_top":57.8 }
 * ]
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/../admin/config.php';
require __DIR__ . '/../admin/util.php';

const HIDE_DRAFTS = false; // <-- flip to true later if you want to hide drafts from public

// Candidate directories where eggs might be stored (first ones that exist will be scanned)
$candidates = [
  __DIR__ . '/data',                 // typical: /eggs/data
  __DIR__ . '/../eggs/data',         // safety if list.php moved
  __DIR__ . '/../data',              // some repos use /data at root
  __DIR__ . '/../admin/eggs/data',   // fallback if admin namespaced data
];

// De-duplicate and keep only existing directories
$dirs = [];
foreach ($candidates as $d) {
  $real = realpath($d);
  if ($real && is_dir($real) && !in_array($real, $dirs, true)) {
    $dirs[] = $real;
  }
}

$out = [];
$authed = !empty($_SESSION['authed']);

foreach ($dirs as $DIR) {
  foreach (glob($DIR . '/*.json') as $file) {
    $json = json_decode(@file_get_contents($file), true);
    if (!is_array($json)) continue;

    // slug: prefer JSON slug, else filename without extension
    $slug = trim((string)($json['slug'] ?? ''));
    if ($slug === '') {
      $slug = basename($file, '.json');
    }

    // Draft visibility
    $isDraft = !empty($json['draft']);
    if (HIDE_DRAFTS && $isDraft && !$authed) {
      continue;
    }

    // Must have some form of position
    if (!isset($json['pos_left']) || !isset($json['pos_top'])) {
      continue;
    }

    // Coerce positions to numbers
    $left = (float)$json['pos_left'];
    $top  = (float)$json['pos_top'];

    // Sanitize into [0..100] (viewport units)
    if (!is_finite($left) || !is_finite($top)) continue;
    if ($left < 0) $left = 0; if ($left > 100) $left = 100;
    if ($top  < 0) $top  = 0; if ($top  > 100) $top  = 100;

    $out[] = [
      'slug'     => $slug,
      'title'    => isset($json['title'])   ? (string)$json['title']   : '',
      'caption'  => isset($json['caption']) ? (string)$json['caption'] : '',
      'pos_left' => $left,
      'pos_top'  => $top,
    ];
  }
}

// If nothing was found anywhere, return an empty array (client code shows "0 eggs loaded")
echo json_encode(array_values($out), JSON_UNESCAPED_SLASHES);