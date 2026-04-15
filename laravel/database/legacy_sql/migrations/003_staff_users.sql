-- Mitarbeiter-Login: nur zugewiesene Kanäle (Video-Upload), keine Analytics/Umsatz-UI

CREATE TABLE IF NOT EXISTS staff_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_staff_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_channel_access (
    staff_id INT UNSIGNED NOT NULL,
    channel_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (staff_id, channel_id),
    CONSTRAINT fk_staff_ch_staff FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_staff_ch_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
