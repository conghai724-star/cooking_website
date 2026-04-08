USE cooking_website;

-- Update these emails before running.
START TRANSACTION;

UPDATE users
SET role = 'super_admin'
WHERE email IN (
    'owner@example.com'
);

UPDATE users
SET role = 'mod'
WHERE email IN (
    'mod1@example.com',
    'mod2@example.com'
);

UPDATE users
SET role = 'support'
WHERE email IN (
    'support1@example.com'
);

-- Optional quick verification
SELECT id, name, email, role
FROM users
WHERE role IN ('super_admin', 'mod', 'support')
ORDER BY role, id;

COMMIT;
