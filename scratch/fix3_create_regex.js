const fs = require('fs');
let text = fs.readFileSync('app/views/recipes/create.php', 'utf8');

const patterns = [
    [/<h1 class="text-3xl font-black text-slate-900">[^\<]+<\/h1>/gs, '<h1 class="text-3xl font-black text-slate-900">Đăng công thức mới</h1>'],
    [/<p class="mt-1 text-slate-500">[^\<]+<\/p>/gs, '<p class="mt-1 text-slate-500">Thêm món ăn, nguyên liệu, hình ảnh và các bước thực hiện.</p>'],
    [/<h2 class="mb-5 text-xl font-bold">[^\<]+<\/h2>/gs, '<h2 class="mb-5 text-xl font-bold">Thông tin cơ bản</h2>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">Ti[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Tiêu đề</label>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">Danh[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Danh mục</label>'],
    [/<option value="">[^\<]+<\/option>/gs, '<option value="">-- Chọn danh mục --</option>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">A[^\<]+kh[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Độ khó</label>'],
    [/<option value="easy">[^\<]+<\/option>/gs, '<option value="easy">Dễ</option>'],
    [/<option value="medium">[^\<]+<\/option>/gs, '<option value="medium">Trung bình</option>'],
    [/<option value="hard">[^\<]+<\/option>/gs, '<option value="hard">Khó</option>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">Tags m[^\<]+n [^\<]+n<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Tags món ăn</label>'],
    [/<p class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">\s*Ch[^\<]+<\/p>/gs, '<p class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">\n                                Chưa có dữ liệu tags. Hãy chạy migration tags trước.\n                            </p>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">Th[^\<]+gian n[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Thời gian nấu (phút)</label>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">A[^\<]+nh m[^\<]+n A[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Ảnh món ăn</label>'],
    [/<label class="mb-2 block text-sm font-semibold text-slate-700">M[^\<]+t[^\<]+<\/label>/gs, '<label class="mb-2 block text-sm font-semibold text-slate-700">Mô tả</label>'],
    [/<h2 class="text-xl font-bold">Nguy[^\<]+n li[^\<]+u<\/h2>/gs, '<h2 class="text-xl font-bold">Nguyên liệu</h2>'],
    [/<button id="add-ingredient" class="([^"]+)" type="button">\+ Th[^\<]+m nguy[^\<]+n li[^\<]+u<\/button>/gs, '<button id="add-ingredient" class="$1" type="button">+ Thêm nguyên liệu</button>'],
    [/<label class="mb-1 block text-xs font-semibold text-slate-500">T[^\<]+n nguy[^\<]+n li[^\<]+u<\/label>/gs, '<label class="mb-1 block text-xs font-semibold text-slate-500">Tên nguyên liệu</label>'],
    [/<label class="mb-1 block text-xs font-semibold text-slate-500">S[^\<]+ l[^\<]+ng<\/label>/gs, '<label class="mb-1 block text-xs font-semibold text-slate-500">Số lượng</label>'],
    [/<h2 class="text-xl font-bold">C[^\<]+c b[^\<]+c th[^\<]+c hi[^\<]+n<\/h2>/gs, '<h2 class="text-xl font-bold">Các bước thực hiện</h2>'],
    [/<button id="add-step" class="([^"]+)" type="button">\+ Th[^\<]+m b[^\<]+c<\/button>/gs, '<button id="add-step" class="$1" type="button">+ Thêm bước</button>'],
    [/<p class="step-label text-sm font-bold text-slate-700">B[^\<]+c 1<\/p>/gs, '<p class="step-label text-sm font-bold text-slate-700">Bước 1</p>'],
    [/<p class="step-label text-sm font-bold text-slate-700">B[^\<]+c<\/p>/gs, '<p class="step-label text-sm font-bold text-slate-700">Bước</p>'],
    [/<button class="remove-ingredient([^"]*)" type="button">[^\<]+<\/button>/gs, '<button class="remove-ingredient$1" type="button">Xóa</button>'],
    [/<button class="remove-step([^"]*)" type="button">[^\<]+<\/button>/gs, '<button class="remove-step$1" type="button">Xóa</button>'],
    [/<button class="rounded-xl border border-slate-300([^"]*)" type="submit" name="action" value="cancel">[^\<]+<\/button>/gs, '<button class="rounded-xl border border-slate-300$1" type="submit" name="action" value="cancel">Hủy</button>'],
    [/<button class="rounded-xl border border-primary([^"]*)" type="submit" name="action" value="save">[^\<]+<\/button>/gs, '<button class="rounded-xl border border-primary$1" type="submit" name="action" value="save">Lưu</button>'],
    [/<button class="rounded-xl bg-primary([^"]*)" type="submit" name="action" value="submit">[^\<]+<\/button>/gs, '<button class="rounded-xl bg-primary$1" type="submit" name="action" value="submit">Đăng công thức</button>'],
    [/placeholder="V[^\"]+ dA[^\"]+ Th[^\"]+t g[^\"]+"/gs, 'placeholder="Ví dụ: Thịt gà"'],
    [/placeholder="V[^\"]+ dA[^\"]+ Mu[^\"]+"/gs, 'placeholder="Ví dụ: Muối"'],
    [/placeholder="V[^\"]+ dA[^\"]+ 45"/gs, 'placeholder="Ví dụ: 45"'],
    [/placeholder="M[^\"]+ t[^\"]+ b[^\"]+c th[^\"]+c hi[^\"]+n..."/gs, 'placeholder="Mô tả bước thực hiện..."'],
    [/placeholder="mu[^\"]+ng"/gs, 'placeholder="muỗng"'],
    [/`B[^\$\\]+c \$\{index \+ 1\}`/gs, '`Bước ${index + 1}`'],
    [/'Cach nau': 'Cach nau'/, "'Cach nau': 'Cách nấu'"],
    [/'Huong vi': 'Huong vi'/, "'Huong vi': 'Hương vị'"],
    [/'Dinh huong suc khoe': 'Dinh huong suc khoe'/, "'health': 'Định hướng sức khỏe'"],
    [/'Bua an': 'Bua an'/, "'meal': 'Bữa ăn'"]
];

for (const [regex, replacement] of patterns) {
    const before = text;
    text = text.replace(regex, replacement);
    if (before !== text) {
        // console.log("Replaced via", regex);
    }
}

// Special case for $tagTypeLabels because they are just unaccented
text = text.replace(/'method' => 'Cach nau',/g, "'method' => 'Cách nấu',");
text = text.replace(/'taste' => 'Huong vi',/g, "'taste' => 'Hương vị',");
text = text.replace(/'health' => 'Dinh huong suc khoe',/g, "'health' => 'Định hướng sức khỏe',");
text = text.replace(/'meal' => 'Bua an',/g, "'meal' => 'Bữa ăn',");
text = text.replace(/<label class="mb-1 block text-xs font-semibold text-slate-500">Đơn vị<\/label>/g, '<label class="mb-1 block text-xs font-semibold text-slate-500">Đơn vị</label>');

fs.writeFileSync('app/views/recipes/create.php', text, 'utf8');
console.log("Safe Regex Fix Done");
