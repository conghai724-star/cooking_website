USE cooking_website;

START TRANSACTION;

INSERT INTO roles (role_name, description)
SELECT 'super_admin', 'Toan quyen he thong'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'super_admin');

INSERT INTO roles (role_name, description)
SELECT 'mod', 'Kiem duyet noi dung va bao cao'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'mod');

INSERT INTO roles (role_name, description)
SELECT 'support', 'Ho tro xu ly bao cao va thong bao'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'support');

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.role_name IN ('super_admin', 'mod', 'support');

-- super_admin: full permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'super_admin';

-- mod: moderation-focused permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_name IN (
    'admin.dashboard.view',
    'admin.reports.manage',
    'admin.comments.manage',
    'admin.recipes.review',
    'admin.ingredients.review',
    'admin.tips.review'
)
WHERE r.role_name = 'mod';

-- support: report workflow + visibility
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_name IN (
    'admin.dashboard.view',
    'admin.reports.manage',
    'admin.system.notifications.manage',
    'admin.stats.view'
)
WHERE r.role_name = 'support';

COMMIT;
