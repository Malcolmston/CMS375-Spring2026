DROP TRIGGER IF EXISTS trg_log_user_insert;

CREATE TRIGGER trg_log_user_insert
AFTER INSERT ON users
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.id,
               'CREATE',
               'users',
               NEW.id,
               JSON_OBJECT(
                       'firstname', NEW.firstname,
                       'lastname',  NEW.lastname,
                       'email',     NEW.email,
                       'age',       NEW.age,
                       'blood',     NEW.blood,
                       'gender',    NEW.gender,
                       'role',      @insert_user_role
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_user_update;

CREATE TRIGGER trg_log_user_update
AFTER UPDATE ON users
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.id,
                   'UPDATE',
                   'users',
                   NEW.id,
                   JSON_OBJECT(
                           'firstname', OLD.firstname,
                           'lastname',  OLD.lastname,
                           'email',     OLD.email,
                           'age',       OLD.age,
                           'blood',     OLD.blood,
                           'gender',    OLD.gender
                   ),
                   JSON_OBJECT(
                           'firstname', NEW.firstname,
                           'lastname',  NEW.lastname,
                           'email',     NEW.email,
                           'age',       NEW.age,
                           'blood',     NEW.blood,
                           'gender',    NEW.gender
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_user_soft_delete;

CREATE TRIGGER trg_log_user_soft_delete
AFTER UPDATE ON users
    FOR EACH ROW BEGIN
    IF NOT (OLD.deleted_at <=> NEW.deleted_at) THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.id,
                   'DELETE',
                   'users',
                   NEW.id,
                   JSON_OBJECT(
                           'firstname',  OLD.firstname,
                           'lastname',   OLD.lastname,
                           'email',      OLD.email,
                           'age',        OLD.age,
                           'blood',      OLD.blood,
                           'gender',     OLD.gender,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'firstname',  NEW.firstname,
                           'lastname',   NEW.lastname,
                           'email',      NEW.email,
                           'age',        NEW.age,
                           'blood',      NEW.blood,
                           'gender',     NEW.gender,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_user_recover;

CREATE TRIGGER trg_log_user_recover
AFTER UPDATE ON users
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.id,
                   'RECOVER',
                   'users',
                   NEW.id,
                   JSON_OBJECT(
                           'firstname',  OLD.firstname,
                           'lastname',   OLD.lastname,
                           'email',      OLD.email,
                           'age',        OLD.age,
                           'blood',      OLD.blood,
                           'gender',     OLD.gender,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'firstname',  NEW.firstname,
                           'lastname',   NEW.lastname,
                           'email',      NEW.email,
                           'age',        NEW.age,
                           'blood',      NEW.blood,
                           'gender',     NEW.gender,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_user_hard_delete;

CREATE TRIGGER trg_log_user_hard_delete
AFTER DELETE ON users
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (
               OLD.id,
               'HARD_DELETE',
               'users',
               OLD.id,
               JSON_OBJECT(
                       'firstname',  OLD.firstname,
                       'lastname',   OLD.lastname,
                       'email',      OLD.email,
                       'age',        OLD.age,
                       'blood',      OLD.blood,
                       'gender',     OLD.gender,
                       'deleted_at', OLD.deleted_at
               ),
               NULL
           );
END;

DROP TRIGGER IF EXISTS trg_log_role_insert;

CREATE TRIGGER trg_log_role_insert
    AFTER INSERT ON user_role
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.user_id,
               'ROLE_ASSIGNED',
               'user_role',
               NEW.user_id,
               JSON_OBJECT('role', NEW.role, 'assigned_at', NEW.assigned_at)
    );
END ;

DROP TRIGGER IF EXISTS trg_log_role_delete;

CREATE TRIGGER trg_log_role_delete
    AFTER DELETE ON user_role
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (
               OLD.user_id,
               'ROLE_REMOVED',
               'user_role',
               OLD.user_id,
               JSON_OBJECT('role', OLD.role)
           );
END ;
