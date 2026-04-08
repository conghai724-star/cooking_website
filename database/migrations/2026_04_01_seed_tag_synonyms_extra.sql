-- Extra synonyms to improve spicy / low-oil mapping
-- Date: 2026-04-01

START TRANSACTION;

INSERT INTO tag_synonyms (tag_id, keyword, keyword_norm)
SELECT t.id, v.keyword, v.keyword_norm
FROM tags t
INNER JOIN (
    SELECT 'cay' AS slug, 'ot' AS keyword, 'ot' AS keyword_norm UNION ALL
    SELECT 'cay', 'ớt', 'ot' UNION ALL
    SELECT 'cay', 'sa te', 'sa te' UNION ALL
    SELECT 'cay', 'sa tế', 'sa te' UNION ALL
    SELECT 'cay', 'hot', 'hot' UNION ALL

    SELECT 'it_dau', 'khong dau', 'khong dau' UNION ALL
    SELECT 'it_dau', 'không dầu', 'khong dau' UNION ALL
    SELECT 'it_dau', 'han che dau mo', 'han che dau mo' UNION ALL
    SELECT 'it_dau', 'hạn chế dầu mỡ', 'han che dau mo' UNION ALL
    SELECT 'it_dau', 'air fry', 'air fry' UNION ALL
    SELECT 'it_dau', 'noi chien khong dau', 'noi chien khong dau' UNION ALL
    SELECT 'it_dau', 'nồi chiên không dầu', 'noi chien khong dau'
) v ON v.slug = t.slug
ON DUPLICATE KEY UPDATE keyword = VALUES(keyword);

COMMIT;
