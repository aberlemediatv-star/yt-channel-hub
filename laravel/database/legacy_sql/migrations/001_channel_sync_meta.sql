-- Bestehende Installationen: Spalten nachziehen (MariaDB 10.5.2+ IF NOT EXISTS)

ALTER TABLE channels
    ADD COLUMN IF NOT EXISTS last_video_sync_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS last_video_sync_error VARCHAR(512) NULL,
    ADD COLUMN IF NOT EXISTS last_analytics_sync_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS last_analytics_sync_error VARCHAR(512) NULL;
