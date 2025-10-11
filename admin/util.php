<?php
declare(strict_types=1);

/**
 * admin/util.php
 * Shared helpers: sessions, CSRF, repo access, and legacy-compatible wrappers.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}
function csrf_input(): string {
  $v = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="csrf" value="'.$v.'">';
}

/* ---------- Repository bootstrapping ---------- */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/storage/SqlEggRepo.php';

/** @return EggRepository */
function repo(): EggRepository {
  static $repo = null;
  if ($repo instanceof EggRepository) return $repo;
  $repo = new SqlEggRepo(sucky_pdo());
  return $repo;
}

/* ---------- Legacy-compatible wrappers used by templates ---------- */

/** @return string[] */
function list_eggs(): array {
  return repo()->list();
}

/** @return array|null */
function load_egg(string $slug): ?array {
  return repo()->get($slug);
}