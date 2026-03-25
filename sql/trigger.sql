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
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
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

DROP TRIGGER IF EXISTS trg_log_password_change;

CREATE TRIGGER trg_log_password_change
AFTER UPDATE ON users
    FOR EACH ROW BEGIN
    IF OLD.password <> NEW.password AND OLD.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, new_data)
        VALUES (
                   NEW.id,
                   'PASSWORD_CHANGE',
                   'users',
                   NEW.id,
                   JSON_OBJECT('changed', TRUE)
               );
    END IF;
END;

DROP TRIGGER IF EXISTS user_soft_delete;

CREATE TRIGGER user_soft_delete
BEFORE DELETE ON users
    FOR EACH ROW BEGIN
    IF @hard_delete_user IS NOT TRUE THEN
        UPDATE users SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;

        CALL throw( 'Use hard_delete_user for permanent deletion');
    END IF;
END;

DROP TRIGGER IF EXISTS on_remove_of_log;

CREATE TRIGGER on_remove_of_log
    BEFORE DELETE ON logs
    FOR EACH ROW
BEGIN
    INSERT INTO backup_logs (
        id,
        user_id,
        action,
        severity,
        table_name,
        record_id,
        old_data,
        new_data,
        created_at,
        deleted_at
    ) VALUES (
                 OLD.id,
                 OLD.user_id,
                 OLD.action,
                 OLD.severity,
                 OLD.table_name,
                 OLD.record_id,
                 OLD.old_data,
                 OLD.new_data,
                 OLD.created_at,
                 NOW()
             );
END;

DROP TRIGGER IF EXISTS diagnosis_soft_delete;

CREATE TRIGGER diagnosis_soft_delete
    BEFORE DELETE ON diagnosis
    FOR EACH ROW
BEGIN
   UPDATE diagnosis
   SET deleted_at = NOW()
   WHERE id = OLD.id AND deleted_at IS NULL;
END;

DROP TRIGGER IF EXISTS trg_log_diagnosis_insert;

CREATE TRIGGER trg_log_diagnosis_insert
AFTER INSERT ON diagnosis
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.patient_id,
               'CREATE',
               'diagnosis',
               NEW.id,
               JSON_OBJECT(
                   'condition', NEW.condition,
                   'severity',  NEW.severity,
                   'notes',     NEW.notes
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_diagnosis_update;

CREATE TRIGGER trg_log_diagnosis_update
    AFTER UPDATE ON diagnosis
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'UPDATE',
                   'diagnosis',
                   NEW.id,
                   JSON_OBJECT(
                           'condition', OLD.condition,
                           'severity',  OLD.severity,
                           'notes',     OLD.notes
                   ),
                   JSON_OBJECT(
                           'condition', NEW.condition,
                           'severity',  NEW.severity,
                           'notes',     NEW.notes
                   )
               );
    END IF;
END;


DROP TRIGGER IF EXISTS trg_log_diagnosis_soft_delete;

CREATE TRIGGER trg_log_diagnosis_soft_delete
    AFTER UPDATE ON diagnosis
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'DELETE',
                   'diagnosis',
                   NEW.id,
                   JSON_OBJECT(
                           'condition',  OLD.condition,
                           'severity',   OLD.severity,
                           'notes',      OLD.notes,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'condition',  NEW.condition,
                           'severity',   NEW.severity,
                           'notes',      NEW.notes,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_diagnosis_recover;

CREATE TRIGGER trg_log_diagnosis_recover
    AFTER UPDATE ON diagnosis
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'RECOVER',
                   'diagnosis',
                   NEW.id,
                   JSON_OBJECT(
                           'condition',  OLD.condition,
                           'severity',   OLD.severity,
                           'notes',      OLD.notes,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'condition',  NEW.condition,
                           'severity',   NEW.severity,
                           'notes',      NEW.notes,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

-- ============================================================
-- INSTITUTION Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_institution_insert;

CREATE TRIGGER trg_log_institution_insert
AFTER INSERT ON institution
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               1,
               'CREATE',
               'institution',
               NEW.id,
               JSON_OBJECT(
                       'name', NEW.name,
                       'institution_type', NEW.institution_type,
                       'phone', NEW.phone,
                       'email', NEW.email,
                       'address', NEW.address
               )
           );
END;
DROP TRIGGER IF EXISTS trg_log_institution_update;

CREATE TRIGGER trg_log_institution_update
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'UPDATE',
                   'institution',
                   NEW.id,
                   JSON_OBJECT(
                           'name', OLD.name,
                           'institution_type', OLD.institution_type,
                           'phone', OLD.phone,
                           'email', OLD.email,
                           'address', OLD.address
                   ),
                   JSON_OBJECT(
                           'name', NEW.name,
                           'institution_type', NEW.institution_type,
                           'phone', NEW.phone,
                           'email', NEW.email,
                           'address', NEW.address
                   )
               );
    END IF;
END;
DROP TRIGGER IF EXISTS trg_log_institution_soft_delete;

CREATE TRIGGER trg_log_institution_soft_delete
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'DELETE',
                   'institution',
                   NEW.id,
                   JSON_OBJECT(
                           'name', OLD.name,
                           'institution_type', OLD.institution_type,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'name', NEW.name,
                           'institution_type', NEW.institution_type,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;