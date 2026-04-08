<?php
require_once 'config/config.php';
require_once 'app/models/Database.php';
require_once 'app/core/Model.php';
require_once 'app/models/RecipeModel.php';

try {
    $model = new RecipeModel();
    $result = $model->saveReport(1, 1, "Test report reason");
    var_dump($result);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
