CREATE OR REPLACE VIEW view_users AS
SELECT * FROM users u
WHERE deleted_at IS NULL;

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

-- View: view_prescription_item - prescription line items
CREATE OR REPLACE VIEW view_prescription_item AS
SELECT
    pi.id,
    pi.prescription_id,
    pi.medicine_id,
    pi.vaccine_id,
    pi.dosage,
    pi.frequency,
    pi.route,
    pi.duration_days,
    pi.quantity_prescribed,
    pi.instructions,
    pi.filled_date,
    pi.created_at,
    pi.updated_at,
    pi.deleted_at
FROM prescription_item pi
WHERE pi.deleted_at IS NULL;

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
    di.id,
    di.agent_1_type,
    di.agent_1_id,
    di.agent_2_type,
    di.agent_2_id,
    di.severity,
    di.description,
    di.recommendation,

    -- Agent 1 resolved name
    CASE di.agent_1_type
        WHEN 'medicine' THEN m1.generic_name
        WHEN 'vaccine'  THEN v1.name
    END AS agent_1_name,
    CASE di.agent_1_type
        WHEN 'medicine' THEN m1.brand_name
        ELSE NULL
    END AS agent_1_brand,

    -- Agent 2 resolved name
    CASE di.agent_2_type
        WHEN 'medicine' THEN m2.generic_name
        WHEN 'vaccine'  THEN v2.name
    END AS agent_2_name,
    CASE di.agent_2_type
        WHEN 'medicine' THEN m2.brand_name
        ELSE NULL
    END AS agent_2_brand

FROM drug_interaction di
LEFT JOIN medicine m1 ON di.agent_1_type = 'medicine' AND m1.id = di.agent_1_id
LEFT JOIN vaccine  v1 ON di.agent_1_type = 'vaccine'  AND v1.id = di.agent_1_id
LEFT JOIN medicine m2 ON di.agent_2_type = 'medicine' AND m2.id = di.agent_2_id
LEFT JOIN vaccine  v2 ON di.agent_2_type = 'vaccine'  AND v2.id = di.agent_2_id;


-- View: view_patient_summary gives one row per active patient with their
-- active diagnoses, active prescriptions, allergies, and upcoming visits
-- aggregated as JSON arrays.
CREATE OR REPLACE VIEW view_patient_summary AS
SELECT
    u.id                                                        AS patient_id,
    u.firstname,
    u.lastname,
    u.middlename,
    u.prefix,
    u.age,
    u.status                                                    AS age_group,
    u.blood,
    u.gender,
    u.phone,
    u.email,

    COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id',         d.id,
                'condition',  d.`condition`,
                'severity',   d.severity,
                'notes',      d.notes,
                'created_at', d.created_at
            )
        )
        FROM diagnosis d
        WHERE d.patient_id = u.id
          AND d.deleted_at IS NULL),
        JSON_ARRAY()
    )                                                           AS active_diagnoses,

    COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id',          p.id,
                'doctor_id',   p.doctor_id,
                'issue_date',  p.issue_date,
                'expire_date', p.expire_date,
                'status',      p.status,
                'notes',       p.notes
            )
        )
        FROM prescription p
        WHERE p.patient_id = u.id
          AND p.status = 'active'),
        JSON_ARRAY()
    )                                                           AS active_prescriptions,

    COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'allergy_id',   a.id,
                'allergy_name', a.allergy_name,
                'allergy_type', a.allergy_type,
                'reaction',     ua.reaction,
                'severity',     ua.severity
            )
        )
        FROM user_allergy ua
        JOIN allergy a ON a.id = ua.allergy_id
        WHERE ua.user_id = u.id
          AND a.deleted_at IS NULL),
        JSON_ARRAY()
    )                                                           AS allergies,

    COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'visit_id',       v.id,
                'institution_id', v.institution_id,
                'visit_type',     v.visit_type,
                'scheduled_at',   v.scheduled_at,
                'reason',         v.reason
            )
        )
        FROM visit v
        WHERE v.patient_id = u.id
          AND v.deleted_at IS NULL
          AND v.status = 'SCHEDULED'
          AND v.scheduled_at >= NOW()),
        JSON_ARRAY()
    )                                                           AS upcoming_visits

FROM view_users u
WHERE u.id IN (
    SELECT user_id FROM user_role WHERE role = 'PATIENT'
);

-- View Visits

