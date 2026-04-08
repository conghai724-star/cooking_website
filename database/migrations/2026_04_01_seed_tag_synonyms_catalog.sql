-- Seed tag synonyms for expanded tags catalog
-- Date: 2026-04-01

START TRANSACTION;

INSERT INTO tag_synonyms (tag_id, keyword, keyword_norm)
SELECT t.id, v.keyword, v.keyword_norm
FROM tags t
INNER JOIN (
    SELECT 'xao' AS slug, 'xao' AS keyword, 'xao' AS keyword_norm UNION ALL
    SELECT 'xao', 'xào', 'xao' UNION ALL
    SELECT 'xao', 'stir fry', 'stir fry' UNION ALL

    SELECT 'chien', 'chien', 'chien' UNION ALL
    SELECT 'chien', 'chiên', 'chien' UNION ALL
    SELECT 'chien', 'fried', 'fried' UNION ALL

    SELECT 'hap', 'hap', 'hap' UNION ALL
    SELECT 'hap', 'hấp', 'hap' UNION ALL
    SELECT 'hap', 'steam', 'steam' UNION ALL

    SELECT 'luoc', 'luoc', 'luoc' UNION ALL
    SELECT 'luoc', 'luộc', 'luoc' UNION ALL
    SELECT 'luoc', 'boil', 'boil' UNION ALL

    SELECT 'nuong', 'nuong', 'nuong' UNION ALL
    SELECT 'nuong', 'nướng', 'nuong' UNION ALL
    SELECT 'nuong', 'grill', 'grill' UNION ALL

    SELECT 'kho', 'kho', 'kho' UNION ALL
    SELECT 'kho', 'rim', 'rim' UNION ALL

    SELECT 'canh', 'canh', 'canh' UNION ALL
    SELECT 'canh', 'soup', 'soup' UNION ALL

    SELECT 'sup', 'sup', 'sup' UNION ALL
    SELECT 'sup', 'súp', 'sup' UNION ALL

    SELECT 'ap_chao', 'ap chao', 'ap chao' UNION ALL
    SELECT 'ap_chao', 'áp chảo', 'ap chao' UNION ALL
    SELECT 'ap_chao', 'pan seared', 'pan seared' UNION ALL

    SELECT 'tron', 'tron', 'tron' UNION ALL
    SELECT 'tron', 'trộn', 'tron' UNION ALL
    SELECT 'tron', 'mix', 'mix' UNION ALL

    SELECT 'cay', 'cay', 'cay' UNION ALL
    SELECT 'cay', 'cay nong', 'cay nong' UNION ALL
    SELECT 'cay', 'spicy', 'spicy' UNION ALL

    SELECT 'ngot', 'ngot', 'ngot' UNION ALL
    SELECT 'ngot', 'ngọt', 'ngot' UNION ALL
    SELECT 'ngot', 'sweet', 'sweet' UNION ALL

    SELECT 'man', 'man', 'man' UNION ALL
    SELECT 'man', 'mặn', 'man' UNION ALL
    SELECT 'man', 'salty', 'salty' UNION ALL

    SELECT 'chua', 'chua', 'chua' UNION ALL
    SELECT 'chua', 'sour', 'sour' UNION ALL

    SELECT 'beo', 'beo', 'beo' UNION ALL
    SELECT 'beo', 'béo', 'beo' UNION ALL
    SELECT 'beo', 'fatty', 'fatty' UNION ALL

    SELECT 'dang', 'dang', 'dang' UNION ALL
    SELECT 'dang', 'đắng', 'dang' UNION ALL
    SELECT 'dang', 'bitter', 'bitter' UNION ALL

    SELECT 'thanh', 'thanh', 'thanh' UNION ALL
    SELECT 'thanh', 'thanh nhe', 'thanh nhe' UNION ALL
    SELECT 'thanh', 'thanh nhẹ', 'thanh nhe' UNION ALL

    SELECT 'dam_da', 'dam da', 'dam da' UNION ALL
    SELECT 'dam_da', 'đậm đà', 'dam da' UNION ALL

    SELECT 'chua_ngot', 'chua ngot', 'chua ngot' UNION ALL
    SELECT 'chua_ngot', 'chua ngọt', 'chua ngot' UNION ALL
    SELECT 'chua_ngot', 'sweet sour', 'sweet sour' UNION ALL

    SELECT 'it_dau', 'it dau', 'it dau' UNION ALL
    SELECT 'it_dau', 'ít dầu', 'it dau' UNION ALL
    SELECT 'it_dau', 'low oil', 'low oil' UNION ALL

    SELECT 'healthy', 'lanh manh', 'lanh manh' UNION ALL
    SELECT 'healthy', 'lành mạnh', 'lanh manh' UNION ALL
    SELECT 'healthy', 'healthy', 'healthy' UNION ALL

    SELECT 'an_kieng', 'an kieng', 'an kieng' UNION ALL
    SELECT 'an_kieng', 'ăn kiêng', 'an kieng' UNION ALL
    SELECT 'an_kieng', 'diet', 'diet' UNION ALL

    SELECT 'giam_can', 'giam can', 'giam can' UNION ALL
    SELECT 'giam_can', 'giảm cân', 'giam can' UNION ALL
    SELECT 'giam_can', 'weight loss', 'weight loss' UNION ALL

    SELECT 'it_calo', 'it calo', 'it calo' UNION ALL
    SELECT 'it_calo', 'ít calo', 'it calo' UNION ALL
    SELECT 'it_calo', 'low calorie', 'low calorie' UNION ALL

    SELECT 'eat_clean', 'eat clean', 'eat clean' UNION ALL
    SELECT 'eat_clean', 'clean eating', 'clean eating' UNION ALL

    SELECT 'khong_duong', 'khong duong', 'khong duong' UNION ALL
    SELECT 'khong_duong', 'không đường', 'khong duong' UNION ALL
    SELECT 'khong_duong', 'sugar free', 'sugar free' UNION ALL

    SELECT 'thuan_chay', 'thuan chay', 'thuan chay' UNION ALL
    SELECT 'thuan_chay', 'thuần chay', 'thuan chay' UNION ALL
    SELECT 'thuan_chay', 'vegan', 'vegan' UNION ALL

    SELECT 'mon_chinh', 'mon chinh', 'mon chinh' UNION ALL
    SELECT 'mon_chinh', 'món chính', 'mon chinh' UNION ALL
    SELECT 'mon_chinh', 'main dish', 'main dish' UNION ALL

    SELECT 'mon_phu', 'mon phu', 'mon phu' UNION ALL
    SELECT 'mon_phu', 'món phụ', 'mon phu' UNION ALL
    SELECT 'mon_phu', 'side dish', 'side dish' UNION ALL

    SELECT 'mon_nuoc', 'mon nuoc', 'mon nuoc' UNION ALL
    SELECT 'mon_nuoc', 'món nước', 'mon nuoc' UNION ALL

    SELECT 'mon_kho', 'mon kho', 'mon kho' UNION ALL
    SELECT 'mon_kho', 'món khô', 'mon kho' UNION ALL

    SELECT 'an_vat', 'an vat', 'an vat' UNION ALL
    SELECT 'an_vat', 'ăn vặt', 'an vat' UNION ALL
    SELECT 'an_vat', 'snack', 'snack'
) v ON v.slug = t.slug
ON DUPLICATE KEY UPDATE keyword = VALUES(keyword);

COMMIT;
