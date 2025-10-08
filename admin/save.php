<?php
require __DIR__.'/util.php';
if(empty($_SESSION['authed'])){ http_response_code(403); exit('Forbidden'); }

$slug = slugify($_POST['slug'] ?? '');
if(!$slug) die('Invalid slug');

$payload = load_egg($slug) ?: [ 'title'=>'', 'alt'=>'', 'caption'=>'', 'body'=>'', 'image'=>'', 'audio'=>'', 'video'=>'' ];

$payload['title']   = trim($_POST['title'] ?? '');
$payload['alt']     = trim($_POST['alt'] ?? '');
$payload['caption'] = trim($_POST['caption'] ?? '');
$payload['body']    = trim($_POST['body'] ?? '');

// ----- IMAGE -----
if(!empty($_POST['image_url'])){
  $payload['image'] = filter_var($_POST['image_url'], FILTER_SANITIZE_URL);
}
if(empty($_POST['image_url']) && !empty($_FILES['image']['name']) && $_FILES['image']['error']===UPLOAD_ERR_OK){
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

// ----- AUDIO -----
if(!empty($_POST['audio_url'])){
  $payload['audio'] = filter_var($_POST['audio_url'], FILTER_SANITIZE_URL);
}
if(empty($_POST['audio_url']) && !empty($_FILES['audio']['name']) && $_FILES['audio']['error']===UPLOAD_ERR_OK){
  $tmp = $_FILES['audio']['tmp_name'];
  $ext = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
  $allowed = ['mp3','m4a','aac','wav','ogg','oga','webm'];
  if(!in_array($ext, $allowed)) die('Unsupported audio type');
  $fname = $slug.'-audio-'.time().'.'.$ext;
  $dest = $UPLOAD_DIR.'/'.$fname;
  if(!move_uploaded_file($tmp, $dest)) die('Failed to save audio');
  $payload['audio'] = $BASE_UPLOAD_URL.'/'.$fname;
}

// ----- VIDEO -----
if(!empty($_POST['video_url'])){
  $payload['video'] = filter_var($_POST['video_url'], FILTER_SANITIZE_URL);
}
if(empty($_POST['video_url']) && !empty($_FILES['video']['name']) && $_FILES['video']['error']===UPLOAD_ERR_OK){
  $tmp = $_FILES['video']['tmp_name'];
  $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
  $allowed = ['mp4','webm','ogg','ogv','mov','m4v'];
  if(!in_array($ext, $allowed)) die('Unsupported video type');
  $fname = $slug.'-video-'.time().'.'.$ext;
  $dest = $UPLOAD_DIR.'/'.$fname;
  if(!move_uploaded_file($tmp, $dest)) die('Failed to save video');
  $payload['video'] = $BASE_UPLOAD_URL.'/'.$fname;
}

save_egg($slug, $payload);
header('Location: index.php?slug='.urlencode($slug));
