import sys
import re

MOJIBAKE_MARKER_REGEX = re.compile(r'[ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿]')
VIETNAMESE_CHAR_REGEX = re.compile(r'[ắằẳẵặâấầẩẫậăáàảãạđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵĂÂÊÔƠƯĐ]')

def mojibake_score(text):
    return len(MOJIBAKE_MARKER_REGEX.findall(text))

def vietnamese_score(text):
    return len(VIETNAMESE_CHAR_REGEX.findall(text))

def repair_text(text):
    print(f"Testing: {text}")
    print(f"Mojibake Score: {mojibake_score(text)}")
    print(f"Vietnamese Score: {vietnamese_score(text)}")
    
    candidates = []
    
    # Heuristic 1: latin1 -> utf8
    try:
        candidates.append(('latin1->utf8', text.encode('latin1').decode('utf-8')))
    except: pass
    
    # Heuristic 2: cp1252 -> utf8
    try:
        candidates.append(('cp1252->utf8', text.encode('cp1252').decode('utf-8')))
    except: pass

    for reason, candidate in candidates:
        print(f"Candidate ({reason}): {candidate} | Mojibake: {mojibake_score(candidate)} | Viet: {vietnamese_score(candidate)}")

test_strings = [
    "QuAº£n lĂ½ cĂ´ng thA»©c",
    "nA»™i dung",
    "Bá»™ cĂ¢u há»i",
    "Tiếng Việt chuẩn"
]

for s in test_strings:
    repair_text(s)
    print("-" * 20)
