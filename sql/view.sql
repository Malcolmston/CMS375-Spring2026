-- View: view_user_roles creates a view of users and their roles excluding deleted users and passwords
CREATE OR REPLACE VIEW view_user_roles AS
SELECT
    id,
    firstname,
    lastname,
    middlename,
    prefix,
    suffix,
    gender,
    phone,
    location,
    email,
    age,
    status,
    blood,
    extra,
    employid,
    adminid,
    created_at,
    updated_at,
    deleted_at,
    role,
    assigned_at
FROM view_user_role_pwd;

CREATE OR REPLACE VIEW view_user_role_pwd  AS
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
    u.password,
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
FROM view_users u
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


CREATE OR REPLACE VIEW view_active_diagnoses AS
    SELECT * FROM diagnosis d
    WHERE d.deleted_at IS NULL;


CREATE OR REPLACE VIEW view_prescriptions AS
SELECT
    -- Prescription details
    p.id,
    p.status,
    p.issue_date,
    p.expire_date,
    p.notes,

    -- Patient details
    p.patient_id,
    patient.firstname  AS patient_firstname,
    patient.lastname   AS patient_lastname,
    patient.age        AS patient_age,
    patient.status     AS patient_status,

    -- Doctor details
    p.doctor_id,
    doctor.prefix      AS doctor_prefix,
    doctor.firstname   AS doctor_firstname,
    doctor.lastname    AS doctor_lastname
FROM prescription p
    INNER JOIN view_users patient ON patient.id = p.patient_id
    INNER JOIN view_users doctor  ON doctor.id  = p.doctor_id;
