import json

# PHASE 3 - RESIDUALS REPAIR DICTIONARY (Aggressive mapping for placeholders)
fixes = {
    # 1. Complex Phrase mapping for Profile/Auth/Recipes
    "nA\ufffd\u00ba\u00a5u A\ufffd\u0103n": "n\u1ea5u \u0103n",
    "chia sA\ufffd\u00ba\u00a3": "chia s\u1ebb",
    "nA\ufffd\u00bb\u2122i dung": "n\u1ed9i dung",
    "mA\ufffd\u00bb\u203a i mA\ufffd\u00bb\u201d i": "m\u1edbi m\u1ed7i",
    "HA\ufffd\u00bb\u201c sA\ufffd\u00a1": "H\u1ed3 s\u01a1",
    "A\ufffd\u2014": "\u2014",
    "A\ufffd\u0111\u0102\u00a3 chA\ufffd\u00ba\u00b7n": "\u0111\u00e3 ch\u1eb7n",
    "ngA\ufffd\u01b0\u00a1\u00a1 i d\u0102\u00b9ng": "ng\u01b0\u1eddi d\u00f9ng",
    "kA\ufffd\u00ba\u00bf ho\u1ea1ch": "k\u1ebf ho\u1ea1ch",
    "bA\ufffd\u00bb\u00bba A\ufffd\u0103n": "b\u1eefa \u0103n",
    "ChA\ufffd\u00b0a c\u0103\u00b3": "Ch\u01b0a c\u00f3",
    "c\u0103\u00b4ng th\u0103\u00ba\u00a3\u00bb\u00a9c": "c\u00f4ng th\u1ee9c",
    "nA\ufffd\u00e0 o": "n\u00e0o",
    "TA\ufffd\u00a0i kho\u0103\u00ba\u00a3n": "T\u00e0i kho\u1ea3n",
    "kh\u0102\u00b4ng kh\u0103\u00ba\u00a3 d\u00bb\u00a5ng": "kh\u00f4ng kh\u1ea3 d\u1ee5ng",
    "th\u0103\u00bb\u00a3ng k\u0102\u00aa": "th\u1ed1ng k\u00ea",
    "hi\u0103\u00ba\u00a3n th\u00bb\u008b": "hi\u1ec3n th\u1ecb",
    
    # 2. Key labels
    "GA\ufffd\u00bb\u2122i": "G\u1eedi",
    "ChA\ufffd\u00bb\u201d n": "Ch\u1ecdn",
    "SA\ufffd\u00bb\u00ada": "S\u1eeda",
    "A\ufffd\u0110\u0102\u00a3ng": "\u0110\u0103ng",
    "Khi\u0102\u00ba\u00bfu n\u1ea1i": "Khi\u1ebfu n\u1ea1i",
    "ChA\ufffd\u00bb\u00a9ng nhA\ufffd\u00ba\u00ad n": "Ch\u1ee9ng nh\u1eadn",
    "Th\u0103\u00a0nh vi\u0102\u00aan": "Th\u00e0nh vi\u00ean",
    
    # 3. Simple leftovers
    "v\u0103\u00a0": "v\u00e0",
    "l\u0102\u00ba\u00ba c": "l\u00fac",
    "n\u0103\u00a0 y": "n\u00e0y",
}

with open('D:/xampp/htdocs/cooking_website/scripts/mojibake-fixes.json', 'w', encoding='utf-8') as f:
    json.dump(fixes, f, ensure_ascii=True, indent=2)

print(f"Phase 3 Dictionary built with {len(fixes)} aggressive patterns.")
