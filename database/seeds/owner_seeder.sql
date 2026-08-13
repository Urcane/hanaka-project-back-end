-- Owner Seeder
-- Default owner: owner@hanakacake.com / Owner12345
-- Password hash generated with: password_hash('Owner12345', PASSWORD_BCRYPT, ['cost' => 12])
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
('usr_owner001', 'Owner Hanaka', 'owner@hanakacake.com', '081299998877', '$2y$12$d05nhR1zYesRPDR8Nr8CKelau49ed73PBmTyCBevTUB7DXXZZ3kxK', 'owner')
ON DUPLICATE KEY UPDATE role = 'owner';
