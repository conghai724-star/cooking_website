USE cooking_website;

-- 1) Ensure report permissions exist
SELECT id, permission_name
FROM permissions
WHERE permission_name IN (
    'user.recipes.report',
    'user.ingredients.report',
    'user.tips.report'
)
ORDER BY id;

-- 2) Check role -> permission mapping for user role
SELECT r.role_name, p.permission_name
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name = 'user'
  AND p.permission_name IN (
      'user.recipes.report',
      'user.ingredients.report',
      'user.tips.report'
  )
ORDER BY p.permission_name;

-- 3) Check a specific account role + report permissions (change email before run)
SELECT u.id, u.email, u.role, p.permission_name
FROM users u
INNER JOIN roles r ON r.role_name = u.role
INNER JOIN role_permissions rp ON rp.role_id = r.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE u.email = 'your_email@example.com'
  AND p.permission_name IN (
      'user.recipes.report',
      'user.ingredients.report',
      'user.tips.report'
  )
ORDER BY p.permission_name;

-- 4) Optional: inspect recent report actions of one user (change user id before run)
SELECT id, reporter_id, target_type, target_id, status, created_at
FROM reports
WHERE reporter_id = 1
  AND target_type IN ('recipe', 'ingredient', 'tip')
ORDER BY created_at DESC
LIMIT 50;
