-- Admin Seeder
-- Default admin: admin@hanakacake.com / Admin12345
-- Password hash generated with: password_hash('Admin12345', PASSWORD_BCRYPT, ['cost' => 12])
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
('usr_admin001', 'Admin Hanaka', 'admin@hanakacake.com', '081299998888', '$2y$12$yEntbgNY8IntM5.G.W.Oiu09.kHwNgYi7ecjkXURhCMrxvvi.R3.G', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin';
