-- V1 tags for recipe search/chatbot
-- Date: 2026-04-01

START TRANSACTION;

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    type ENUM('method', 'taste', 'health', 'meal') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tags_slug (slug),
    INDEX idx_tags_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tag_synonyms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_id INT NOT NULL,
    keyword VARCHAR(120) NOT NULL,
    keyword_norm VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tag_synonyms_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tag_synonyms_norm (tag_id, keyword_norm),
    INDEX idx_tag_synonyms_keyword_norm (keyword_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipe_tags (
    recipe_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (recipe_id, tag_id),
    CONSTRAINT fk_recipe_tags_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipe_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_recipe_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tags (id, name, slug, type) VALUES
(1, 'xao', 'xao', 'method'),
(2, 'cay', 'cay', 'taste'),
(3, 'it_dau', 'it-dau', 'health');

INSERT IGNORE INTO tag_synonyms (tag_id, keyword, keyword_norm) VALUES
(1, 'xao', 'xao'),
(1, 'xào', 'xao'),
(1, 'ap chao', 'ap chao'),
(1, 'áp chảo', 'ap chao'),
(2, 'cay', 'cay'),
(2, 'cay nong', 'cay nong'),
(2, 'spicy', 'spicy'),
(3, 'it dau', 'it dau'),
(3, 'ít dầu', 'it dau');

COMMIT;
