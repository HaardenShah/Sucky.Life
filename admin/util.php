<?php
function slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9]+~','-',$s);
  $s = trim($s,'-');
  return $s ?: bin2hex(random_bytes(4));
}
function data_dir(): string { return __DIR__ . '/../eggs/data'; }
function list_eggs(): array {
  $dir = data_dir(); if(!is_dir($dir)) return [];
  $out = [];
  foreach (glob($dir.'/*.json') as $f) { $out[] = basename($f, '.json'); }
  sort($out, SORT_NATURAL|SORT_FLAG_CASE);
  return $out;
}
function load_egg(string $slug): ?array {
  $f = data_dir().'/'.$slug.'.json';
  if(!is_file($f)) return null;
  return json_decode(@file_get_contents($f), true) ?: [];
}