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
    IF OLD.deleted_at <=> NEW.deleted_at THEN
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
