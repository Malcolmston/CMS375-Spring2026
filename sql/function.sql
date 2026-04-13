DROP FUNCTION IF EXISTS addr_to_point;
CREATE FUNCTION addr_to_point RETURNS STRING SONAME 'addr_to_point.so';

DROP FUNCTION IF EXISTS is_valid_address;
CREATE FUNCTION IF NOT EXISTS is_valid_address RETURNS INTEGER SONAME 'addr_to_point.so';

DROP FUNCTION IF EXISTS nearest_addr;
CREATE FUNCTION IF NOT EXISTS nearest_addr RETURNS STRING SONAME 'addr_to_point.so';

DROP FUNCTION IF EXISTS nearest_point;
CREATE FUNCTION IF NOT EXISTS nearest_point RETURNS STRING SONAME 'addr_to_point.so';

DROP FUNCTION IF EXISTS address_parts;
CREATE FUNCTION IF NOT EXISTS address_parts   RETURNS STRING  SONAME 'addr_to_point.so';

DROP FUNCTION IF EXISTS address_zip;
CREATE FUNCTION IF NOT EXISTS address_zip     RETURNS STRING  SONAME 'addr_to_point.so';"

DROP FUNCTION IF EXISTS has_user;

CREATE FUNCTION has_user(p_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_result BOOLEAN DEFAULT NULL;

    SELECT TRUE INTO v_result
    FROM view_users
    WHERE id = p_id
    LIMIT 1;

    IF v_result IS NOT NULL THEN
        RETURN TRUE;
    END IF;

    SELECT FALSE INTO v_result
    FROM view_deleted_users
    WHERE id = p_id
    LIMIT 1;

    RETURN v_result;
END;

-- ============================================================
-- user_exists_by_email checks if an active (non-deleted) user exists with the given email
-- - p_email is the email address to check
-- - Returns TRUE if an active user exists, FALSE otherwise
-- ============================================================
DROP FUNCTION IF EXISTS user_exists_by_email;

CREATE FUNCTION user_exists_by_email(p_email VARCHAR(255))
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_exists BOOLEAN DEFAULT FALSE;

    SELECT TRUE INTO v_exists
    FROM view_users
    WHERE email = p_email
    LIMIT 1;

    RETURN COALESCE(v_exists, FALSE);
END;

-- ============================================================
-- Is deleted checks if a user has been soft-deleted
-- - p_id is the user's id
-- - Returns true if deleted_at is set, false otherwise
-- ============================================================
DROP FUNCTION IF EXISTS is_deleted;

CREATE FUNCTION is_deleted(p_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_result BOOLEAN DEFAULT FALSE;

    SELECT TRUE INTO v_result
    FROM view_deleted_users
    WHERE id = p_id
    LIMIT 1;

    RETURN COALESCE(v_result, FALSE);
END;

-- ============================================================
-- Has role checks if a user with a given role exists
-- - p_user_id is the user's id
-- - p_role is the role to check for
-- - Returns true if user exists, false otherwise
-- ============================================================
DROP FUNCTION IF EXISTS has_role;

CREATE FUNCTION has_role(p_user_id INT, p_role ENUM(
    'PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH',
    'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'
    ))
    RETURNS BOOLEAN
    DETERMINISTIC
BEGIN
    DECLARE v_has_role BOOLEAN DEFAULT FALSE;

    SELECT TRUE INTO v_has_role
    FROM user_role
    WHERE user_id = p_user_id
      AND role = p_role
    LIMIT 1;

    RETURN COALESCE(v_has_role, FALSE);
END;

DROP FUNCTION IF EXISTS my_diagnosis;

CREATE FUNCTION my_diagnosis (
    user_id INT
)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',         id,
                   'condition',  `condition`,
                   'severity',   severity,
                   'notes',      notes,
                   'created_at', created_at,
                   'updated_at', updated_at
               )
           ) INTO v_result
    FROM diagnosis
    WHERE patient_id = user_id
      AND deleted_at IS NULL;

    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- Get active prescriptions returns all active prescriptions
-- for a given patient as JSON
-- - p_patient_id is the patient's user id
-- ============================================================
DROP FUNCTION IF EXISTS get_active_prescriptions;
CREATE FUNCTION get_active_prescriptions(p_patient_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id', id,
                   'doctor_id',       doctor_id,
                   'issue_date',      issue_date,
                   'expire_date',     expire_date,
                   'status',          status,
                   'notes',           notes
               )
           ) INTO v_result
    FROM prescription
    WHERE patient_id = p_patient_id
      AND status = 'active';
    RETURN v_result;
END;

