-- Products Seeder
INSERT INTO products (id, name, short_description, long_description, featured, cover_gradient, cover_image, max_message_length) VALUES
('black-forest', 'Black Forest Cake', 'Manis, lembut, dengan perpaduan cokelat, krim, dan alsen segar dari ceri.', 'Tekstur lembut dan rasa cokelat yang kaya berpadu dengan krim segar dan ceri hitam pilihan. Setiap gigitan menghadirkan keseimbangan sempurna antara manis dan sedikit asam dari buah ceri, menjadikannya pilihan klasik yang tak pernah gagal memanjakan lidah.', TRUE, 'linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)', 'brownies.jpg', 60),
('red-velvet', 'Red Velvet Cake', 'Manis, lembut, sedikit cokelat dengan sentuhan keju krim yang gurih.', 'Tekstur lembut dan rasa manis ringan berpadu sentuhan cokelat halus serta krim keju yang creamy. Warna merah khasnya menjadikan cake ini elegan dan menggoda, cocok untuk perayaan spesial maupun hadiah istimewa.', TRUE, 'linear-gradient(135deg, #d38182 0%, #f0b1a6 100%)', 'strawberry-cake.jpg', 60),
('vanilla-cake', 'Vanila Cake', 'Sponge vanilla ringan dengan buttercream silky.', 'Sponge vanilla yang ringan dan lembut dilapisi buttercream silky yang mewah. Rasanya yang klasik dan universal menjadikannya sempurna untuk segala ocasion, dari ulang tahun hingga arisan keluarga.', FALSE, 'linear-gradient(135deg, #f7e9d5 0%, #f3d7bb 100%)', NULL, 60),
('lemon-cake', 'Lemon Cake', 'Rasa lemon segar dengan frosting cream cheese ringan.', 'Cake lemon yang segar dengan frosting cream cheese ringan menghadirkan kesegaran alami di setiap suapan. Perpaduan asam manis yang seimbang menjadikannya pilihan ideal untuk hari yang cerah.', FALSE, 'linear-gradient(135deg, #f5e6a3 0%, #e8d77b 100%)', NULL, 60),
('rainbow-cake', 'Rainbow Cake', 'Cake warna-warni yang ceria dengan rasa vanilla lembut.', 'Cake berlapis warna-warni yang ceria dan menyenangkan dengan rasa vanilla lembut. Sempurna untuk pesta anak-anak atau siapa saja yang ingin menambahkan keceriaan di hari spesial mereka.', FALSE, 'linear-gradient(135deg, #f5a3a3 0%, #a3d5f5 50%, #a3f5c4 100%)', NULL, 60);

-- Product Sizes Seeder
INSERT INTO product_sizes (id, product_id, label, full_label, price) VALUES
-- Black Forest
('size-16-bf', 'black-forest', '16', 'Ukuran 16 cm', 120000),
('size-18-bf', 'black-forest', '18', 'Ukuran 18 cm', 170000),
('size-20-bf', 'black-forest', '20', 'Ukuran 20 cm', 220000),
('size-22-bf', 'black-forest', '22', 'Ukuran 22 cm', 270000),
-- Red Velvet
('size-16-rv', 'red-velvet', '16', 'Ukuran 16 cm', 120000),
('size-18-rv', 'red-velvet', '18', 'Ukuran 18 cm', 170000),
('size-20-rv', 'red-velvet', '20', 'Ukuran 20 cm', 220000),
('size-22-rv', 'red-velvet', '22', 'Ukuran 22 cm', 270000),
-- Vanilla
('size-16-vc', 'vanilla-cake', '16', 'Ukuran 16 cm', 120000),
('size-18-vc', 'vanilla-cake', '18', 'Ukuran 18 cm', 170000),
('size-20-vc', 'vanilla-cake', '20', 'Ukuran 20 cm', 220000),
('size-22-vc', 'vanilla-cake', '22', 'Ukuran 22 cm', 270000),
-- Lemon
('size-16-lc', 'lemon-cake', '16', 'Ukuran 16 cm', 120000),
('size-18-lc', 'lemon-cake', '18', 'Ukuran 18 cm', 170000),
('size-20-lc', 'lemon-cake', '20', 'Ukuran 20 cm', 220000),
('size-22-lc', 'lemon-cake', '22', 'Ukuran 22 cm', 270000),
-- Rainbow
('size-16-rc', 'rainbow-cake', '16', 'Ukuran 16 cm', 120000),
('size-18-rc', 'rainbow-cake', '18', 'Ukuran 18 cm', 170000),
('size-20-rc', 'rainbow-cake', '20', 'Ukuran 20 cm', 220000),
('size-22-rc', 'rainbow-cake', '22', 'Ukuran 22 cm', 270000);
