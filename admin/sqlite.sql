-- SQLite schema for Sucky.life
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS eggs (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  slug          TEXT    NOT NULL UNIQUE,
  title         TEXT    NOT NULL,
  caption       TEXT,
  alt           TEXT,
  body          TEXT,                -- HTML (escape on render)
  image         TEXT,
  audio         TEXT,
  video         TEXT,
  draft         INTEGER NOT NULL DEFAULT 1,
  published_at  TEXT,                -- YYYY-MM-DD (kept as text)
  created_at    TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_eggs_draft         ON eggs(draft);
CREATE INDEX IF NOT EXISTS idx_eggs_published_at  ON eggs(published_at);