-- ============================================================
-- Get items for prescription returns all medicine line items
-- on a given prescription as JSON
-- - p_prescription_id is the prescription's id
-- ============================================================
DROP FUNCTION IF EXISTS get_items_for_prescription;
CREATE FUNCTION get_items_for_prescription(p_prescription_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id', id,
                   'medicine_id',          medicine_id,
                   'dosage',               dosage,
                   'frequency',            frequency,
                   'route',                route,
                   'duration_days',        duration_days,
                   'quantity_prescribed',  quantity_prescribed,
                   'instructions',         instructions,
                   'filled_date',          filled_date
               )
           ) INTO v_result
    FROM prescription_item
    WHERE prescription_id = p_prescription_id;
    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- Check low stock returns all medicines where stock_quantity
-- is at or below a given threshold as JSON
-- - p_threshold is the minimum stock level to warn at
-- ============================================================
DROP FUNCTION IF EXISTS check_low_stock;
CREATE FUNCTION check_low_stock(p_threshold INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',   id,
                   'generic_name',  generic_name,
                   'brand_name',    brand_name,
                   'stock_quantity', stock_quantity
               )
           ) INTO v_result
    FROM medicine
    WHERE stock_quantity <= p_threshold;
    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- Get medicine by class returns all medicines belonging to
-- a given drug class as JSON
-- - p_drug_class is the class to filter by
-- ============================================================
DROP FUNCTION IF EXISTS get_medicine_by_class;
CREATE FUNCTION get_medicine_by_class(p_drug_class VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',   id,
                   'generic_name',  generic_name,
                   'brand_name',    brand_name,
                   'form',          form,
                   'standard_dose', standard_dose,
                   'stock_quantity', stock_quantity
               )
           ) INTO v_result
    FROM medicine
    WHERE drug_class = p_drug_class;
    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- check_drug_interactions takes two medicine IDs and returns
-- any known interaction between them as JSON.
-- IDs are sorted so the smaller is always agent_1_id.
-- ============================================================
DROP FUNCTION IF EXISTS check_drug_interactions;
CREATE FUNCTION check_drug_interactions(p_medicine_id_1 INT, p_medicine_id_2 INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    DECLARE v_low  INT DEFAULT LEAST(p_medicine_id_1, p_medicine_id_2);
    DECLARE v_high INT DEFAULT GREATEST(p_medicine_id_1, p_medicine_id_2);

    SELECT JSON_OBJECT(
               'id',             id,
               'agent_1_type',   agent_1_type,
               'agent_1_id',     agent_1_id,
               'agent_2_type',   agent_2_type,
               'agent_2_id',     agent_2_id,
               'severity',       severity,
               'description',    description,
               'recommendation', recommendation,
               'medicine_name',  medicine_name
           ) INTO v_result
    FROM view_all_interactions
    WHERE agent_1_type = 'medicine'
      AND agent_2_type = 'medicine'
      AND agent_1_id   = v_low
      AND agent_2_id   = v_high
      AND deleted_at   IS NULL
    LIMIT 1;

    RETURN COALESCE(v_result, JSON_OBJECT());
END;

-- ============================================================
-- check_vaccine_interaction takes two vaccine IDs and returns
-- all medicines that interact with either vaccine as a JSON array.
-- ============================================================
DROP FUNCTION IF EXISTS check_vaccine_interaction;
CREATE FUNCTION check_vaccine_interaction(p_vaccine_id_1 INT, p_vaccine_id_2 INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'medicine_id',    agent_1_id,
                   'medicine_name',  medicine_name,
                   'vaccine_id',     agent_2_id,
                   'vaccine_name',   vaccine_name,
                   'severity',       severity,
                   'description',    description,
                   'recommendation', recommendation
               )
           ) INTO v_result
    FROM view_all_interactions
    WHERE agent_1_type = 'medicine'
      AND agent_2_type = 'vaccine'
      AND agent_2_id   IN (p_vaccine_id_1, p_vaccine_id_2)
      AND deleted_at   IS NULL;

    RETURN COALESCE(v_result, JSON_ARRAY());
END;

-- ============================================================
-- check_interaction checks any two agents (medicine or vaccine).
-- Enforces canonical order: 'medicine' < 'vaccine',
-- same-type uses smaller ID first.
-- ============================================================
DROP FUNCTION IF EXISTS check_interaction;
CREATE FUNCTION check_interaction(
    p_type_1 ENUM('medicine','vaccine'), p_id_1 INT,
    p_type_2 ENUM('medicine','vaccine'), p_id_2 INT
)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result  JSON;
    DECLARE v_type_a  ENUM('medicine','vaccine');
    DECLARE v_id_a    INT;
    DECLARE v_type_b  ENUM('medicine','vaccine');
    DECLARE v_id_b    INT;

    -- Canonicalise order
    IF p_type_1 < p_type_2
        OR (p_type_1 = p_type_2 AND p_id_1 <= p_id_2) THEN
        SET v_type_a = p_type_1; SET v_id_a = p_id_1;
        SET v_type_b = p_type_2; SET v_id_b = p_id_2;
    ELSE
        SET v_type_a = p_type_2; SET v_id_a = p_id_2;
        SET v_type_b = p_type_1; SET v_id_b = p_id_1;
    END IF;

    SELECT JSON_OBJECT(
               'id',             id,
               'agent_1_type',   agent_1_type,
               'agent_1_id',     agent_1_id,
               'agent_2_type',   agent_2_type,
               'agent_2_id',     agent_2_id,
               'severity',       severity,
               'description',    description,
               'recommendation', recommendation,
               'medicine_name',  medicine_name,
               'vaccine_name',   vaccine_name
           ) INTO v_result
    FROM view_all_interactions
    WHERE agent_1_type = v_type_a
      AND agent_1_id   = v_id_a
      AND agent_2_type = v_type_b
      AND agent_2_id   = v_id_b
      AND deleted_at   IS NULL
    LIMIT 1;

    RETURN COALESCE(v_result, JSON_OBJECT());
