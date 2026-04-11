const fs = require('fs');
let text = fs.readFileSync('app/views/recipes/create.php', 'utf8');

const replacements = {
    'A Aƒng cĂ´ng thA»©c mA»›i': 'Đăng công thức mới',
    'ThĂªm mĂ³n Aƒn, nguyĂªn liA»‡u, hình Aº£nh vĂ cĂ¡c bA°A»›c thA»±c hiA»‡n.': 'Thêm món ăn, nguyên liệu, hình ảnh và các bước thực hiện.',
    'ThĂ´ng tin cA¡ bAº£n': 'Thông tin cơ bản',
    'TiĂªu A‘A» ': 'Tiêu đề',
    'Danh mA»¥c': 'Danh mục',
    '-- ChA» n danh mA»¥c --': '-- Chọn danh mục --',
    'A A»™ khĂ³': 'Độ khó',
    'DA»…': 'Dễ',
    'Tags mÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'žĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\'šÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"â€šĂ¢â\'¬ÂšÄ\'\'Ă\'³n Ă"â€šĂ¢â\'¬ÂžÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\'žÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\' Ă"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'Â¢Ă¢â\'¬ÂžĂ\'¢n': 'Tags món ăn',
    'ChA°a cĂ³ dA»¯ liA»‡u tags. HĂ£y chAº¡y migration tags trA°A»›c.': 'Chưa có dữ liệu tags. Hãy chạy migration tags trước.',
    'ThA» i gian nAº¥u (phĂºt)': 'Thời gian nấu (phút)',
    'Aº¢nh mĂ³n Aƒn': 'Ảnh món ăn',
    'MĂ´ tAº£': 'Mô tả',
    '+ ThĂªm nguyĂªn liA»‡u': '+ Thêm nguyên liệu',
    'TĂªn nguyĂªn liA»‡u': 'Tên nguyên liệu',
    'VĂ\xad dA»¥:': 'Ví dụ:',
    'SA»‘ lA°A»£ng': 'Số lượng',
    'SA»\' lA°A»£ng': 'Số lượng',
    'BA°A»›c': 'Bước',
    'MĂ´ tAº£ bA°A»›c thA»±c hiA»‡n...': 'Mô tả bước thực hiện...',
    'HA»§y': 'Hủy',
    'LĂ„â€šĂ¢â‚¬žÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\' Ä\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"â€šĂ¢â\'¬ÂšÄ\'\'Ă\'°u': 'Lưu',
    'A Aƒng cĂ´ng thA»©c': 'Đăng công thức',
    'MuA»‘i': 'Muối',
    'muA»—ng': 'muỗng',
    'ThĂªm mĂ³n Aƒn, nguyĂªn liA»‡u, hĂ¬nh Aº£nh vĂ cĂ¡c bA°A»›c thA»±c hiA»‡n.': 'Thêm món ăn, nguyên liệu, hình ảnh và các bước thực hiện.',
    'CĂ¡c bA°A»›c thA»±c hiA»‡n': 'Các bước thực hiện',
    '+ ThĂªm bA°A»›c': '+ Thêm bước',
    'Trung bÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'žĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\'šÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"â€šĂ¢â\'¬ÂšÄ\'\'Ă\'¬nh': 'Trung bình',
    'KhÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'žĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\'šÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"â€šĂ¢â\'¬ÂšÄ\'\'Ă\'³': 'Khó',
    'vĂ ': 'và'
};

for (const [bad, good] of Object.entries(replacements)) {
    text = text.split(bad).join(good);
}

fs.writeFileSync('app/views/recipes/create.php', text, 'utf8');
console.log("Done 2!");
