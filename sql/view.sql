-- View: view_user_roles creates a view of users and their roles excluding deleted users and passwords
CREATE OR REPLACE VIEW view_user_roles AS
SELECT
    u.id,
    u.firstname,
    u.lastname,
    u.middlename,
    u.prefix,
    u.suffix,
    u.gender,
    u.phone,
    u.location,
    u.email,
    u.age,
    u.status,
    u.blood,
    u.extra,
    u.employid,
    u.adminid,
    u.created_at,
    u.updated_at,
    u.deleted_at,
    ur.role,
    ur.assigned_at
FROM users u
         LEFT JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL;

CREATE OR REPLACE VIEW view_users AS
SELECT * FROM users u
WHERE deleted_at IS NULL;

CREATE OR REPLACE VIEW view_deleted_users AS
    SELECT * FROM users u
WHERE deleted_at IS NOT NULL;

CREATE OR REPLACE VIEW total_view AS
SELECT
    id, user_id, action, severity, table_name, record_id,
    old_data, new_data, created_at,
    NULL AS deleted_at
FROM logs
UNION ALL
SELECT
    id, user_id, action, severity, table_name, record_id,
    old_data, new_data, created_at,
    deleted_at
FROM backup_logs;