END;

-- ============================================================
-- get_interactions_for_medicine returns all active interactions
-- involving a given medicine ID as a JSON array.
-- ============================================================
DROP FUNCTION IF EXISTS get_interactions_for_medicine;
CREATE FUNCTION get_interactions_for_medicine(p_medicine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',             id,
                   'agent_1_type',   agent_1_type,
                   'agent_1_id',     agent_1_id,
                   'agent_2_type',   agent_2_type,
                   'agent_2_id',     agent_2_id,
                   'severity',       severity,
                   'description',    description,
                   'recommendation', recommendation,
                   'medicine_name',  medicine_name,
                   'vaccine_name',   vaccine_name
               )
           ) INTO v_result
    FROM view_all_interactions
    WHERE ((agent_1_type = 'medicine' AND agent_1_id = p_medicine_id)
        OR (agent_2_type = 'medicine' AND agent_2_id = p_medicine_id));

    RETURN COALESCE(v_result, JSON_ARRAY());
END;

-- ============================================================
-- get_interactions_for_vaccine returns all active interactions
-- involving a given vaccine ID as a JSON array.
-- ============================================================
DROP FUNCTION IF EXISTS get_interactions_for_vaccine;
CREATE FUNCTION get_interactions_for_vaccine(p_vaccine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',             id,
                   'agent_1_type',   agent_1_type,
                   'agent_1_id',     agent_1_id,
                   'agent_2_type',   agent_2_type,
                   'agent_2_id',     agent_2_id,
                   'severity',       severity,
                   'description',    description,
                   'recommendation', recommendation,
                   'medicine_name',  medicine_name,
                   'vaccine_name',   vaccine_name
               )
           ) INTO v_result
    FROM view_all_interactions
    WHERE ((agent_1_type = 'vaccine' AND agent_1_id = p_vaccine_id)
        OR (agent_2_type = 'vaccine' AND agent_2_id = p_vaccine_id));

    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- Get guardians for patient returns all parents or guardians
-- linked to a given patient as JSON
-- - p_patient_id is the patient's user id
-- ============================================================
DROP FUNCTION IF EXISTS get_guardians_for_patient;
CREATE FUNCTION get_guardians_for_patient(p_patient_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'parent_relationship_id', parent_relationship_id,
                   'parent_id',              parent_id,
                   'relationship',           relationship
               )
           ) INTO v_result
    FROM parent_relationship
    WHERE patient_id = p_patient_id;
    RETURN COALESCE(v_result, JSON_ARRAY());
END;
-- ============================================================
-- Get patients for guardian returns all patients linked to
-- a given guardian as JSON
-- - p_parent_id is the guardian's user id
-- ============================================================
DROP FUNCTION IF EXISTS get_patients_for_guardian;
CREATE FUNCTION get_patients_for_guardian(p_parent_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;
    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'parent_relationship_id', parent_relationship_id,
                   'patient_id',             patient_id,
                   'relationship',           relationship
               )
           ) INTO v_result
    FROM parent_relationship
    WHERE parent_id = p_parent_id;
    RETURN COALESCE(v_result, JSON_ARRAY());
END;


DROP FUNCTION IF EXISTS has_diagnosis;

CREATE FUNCTION has_diagnosis(p_diagnosis_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_result BOOLEAN DEFAULT NULL;

    SELECT TRUE INTO v_result
    FROM diagnosis
    WHERE id = p_diagnosis_id
      AND deleted_at IS NULL
    LIMIT 1;

    IF v_result IS NOT NULL THEN
        RETURN TRUE;
    END IF;

    SELECT FALSE INTO v_result
    FROM diagnosis
    WHERE id = p_diagnosis_id
      AND deleted_at IS NOT NULL
    LIMIT 1;

    RETURN v_result;
END;

DROP FUNCTION IF EXISTS get_patient_allergies;

CREATE FUNCTION get_patient_allergies(p_patient_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'allergy_id',   ua.allergy_id,
                   'allergy_name', a.allergy_name,
                   'allergy_type', a.allergy_type,
                   'reaction',     ua.reaction,
                   'severity',     ua.severity,
                   'notes',        ua.notes,
                   'recorded_at',  ua.recorded_at
               )
           ) INTO v_result
    FROM user_allergy ua
    JOIN allergy a ON a.id = ua.allergy_id
    WHERE ua.user_id = p_patient_id
      AND a.deleted_at IS NULL;

    RETURN COALESCE(v_result, JSON_ARRAY());