CREATE OR REPLACE VIEW view_visits AS
SELECT
    v.id,
    v.patient_id,
    full_name(u.id) AS patient_name,
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
    full_name(d.id) AS doctor_name,
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
    full_name(ua.user_id) AS user_name,
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

CREATE OR REPLACE VIEW view_active_institutions AS
    SELECT * FROM institution i
    WHERE i.deleted_at IS NULL;

CREATE OR REPLACE VIEW view_full_visit AS
SELECT
    v.id                     AS visit_id,
    v.patient_id,
    v.patient_name,
    v.institution_id,
    v.institution_name,
    v.institution_type,
    v.visit_type,
    v.scheduled_at,
    v.status                 AS visit_status,
    v.reason,
    v.notes                  AS visit_notes,
    v.created_at             AS visit_created_at,
    v.updated_at             AS visit_updated_at,
    dv.id                    AS doctor_visit_id,
    dv.visit_id              AS doctor_visit_ref_id,
    dv.doctor_id,
    dv.doctor_name,
    dv.doctor_notes,
    dv.diagnosis_summary,
    dv.created_at            AS doctor_visit_created_at,
    dv.updated_at            AS doctor_visit_updated_at
FROM view_visits v
LEFT JOIN view_doctor_visits dv
    ON v.id = dv.visit_id;

CREATE OR REPLACE VIEW view_active_allergies AS
SELECT
    ua.id,
    ua.user_id,
    full_name(ua.user_id) AS user_name,
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

CREATE OR REPLACE VIEW view_parent_relationships AS
SELECT
    pr.parent_relationship_id,
    pr.parent_id,
    full_name(pr.parent_id) AS parent_name,
    pr.patient_id,
    full_name(pr.patient_id) AS patient_name,
    pr.relationship
FROM parent_relationship pr;

CREATE OR REPLACE VIEW view_expired_prescriptions AS
SELECT
    p.id,
    p.patient_id,
    full_name(p.patient_id) AS patient_name,
    p.doctor_id,
    full_name(p.doctor_id) AS doctor_name,
    p.issue_date,
    p.expire_date,
    p.status,
    p.notes,
    DATEDIFF(NOW(), p.expire_date) AS days_since_expired
FROM prescription p
WHERE p.expire_date < NOW()
   OR p.status = 'expired';

-- View: view_active_vaccines - returns all active (non-deleted) vaccines
CREATE OR REPLACE VIEW view_active_vaccines AS
SELECT * FROM vaccine v
WHERE v.deleted_at IS NULL;

CREATE OR REPLACE VIEW view_active_out_vaccines AS
SELECT * FROM view_active_vaccines
         WHERE development = 'RELEASED'
           AND status = 'Active';

CREATE OR REPLACE VIEW view_active_medicines AS
SELECT * FROM medicine m
WHERE m.deleted_at IS NULL;

-- View: view_medicine_search - search medicines (base view, use function for search)
CREATE OR REPLACE VIEW view_medicine_search AS
SELECT * FROM view_active_medicines;

-- View: view_vaccine_details - returns vaccine details with status info
CREATE OR REPLACE VIEW view_vaccine_details AS
SELECT
    v.id,
    v.name,
    v.cvx_code,
    v.status,
    v.last_updated_date,
    v.manufacturer,
    v.type,
    v.development,
    v.recommended_age,
    v.dose_count,
    v.lethal_dose_mg_per_kg,
    v.lethal_dose_route,
    v.lethal_dose_source,
    v.extra,
    v.created_at,
    v.updated_at
FROM view_active_vaccines v;

-- View: view_vaccines_by_type - returns vaccines grouped by type
CREATE OR REPLACE VIEW view_vaccines_by_type AS
SELECT
    v.type,
    COUNT(*) AS vaccine_count,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'id', v.id,
            'name', v.name,
            'cvx_code', v.cvx_code,
            'status', v.status,
            'development', v.development
        )
    ) AS vaccines
FROM view_active_vaccines v
GROUP BY v.type;

-- View: view_vaccines_by_development - returns vaccines grouped by development status
CREATE OR REPLACE VIEW view_vaccines_by_development AS
SELECT
    v.development,
    COUNT(*) AS vaccine_count,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'id', v.id,
            'name', v.name,
            'cvx_code', v.cvx_code,
            'type', v.type
        )
    ) AS vaccines
FROM view_active_vaccines v
GROUP BY v.development;

-- View: view_vaccine_count_by_development - returns count of vaccines by development status
CREATE OR REPLACE VIEW view_vaccine_count_by_development AS
SELECT
    v.development,
    COUNT(*) AS count
