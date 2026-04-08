# Encoding Guard

Project uses UTF-8 text files. To prevent Vietnamese mojibake (`Kh�m`, `c�ng th?c`, ...), this repo enforces a pre-commit check.

## One-time setup

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-githooks.ps1
```

## Manual check

```powershell
powershell -ExecutionPolicy Bypass -File scripts/check-mojibake.ps1
```

## Auto-fix helper

```powershell
powershell -ExecutionPolicy Bypass -File scripts/fix_mojibake.ps1 -Apply
```

Then re-check and commit again.