END;

-- ============================================================
-- Check allergy-medication conflict checks if a prescribed medicine
-- conflicts with any of the patient's recorded allergies.
-- Returns JSON with conflict details or empty object if no conflict.
-- ============================================================
DROP FUNCTION IF EXISTS check_allergy_medication_conflict;

CREATE FUNCTION check_allergy_medication_conflict(p_patient_id INT, p_medicine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_OBJECT(
               'has_conflict', TRUE,
               'medicine_id', m.id,
               'generic_name', m.generic_name,
               'brand_name', m.brand_name,
               'drug_class', m.drug_class,
               'conflict_with', JSON_ARRAYAGG(
                   JSON_OBJECT(
                       'allergy_id', a.id,
                       'allergy_name', a.allergy_name,
                       'allergy_type', a.allergy_type,
                       'reaction', ua.reaction,
                       'severity', ua.severity
                   )
               )
           ) INTO v_result
    FROM user_allergy ua
    JOIN allergy a ON a.id = ua.allergy_id
    JOIN medicine m ON m.id = p_medicine_id
    WHERE ua.user_id = p_patient_id
      AND a.deleted_at IS NULL
      AND (m.drug_class IS NOT NULL AND a.allergy_name LIKE CONCAT('%', m.drug_class, '%'))
    LIMIT 1;

    RETURN COALESCE(v_result, JSON_OBJECT('has_conflict', FALSE));
END;

-- ============================================================
-- Get visits for patient returns all non-deleted visits for a patient.
-- ============================================================
DROP FUNCTION IF EXISTS get_visits_for_patient;

CREATE FUNCTION get_visits_for_patient(p_patient_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'visit_id', v.id,
                   'institution_id', v.institution_id,
                   'visit_type', v.visit_type,
                   'scheduled_at', v.scheduled_at,
                   'status', v.status,
                   'reason', v.reason,
                   'notes', v.notes,
                   'created_at', v.created_at
               )
           ) INTO v_result
    FROM visit v
    WHERE v.patient_id = p_patient_id
      AND v.deleted_at IS NULL
    ORDER BY v.scheduled_at DESC;

    RETURN COALESCE(v_result, JSON_ARRAY());
END;

-- ============================================================
-- Count active visits returns the number of SCHEDULED visits.
-- ============================================================
DROP FUNCTION IF EXISTS count_active_visits;

CREATE FUNCTION count_active_visits(p_patient_id INT)
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM visit
    WHERE patient_id = p_patient_id
      AND deleted_at IS NULL
      AND status = 'SCHEDULED'
      AND scheduled_at >= NOW();

    RETURN v_count;
END;

-- ============================================================
-- Prescription is expired returns TRUE if prescription is expired.
-- ============================================================
DROP FUNCTION IF EXISTS prescription_is_expired;

