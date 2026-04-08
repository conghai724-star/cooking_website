#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Mojibake Fixer - Precise Vietnamese Text Encoding Correction

This script fixes mojibake (encoding corruption) in Vietnamese text by applying
only the specific mappings from mojibake-fixes.json. It performs unidirectional
replacements to avoid corrupting correctly-encoded text.

Usage:
    python fix_mojibake.py [--dry-run] [--root app/views] [--output-changes changes.log]

Options:
    --dry-run: Show what would be changed without modifying files
    --root: Root directory to scan (default: app/views)
    --output-changes: File to log changes (default: storage/logs/mojibake_fixes.log)
"""

import os
import json
import argparse
import logging
from pathlib import Path

def load_fixes(fixes_file):
    """Load the mojibake fixes dictionary."""
    try:
        with open(fixes_file, 'r', encoding='utf-8-sig') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"Error: Fixes file not found: {fixes_file}")
        return {}
    except json.JSONDecodeError as e:
        print(f"Error: Invalid JSON in fixes file: {e}")
        return {}

def should_process_file(file_path):
    """Check if file should be processed based on extension."""
    extensions = {'.php', '.html', '.js', '.css', '.md', '.sql', '.json'}
    return file_path.suffix.lower() in extensions

def fix_file(file_path, fixes, dry_run=False, logger=None):
    """Fix mojibake in a single file using exact string replacements."""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
    except UnicodeDecodeError:
        logger.warning(f"Skipping {file_path}: Cannot decode as UTF-8")
        return False
    except Exception as e:
        logger.error(f"Error reading {file_path}: {e}")
        return False

    original_content = content
    changes_made = []

    # Apply fixes in order (longest first to avoid partial matches)
    sorted_fixes = sorted(fixes.items(), key=lambda x: len(x[0]), reverse=True)

    for corrupted, correct in sorted_fixes:
        if corrupted in content:
            content = content.replace(corrupted, correct)
            changes_made.append(f"'{corrupted}' -> '{correct}'")

    if content != original_content:
        if dry_run:
            logger.info(f"Would fix {file_path}: {', '.join(changes_made)}")
        else:
            try:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                logger.info(f"Fixed {file_path}: {', '.join(changes_made)}")
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
