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
