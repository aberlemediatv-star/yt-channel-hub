-- MariaDB 10.3+ (Plesk) / MySQL 8+
-- In Plesk: Datenbank + Benutzer anlegen, Charset utf8mb4; JSON-Spalten ab MariaDB 10.2.7

CREATE TABLE IF NOT EXISTS oauth_credentials (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label           VARCHAR(128) NOT NULL DEFAULT '',
    refresh_token   TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS channels (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                VARCHAR(64) NOT NULL UNIQUE,
    title               VARCHAR(255) NOT NULL DEFAULT '',
    youtube_channel_id VARCHAR(32) NOT NULL UNIQUE,
    uploads_playlist_id VARCHAR(64) NULL,
    oauth_credential_id INT UNSIGNED NULL,
    sort_order          INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    last_video_sync_at   DATETIME NULL,
    last_video_sync_error VARCHAR(512) NULL,
    last_analytics_sync_at DATETIME NULL,
    last_analytics_sync_error VARCHAR(512) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_channels_oauth FOREIGN KEY (oauth_credential_id) REFERENCES oauth_credentials(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id      INT UNSIGNED NOT NULL,
    video_id        VARCHAR(32) NOT NULL,
    title           VARCHAR(512) NOT NULL,
    description     TEXT NULL,
    published_at    DATETIME NULL,
    thumbnail_url   VARCHAR(1024) NULL,
    view_count      BIGINT UNSIGNED NOT NULL DEFAULT 0,
    duration_iso    VARCHAR(32) NULL,
    raw_json        JSON NULL,
    synced_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_channel_video (channel_id, video_id),
    CONSTRAINT fk_videos_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_published (channel_id, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_daily (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id      INT UNSIGNED NOT NULL,
    report_date     DATE NOT NULL,
    views           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    watch_time_minutes DECIMAL(18,4) NOT NULL DEFAULT 0,
    subscribers_gained INT NOT NULL DEFAULT 0,
    estimated_revenue DECIMAL(18,6) NULL,
    estimated_ad_revenue DECIMAL(18,6) NULL,
    raw_json        JSON NULL,
    synced_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_channel_day (channel_id, report_date),
    CONSTRAINT fk_analytics_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_report_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(64) NOT NULL,
    channel_id INT UNSIGNED NULL,
    payload JSON NULL,
    status ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
    error_message VARCHAR(1024) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    INDEX idx_status_created (status, created_at),
    CONSTRAINT fk_jobs_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
