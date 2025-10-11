<?php
declare(strict_types=1);

require_once __DIR__ . '/EggRepository.php';

final class SqlEggRepo implements EggRepository {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
    $this->pdo->exec('PRAGMA foreign_keys = ON');
  }

  public function list(): array {
    $stmt = $this->pdo->query("SELECT slug FROM eggs ORDER BY updated_at DESC, id DESC");
    return array_map(fn($r) => $r['slug'], $stmt->fetchAll());
  }

  public function get(string $slug): ?array {
    $stmt = $this->pdo->prepare("SELECT * FROM eggs WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public function save(array $egg): array {
    $slug = trim((string)($egg['slug'] ?? ''));
    $title = trim((string)($egg['title'] ?? ''));

    if ($slug === '') {
      // derive from title
      $base = $title !== '' ? $title : ('egg-' . date('Ymd-His'));
      $slug = $this->uniqueSlug($base);
    } else {
      // normalize slug
      $slug = $this->slugify($slug);
      // if new insert and slug collides, adjust
      if (!$this->get($slug)) {
        // OK as new
      }
    }

    $payload = [
      ':slug'         => $slug,
      ':title'        => $title !== '' ? $title : $slug,
      ':caption'      => (string)($egg['caption'] ?? ''),
      ':alt'          => (string)($egg['alt'] ?? ''),
      ':body'         => (string)($egg['body'] ?? ''),
      ':image'        => (string)($egg['image'] ?? ''),
      ':audio'        => (string)($egg['audio'] ?? ''),
      ':video'        => (string)($egg['video'] ?? ''),
      ':draft'        => !empty($egg['draft']) ? 1 : 0,
      ':published_at' => ($egg['published_at'] ?? null) ?: null,
    ];

    $sql = "
      INSERT INTO eggs(slug,title,caption,alt,body,image,audio,video,draft,published_at,created_at,updated_at)
      VALUES(:slug,:title,:caption,:alt,:body,:image,:audio,:video,:draft,:published_at,datetime('now'),datetime('now'))
      ON CONFLICT(slug) DO UPDATE SET
        title=excluded.title,
        caption=excluded.caption,
        alt=excluded.alt,
        body=excluded.body,
        image=excluded.image,
        audio=excluded.audio,
        video=excluded.video,
        draft=excluded.draft,
        published_at=excluded.published_at,
        updated_at=datetime('now')
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($payload);

    $saved = $this->get($slug);
    if (!$saved) throw new RuntimeException('Save failed unexpectedly');
    return $saved;
  }

  public function rename(string $slug, string $new): bool {
    $slug = $this->slugify($slug);
    $new  = $this->slugify($new);
    if ($slug === '' || $new === '') return false;

    // If target exists, make it unique by suffix
    if ($this->get($new)) {
      $new = $this->uniqueSlug($new);
    }

    $stmt = $this->pdo->prepare("UPDATE eggs SET slug = :new, updated_at = datetime('now') WHERE slug = :old");
    $stmt->execute([':new' => $new, ':old' => $slug]);
    return $stmt->rowCount() > 0;
  }

  public function delete(string $slug): bool {
    $stmt = $this->pdo->prepare("DELETE FROM eggs WHERE slug = :slug");
    $stmt->execute([':slug' => $this->slugify($slug)]);
    return $stmt->rowCount() > 0;
  }

  /* ----------------- helpers ----------------- */

  private function slugify(string $s): string {
    $s = trim(mb_strtolower($s, 'UTF-8'));
    $s = preg_replace('~[^\\pL\\pN]+~u', '-', $s) ?? '';
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('~[^-a-z0-9]+~', '', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'egg';
  }

  private function uniqueSlug(string $base): string {
    $base = $this->slugify($base);
    $i = 0;
    do {
      $candidate = $base . ($i ? "-$i" : '');
      if (!$this->get($candidate)) return $candidate;
      $i++;
    } while (true);
  }
}
