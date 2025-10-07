<?php
require __DIR__.'/util.php';
if(empty($_SESSION['authed'])){ http_response_code(403); exit('Forbidden'); }

$slug = slugify($_POST['slug'] ?? '');
if(!$slug) die('Invalid slug');
$payload = load_egg($slug) ?: [ 'title'=>'', 'alt'=>'', 'caption'=>'', 'body'=>'', 'image'=>'' ];

$payload['title'] = trim($_POST['title'] ?? '');
$payload['alt'] = trim($_POST['alt'] ?? '');
$payload['caption'] = trim($_POST['caption'] ?? '');
$payload['body'] = trim($_POST['body'] ?? '');

// Image URL takes priority if given
if(!empty($_POST['image_url'])){
  $payload['image'] = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
}

// File upload → WebP
if(!empty($_FILES['image']['name']) && $_FILES['image']['error']===UPLOAD_ERR_OK){
  $tmp = $_FILES['image']['tmp_name'];
  $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
  if(!in_array($ext, ['jpg','jpeg','png','gif','webp'])) die('Unsupported image type');
  $fname = $slug.'-'.time().'.webp';
  $dest = $UPLOAD_DIR.'/'.$fname;
  if(convert_to_webp($tmp, $dest, 82)){
    $payload['image'] = $BASE_UPLOAD_URL.'/'.$fname;
  } else {
    $fname = $slug.'-'.time().'.'.$ext; $dest = $UPLOAD_DIR.'/'.$fname;
    move_uploaded_file($tmp, $dest);
    $payload['image'] = $BASE_UPLOAD_URL.'/'.$fname;
  }
}

save_egg($slug, $payload);
header('Location: index.php?slug='.urlencode($slug));
