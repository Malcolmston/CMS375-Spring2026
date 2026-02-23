-- ============================================================
-- Insert a new user and return the generated id
-- - employid / adminid are set by BEFORE INSERT triggers
-- - Primary role is seeded into user_role by AFTER INSERT trigger
-- - Password must already be bcrypt-hashed before calling
-- ============================================================
DELIMITER $$
DROP FUNCTION IF EXISTS insert_user$$

CREATE FUNCTION insert_user(
    p_firstname  VARCHAR(255),
    p_lastname   VARCHAR(255),
    p_middlename VARCHAR(255),
    p_prefix ENUM(
        'Mr','Ms','Mrs','Miss','Dr','Prof','Mx','Sir','Lady','Rev','Hon',
        'Sgt','Cpl','Col','Fr','Sr'
        ),
    p_suffix     ENUM(
        'Jr','Sr','II','III','IV','V','PhD','MD','DO','DDS','DMD','JD','Esq',
        'RN','CPA','MBA','MS','MA','BA','BS','OBE','MBE','KBE'
        ),
    p_user_role  ENUM('PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH', 'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'),
    p_gender     VARCHAR(30),
    p_phone      VARCHAR(45),
    p_loc_x      DECIMAL(10,6),
    p_loc_y      DECIMAL(10,6),
    p_email      VARCHAR(255),
    p_age        INTEGER,
    p_blood      VARCHAR(5),
    p_password   VARCHAR(255),
    p_extra      VARCHAR(2000)
)
    RETURNS INTEGER
    NOT DETERMINISTIC
    MODIFIES SQL DATA
BEGIN
    DECLARE v_user_id INTEGER;

    INSERT INTO users (
        firstname, lastname, middlename, prefix, suffix, gender, phone, location,
        email, age, blood, password, extra
    ) VALUES (
                 p_firstname, p_lastname, p_middlename, p_prefix, p_suffix, p_gender, p_phone,
                 ST_SRID(POINT(p_loc_x, p_loc_y), 4326),
                 p_email, p_age, p_blood, p_password, p_extra
             );

    SET v_user_id = LAST_INSERT_ID();

    INSERT INTO user_role (
        user_id, role
    ) VALUES (v_user_id, p_user_role);

    RETURN v_user_id;
    END$$

-- ============================================================
-- Has role checks if a user with a given role exists
-- - p_user_id is the user's id'
-- - p_role is the role to check for
-- - Returns true if user exists, false otherwise
-- ============================================================

DROP FUNCTION IF EXISTS has_role$$

CREATE FUNCTION has_role(p_user_id INT, p_role ENUM(
    'PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH',
    'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'
    ))
    RETURNS BOOLEAN
    DETERMINISTIC
BEGIN
    DECLARE v_has_role BOOLEAN DEFAULT FALSE;

    SELECT TRUE INTO v_has_role
    FROM usser_role
        WHERE userid = p_user_id
          AND role = p_role
        LIMIT 1;

    RETURN COALESCE(v_has_role, FALSE);
END$$

DELIMITER ;
