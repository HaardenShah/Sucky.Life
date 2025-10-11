<?php
declare(strict_types=1);

interface EggRepository {
  /** @return string[] slugs ordered by updated_at desc */
  public function list(): array;

  /** @return array|null Full egg by slug or null */
  public function get(string $slug): ?array;

  /**
   * Save (create or update) an egg. If $egg['slug'] is empty, slug is derived from title.
   * Returns saved egg (including final slug).
   * @param array $egg
   * @return array
   */
  public function save(array $egg): array;

  /** Rename a slug; returns true on success */
  public function rename(string $slug, string $new): bool;

  /** Delete by slug; returns true on success */
  public function delete(string $slug): bool;
}
