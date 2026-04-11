import json

def get_mojibake(char):
    try:
        # Standard Double UTF-8 (C3 XX)
        return char.encode('utf-8').decode('latin-1')
    except: return char

def get_stripped_mojibake(char):
    try:
        # Stripped High Bit variant (E1 -> A)
        b = char.encode('utf-8')
        if b[0] == 0xE1:
            # Replace E1 with 'A' (41) and use the rest as Latin-1
            mojibake = chr(0x41) + b[1:].decode('latin-1')
            # Some systems also insert a replacement char (EF BF BD)
            mojibake_repl = chr(0x41) + '\ufffd' + b[1:].decode('latin-1')
            return [mojibake, mojibake_repl]
        return []
    except: return []

viet_chars = "áàảãạâấầẩẫậăắằẳẵặđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵ"
viet_chars += viet_chars.upper()

component_fixes = {}
for c in viet_chars:
    # Standard mojibake
    m = get_mojibake(c)
    if m != c:
        component_fixes[m] = c
    
    # Stripped variants
    for sm in get_stripped_mojibake(c):
        component_fixes[sm] = c

# Add manual whole-word overrides from previous successful fixes
manual = {
    "Ti\u0102\u00aa\u00ad u \u0111\u00e1\u00bb\u00a0": "Ti\u00eau \u0111\u1ec1",
    "Tr\u0103\u00a1ng th\u0103\u00a1i": "Tr\u1ea1ng th\u00e1i",
    "H\u0103\u00a1nh \u0111\u1003\u00b3\u00a2\u00a2\u2122ng": "H\u00e0nh \u0111\u1ed9ng",
    "T\u0102\u00a1c gi\u0103\u00ba\u00a3": "T\u00e1c gi\u1ea3",
    "xA\u00bb\u00ad l\u0102\u00bd": "x\u1eed l\u00fd",
    "Thao t\u0103\u00a1c": "Thao t\u00e1c",
}
component_fixes.update(manual)

def repair_string(text):
    temp = text
    # Replace longest patterns first
    for corrupted, correct in sorted(component_fixes.items(), key=lambda x: len(x[0]), reverse=True):
        temp = temp.replace(corrupted, correct)
    return temp

with open('scripts/discovered_patterns.json', 'r', encoding='utf-8') as f:
    patterns = json.load(f)

final_fixes = {}
for p in patterns:
    repaired = repair_string(p)
    if repaired != p:
        # High confidence check
        if not any(c in repaired for c in "ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿"):
            if any(c in repaired for c in viet_chars):
                final_fixes[p] = repaired

with open('scripts/mojibake-fixes.json', 'w', encoding='utf-8') as f:
    json.dump(final_fixes, f, ensure_ascii=True, indent=2)

print(f"Global Dictionary built with {len(final_fixes)} safe replacements.")
