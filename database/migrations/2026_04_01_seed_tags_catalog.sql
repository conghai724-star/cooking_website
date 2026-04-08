-- Seed expanded tags catalog
-- Date: 2026-04-01

START TRANSACTION;

-- Keep slug style consistent with underscore naming
UPDATE tags SET slug = 'it_dau' WHERE slug = 'it-dau';

INSERT INTO tags (name, slug, type) VALUES
('Xào', 'xao', 'method'),
('Chiên', 'chien', 'method'),
('Hấp', 'hap', 'method'),
('Luộc', 'luoc', 'method'),
('Nướng', 'nuong', 'method'),
('Kho', 'kho', 'method'),
('Canh', 'canh', 'method'),
('Súp', 'sup', 'method'),
('Áp chảo', 'ap_chao', 'method'),
('Trộn', 'tron', 'method'),

('Cay', 'cay', 'taste'),
('Ngọt', 'ngot', 'taste'),
('Mặn', 'man', 'taste'),
('Chua', 'chua', 'taste'),
('Béo', 'beo', 'taste'),
('Đắng', 'dang', 'taste'),
('Thanh nhẹ', 'thanh', 'taste'),
('Đậm đà', 'dam_da', 'taste'),
('Chua ngọt', 'chua_ngot', 'taste'),

('Ít dầu', 'it_dau', 'health'),
('Lành mạnh', 'healthy', 'health'),
('Ăn kiêng', 'an_kieng', 'health'),
('Giảm cân', 'giam_can', 'health'),
('Ít calo', 'it_calo', 'health'),
('Eat clean', 'eat_clean', 'health'),
('Không đường', 'khong_duong', 'health'),
('Thuần chay', 'thuan_chay', 'health'),

('Món chính', 'mon_chinh', 'meal'),
('Món phụ', 'mon_phu', 'meal'),
('Món nước', 'mon_nuoc', 'meal'),
('Món khô', 'mon_kho', 'meal'),
('Ăn vặt', 'an_vat', 'meal')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    type = VALUES(type),
    updated_at = CURRENT_TIMESTAMP;

COMMIT;
