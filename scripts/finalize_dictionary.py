import json

with open('scripts/debug_repair.json', 'r', encoding='utf-8') as f:
    debug_data = json.load(f)

final_fixes = {}
for item in debug_data:
    if item['diff']:
        # Only add if the repaired version is definitely Vietnamese
        repaired = item['repaired']
        if any(c in repaired for c in "áàảãạâấầẩẫậăắằẳẵặđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵ"):
            # Ensure no mojibake markers remain
            if not any(c in repaired for c in "ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿"):
                final_fixes[item['original']] = item['repaired']

# Add some manual high-frequency ones we know are missing
manual = {
    "TiĂªu Ä‘á» ": "Tiêu đề",
    "TĂ¡c giáº£": "Tác giả",
    "Tráº¡ng thĂ¡i": "Trạng thái",
    "HĂ nh Ä‘á»™ng": "Hành động",
    "Ä Ă£ duyá»‡t": "Đã duyệt",
    "KhĂ´ng rĂµ": "Không rõ",
    "Tá»« chá»‘i": "Từ chối",
    "ChÆ°a cĂ³": "Chưa có",
    "Bá»™ cĂ¢u há» i": "Bộ câu hỏi",
    "nA»™i dung": "nội dung",
    "ngA°A» i": "người",
    "xA»­ lĂ½": "xử lý",
    "xA¡c nhAº­n": "xác nhận",
    "ThA» i gian": "Thời gian",
    "bĂ¬nh luAº­n": "bình luận",
    "KhĂ´i phA»¥c": "Khôi phục",
}
final_fixes.update(manual)

with open('scripts/mojibake-fixes.json', 'w', encoding='utf-8') as f:
    json.dump(final_fixes, f, ensure_ascii=True, indent=2)

print(f"Finalized dictionary with {len(final_fixes)} safe replacements.")
