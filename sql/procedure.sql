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
    IN  p_blood      VARCHAR(5),
    IN  p_password   VARCHAR(255),
    IN  p_extra      VARCHAR(2000),
    OUT p_user_id    INT
)
BEGIN
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

    INSERT INTO user_role (
        user_id, role
    ) VALUES (p_user_id, p_user_role);
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
