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

-- View: view_prescription_detail expands each prescription line item
-- with full medicine info and patient/doctor names from view_prescriptions.
CREATE OR REPLACE VIEW view_prescription_detail AS
SELECT
    -- Prescription item
    pi.id                  AS item_id,
    pi.prescription_id,
    pi.dosage,
    pi.frequency,
    pi.route,
    pi.duration_days,
    pi.quantity_prescribed,
    pi.instructions,
    pi.filled_date,

    -- Medicine
    m.id                   AS medicine_id,
    m.generic_name,
    m.brand_name,
    m.drug_class,
    m.form,
    m.standard_dose,
    m.controlled_substance,
    m.requires_prescription,
    m.stock_quantity,
    m.unit_of_measure,
    m.manufacturer,
    m.storage_requirements,

    -- Prescription header (from view_prescriptions for patient/doctor names)
    vp.status,
    vp.issue_date,
    vp.expire_date,
    vp.notes              AS prescription_notes,

    -- Patient details
    vp.patient_id,
    vp.patient_firstname,
    vp.patient_lastname,
    vp.patient_age,
    vp.patient_status,

    -- Doctor details
    vp.doctor_id,
    vp.doctor_prefix,
    vp.doctor_firstname,
    vp.doctor_lastname


FROM prescription_item     pi
    INNER JOIN medicine m ON m.id  = pi.medicine_id
    INNER JOIN view_prescriptions vp ON vp.id = pi.prescription_id;

CREATE OR REPLACE VIEW view_drug_interactions AS
SELECT
    -- Medicine interaction details
    mi.id,
    mi.severity,
    mi.description,
    mi.recommendation,

    -- Medicine details for each side of the interaction

    -- Medicine 1
    m1.id           AS medicine_1_id,
    m1.generic_name AS medicine_1_generic,
    m1.brand_name   AS medicine_1_brand,

    -- Medicine 2
    m2.id           AS medicine_2_id,
    m2.generic_name AS medicine_2_generic,
    m2.brand_name   AS medicine_2_brand
FROM medicine_interaction mi
    INNER JOIN medicine m1 ON m1.id = mi.medicine_1
    INNER JOIN medicine m2 ON m2.id = mi.medicine_2;


-- View Visits

CREATE OR REPLACE VIEW view_visits AS
SELECT
    v.id,
    v.patient_id,
    CONCAT(u.firstname, ' ', u.lastname) AS patient_name,
    v.institution_id,
    i.name AS institution_name,
    i.institution_type,
    v.visit_type,
    v.scheduled_at,
    v.status,
    v.reason,
    v.notes,
    v.created_at,
    v.updated_at
FROM visit v
JOIN users u ON v.patient_id = u.id
JOIN institution i ON v.institution_id = i.id
WHERE v.deleted_at IS NULL
  AND i.deleted_at IS NULL;

-- View Doctor Visits

CREATE OR REPLACE VIEW view_doctor_visits AS
SELECT
    dv.id,
    dv.visit_id,
    dv.doctor_id,
    CONCAT(d.firstname, ' ', d.lastname) AS doctor_name,
    dv.doctor_notes,
    dv.diagnosis_summary,
    dv.created_at,
    dv.updated_at
FROM doctor_visit dv
JOIN users d ON dv.doctor_id = d.id;

-- View Allergies

CREATE OR REPLACE VIEW view_user_allergies AS
SELECT
    ua.id,
    ua.user_id,
    CONCAT(u.firstname, ' ', u.lastname) AS user_name,
    ua.allergy_id,
    a.allergy_name,
    a.allergy_type,
    ua.reaction,
    ua.severity,
    ua.notes,
    ua.recorded_at
FROM user_allergy ua
JOIN users u ON ua.user_id = u.id
JOIN allergy a ON ua.allergy_id = a.id
WHERE a.deleted_at IS NULL;