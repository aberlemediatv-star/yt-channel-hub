-- Module pro Mitarbeiter (JSON); Kanal-Zugriff mit optionaler Sperre (blocked)

ALTER TABLE staff_users ADD COLUMN modules_json JSON NULL;

ALTER TABLE staff_channel_access ADD COLUMN blocked TINYINT(1) NOT NULL DEFAULT 0;
