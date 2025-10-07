<?php
require __DIR__.'/util.php';
if(empty($_SESSION['authed'])){ http_response_code(403); exit('Forbidden'); }
$slug = slugify($_POST['slug'] ?? ''); if(!$slug) exit('Bad slug');
$f = $DATA_DIR.'/'.$slug.'.json';
if(file_exists($f)) unlink($f);
echo 'OK';