FROM view_active_vaccines v
GROUP BY v.development;

-- View: view_active_vaccines
CREATE OR REPLACE VIEW view_active_vaccines AS
SELECT * FROM vaccine
WHERE deleted_at IS NULL;

-- View: view_vaccine_by_type
CREATE OR REPLACE VIEW view_vaccine_by_type AS
SELECT id, name, cvx_code, status, type, development, manufacturer, recommended_age
FROM vaccine
WHERE deleted_at IS NULL;

-- View: view_discontinued_vaccines
CREATE OR REPLACE VIEW view_discontinued_vaccines AS
SELECT id, name, cvx_code, status, type, development, manufacturer, last_updated_date
FROM vaccine
WHERE development = 'DISCONTINUED' AND deleted_at IS NULL;

CREATE OR REPLACE VIEW view_vaccine_count AS
SELECT vaccine_count AS count, type FROM view_vaccines_by_type;


-- View: view_all_employees - all non-deleted users with staff roles
CREATE OR REPLACE VIEW view_all_employees AS
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
    u.employid,
    u.created_at,
    ur.role,
    ur.assigned_at
FROM users u
JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL
  AND ur.role != 'PATIENT';

-- View: view_admins - all administrators
CREATE OR REPLACE VIEW view_admins AS
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
    u.adminid,
    u.created_at,
    ur.assigned_at
FROM users u
JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL
  AND ur.role = 'ADMIN';

-- View: view_doctors - all physicians and surgeons
CREATE OR REPLACE VIEW view_doctors AS
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
    u.employid,
    u.created_at,
    ur.role,
    ur.assigned_at
FROM users u
JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL
  AND ur.role IN ('PHYSICIAN', 'SURGEON');

-- View: view_nurses - all nurses
CREATE OR REPLACE VIEW view_nurses AS
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
    u.employid,
    u.created_at,
    ur.role,
    ur.assigned_at
FROM users u
JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL
  AND ur.role = 'NURSE';

-- View: view_pharmacists - all pharmacists
CREATE OR REPLACE VIEW view_pharmacists AS
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
    u.employid,
    u.created_at,
    ur.role,
    ur.assigned_at
FROM users u
JOIN user_role ur ON ur.user_id = u.id
WHERE u.deleted_at IS NULL
  AND ur.role = 'PHARMACIST';

-- View: view_staff_by_institution - staff members at a specific institution
CREATE OR REPLACE VIEW view_staff_by_institution AS
SELECT
    iu.id AS institution_user_id,
    iu.institution_id,
    i.name AS institution_name,
    iu.user_id,
    u.firstname,
    u.lastname,
    u.email,
    u.phone,
    iu.role,
    iu.created_at
FROM institution_user iu
JOIN institution i ON i.id = iu.institution_id
JOIN users u ON u.id = iu.user_id
WHERE iu.deleted_at IS NULL
  AND i.deleted_at IS NULL;

--- ============================================================
--- Employee Views for Admin
--- ============================================================

-- View: view_institution_staff - all staff members at an institution (excluding patients)
CREATE OR REPLACE VIEW view_institution_staff AS
SELECT
    iu.id,
    iu.institution_id,
    iu.user_id,
    iu.role,
    iu.created_at,
    iu.updated_at,
    u.firstname,
    u.lastname,
    u.middlename,
    u.prefix,
    u.suffix,
    u.gender,
    u.phone,
    u.email,
    u.location,
    u.employid
FROM institution_user iu
         JOIN users u ON u.id = iu.user_id
WHERE iu.deleted_at IS NULL AND u.deleted_at IS NULL;

-- View: view_institution_doctors - doctors at an institution
CREATE OR REPLACE VIEW view_institution_doctors AS
SELECT * FROM view_institution_staff
WHERE role IN ('PHYSICIAN', 'SURGEON', 'RADIOLOGIST');

-- View: view_institution_nurses - nurses at an institution
CREATE OR REPLACE VIEW view_institution_nurses AS
SELECT * FROM view_institution_staff
WHERE role = 'NURSE';

-- View: view_institution_pharmacists - pharmacists at an institution
CREATE OR REPLACE VIEW view_institution_pharmacists AS
SELECT * FROM view_institution_staff
WHERE role = 'PHARMACIST';

-- View: view_institution_admins - other admins (not current user)
CREATE OR REPLACE VIEW view_institution_admins AS
SELECT * FROM view_institution_staff
WHERE role = 'ADMIN';