CREATE FUNCTION prescription_is_expired(p_prescription_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_expire_date DATE;

    SELECT status, expire_date INTO v_status, v_expire_date
    FROM prescription
    WHERE id = p_prescription_id
    LIMIT 1;

    IF v_status IS NULL THEN
        RETURN NULL;
    END IF;

    RETURN v_status = 'expired' OR v_expire_date < CURDATE();
END;

-- ============================================================
-- Get institution returns institution details as JSON.
-- ============================================================
DROP FUNCTION IF EXISTS get_institution;

CREATE FUNCTION get_institution(p_institution_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_OBJECT(
               'id', id,
               'name', name,
               'institution_type', institution_type,
               'phone', phone,
               'email', email,
               'address', address,
               'created_at', created_at,
               'updated_at', updated_at,
               'deleted_at', deleted_at
           ) INTO v_result
    FROM institution
    WHERE id = p_institution_id;

    RETURN v_result;
END;

-- ============================================================
-- check_allergy_medication_conflict checks if a patient has an allergy
-- to a medicine (by matching drug class or generic name)
-- ============================================================
DROP FUNCTION IF EXISTS check_allergy_medication_conflict;

CREATE FUNCTION check_allergy_medication_conflict(p_patient_id INT, p_medicine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_conflict JSON DEFAULT JSON_OBJECT();

    SELECT JSON_OBJECT(
               'has_conflict', TRUE,
               'allergy_id', ua.allergy_id,
               'allergy_name', a.allergy_name,
               'allergy_type', a.allergy_type,
               'reaction', ua.reaction,
               'severity', ua.severity,
               'medicine_id', m.id,
               'generic_name', m.generic_name,
               'brand_name', m.brand_name,
               'drug_class', m.drug_class
           ) INTO v_conflict
    FROM user_allergy ua
    JOIN allergy a ON a.id = ua.allergy_id
    JOIN medicine m ON m.id = p_medicine_id
    WHERE ua.user_id = p_patient_id
      AND a.deleted_at IS NULL
      AND (m.drug_class = a.allergy_name OR m.generic_name = a.allergy_name)
    LIMIT 1;

    IF v_conflict IS NOT NULL AND JSON_KEYS(v_conflict) IS NOT NULL THEN
        RETURN v_conflict;
    END IF;

    RETURN JSON_OBJECT('has_conflict', FALSE);
END;

-- ============================================================
-- get_visits_for_patient returns all visits for a patient as JSON
-- ============================================================
DROP FUNCTION IF EXISTS get_visits_for_patient;

CREATE FUNCTION get_visits_for_patient(p_patient_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_ARRAYAGG(
               JSON_OBJECT(
                   'id',            v.id,
                   'institution_id', v.institution_id,
                   'institution_name', i.name,
                   'visit_type',    v.visit_type,
                   'scheduled_at',  v.scheduled_at,
                   'status',        v.status,
                   'reason',        v.reason,
                   'notes',         v.notes,
                   'created_at',    v.created_at
               )
           ) INTO v_result
    FROM visit v
    JOIN institution i ON v.institution_id = i.id
    WHERE v.patient_id = p_patient_id
      AND v.deleted_at IS NULL;

    RETURN COALESCE(v_result, JSON_ARRAY());
END;

-- ============================================================
-- count_active_visits returns the number of scheduled visits for a patient
-- ============================================================
DROP FUNCTION IF EXISTS count_active_visits;

CREATE FUNCTION count_active_visits(p_patient_id INT)
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM visit
    WHERE patient_id = p_patient_id
      AND deleted_at IS NULL
      AND status = 'SCHEDULED'
      AND scheduled_at >= NOW();

    RETURN v_count;
END;

-- ============================================================
-- prescription_is_expired returns TRUE if prescription is expired
-- ============================================================
DROP FUNCTION IF EXISTS prescription_is_expired;

CREATE FUNCTION prescription_is_expired(p_prescription_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_expire_date DATE;
    DECLARE v_status ENUM('active', 'filled', 'partially filled', 'cancelled', 'expired');

    SELECT expire_date, status INTO v_expire_date, v_status
    FROM prescription
    WHERE id = p_prescription_id;

    IF v_status = 'expired' OR v_expire_date < CURDATE() THEN
        RETURN TRUE;
    END IF;

    RETURN FALSE;
END;

-- ============================================================
-- get_institution returns institution details as JSON
-- ============================================================
DROP FUNCTION IF EXISTS get_institution;

CREATE FUNCTION get_institution(p_institution_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_OBJECT(
               'id', i.id,
               'name', i.name,
               'institution_type', i.institution_type,
               'phone', i.phone,
               'email', i.email,
               'address', i.address,
               'deleted_at', i.deleted_at
           ) INTO v_result
    FROM institution i
    WHERE i.id = p_institution_id;

    RETURN COALESCE(v_result, JSON_OBJECT());
END;

-- ============================================================
-- full_name returns user's full name (firstname + lastname)
-- ============================================================
DROP FUNCTION IF EXISTS full_name;

CREATE FUNCTION full_name(p_user_id INT)
    RETURNS VARCHAR(512)
    READS SQL DATA
BEGIN
    DECLARE v_name VARCHAR(512);

    SELECT CONCAT(firstname, ' ', lastname) INTO v_name
    FROM users
    WHERE id = p_user_id;

    RETURN v_name;
END;

-- ============================================================
-- get_vaccine returns vaccine details by ID
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccine;

CREATE FUNCTION get_vaccine(p_vaccine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_OBJECT(
            'id', v.id,
            'name', v.name,
            'cvx_code', v.cvx_code,
            'status', v.status,
            'last_updated_date', v.last_updated_date,
            'manufacturer', v.manufacturer,
            'type', v.type,
            'development', v.development,
            'recommended_age', v.recommended_age,
            'dose_count', v.dose_count
        )
        FROM vaccine v
        WHERE v.id = p_vaccine_id
          AND v.deleted_at IS NULL
    );
END;

-- ============================================================
-- get_vaccines_by_type returns all vaccines of a specific type
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccines_by_type;

CREATE FUNCTION get_vaccines_by_type(p_type VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', v.id,
                'name', v.name,
                'cvx_code', v.cvx_code,
                'status', v.status,
                'development', v.development,
                'recommended_age', v.recommended_age
            )
        )
        FROM vaccine v
        WHERE v.type = p_type
          AND v.deleted_at IS NULL
    );
END;

-- ============================================================
-- get_vaccines_by_development returns all vaccines with a specific development status
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccines_by_development;

CREATE FUNCTION get_vaccines_by_development(p_development VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', v.id,
                'name', v.name,
                'cvx_code', v.cvx_code,
                'type', v.type,
                'status', v.status
            )
        )
        FROM vaccine v
        WHERE v.development = p_development
          AND v.deleted_at IS NULL
    );
END;

-- ============================================================
-- vaccine_exists checks if a vaccine exists by ID
-- ============================================================
DROP FUNCTION IF EXISTS vaccine_exists;

CREATE FUNCTION vaccine_exists(p_vaccine_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_exists BOOLEAN DEFAULT FALSE;

    SELECT EXISTS(SELECT 1 FROM vaccine v WHERE v.id = p_vaccine_id AND v.deleted_at IS NULL) INTO v_exists;

    RETURN v_exists;
END;

-- ============================================================
-- count_vaccines_by_type returns count of vaccines by type
-- ============================================================
DROP FUNCTION IF EXISTS count_vaccines_by_type;

CREATE FUNCTION count_vaccines_by_type(p_type VARCHAR(50))
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM vaccine v
    WHERE v.type = p_type
      AND v.deleted_at IS NULL;

    RETURN v_count;
END;

-- ============================================================
-- get_vaccine returns vaccine details by ID
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccine;

CREATE FUNCTION get_vaccine(p_vaccine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_OBJECT(
        'id', id,
        'name', name,
        'cvx_code', cvx_code,
        'status', status,
        'last_updated_date', last_updated_date,
        'manufacturer', manufacturer,
        'type', type,
        'development', development,
        'recommended_age', recommended_age,
        'dose_count', dose_count,
        'lethal_dose_mg_per_kg', lethal_dose_mg_per_kg,
        'lethal_dose_route', lethal_dose_route,
        'lethal_dose_source', lethal_dose_source
    ) INTO v_result
    FROM view_active_vaccines
    WHERE id = p_vaccine_id;

    RETURN v_result;
END;

-- ============================================================
-- get_vaccines_by_type returns all vaccines of a specific type
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccines_by_type;

CREATE FUNCTION get_vaccines_by_type(p_type VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT COALESCE(
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', id,
                'name', name,
                'cvx_code', cvx_code,
                'status', status,
                'development', development,
                'recommended_age', recommended_age
            )
        ),
        JSON_ARRAY()
    ) INTO v_result
    FROM view_active_vaccines
    WHERE type = p_type;

    RETURN v_result;
END;

-- ============================================================
-- get_vaccines_by_development returns all vaccines with a specific development status
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccines_by_development;

CREATE FUNCTION get_vaccines_by_development(p_development VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT COALESCE(
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', id,
                'name', name,
                'cvx_code', cvx_code,
                'type', type,
                'status', status,
                'recommended_age', recommended_age
            )
        ),
        JSON_ARRAY()
    ) INTO v_result
    FROM view_active_vaccines
    WHERE development = p_development;

    RETURN v_result;
END;

-- ============================================================
-- get_vaccine_by_cvx returns vaccine details by CVX code
-- ============================================================
DROP FUNCTION IF EXISTS get_vaccine_by_cvx;

CREATE FUNCTION get_vaccine_by_cvx(p_cvx_code VARCHAR(20))
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_result JSON;

    SELECT JSON_OBJECT(
        'id', id,
        'name', name,
        'cvx_code', cvx_code,
        'status', status,
        'manufacturer', manufacturer,
        'type', type,
        'development', development,
        'recommended_age', recommended_age,
        'dose_count', dose_count
    ) INTO v_result
    FROM view_active_vaccines
    WHERE cvx_code = p_cvx_code;

    RETURN v_result;
END;

-- ============================================================
-- has_vaccine checks if a vaccine exists by ID
-- ============================================================
DROP FUNCTION IF EXISTS has_vaccine;

CREATE FUNCTION has_vaccine(p_vaccine_id INT)
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_exists INT;

    SELECT COUNT(*) INTO v_exists
    FROM view_active_vaccines
    WHERE id = p_vaccine_id;

    RETURN v_exists;
END;

-- ============================================================
-- count_vaccines_by_type counts vaccines by type
-- ============================================================
DROP FUNCTION IF EXISTS count_vaccines_by_type;

CREATE FUNCTION count_vaccines_by_type(p_type VARCHAR(50))
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT;

    SELECT COUNT(*) INTO v_count
    FROM view_active_vaccines
    WHERE type = p_type;

    RETURN v_count;
END;

-- ============================================================
-- count_released_vaccines counts all released vaccines
-- ============================================================
DROP FUNCTION IF EXISTS count_released_vaccines;

CREATE FUNCTION count_released_vaccines()
    RETURNS INT
    DETERMINISTIC
BEGIN
    DECLARE v_count INT;

    SELECT COUNT(*) INTO v_count
    FROM view_active_vaccines
    WHERE development = 'RELEASED';

    RETURN v_count;
END;

-- ============================================================
-- MEDICINE Functions
-- ============================================================
-- has_medicine checks if a medicine exists by ID
DROP FUNCTION IF EXISTS has_medicine;

CREATE FUNCTION has_medicine(p_medicine_id INT)
    RETURNS BOOLEAN
    DETERMINISTIC
BEGIN
    DECLARE v_exists BOOLEAN DEFAULT FALSE;

    SELECT EXISTS(SELECT 1 FROM medicine m WHERE m.id = p_medicine_id AND m.deleted_at IS NULL) INTO v_exists;

    RETURN v_exists;
END;

-- get_medicine returns medicine details by ID
DROP FUNCTION IF EXISTS get_medicine;

CREATE FUNCTION get_medicine(p_medicine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_OBJECT(
            'id', m.id,
            'generic_name', m.generic_name,
            'brand_name', m.brand_name,
            'drug_class', m.drug_class,
            'form', m.form,
            'standard_dose', m.standard_dose,
            'controlled_substance', m.controlled_substance,
            'requires_prescription', m.requires_prescription,
            'stock_quantity', m.stock_quantity,
            'unit_of_measure', m.unit_of_measure,
            'manufacturer', m.manufacturer,
            'storage_requirements', m.storage_requirements
        )
        FROM medicine m
        WHERE m.id = p_medicine_id
          AND m.deleted_at IS NULL
    );
END;

-- get_medicines_by_class returns all medicines of a specific drug class
DROP FUNCTION IF EXISTS get_medicines_by_class;

CREATE FUNCTION get_medicines_by_class(p_drug_class VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', m.id,
                'generic_name', m.generic_name,
                'brand_name', m.brand_name,
                'form', m.form,
                'standard_dose', m.standard_dose,
                'stock_quantity', m.stock_quantity
            )
        )
        FROM medicine m
        WHERE m.drug_class = p_drug_class
          AND m.deleted_at IS NULL
    );
