<?php
require 'config/config.php';
$db = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASS);
$q = $db->query('DESCRIBE users');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
