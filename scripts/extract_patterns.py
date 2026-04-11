import json
import re
from pathlib import Path

# Moijbake markers
MOJIBAKE_MARKER_REGEX = re.compile(r'[ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿]')

def extract_patterns(root_dir):
    patterns = set()
    for p in Path(root_dir).rglob('*.php'):
        try:
            content = p.read_text(encoding='utf-8')
            matches = re.finditer(r'[a-zA-Z0-9ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿]+', content)
            for match in matches:
                m = match.group(0)
                if MOJIBAKE_MARKER_REGEX.search(m):
                    patterns.add(m)
        except:
            pass
    return sorted(list(patterns), key=len, reverse=True)

patterns = extract_patterns('app/views/admin')
with open('scripts/discovered_patterns.json', 'w', encoding='utf-8') as f:
    json.dump(patterns, f, ensure_ascii=True, indent=2)
print(f"Saved {len(patterns)} patterns to scripts/discovered_patterns.json")
