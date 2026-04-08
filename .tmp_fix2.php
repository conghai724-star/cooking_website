<?php
function fixFile($file) {
    if (!file_exists($file)) return;
    $c = file_get_contents($file);
    
    // RecipeController
    $c = preg_replace('/Phuong thuc khong hop le\./', 'Phương thức không hợp lệ.', $c);
    $c = preg_replace('/Vui lA.{1,3}ng nh\?p lA.{1,3} do\./', 'Vui lòng nhập lý do.', $c);
    $c = preg_replace('/B\?n dA.{1,3} bA.{1,3}o cA.{1,3}o cA.{1,3}ng th\?c nA.{1,3}y\./', 'Bạn đã báo cáo công thức này.', $c);
    $c = preg_replace('/CA.{1,3} bA.{1,3}o cA.{1,3}o cA.{1,3}ng th\?c m\?i/', 'Có báo cáo công thức mới', $c);
    $c = preg_replace('/A.{1,3}A.{1,3} g\?i bA.{1,3}o cA.{1,3}o\./', 'Đã gửi báo cáo.', $c);
    
    // CommentController
    $c = preg_replace('/N\?i dung kh.{1,3}ng ph.{1,3} h\?p/', 'Nội dung không phù hợp', $c);
    $c = preg_replace('/Kh.{1,3}c: /', 'Khác: ', $c);
    $c = preg_replace('/Kh.{1,3}c/', 'Khác', $c);
    $c = preg_replace('/C.{1,3} b.{1,3}o c.{1,3}o b.{1,3}nh lu\?n m\?i/', 'Có báo cáo bình luận mới', $c);
    
    // UserController
    $c = preg_replace('/CĂ³ bĂ¡o cĂ¡o tĂ i khoáº£n má»›i/', 'Có báo cáo tài khoản mới', $c);
    
    file_put_contents($file, $c);
    echo "Fixed $file\n";
}

fixFile('app/controllers/RecipeController.php');
fixFile('app/controllers/CommentController.php');
fixFile('app/controllers/UserController.php');
fixFile('app/controllers/TipsController.php');
fixFile('app/controllers/IngredientController.php');
