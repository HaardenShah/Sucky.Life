<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/util.php';

if (empty($_SESSION['authed'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Not authenticated.']);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Method not allowed.']);
  exit;
}
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'CSRF validation failed.']);
  exit;
}

$slug = trim((string)($_POST['slug'] ?? ''));
if ($slug === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Missing slug.']);
  exit;
}

try {
  $ok = repo()->delete($slug);
  if (!$ok) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Egg not found.']);
    exit;
  }
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
