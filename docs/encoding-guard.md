# Encoding Guard

Project uses UTF-8 text files. To prevent Vietnamese mojibake (`Khám`, `công thức`, ...), this repo enforces a pre-commit check.

## Recommended UTF-8 setup (PHP + MySQL)

1. Database

```sql
CREATE DATABASE cooking_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

For tables:

```sql
CREATE TABLE recipes (
  name VARCHAR(255)
) CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

2. PHP PDO connection

```php
$pdo = new PDO(
  "mysql:host=localhost;dbname=cooking_db;charset=utf8mb4",
  "root",
  ""
);
```

3. HTTP header

```php
header('Content-Type: text/html; charset=UTF-8');
```

4. VSCode

- Bottom-right encoding: `UTF-8`
- Or workspace setting:

```json
"files.encoding": "utf8"
```

5. Windows terminal

```powershell
chcp 65001
```

6. Git

```bash
git config --global core.quotepath false
git config --global i18n.commitencoding utf-8
```

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