END;

-- get_medicines_by_form returns all medicines of a specific form
DROP FUNCTION IF EXISTS get_medicines_by_form;

CREATE FUNCTION get_medicines_by_form(p_form VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', m.id,
                'generic_name', m.generic_name,
                'brand_name', m.brand_name,
                'standard_dose', m.standard_dose,
                'stock_quantity', m.stock_quantity
            )
        )
        FROM medicine m
        WHERE m.form = p_form
          AND m.deleted_at IS NULL
    );
END;

-- count_medicines_by_class counts medicines by drug class
DROP FUNCTION IF EXISTS count_medicines_by_class;

CREATE FUNCTION count_medicines_by_class(p_drug_class VARCHAR(50))
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM medicine m
    WHERE m.drug_class = p_drug_class
      AND m.deleted_at IS NULL;

    RETURN v_count;
END;

-- ============================================================
-- MEDICINE Functions
-- ============================================================
DROP FUNCTION IF EXISTS medicine_exists;

CREATE FUNCTION medicine_exists(p_medicine_id INT)
    RETURNS BOOLEAN
    READS SQL DATA
BEGIN
    DECLARE v_exists BOOLEAN DEFAULT FALSE;

    SELECT EXISTS(SELECT 1 FROM medicine m WHERE m.id = p_medicine_id AND m.deleted_at IS NULL) INTO v_exists;

    RETURN v_exists;
