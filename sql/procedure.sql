DROP PROCEDURE IF EXISTS throw;

CREATE PROCEDURE throw(IN p_message VARCHAR(255))
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = p_message;
END ;

-- ============================================================
-- insert_user inserts a new user and returns the generated id
-- - employid / adminid are set by BEFORE INSERT triggers
-- - Primary role is seeded into user_role by AFTER INSERT trigger
-- - Password must already be bcrypt-hashed before calling
-- ============================================================
DROP PROCEDURE IF EXISTS insert_user;

CREATE PROCEDURE insert_user(
    IN  p_firstname  VARCHAR(255),
    IN  p_lastname   VARCHAR(255),
    IN  p_middlename VARCHAR(255),
    IN  p_prefix ENUM(
        'Mr','Ms','Mrs','Miss','Dr','Prof','Mx','Sir','Lady','Rev','Hon',
        'Sgt','Cpl','Col','Fr','Sr'
        ),
    IN  p_suffix     ENUM(
        'Jr','Sr','II','III','IV','V','PhD','MD','DO','DDS','DMD','JD','Esq',
        'RN','CPA','MBA','MS','MA','BA','BS','OBE','MBE','KBE'
        ),
    IN  p_user_role  ENUM('PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH','SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'),
    IN  p_gender     VARCHAR(30),
    IN  p_phone      VARCHAR(45),
    IN  p_loc_x      DECIMAL(10,6),
    IN  p_loc_y      DECIMAL(10,6),
    IN  p_email      VARCHAR(255),
    IN  p_age        INTEGER,
    IN  p_blood      ENUM('O','O+','O-','A','A+','A-','B','B+','B-','AB','AB+','AB-'),
    IN  p_password   VARCHAR(255),
    IN  p_extra      VARCHAR(2000),
    OUT p_user_id    INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET @insert_user_role = p_user_role;

    INSERT INTO users (
        firstname, lastname, middlename, prefix, suffix, gender, phone, location,
        email, age, blood, password, extra
    ) VALUES (
        p_firstname, p_lastname, p_middlename, p_prefix, p_suffix, p_gender, p_phone,
        ST_SRID(POINT(p_loc_x, p_loc_y), 4326),
        p_email, p_age, p_blood, p_password, p_extra
    );

    SET p_user_id = LAST_INSERT_ID();

    INSERT INTO user_role (user_id, role) VALUES (p_user_id, p_user_role);

    COMMIT;
END;

-- ============================================================
-- assign_role assigns a role to a user
-- - p_user_id is the user's id
-- - p_role is the role to assign
-- - result is TRUE if new role was assigned, FALSE otherwise
-- ============================================================
DROP PROCEDURE IF EXISTS assign_role;

CREATE PROCEDURE assign_role(
    IN  p_user_id INT,
    IN  p_role    ENUM('PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH','SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'),
    OUT result    BOOLEAN
)
BEGIN
    INSERT IGNORE INTO user_role (user_id, role) VALUES (p_user_id, p_role);
    SET result = ROW_COUNT() > 0;
END;

-- ============================================================
-- revoke_role revokes a role from a user
-- - p_user_id is the user's id
-- - p_role is the role to revoke
-- - result is TRUE if role was removed, FALSE otherwise
-- ============================================================
DROP PROCEDURE IF EXISTS revoke_role;

CREATE PROCEDURE revoke_role(
    IN  p_user_id INT,
    IN  p_role    VARCHAR(30),
    OUT result    BOOLEAN
)
BEGIN
    DELETE FROM user_role WHERE user_id = p_user_id AND role = p_role;
    SET result = ROW_COUNT() > 0;
END;

DROP PROCEDURE IF EXISTS get_user;

CREATE PROCEDURE get_user(
    IN  user_id    INT,
    OUT user_name  VARCHAR(255),
    OUT user_email VARCHAR(255),
    OUT user_phone VARCHAR(255),
    INOUT is_deleted BOOLEAN
)
BEGIN
    DECLARE v_status BOOLEAN DEFAULT NULL;
    SET v_status = COALESCE(is_deleted, has_user(user_id));

    IF v_status = TRUE THEN
        SELECT firstname, email, phone, TRUE
        INTO user_name, user_email, user_phone, is_deleted
        FROM view_deleted_users
        WHERE id = user_id
        LIMIT 1;
    ELSEIF v_status = FALSE THEN
        SELECT firstname, email, phone, FALSE
        INTO user_name, user_email, user_phone, is_deleted
        FROM view_users
        WHERE id = user_id
        LIMIT 1;
    ELSE
        SET user_name  = NULL;
        SET user_email = NULL;
        SET user_phone = NULL;
        SET is_deleted = NULL;
    END IF;
END;

DROP PROCEDURE IF EXISTS soft_delete_user;

CREATE PROCEDURE soft_delete_user(IN p_user_id INT)
BEGIN
    DECLARE v_status BOOLEAN;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET v_status = has_user(p_user_id);

    IF v_status IS NULL THEN
        CALL throw('User does not exist');
    END IF;

    IF v_status = FALSE THEN
        CALL throw('User is already deleted');
    END IF;

        UPDATE users SET deleted_at = NOW() WHERE id = p_user_id;
    COMMIT;
END;

DROP PROCEDURE IF EXISTS restore_user;

CREATE PROCEDURE restore_user(IN p_user_id INT)
BEGIN
    DECLARE v_status BOOLEAN;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SET v_status = has_user(p_user_id);

    IF v_status IS NULL THEN
        CALL throw('User does not exist');
    END IF;

    IF v_status = TRUE THEN
        CALL throw('User is not deleted');
    END IF;

        UPDATE users SET deleted_at = NULL WHERE id = p_user_id;
    COMMIT;
END;

DROP PROCEDURE IF EXISTS hard_delete_user;

CREATE PROCEDURE hard_delete_user(IN p_user_id INT)
BEGIN
   DECLARE v_status BOOLEAN;
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
       BEGIN
        ROLLBACK;
        SET @hard_delete_user = NULL;
        RESIGNAL;
      END;

   SET v_status = has_user(p_user_id);

   IF v_status IS NULL THEN
       CALL throw('User does not exist');
   END IF;

   SET @hard_delete_user = TRUE;
   START TRANSACTION;
       DELETE FROM users WHERE id = p_user_id;
   COMMIT;
   SET @hard_delete_user = NULL;
END;


DROP PROCEDURE IF EXISTS create_diagnosis;

CREATE PROCEDURE create_diagnosis(
    IN p_user_id INT,
    IN p_condition VARCHAR(255),
    IN p_severity INT,
    IN p_notes TEXT,

    OUT p_diagnosis_id INT
)
BEGIN
    DECLARE v_status BOOLEAN;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SET @hard_delete_user = NULL;
            RESIGNAL;
        END;

    SET v_status = has_user(p_user_id);

    IF v_status IS NULL THEN
        CALL throw('User does not exist');
    END IF;

    IF p_severity NOT BETWEEN 0 AND 5 THEN
        CALL throw('Invalid severity must be between 0 and 5');
    END IF;

    START TRANSACTION;
        INSERT INTO diagnosis (patient_id, `condition`, severity, notes) VALUES (p_user_id, p_condition, p_severity, p_notes);
        SET p_diagnosis_id = LAST_INSERT_ID();
    COMMIT;
END;


DROP PROCEDURE IF EXISTS check_prescription_expired;

-- ============================================================
-- Check prescription expired compares expire_date to today
-- and updates status to expired if it has passed
-- - p_prescription_id is the prescription's id
-- - Returns true if it was expired, false otherwise
-- ============================================================

CREATE PROCEDURE check_prescription_expired (
    IN p_prescription_id INT,
    OUT p_is_expired BOOLEAN
)
BEGIN
    DECLARE v_expire_date DATE;
    DECLARE v_status      VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET p_is_expired = FALSE;

    SELECT expire_date, status INTO v_expire_date, v_status
    FROM prescription
    WHERE id = p_prescription_id
    LIMIT 1;

    IF v_status IS NULL THEN
        CALL throw('Prescription does not exist');
    END IF;

    IF v_expire_date IS NOT NULL
        AND v_expire_date < CURDATE()
        AND v_status IN ('active', 'partially filled') THEN

        START TRANSACTION;
            UPDATE prescription
            SET status = 'expired'
            WHERE id = p_prescription_id;
        COMMIT;

        SET p_is_expired = TRUE;
    END IF;
END;

DROP PROCEDURE IF EXISTS create_institution;
CREATE PROCEDURE create_institution(
    IN p_name VARCHAR(255),
    IN p_institution_type VARCHAR(50),
    IN p_phone VARCHAR(45),
    IN p_email VARCHAR(255),
    IN p_address VARCHAR(255)
)
BEGIN
    INSERT INTO institution (name, institution_type, phone, email, address)
    VALUES (p_name, p_institution_type, p_phone, p_email, p_address);
END;

DROP PROCEDURE IF EXISTS create_visit;
CREATE PROCEDURE create_visit(
    IN p_patient_id INTEGER,
    IN p_institution_id INTEGER,
    IN p_visit_type VARCHAR(50),
    IN p_scheduled_at DATETIME,
    IN p_reason VARCHAR(255),
    IN p_notes TEXT
)
BEGIN
    INSERT INTO visit (patient_id, institution_id, visit_type, scheduled_at, reason, notes)
    VALUES (p_patient_id, p_institution_id, p_visit_type, p_scheduled_at, p_reason, p_notes);
END;

DROP PROCEDURE IF EXISTS create_doctor_visit;
CREATE PROCEDURE create_doctor_visit(
    IN p_visit_id INTEGER,
    IN p_doctor_id INTEGER,
    IN p_doctor_notes TEXT,
    IN p_diagnosis_summary VARCHAR(255)
)
BEGIN
    INSERT INTO doctor_visit (visit_id, doctor_id, doctor_notes, diagnosis_summary)
    VALUES (p_visit_id, p_doctor_id, p_doctor_notes, p_diagnosis_summary);
END;

DROP PROCEDURE IF EXISTS create_allergy;
CREATE PROCEDURE create_allergy(
    IN p_allergy_name VARCHAR(255),
    IN p_allergy_type VARCHAR(50),
    IN p_description VARCHAR(255)
)
BEGIN
    INSERT INTO allergy (allergy_name, allergy_type, description)
    VALUES (p_allergy_name, p_allergy_type, p_description);
END;

DROP PROCEDURE IF EXISTS create_user_allergy;
CREATE PROCEDURE create_user_allergy(
    IN p_user_id INTEGER,
    IN p_allergy_id INTEGER,
    IN p_reaction VARCHAR(255),
    IN p_severity VARCHAR(50),
    IN p_notes VARCHAR(255)
)
BEGIN
    INSERT INTO user_allergy (user_id, allergy_id, reaction, severity, notes)
    VALUES (p_user_id, p_allergy_id, p_reaction, p_severity, p_notes);
END;

-- ============================================================
-- VACCINE Procedures (CRUD)
-- ============================================================
DROP PROCEDURE IF EXISTS create_vaccine;
CREATE PROCEDURE create_vaccine(
    IN p_name VARCHAR(1024),
    IN p_cvx_code VARCHAR(20),
    IN p_status VARCHAR(50),
    IN p_last_updated_date DATE,
    IN p_manufacturer VARCHAR(255),
    IN p_type VARCHAR(50),
    IN p_development VARCHAR(50),
    IN p_recommended_age VARCHAR(100),
    IN p_dose_count INTEGER,
    IN p_lethal_dose_mg_per_kg DECIMAL(10,2),
    IN p_lethal_dose_route VARCHAR(50),
    IN p_lethal_dose_source VARCHAR(50),
    IN p_extra JSON
)
BEGIN
    INSERT INTO vaccine (name, cvx_code, status, last_updated_date, manufacturer, type, development, recommended_age, dose_count, lethal_dose_mg_per_kg, lethal_dose_route, lethal_dose_source, extra)
    VALUES (p_name, p_cvx_code, p_status, p_last_updated_date, p_manufacturer, p_type, p_development, p_recommended_age, p_dose_count, p_lethal_dose_mg_per_kg, p_lethal_dose_route, p_lethal_dose_source, p_extra);
END;

DROP PROCEDURE IF EXISTS get_vaccine_by_id;
CREATE PROCEDURE get_vaccine_by_id(
    IN p_vaccine_id INTEGER
)
BEGIN
    SELECT * FROM vaccine v
    WHERE v.id = p_vaccine_id
      AND v.deleted_at IS NULL;
END;

DROP PROCEDURE IF EXISTS update_vaccine;
CREATE PROCEDURE update_vaccine(
    IN p_vaccine_id INTEGER,
    IN p_name VARCHAR(1024),
    IN p_cvx_code VARCHAR(20),
    IN p_status VARCHAR(50),
    IN p_last_updated_date DATE,
    IN p_manufacturer VARCHAR(255),
    IN p_type VARCHAR(50),
    IN p_development VARCHAR(50),
    IN p_recommended_age VARCHAR(100),
    IN p_dose_count INTEGER
)
BEGIN
    UPDATE vaccine v
    SET v.name = p_name,
        v.cvx_code = p_cvx_code,
        v.status = p_status,
        v.last_updated_date = p_last_updated_date,
        v.manufacturer = p_manufacturer,
        v.type = p_type,
        v.development = p_development,
        v.recommended_age = p_recommended_age,
        v.dose_count = p_dose_count
    WHERE v.id = p_vaccine_id;
END;

DROP PROCEDURE IF EXISTS soft_delete_vaccine;
CREATE PROCEDURE soft_delete_vaccine(
    IN p_vaccine_id INTEGER
)
BEGIN
    UPDATE vaccine v
    SET v.deleted_at = NOW()
    WHERE v.id = p_vaccine_id;
END;

DROP PROCEDURE IF EXISTS restore_vaccine;
CREATE PROCEDURE restore_vaccine(
    IN p_vaccine_id INTEGER
)
BEGIN
    UPDATE vaccine v
    SET v.deleted_at = NULL
    WHERE v.id = p_vaccine_id;
END;

DROP PROCEDURE IF EXISTS hard_delete_vaccine;
CREATE PROCEDURE hard_delete_vaccine(
    IN p_vaccine_id INTEGER
)
BEGIN
    DELETE FROM vaccine v WHERE v.id = p_vaccine_id;
END;

DROP PROCEDURE IF EXISTS list_vaccines;
CREATE PROCEDURE list_vaccines()
BEGIN
    SELECT * FROM vaccine v
    WHERE v.deleted_at IS NULL
    ORDER BY v.name;
END;

DROP PROCEDURE IF EXISTS list_vaccines_by_type;
CREATE PROCEDURE list_vaccines_by_type(
    IN p_type VARCHAR(50)
)
BEGIN
    SELECT * FROM vaccine v
    WHERE v.type = p_type
      AND v.deleted_at IS NULL
    ORDER BY v.name;
END;

DROP PROCEDURE IF EXISTS list_vaccines_by_development;
CREATE PROCEDURE list_vaccines_by_development(
    IN p_development VARCHAR(50)
)
BEGIN
    SELECT * FROM vaccine v
    WHERE v.development = p_development
      AND v.deleted_at IS NULL
    ORDER BY v.name;
END;

-- ============================================================
-- MEDICINE Insert/Update Procedures
-- ============================================================
DROP PROCEDURE IF EXISTS insert_medicine;
CREATE PROCEDURE insert_medicine(
    IN p_generic_name VARCHAR(50),
    IN p_brand_name VARCHAR(50),
    IN p_drug_class VARCHAR(50),
    IN p_form VARCHAR(50),
    IN p_standard_dose VARCHAR(20),
    IN p_controlled_substance BOOLEAN,
    IN p_requires_prescription BOOLEAN,
    IN p_stock_quantity INTEGER,
    IN p_unit_of_measure VARCHAR(50),
    IN p_manufacturer VARCHAR(50),
    IN p_storage_requirements VARCHAR(50)
)
BEGIN
    INSERT INTO medicine (generic_name, brand_name, drug_class, form, standard_dose, controlled_substance, requires_prescription, stock_quantity, unit_of_measure, manufacturer, storage_requirements)
    VALUES (p_generic_name, p_brand_name, p_drug_class, p_form, p_standard_dose, p_controlled_substance, p_requires_prescription, p_stock_quantity, p_unit_of_measure, p_manufacturer, p_storage_requirements);
END;

DROP PROCEDURE IF EXISTS update_medicine;
CREATE PROCEDURE update_medicine(
    IN p_medicine_id INTEGER,
    IN p_generic_name VARCHAR(50),
    IN p_brand_name VARCHAR(50),
    IN p_drug_class VARCHAR(50),
    IN p_form VARCHAR(50),
    IN p_standard_dose VARCHAR(20),
    IN p_controlled_substance BOOLEAN,
    IN p_requires_prescription BOOLEAN,
    IN p_stock_quantity INTEGER,
    IN p_unit_of_measure VARCHAR(50),
    IN p_manufacturer VARCHAR(50),
    IN p_storage_requirements VARCHAR(50)
)
BEGIN
    UPDATE medicine m
    SET m.generic_name = p_generic_name,
        m.brand_name = p_brand_name,
        m.drug_class = p_drug_class,
        m.form = p_form,
        m.standard_dose = p_standard_dose,
        m.controlled_substance = p_controlled_substance,
        m.requires_prescription = p_requires_prescription,
        m.stock_quantity = p_stock_quantity,
        m.unit_of_measure = p_unit_of_measure,
        m.manufacturer = p_manufacturer,
        m.storage_requirements = p_storage_requirements
    WHERE m.id = p_medicine_id;
END;

-- Mass insert for medicine (insert multiple at once)
DROP PROCEDURE IF EXISTS insert_medicine_batch;
CREATE PROCEDURE insert_medicine_batch(
    IN p_data JSON
)
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE n INT;

    SET n = JSON_LENGTH(p_data);

    WHILE i < n
        DO
            SET @generic_name = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'generic_name'));
            SET @brand_name = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'brand_name'));
            SET @drug_class = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'drug_class'));
            SET @form = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'form'));
            SET @standard_dose = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'standard_dose'));
            SET @controlled_substance = JSON_EXTRACT(p_data, 'controlled_substance');
            SET @requires_prescription = JSON_EXTRACT(p_data, 'requires_prescription');
            SET @stock_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'stock_quantity'));
            SET @unit_of_measure = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'unit_of_measure'));
            SET @manufacturer = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'manufacturer'));
            SET @storage_requirements = JSON_UNQUOTE(JSON_EXTRACT(p_data, 'storage_requirements'));

            CALL insert_medicine(@generic_name, @brand_name, @drug_class, @form, @standard_dose, @controlled_substance,
                                 @requires_prescription, @stock_quantity, @unit_of_measure, @manufacturer,
                                 @storage_requirements);

            SET i = i + 1;
        END WHILE;
END;


DROP PROCEDURE IF EXISTS assign_institution;

CREATE PROCEDURE assign_institution(
    IN p_user_id INT,
    IN p_institution_id INT,
    IN p_role VARCHAR(255),
    OUT res BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SET res = FALSE;
            RESIGNAL;
        END;

    START TRANSACTION;

    IF NOT (is_staff(p_user_id) OR is_admin(p_user_id)) THEN
        CALL throw('Invalid role');
    END IF;

    INSERT IGNORE INTO institution_user (user_id, institution_id, role)
    VALUES (p_user_id, p_institution_id, p_role);

    SET res = ROW_COUNT() > 0;
    COMMIT;
END;
