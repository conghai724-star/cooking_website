<?php
require 'config/config.php';
$db = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASS);
try {
    $db->exec("ALTER TABLE users 
               ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email, 
               ADD COLUMN auth_provider VARCHAR(50) NOT NULL DEFAULT 'local' AFTER google_id,
               ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER auth_provider;");
    echo "Columns added successfully";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "Columns already exist";
    } else {
        echo "Database error: " . $e->getMessage();
    }
}
