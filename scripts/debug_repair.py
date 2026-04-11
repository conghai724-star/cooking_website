import json

def get_mojibake(char):
    try:
        return char.encode('utf-8').decode('latin-1')
    except: return char

viet_chars = "áàảãạâấầẩẫậăắằẳẵặđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵ"
viet_chars += viet_chars.upper()

component_fixes = {}
for c in viet_chars:
    m = get_mojibake(c)
    if m != c:
        component_fixes[m] = c

# Double
double_fixes = {}
for m, c in component_fixes.items():
    dm = get_mojibake(m)
    if dm != m:
        double_fixes[dm] = c
component_fixes.update(double_fixes)

def repair_string(text):
    temp = text
    for corrupted, correct in sorted(component_fixes.items(), key=lambda x: len(x[0]), reverse=True):
        temp = temp.replace(corrupted, correct)
    return temp

with open('scripts/discovered_patterns.json', 'r', encoding='utf-8') as f:
    patterns = json.load(f)

debug_data = []
for p in patterns:
    repaired = repair_string(p)
    debug_data.append({
        "original": p,
        "repaired": repaired,
        "diff": p != repaired
    })

with open('scripts/debug_repair.json', 'w', encoding='utf-8') as f:
    json.dump(debug_data, f, ensure_ascii=True, indent=2)

print(f"Saved debug data for {len(debug_data)} patterns.")
