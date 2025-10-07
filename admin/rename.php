<?php
require __DIR__.'/util.php';
if(empty($_SESSION['authed'])){ http_response_code(403); exit('Forbidden'); }
$slug = slugify($_POST['slug'] ?? '');
$new = slugify($_POST['new_slug'] ?? '');
if(!$slug || !$new) exit('Bad slug');
if($slug === $new) exit('No change');
$src = $DATA_DIR.'/'.$slug.'.json';
$dst = $DATA_DIR.'/'.$new.'.json';
if(!file_exists($src)) exit('Not found');
if(file_exists($dst)) exit('Exists');
rename($src, $dst);
echo 'OK';
