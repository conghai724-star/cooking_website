const fs = require('fs');
let text = fs.readFileSync('app/views/recipes/create.php', 'utf8');

const replacements = {
    'Cach nau': 'Cách nấu',
    'Huong vi': 'Hương vị',
    'Dinh huong suc khoe': 'Định hướng sức khỏe',
    'Bua an': 'Bữa ăn',
    'A Aƒng cĂ´ng thA»©c mA»›i': 'Đăng công thức mới',
    'ThĂªm mĂ³n Aƒn, nguyĂªn liA»‡u, hình Aº£nh vĂ cĂ¡c bA°A»›c thA»±c hiA»‡n.': 'Thêm món ăn, nguyên liệu, hình ảnh và các bước thực hiện.',
    'ThĂ´ng tin cA¡ bAº£n': 'Thông tin cơ bản',
    'TiĂªu A\'A» ': 'Tiêu đề',
    'TiĂªu A‘A» ': 'Tiêu đề',
    'Danh mA»¥c': 'Danh mục',
    '-- ChA» n danh mA»¥c --': '-- Chọn danh mục --',
    'A A»™ khĂ³': 'Độ khó',
    'DA»…': 'Dễ',
    'Tags mĂ"\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¾Ă"\'"\'Ä\'\'Ă\'¢Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ă"\'\'€\'\'Ä\'\'Ă…Â¡Ä\'"\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¡Ă"\'"Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'\'Ä\'\'Ă\'³n Ä\'"Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¾Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¡Ă"\'"\'Ä\'\'Ă\'¢Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ă"\'\'€\'\'Ä\'\'Ă…Â¾Ä\'"\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\' Ă"\'Ä\'\'Ă\'¢Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ă"\'\'€\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¾Ă"\'\'Ă\'¢n': 'Tags món ăn',
    'ChA°a cĂ³ dA»¯ liA»‡u tags. HĂ£y chAº¡y migration tags trA°A»›c.': 'Chưa có dữ liệu tags. Hãy chạy migration tags trước.',
    'ThA» i gian nAº¥u (phĂºt)': 'Thời gian nấu (phút)',
    'Aº¢nh mĂ³n Aƒn': 'Ảnh món ăn',
    'MĂ´ tAº£': 'Mô tả',
    '+ ThĂªm nguyĂªn liA»‡u': '+ Thêm nguyên liệu',
    'TĂªn nguyĂªn liA»‡u': 'Tên nguyên liệu',
    'VĂ\xad dA»¥:': 'Ví dụ:',
    'SA»\' lA°A»£ng': 'Số lượng',
    'XĂ"\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¾Ă"\'"\'Ä\'\'Ă\'¢Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ă"\'\'€\'\'Ä\'\'Ă…Â¡Ä\'"\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¡Ă"\'"Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'\'Ä\'\'Ă\'³a': 'Xóa',
    'XÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'žĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'¬Ä\'\'Ă\'šÄ\'â€žĂ¢â\'¬ÂšÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"â€šĂ¢â\'¬ÂšÄ\'\'Ă\'³a': 'Xóa',
    'CĂ¡c bA°A»›c thA»±c hiA»‡n': 'Các bước thực hiện',
    '+ ThĂªm bA°A»›c': '+ Thêm bước',
    'BA°A»›c': 'Bước',
    'MĂ´ tAº£ bA°A»›c thA»±c hiA»‡n...': 'Mô tả bước thực hiện...',
    'HA»§y': 'Hủy',
    'LĂ"â€šĂ¢â\'¬ÂžÄ\'Â¢Ă¢â€šÂ¬Ă\'šĂ"\'Ă\'¢Ä\'Â¢Ă¢â\'¬ÂšĂ\'Â¬Ă…Â¾Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¡Ă"\'"\'Ä\'\'Ă\'¢Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ă"\'\'€\'\'Ä\'\' Ä\'Ä\'Â¢Ă¢â€šÂ¬Ă…Â¾Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'Ä\'\'Ă\'¢Ă"\'Ă\'¢Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ä\'\'Ă\'¬Ă"\'\'Ă…Â¡Ă"\'"Ä\'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"\'Ă\'¢Ä\'¢\'Ă\'¬Ä\'â€šĂ…Â¡Ä\'"\'\'Ä\'\'°u': 'Lưu',
    'A Aƒng cĂ´ng thA»©c': 'Đăng công thức',
    'MuA»\'i': 'Muối',
    'muA»—ng': 'muỗng',
};

for (const [bad, good] of Object.entries(replacements)) {
    text = text.split(bad).join(good);
}

// Fallback logic for completely broken strings
if (text.includes('KhĂ"\'Ä\'Â¢')) {
    text = text.replace(/<option value="medium">.+?nh<\/option>/g, '<option value="medium">Trung bình</option>');
    text = text.replace(/<option value="hard">.+?<\/option>/g, '<option value="hard">Khó</option>');
}

text = text.replace(/vĂ cĂ¡c/g, 'và các');
text = text.replace(/hĂ¬nh/g, 'hình');

// Catch any remaining Xóa buttons
text = text.replace(/<button class="remove-ingredient[^>]+>[^<]+<\/button>/g, '<button class="remove-ingredient w-full rounded-lg border border-rose-300 px-2 py-2 text-sm text-rose-600 hover:bg-rose-50" type="button">Xóa</button>');
text = text.replace(/<button class="remove-step[^>]+>[^<]+<\/button>/g, '<button class="remove-step rounded-lg border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" type="button">Xóa</button>');


fs.writeFileSync('app/views/recipes/create.php', text, 'utf8');
console.log("Done");
