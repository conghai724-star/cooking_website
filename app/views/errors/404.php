<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang</title>
    <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/details-summary.css">
</head>
<body>
<div class="container">
    <h1>404 - Không tìm thấy trang</h1>
    <p><?= htmlspecialchars($errorMessage ?? 'Trang bạn tìm không tồn tại.', ENT_QUOTES, 'UTF-8'); ?></p>
    <p><a href="<?= URLROOT; ?>/">Quay về trang chủ</a></p>
</div>
</body>
</html>