-- View: view_all_staff - all staff roles across all institutions
CREATE OR REPLACE VIEW view_all_staff AS
SELECT
    iu.user_id,
    iu.role,
    iu.institution_id,
    i.name AS institution_name,
    u.firstname,
    u.lastname,
    u.email,
    u.employid
FROM institution_user iu
         JOIN institution i ON i.id = iu.institution_id
         JOIN users u ON u.id = iu.user_id
WHERE iu.deleted_at IS NULL AND u.deleted_at IS NULL AND i.deleted_at IS NULL;


-- View: view_my_institutions - institutions assigned to a staff member (by user_id)
-- Includes institution name, type, address, and contact info. Used by EmployedTrait::viewMyInstitutions().
CREATE OR REPLACE VIEW view_my_institutions AS
SELECT
    iu.user_id,
    iu.institution_id,
    iu.role,
    iu.created_at  AS joined_at,
    i.name         AS institution_name,
    i.institution_type,
    i.address,
    i.phone        AS institution_phone,
    i.email        AS institution_email
FROM institution_user iu
JOIN institution i ON i.id = iu.institution_id
WHERE iu.deleted_at IS NULL
  AND i.deleted_at  IS NULL;

-- View: view_guardian_dependents - patients linked to a guardian (for patient dashboard)
-- Returns basic info about each dependent patient. Used by Guardian::getMyPatients() display.
CREATE OR REPLACE VIEW view_guardian_dependents AS
SELECT
    pr.parent_id                                                AS guardian_id,
    pr.relationship,
    u.id                                                        AS patient_id,
    u.firstname                                                 AS patient_firstname,
    u.lastname                                                  AS patient_lastname,
    u.age                                                       AS patient_age,
    u.blood                                                     AS patient_blood,
    u.gender                                                    AS patient_gender,
    u.phone                                                     AS patient_phone,
    u.email                                                     AS patient_email
FROM parent_relationship pr
JOIN users u ON u.id = pr.patient_id
WHERE pr.deleted_at IS NULL
  AND u.deleted_at  IS NULL;

-- View: view_patients — flat list of active patients for staff patient-search
CREATE OR REPLACE VIEW view_patients AS
SELECT
    u.id,
    u.firstname,
    u.lastname,
    u.middlename,
    u.age,
    u.blood,
    u.gender,
    u.email,
    u.phone
FROM view_users u
WHERE u.id IN (
    SELECT user_id FROM user_role WHERE role = 'PATIENT'
);

-- View: view_renewal_requests — prescriptions awaiting renewal, with patient and doctor names
CREATE OR REPLACE VIEW view_renewal_requests AS
SELECT
    p.id                AS prescription_id,
    p.patient_id,
    u_pat.firstname     AS patient_firstname,
    u_pat.lastname      AS patient_lastname,
    p.doctor_id,
    u_doc.firstname     AS doctor_firstname,
    u_doc.lastname      AS doctor_lastname,
    p.issue_date,
    p.expire_date,
    p.notes,
    p.status,
    COALESCE(
        (SELECT JSON_ARRAYAGG(m.generic_name)
         FROM prescription_item pi
         JOIN medicine m ON m.id = pi.medicine_id
         WHERE pi.prescription_id = p.id),
        JSON_ARRAY()
    ) AS medicines
FROM prescription p
JOIN users u_pat ON u_pat.id = p.patient_id
JOIN users u_doc ON u_doc.id = p.doctor_id
WHERE p.status = 'renewal_requested';

CREATE OR REPLACE VIEW view_all_interactions AS
    SELECT di.*,
           m.generic_name AS medicine_name,
           v.name AS vaccine_name
    FROM drug_interaction di
             LEFT JOIN view_active_medicines m ON di.agent_1_type = 'medicine' AND di.agent_1_id = m.id
        OR di.agent_2_type = 'medicine' AND di.agent_2_id = m.id
             LEFT JOIN view_active_vaccines v ON di.agent_1_type = 'vaccine' AND di.agent_1_id = v.id
        OR di.agent_2_type = 'vaccine' AND di.agent_2_id = v.id
    AND di.deleted_at IS NULL;

-- View: view_password_reset_tokens - users with valid reset tokens
CREATE OR REPLACE VIEW view_password_reset_tokens AS
SELECT
    prt.id,
    prt.user_id,
    u.email,
    prt.token AS reset_token,
    prt.expires_at AS reset_expires
FROM password_reset_tokens prt
JOIN users u ON prt.user_id = u.id
WHERE prt.used_at IS NULL
  AND prt.expires_at > NOW();
