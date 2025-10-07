<?php
require __DIR__.'/config.php';

function slugify($s){
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9]+~','-', $s);
  return trim($s,'-');
}
function load_egg($slug){
  global $DATA_DIR; $f = "$DATA_DIR/$slug.json"; if(!file_exists($f)) return null;
  return json_decode(file_get_contents($f), true) ?: [];
}
function save_egg($slug, $arr){
  global $DATA_DIR; $f = "$DATA_DIR/$slug.json"; return (bool)file_put_contents($f, json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}
function list_eggs(){
  global $DATA_DIR; $items=[]; foreach(glob($DATA_DIR.'/*.json') as $f){ $items[] = basename($f,'.json'); }
  sort($items); return $items;
}
// Convert any uploaded image to WebP
function convert_to_webp($srcPath, $destPath, $quality=82){
  $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
  if(!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return false;
  if($ext==='webp') { return copy($srcPath, $destPath); }
  if(!function_exists('imagewebp')) return false; // GD must support WebP

  switch($ext){
    case 'jpg': case 'jpeg': $img = @imagecreatefromjpeg($srcPath); break;
    case 'png': $img = @imagecreatefrompng($srcPath); imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); break;
    case 'gif': $img = @imagecreatefromgif($srcPath); imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); break;
    default: return false;
  }
  if(!$img) return false;
  $ok = imagewebp($img, $destPath, $quality);
  imagedestroy($img);
  return $ok;
}
