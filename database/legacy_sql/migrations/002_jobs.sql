-- Warteschlange für Hintergrund-Jobs (Worker: bin/worker.php)

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
