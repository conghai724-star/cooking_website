<?php
$files = [
    'app/controllers/RecipeController.php',
    'app/controllers/CommentController.php',
    'app/controllers/UserController.php',
];

$replacements = [
    'Phuong thuc khong hop le.' => 'Phương thức không hợp lệ.',
    'Vui lA¿½ng nh?p lA¿½ do.' => 'Vui lòng nhập lý do.',
    'B?n dA¿½ bA¿½o cA¿½o cA¿½ng th?c nA¿½y.' => 'Bạn đã báo cáo công thức này.',
    'CA¿½ bA¿½o cA¿½o cA¿½ng th?c m?i' => 'Có báo cáo công thức mới',
    'A¿½A¿½ g?i bA¿½o cA¿½o.' => 'Đã gửi báo cáo.',
    
    'N?i dung khng ph h?p' => 'Nội dung không phù hợp',
    'Khc: ' => 'Khác: ',
    'Khc' => 'Khác',
    'C bo co bnh lu?n m?i' => 'Có báo cáo bình luận mới',
    
    'CĂ³ bĂ¡o cĂ¡o tĂ i khoáº£n má»›i' => 'Có báo cáo tài khoản mới',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($replacements as $old => $new) {
            $content = str_replace($old, $new, $content);
        }
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
