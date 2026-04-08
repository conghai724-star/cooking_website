-- Nhóm nguyên liệu: Rau củ, Thịt, Hải sản, Gia vị (đổi tên Spices nếu còn)
UPDATE categories SET name = 'Gia vị' WHERE type = 'ingredient' AND name = 'Spices';

INSERT IGNORE INTO categories (name, type) VALUES
('Rau củ', 'ingredient'),
('Thịt', 'ingredient'),
('Hải sản', 'ingredient');

INSERT IGNORE INTO categories (name, type)
SELECT 'Gia vị', 'ingredient'
WHERE NOT EXISTS (
    SELECT 1 FROM categories WHERE type = 'ingredient' AND name = 'Gia vị'
);