END;

DROP FUNCTION IF EXISTS get_medicine;

CREATE FUNCTION get_medicine(p_medicine_id INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_OBJECT(
            'id', m.id,
            'generic_name', m.generic_name,
            'brand_name', m.brand_name,
            'drug_class', m.drug_class,
            'form', m.form,
            'standard_dose', m.standard_dose,
            'controlled_substance', m.controlled_substance,
            'requires_prescription', m.requires_prescription,
            'stock_quantity', m.stock_quantity,
            'manufacturer', m.manufacturer
        )
        FROM medicine m
        WHERE m.id = p_medicine_id
          AND m.deleted_at IS NULL
    );
END;

DROP FUNCTION IF EXISTS get_medicines_by_class;

CREATE FUNCTION get_medicines_by_class(p_drug_class VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', m.id,
                'generic_name', m.generic_name,
                'brand_name', m.brand_name,
                'form', m.form,
                'standard_dose', m.standard_dose
            )
        )
        FROM medicine m
        WHERE m.drug_class = p_drug_class
          AND m.deleted_at IS NULL
    );
END;

DROP FUNCTION IF EXISTS get_medicines_by_form;

CREATE FUNCTION get_medicines_by_form(p_form VARCHAR(50))
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', m.id,
                'generic_name', m.generic_name,
                'brand_name', m.brand_name,
                'drug_class', m.drug_class,
                'standard_dose', m.standard_dose
            )
        )
        FROM medicine m
        WHERE m.form = p_form
          AND m.deleted_at IS NULL
    );
