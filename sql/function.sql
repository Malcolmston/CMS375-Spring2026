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

