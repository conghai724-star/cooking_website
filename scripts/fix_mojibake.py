#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Mojibake Fixer - Automatic Vietnamese Text Encoding Correction

This script fixes mojibake (encoding corruption) in Vietnamese text by applying
the decodeURIComponent(escape(str)) method to automatically correct UTF-8
bytes interpreted as Latin-1.

Usage:
    python fix_mojibake.py [--dry-run] [--root app/views] [--output-changes changes.log]

Options:
    --dry-run: Show what would be changed without modifying files
    --root: Root directory to scan (default: app/views)
    --output-changes: File to log changes (default: storage/logs/mojibake_fixes.log)
"""

import argparse
import json
import logging
import re
from pathlib import Path

MOJIBAKE_MARKER_REGEX = re.compile(r'[ÃÂÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßâäàáãåéèêëìíîïñòóôõöùúûüýþœšž¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿]')
VIETNAMESE_CHAR_REGEX = re.compile(r'[ắằẳẵặâấầẩẫậăáàảãạđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵĂÂÊÔƠƯĐ]')

try:
    import ftfy
except ImportError:
    ftfy = None


def load_fixes(fixes_file):
    """Load the mojibake fixes dictionary."""
    try:
        with open(fixes_file, 'r', encoding='utf-8-sig') as f:
            return json.load(f)
    except FileNotFoundError:
        print("Error: Fixes file not found: {}".format(fixes_file))
        return {}
    except json.JSONDecodeError as e:
        print("Error: Invalid JSON in fixes file: {}".format(e))
        return {}


def should_process_file(file_path):
    """Check if file should be processed based on extension."""
    extensions = {'.php', '.html', '.js', '.css', '.md', '.sql', '.json'}
    return file_path.suffix.lower() in extensions


def mojibake_score(text):
    """Score text by how many mojibake markers it contains."""
    if not isinstance(text, str):
        return 0

    marker_count = len(MOJIBAKE_MARKER_REGEX.findall(text))
    marker_count += text.count('�') * 10
    return marker_count


def vietnamese_score(text):
    """Score how likely text contains Vietnamese diacritics."""
    if not isinstance(text, str):
        return 0
    return len(VIETNAMESE_CHAR_REGEX.findall(text))


def is_suspicious_text(text):
    """Detect text containing likely mojibake patterns."""
    if not isinstance(text, str):
        return False
    if '�' in text:
        return True
    return bool(MOJIBAKE_MARKER_REGEX.search(text))


def repair_text(text):
    """Try to repair mojibake in a text block using encoding heuristics."""
    if not is_suspicious_text(text):
        return text, None

    original_score = (mojibake_score(text), -vietnamese_score(text))
    best_text = text
    best_reason = None
    best_score = original_score

    def candidate_score(candidate):
        return mojibake_score(candidate), -vietnamese_score(candidate)

    def js_style_fix(text):
        try:
            return text.encode('latin1').decode('utf-8')
        except:
            return text

    candidates = [('original', text)]
    if ftfy is not None:
        candidates.append(('ftfy.fix_text', ftfy.fix_text(text)))
        candidates.append(('ftfy.fix_encoding', ftfy.fix_encoding(text)))

    candidates.append(('latin1->utf8 (js style)', js_style_fix(text)))

    for source_encoding in ('latin1', 'cp1252', 'cp1258', 'cp1250'):
        try:
            converted = text.encode(source_encoding, errors='replace').decode('utf-8', errors='replace')
            candidates.append(('{source_encoding}->utf8'.format(source_encoding=source_encoding), converted))
        except Exception:
            pass
        try:
            converted = text.encode('utf-8', errors='replace').decode(source_encoding, errors='replace')
            candidates.append(('utf8->{source_encoding}'.format(source_encoding=source_encoding), converted))
        except Exception:
            pass

    for reason, candidate in candidates:
        if candidate == text:
            continue
        score = candidate_score(candidate)
        if score < best_score:
            best_score = score
            best_text = candidate
            best_reason = reason

    return best_text, best_reason


def decode_content(raw_bytes):
    """Decode file bytes as UTF-8 when possible, else preserve raw bytes with Latin1."""
    try:
        return raw_bytes.decode('utf-8'), True
    except UnicodeDecodeError:
        return raw_bytes.decode('latin1'), False


def fix_file(file_path, fixes, dry_run=False, logger=None):
    """Fix mojibake in a single file using exact replacements and segment repairs."""
    try:
        raw_bytes = file_path.read_bytes()
    except Exception as e:
        logger.error("Error reading {}: {}".format(file_path, e))
        return False

    content, is_utf8 = decode_content(raw_bytes)
    original_content = content
    changes_made = []

    # Apply fixes in order (longest first to avoid partial matches)
    sorted_fixes = sorted(fixes.items(), key=lambda x: len(x[0]), reverse=True)
    for corrupted, correct in sorted_fixes:
        if corrupted in content:
            content = content.replace(corrupted, correct)
            changes_made.append(f"'{corrupted}' -> '{correct}'")

    # Always run auto repair if text is suspicious
    if is_suspicious_text(content):
        repaired_content, reason = repair_text(content)
        if repaired_content != content:
            content = repaired_content
            changes_made.append(f"auto repair ({reason})")

    if content != original_content:
        if dry_run:
            logger.info(f"Would fix {file_path}: {', '.join(changes_made) or 'auto repair'}")
        else:
            try:
                file_path.write_text(content, encoding='utf-8')
                logger.info(f"Fixed {file_path}: {', '.join(changes_made) or 'auto repair'}")
            except Exception as e:
                logger.error(f"Error writing {file_path}: {e}")
                return False
        return True

    return False

def main():
    parser = argparse.ArgumentParser(description='Fix mojibake in Vietnamese text files')
    parser.add_argument('--dry-run', action='store_true', help='Show changes without modifying files')
    parser.add_argument('--root', default='app/views', help='Root directory to scan')
    parser.add_argument('--fixes-file', default='scripts/mojibake-fixes.json', help='Path to fixes JSON file')
    parser.add_argument('--output-changes', default='storage/logs/mojibake_fixes.log', help='Log file for changes')

    args = parser.parse_args()

    # Setup logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(args.output_changes, encoding='utf-8'),
            logging.StreamHandler()
        ]
    )
    logger = logging.getLogger(__name__)

    # Load fixes
    fixes = load_fixes(args.fixes_file)
    if not fixes:
        logger.error("No fixes loaded. Exiting.")
        return 1

    logger.info(f"Loaded {len(fixes)} mojibake fixes")

    # Find files
    root_path = Path(args.root)
    if not root_path.exists():
        logger.error(f"Root path not found: {root_path}")
        return 1

    files = [f for f in root_path.rglob('*') if f.is_file() and should_process_file(f)]
    logger.info(f"Found {len(files)} files to check")

    # Process files
    fixed_count = 0
    for file_path in files:
        if fix_file(file_path, fixes, args.dry_run, logger):
            fixed_count += 1

    if args.dry_run:
        logger.info(f"Dry run complete. Would fix {fixed_count} files.")
    else:
        logger.info(f"Fixed {fixed_count} files.")

    return 0

if __name__ == '__main__':
    exit(main())