END;

DROP FUNCTION IF EXISTS check_low_stock;

CREATE FUNCTION check_low_stock(p_threshold INT)
    RETURNS INT
    READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_count
    FROM medicine m
    WHERE m.stock_quantity < p_threshold
      AND m.deleted_at IS NULL;

    RETURN v_count;
END;

-- ============================================================
-- MEDICINE Search Function
-- ============================================================
DROP FUNCTION IF EXISTS search_medicine;

CREATE FUNCTION search_medicine(p_search_term VARCHAR(100))
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_search VARCHAR(110);

    SET v_search = CONCAT('%', p_search_term, '%');

    RETURN (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', m.id,
                'generic_name', m.generic_name,
                'brand_name', m.brand_name,
                'drug_class', m.drug_class,
                'form', m.form,
                'standard_dose', m.standard_dose,
                'stock_quantity', m.stock_quantity,
                'manufacturer', m.manufacturer
            )
        )
        FROM medicine m
        WHERE (m.generic_name LIKE v_search OR m.brand_name LIKE v_search)
          AND m.deleted_at IS NULL
    );
END;

DROP FUNCTION IF EXISTS is_staff;

CREATE FUNCTION is_staff(user_id INT)
    RETURNS BOOLEAN
    DETERMINISTIC
BEGIN
    DECLARE v_is_staff BOOLEAN DEFAULT FALSE;

    SELECT TRUE
    INTO v_is_staff
    FROM user_role
    WHERE user_role.user_id = user_id
      AND role IN ('PHYSICIAN', 'NURSE', 'PHARMACIST', 'RADIOLOGIST', 'LAB_TECH',
                   'SURGEON', 'RECEPTIONIST', 'ADMIN', 'BILLING', 'EMS', 'THERAPIST')
    LIMIT 1;

    RETURN COALESCE(v_is_staff, FALSE);
END;

-- ============================================================
-- Search patients by name (returns JSON array)
-- ============================================================
DROP FUNCTION IF EXISTS search_patients;

CREATE FUNCTION search_patients(p_search VARCHAR(100), p_limit INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    DECLARE v_like VARCHAR(101) DEFAULT CONCAT('%', p_search, '%');

    RETURN COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', id,
                'firstname', firstname,
                'lastname', lastname,
                'age', age,
                'blood', blood,
                'gender', gender
            )
        )
        FROM view_patients
        WHERE firstname LIKE v_like OR lastname LIKE v_like
        ORDER BY lastname, firstname
        LIMIT p_limit),
        JSON_ARRAY()
    );
END;

DROP FUNCTION IF EXISTS is_admin;

CREATE FUNCTION is_admin(user_id INT)
    RETURNS BOOLEAN
    DETERMINISTIC
BEGIN
    DECLARE v_is_admin BOOLEAN DEFAULT FALSE;

    SELECT TRUE
    INTO v_is_admin
    FROM user_role
    WHERE user_role.user_id = user_id
      AND role = 'ADMIN'
    LIMIT 1;

    RETURN COALESCE(v_is_admin, FALSE);
END;

-- ============================================================
-- Get nearest institutions to a given location
-- ============================================================
DROP FUNCTION IF EXISTS get_nearest_institutions;

CREATE FUNCTION get_nearest_institutions(p_lat DECIMAL(10,6), p_lng DECIMAL(10,6), p_limit INT)
    RETURNS JSON
    READS SQL DATA
BEGIN
    RETURN COALESCE(
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'id', id,
                'name', name,
                'institution_type', institution_type,
                'phone', phone,
                'address', address,
                'loc_x', loc_x,
                'loc_y', loc_y,
                'distance_km', distance_km
            )
        )
        FROM (
            SELECT
                id,
                name,
                institution_type,
                phone,
                address,
                loc_x,
                loc_y,
                (6371 * ACOS(
                    COS(RADIANS(p_lat)) * COS(RADIANS(loc_x)) *
                    COS(RADIANS(loc_y) - RADIANS(p_lng)) +
                    SIN(RADIANS(p_lat)) * SIN(RADIANS(loc_x))
                )) AS distance_km
            FROM view_institution_with_location
            WHERE loc_x IS NOT NULL
              AND loc_y IS NOT NULL
            ORDER BY distance_km ASC
            LIMIT p_limit
        ) AS nearest),
        JSON_ARRAY()
    );
END;
