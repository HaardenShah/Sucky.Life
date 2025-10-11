<?php
declare(strict_types=1);

/**
 * admin/db.php
 * Single place to bootstrap a PDO(SQLite) connection and initialize schema.
 */

function sucky_db_path(): string {
  // Put DB file at /admin/data/sucky.sqlite (create folder if missing)
  $dir = __DIR__ . '/data';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  return $dir . '/sucky.sqlite';
}

/** @return PDO */
function sucky_pdo(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $dsn = 'sqlite:' . sucky_db_path();
  $pdo = new PDO($dsn, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);

  // Initialize schema (idempotent)
  $schema = file_get_contents(__DIR__ . '/sqlite.sql');
  if ($schema === false) {
    throw new RuntimeException('Missing sqlite.sql');
  }
  $pdo->exec($schema);

  return $pdo;
}
