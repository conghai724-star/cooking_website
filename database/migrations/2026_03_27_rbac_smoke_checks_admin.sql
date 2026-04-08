USE cooking_website;

-- 1) List all admin roles currently in system
SELECT id, role_name
FROM roles
WHERE role_name IN ('super_admin', 'mod', 'support')
ORDER BY role_name;

-- 2) Count permissions per admin role
SELECT r.role_name, COUNT(*) AS total_permissions
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.role_name IN ('super_admin', 'mod', 'support')
GROUP BY r.role_name
ORDER BY r.role_name;

-- 3) Full permission matrix for admin roles
SELECT r.role_name, p.permission_name
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name IN ('super_admin', 'mod', 'support')
ORDER BY p.permission_name, r.role_name;

-- 4) Critical permissions check (should match your policy)
SELECT p.permission_name,
       MAX(CASE WHEN r.role_name = 'super_admin' THEN 1 ELSE 0 END) AS super_admin_has,
       MAX(CASE WHEN r.role_name = 'mod' THEN 1 ELSE 0 END) AS mod_has,
       MAX(CASE WHEN r.role_name = 'support' THEN 1 ELSE 0 END) AS support_has
FROM permissions p
LEFT JOIN role_permissions rp ON rp.permission_id = p.id
LEFT JOIN roles r ON r.id = rp.role_id
WHERE p.permission_name IN (
    'admin.dashboard.view',
    'admin.users.view',
    'admin.users.manage',
    'admin.users.ban',
    'admin.users.role.assign',
    'admin.roles.manage',
    'admin.reports.view',
    'admin.reports.resolve',
    'admin.stats.view',
    'admin.logs.view',
    'admin.notifications.manage',
    'admin.relationships.view',
    'admin.relationships.moderate'
)
GROUP BY p.permission_name
ORDER BY p.permission_name;

-- 5) Check one specific admin account (change email before run)
SELECT u.id, u.email, u.role, p.permission_name
FROM users u
INNER JOIN roles r ON r.role_name = u.role
INNER JOIN role_permissions rp ON rp.role_id = r.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE u.email = 'admin_email@example.com'
ORDER BY p.permission_name;
