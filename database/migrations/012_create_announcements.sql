-- Owner → admin broadcast notices.
CREATE TABLE IF NOT EXISTS announcements (
    id VARCHAR(20) PRIMARY KEY,
    author_id VARCHAR(20) DEFAULT NULL,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    priority ENUM('info', 'penting') NOT NULL DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_announcements_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per admin who dismissed a notice, so "unread" is per-admin rather
-- than a single global flag.
CREATE TABLE IF NOT EXISTS announcement_reads (
    announcement_id VARCHAR(20) NOT NULL,
    user_id VARCHAR(20) NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (announcement_id, user_id),
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_announcement_reads_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
