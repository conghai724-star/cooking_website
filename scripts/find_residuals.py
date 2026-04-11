import os
import re

ROOT = "D:/xampp/htdocs/cooking_website"
# Focus on common mojibake sequences that shouldn't appear in valid code/text
# These are characteristic of CP1252/UTF-8 double encoding issues.
MOJIBAKE_PATTERNS = [
    r'A\ufffd', # A + Replacement char
    r'\u0102\u00a3', # Ă£
    r'A\u00ba\u00a3', # Aº£
    r'A\u00bb\u2122i', # nội
    r'A\u00bb\u203a', # mới
    r'Ă\u00a2\u20ac\u201c', # Ă¢â‚¬â€œ
    r'A\u00ba\u00ad n', # nhận
    r'A\u00ba\u00af n', # nắn
]

MOJIBAKE_REGEX = re.compile('|'.join(MOJIBAKE_PATTERNS))

def find_corrupted_files():
    corrupted = []
    for root, dirs, files in os.walk(ROOT):
        if any(d in root for d in ['.git', 'vendor', 'storage', 'node_modules', 'scripts']):
            continue
        for file in files:
            if not file.endswith(('.php', '.html', '.js')):
                continue
            path = os.path.join(root, file)
            try:
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    matches = MOJIBAKE_REGEX.findall(content)
                    if matches:
                        rel_path = os.path.relpath(path, ROOT)
                        corrupted.append((rel_path, len(matches)))
            except Exception:
                pass
    
    corrupted.sort(key=lambda x: x[1], reverse=True)
    return corrupted

if __name__ == "__main__":
    files = find_corrupted_files()
    print(f"Found {len(files)} corrupted files with high-confidence mojibake patterns:")
    for path, count in files:
        print(f"{count:5} matches: {path}")
