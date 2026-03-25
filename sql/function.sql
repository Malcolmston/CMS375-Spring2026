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
-- Check drug interactions takes two medicine IDs and returns
-- any known interaction between them as JSON
-- - p_medicine_id_1 and p_medicine_id_2 are the medicines to check
-- - IDs are sorted so the smaller is always checked as medicine_id_1
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
               'id',  id,
               'medicine1',   medicine_1,
               'medicine2',   medicine_2,
               'severity',        severity,
               'description',     description,
               'recommendation',  recommendation
           ) INTO v_result
    FROM medicine_interaction
    WHERE medicine_1 = v_low
      AND medicine_2 = v_high
    LIMIT 1;
    RETURN COALESCE(v_result, JSON_OBJECT());
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
