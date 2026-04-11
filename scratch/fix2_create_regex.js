const fs = require('fs');
let text = fs.readFileSync('app/views/recipes/create.php', 'utf8');

const patterns = [
    [/<h1 class="text-3xl font-black text-slate-900">.*?<\/h1>/gs, '<h1 class="text-3xl font-black text-slate-900">Đăng công thức mới</h1>'],
    [/<p class="mt-1 text-slate-500">.*?<\/p>/gs, '<p class="mt-1 text-slate-500">Thêm món ăn, nguyên liệu, hình ảnh và các bước thực hiện.</p>'],
    [/<h2 class="mb-5 text-xl font-bold">.*?<\/h2>/gs, '<h2 class="mb-5 text-xl font-bold">Thông tin cơ bản</h2>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>Ti.*?<\/label>/gs, '<label$1>Tiêu đề</label>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>Danh.*?<\/label>/gs, '<label$1>Danh mục</label>'],
    [/<option value="">.*?<\/option>/gs, '<option value="">-- Chọn danh mục --</option>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>A.*? kh.*?<\/label>/gs, '<label$1>Độ khó</label>'],
    [/<option value="easy">.*?<\/option>/gs, '<option value="easy">Dễ</option>'],
    [/<p class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">.*?<\/p>/gs, '<p class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">Chưa có dữ liệu tags. Hãy chạy migration tags trước.</p>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>Th.*? gian n.*?<\/label>/gs, '<label$1>Thời gian nấu (phút)</label>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>A.*?nh m.*?n A.*?<\/label>/gs, '<label$1>Ảnh món ăn</label>'],
    [/<label( class="mb-2 block text-sm font-semibold text-slate-700"[^>]*)>M.*? t.*?<\/label>/gs, '<label$1>Mô tả</label>'],
    [/<button id="add-ingredient"([^>]*)>.*?<\/button>/gs, '<button id="add-ingredient"$1>+ Thêm nguyên liệu</button>'],
    [/<label class="mb-1 block text-xs font-semibold text-slate-500">T.*?n nguy[^\<]+<\/label>/gs, '<label class="mb-1 block text-xs font-semibold text-slate-500">Tên nguyên liệu</label>'],
    [/<label class="mb-1 block text-xs font-semibold text-slate-500">S.*? l.*?ng<\/label>/gs, '<label class="mb-1 block text-xs font-semibold text-slate-500">Số lượng</label>'],
    [/<h2 class="text-xl font-bold">C.*?<\/h2>/gs, '<h2 class="text-xl font-bold">Các bước thực hiện</h2>'],
    [/<button id="add-step"([^>]*)>.*?<\/button>/gs, '<button id="add-step"$1>+ Thêm bước</button>'],
    [/<p class="step-label text-sm font-bold text-slate-700">.*?<\/p>/gs, '<p class="step-label text-sm font-bold text-slate-700">Bước 1</p>'],
    [/<button class="rounded-xl border border-slate-300([^>]*) value="cancel">.*?<\/button>/gs, '<button class="rounded-xl border border-slate-300$1 value="cancel">Hủy</button>'],
    [/<button class="rounded-xl bg-primary([^>]*) value="submit">.*?<\/button>/gs, '<button class="rounded-xl bg-primary$1 value="submit">Đăng công thức</button>'],
    [/placeholder="V.*? dA.*? Th.*?t g.*?"/gs, 'placeholder="Ví dụ: Thịt gà"'],
    [/placeholder="V.*? dA.*? 45"/gs, 'placeholder="Ví dụ: 45"'],
    [/placeholder="V.*? dA.*? Mu.*?"/gs, 'placeholder="Ví dụ: Muối"'],
    [/placeholder="M.*? t.*? b.*?"/gs, 'placeholder="Mô tả bước thực hiện..."'],
    [/`BA.*? \$\{index \+ 1\}`/gs, '`Bước ${index + 1}`'],
    [/<button class="rounded-xl border border-primary([^>]*) value="save">.*?<\/button>/gs, '<button class="rounded-xl border border-primary$1 value="save">Lưu</button>']
];

for (const [regex, replacement] of patterns) {
    text = text.replace(regex, replacement);
}

fs.writeFileSync('app/views/recipes/create.php', text, 'utf8');
console.log("JS Regex Fix Done");
