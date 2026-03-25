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

DROP TRIGGER IF EXISTS trg_log_institution_recover;

CREATE TRIGGER trg_log_institution_recover
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'RECOVER',
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

DROP TRIGGER IF EXISTS institution_soft_delete;

CREATE TRIGGER institution_soft_delete
BEFORE DELETE ON institution
    FOR EACH ROW BEGIN
    IF @hard_delete_institution IS NOT TRUE THEN
        UPDATE institution SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_institution for permanent deletion');
    END IF;
END;

-- ============================================================
-- VISIT Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_visit_insert;

CREATE TRIGGER trg_log_visit_insert
AFTER INSERT ON visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.patient_id,
               'CREATE',
               'visit',
               NEW.id,
               JSON_OBJECT(
                       'patient_id', NEW.patient_id,
                       'institution_id', NEW.institution_id,
                       'visit_type', NEW.visit_type,
                       'scheduled_at', NEW.scheduled_at,
                       'status', NEW.status,
                       'reason', NEW.reason
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_visit_update;

CREATE TRIGGER trg_log_visit_update
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'UPDATE',
                   'visit',
                   NEW.id,
                   JSON_OBJECT(
                           'visit_type', OLD.visit_type,
                           'scheduled_at', OLD.scheduled_at,
                           'status', OLD.status,
                           'reason', OLD.reason
                   ),
                   JSON_OBJECT(
                           'visit_type', NEW.visit_type,
                           'scheduled_at', NEW.scheduled_at,
                           'status', NEW.status,
                           'reason', NEW.reason
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_visit_soft_delete;

CREATE TRIGGER trg_log_visit_soft_delete
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'DELETE',
                   'visit',
                   NEW.id,
                   JSON_OBJECT(
                           'visit_type', OLD.visit_type,
                           'scheduled_at', OLD.scheduled_at,
                           'status', OLD.status,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'visit_type', NEW.visit_type,
                           'scheduled_at', NEW.scheduled_at,
                           'status', NEW.status,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_visit_recover;

CREATE TRIGGER trg_log_visit_recover
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   NEW.patient_id,
                   'RECOVER',
                   'visit',
                   NEW.id,
                   JSON_OBJECT(
                           'visit_type', OLD.visit_type,
                           'scheduled_at', OLD.scheduled_at,
                           'status', OLD.status,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'visit_type', NEW.visit_type,
                           'scheduled_at', NEW.scheduled_at,
                           'status', NEW.status,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS visit_soft_delete;

CREATE TRIGGER visit_soft_delete
BEFORE DELETE ON visit
    FOR EACH ROW BEGIN
    IF @hard_delete_visit IS NOT TRUE THEN
        UPDATE visit SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_visit for permanent deletion');
    END IF;
END;

-- ============================================================
-- ALLERGY Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_allergy_insert;

CREATE TRIGGER trg_log_allergy_insert
AFTER INSERT ON allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               1,
               'CREATE',
               'allergy',
               NEW.id,
               JSON_OBJECT(
                       'allergy_name', NEW.allergy_name,
                       'allergy_type', NEW.allergy_type,
                       'description', NEW.description
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_allergy_update;

CREATE TRIGGER trg_log_allergy_update
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'UPDATE',
                   'allergy',
                   NEW.id,
                   JSON_OBJECT(
                           'allergy_name', OLD.allergy_name,
                           'allergy_type', OLD.allergy_type,
                           'description', OLD.description
                   ),
                   JSON_OBJECT(
                           'allergy_name', NEW.allergy_name,
                           'allergy_type', NEW.allergy_type,
                           'description', NEW.description
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_allergy_soft_delete;

CREATE TRIGGER trg_log_allergy_soft_delete
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'DELETE',
                   'allergy',
                   NEW.id,
                   JSON_OBJECT(
                           'allergy_name', OLD.allergy_name,
                           'allergy_type', OLD.allergy_type,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'allergy_name', NEW.allergy_name,
                           'allergy_type', NEW.allergy_type,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_allergy_recover;

CREATE TRIGGER trg_log_allergy_recover
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'RECOVER',
                   'allergy',
                   NEW.id,
                   JSON_OBJECT(
                           'allergy_name', OLD.allergy_name,
                           'allergy_type', OLD.allergy_type,
                           'deleted_at', OLD.deleted_at
                   ),
                   JSON_OBJECT(
                           'allergy_name', NEW.allergy_name,
                           'allergy_type', NEW.allergy_type,
                           'deleted_at', NEW.deleted_at
                   )
               );
    END IF;
END;

DROP TRIGGER IF EXISTS allergy_soft_delete;

CREATE TRIGGER allergy_soft_delete
BEFORE DELETE ON allergy
    FOR EACH ROW BEGIN
    IF @hard_delete_allergy IS NOT TRUE THEN
        UPDATE allergy SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_allergy for permanent deletion');
    END IF;
END;

-- ============================================================
-- PRESCRIPTION Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_prescription_insert;

CREATE TRIGGER trg_log_prescription_insert
AFTER INSERT ON prescription
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.doctor_id,
               'CREATE',
               'prescription',
               NEW.id,
               JSON_OBJECT(
                       'patient_id', NEW.patient_id,
                       'doctor_id', NEW.doctor_id,
                       'issue_date', NEW.issue_date,
                       'expire_date', NEW.expire_date,
                       'status', NEW.status,
                       'notes', NEW.notes
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_prescription_update;

CREATE TRIGGER trg_log_prescription_update
AFTER UPDATE ON prescription
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (
               NEW.doctor_id,
               'UPDATE',
               'prescription',
               NEW.id,
               JSON_OBJECT(
                       'status', OLD.status,
                       'expire_date', OLD.expire_date,
                       'notes', OLD.notes
               ),
               JSON_OBJECT(
                       'status', NEW.status,
                       'expire_date', NEW.expire_date,
                       'notes', NEW.notes
               )
           );
END;

-- ============================================================
-- PRESCRIPTION_ITEM Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_prescription_item_insert;

CREATE TRIGGER trg_log_prescription_item_insert
AFTER INSERT ON prescription_item
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               1,
               'CREATE',
               'prescription_item',
               NEW.id,
               JSON_OBJECT(
                       'prescription_id', NEW.prescription_id,
                       'medicine_id', NEW.medicine_id,
                       'dosage', NEW.dosage,
                       'frequency', NEW.frequency,
                       'route', NEW.route,
                       'duration_days', NEW.duration_days,
                       'quantity_prescribed', NEW.quantity_prescribed
               )
           );
END;

-- ============================================================
-- MEDICINE Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_medicine_insert;

CREATE TRIGGER trg_log_medicine_insert
AFTER INSERT ON medicine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               1,
               'CREATE',
               'medicine',
               NEW.id,
               JSON_OBJECT(
                       'generic_name', NEW.generic_name,
                       'brand_name', NEW.brand_name,
                       'drug_class', NEW.drug_class,
                       'form', NEW.form,
                       'stock_quantity', NEW.stock_quantity
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_medicine_stock_update;

CREATE TRIGGER trg_log_medicine_stock_update
AFTER UPDATE ON medicine
    FOR EACH ROW BEGIN
    IF OLD.stock_quantity != NEW.stock_quantity THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (
                   1,
                   'UPDATE',
                   'medicine',
                   NEW.id,
                   JSON_OBJECT(
                           'stock_quantity', OLD.stock_quantity
                   ),
                   JSON_OBJECT(
                           'stock_quantity', NEW.stock_quantity
                   )
               );
    END IF;
END;

-- ============================================================
-- DOCTOR_VISIT Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_doctor_visit_insert;

CREATE TRIGGER trg_log_doctor_visit_insert
AFTER INSERT ON doctor_visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.doctor_id,
               'CREATE',
               'doctor_visit',
               NEW.id,
               JSON_OBJECT(
                       'visit_id', NEW.visit_id,
                       'doctor_id', NEW.doctor_id,
                       'doctor_notes', NEW.doctor_notes,
                       'diagnosis_summary', NEW.diagnosis_summary
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_doctor_visit_update;

CREATE TRIGGER trg_log_doctor_visit_update
AFTER UPDATE ON doctor_visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (
               NEW.doctor_id,
               'UPDATE',
               'doctor_visit',
               NEW.id,
               JSON_OBJECT(
                       'doctor_notes', OLD.doctor_notes,
                       'diagnosis_summary', OLD.diagnosis_summary
               ),
               JSON_OBJECT(
                       'doctor_notes', NEW.doctor_notes,
                       'diagnosis_summary', NEW.diagnosis_summary
               )
           );
END;

-- ============================================================
-- USER_ALLERGY Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_user_allergy_insert;

CREATE TRIGGER trg_log_user_allergy_insert
AFTER INSERT ON user_allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.user_id,
               'CREATE',
               'user_allergy',
               NEW.id,
               JSON_OBJECT(
                       'user_id', NEW.user_id,
                       'allergy_id', NEW.allergy_id,
                       'reaction', NEW.reaction,
                       'severity', NEW.severity,
                       'notes', NEW.notes
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_user_allergy_delete;

CREATE TRIGGER trg_log_user_allergy_delete
AFTER DELETE ON user_allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (
               OLD.user_id,
               'DELETE',
               'user_allergy',
               OLD.id,
               JSON_OBJECT(
                       'user_id', OLD.user_id,
                       'allergy_id', OLD.allergy_id,
                       'reaction', OLD.reaction,
                       'severity', OLD.severity
               )
           );
END;

-- ============================================================
-- PARENT_RELATIONSHIP Triggers
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_parent_relationship_insert;

CREATE TRIGGER trg_log_parent_relationship_insert
AFTER INSERT ON parent_relationship
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (
               NEW.parent_id,
               'CREATE',
               'parent_relationship',
               NEW.parent_relationship_id,
               JSON_OBJECT(
                       'parent_id', NEW.parent_id,
                       'patient_id', NEW.patient_id,
                       'relationship', NEW.relationship
               )
           );
END;

DROP TRIGGER IF EXISTS trg_log_parent_relationship_delete;

CREATE TRIGGER trg_log_parent_relationship_delete
AFTER DELETE ON parent_relationship
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (
               OLD.parent_id,
               'DELETE',
               'parent_relationship',
               OLD.parent_relationship_id,
               JSON_OBJECT(
                       'parent_id', OLD.parent_id,
                       'patient_id', OLD.patient_id,
                       'relationship', OLD.relationship
               )
           );
END;

-- ============================================================
-- INSTITUTION Triggers (5: INSERT, UPDATE, soft-delete, recover, BEFORE DELETE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_institution_insert;

CREATE TRIGGER trg_log_institution_insert
AFTER INSERT ON institution
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (1, 'CREATE', 'institution', NEW.id, JSON_OBJECT('name', NEW.name, 'institution_type', NEW.institution_type, 'phone', NEW.phone, 'email', NEW.email, 'address', NEW.address));
END;

DROP TRIGGER IF EXISTS trg_log_institution_update;

CREATE TRIGGER trg_log_institution_update
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'UPDATE', 'institution', NEW.id, JSON_OBJECT('name', OLD.name, 'institution_type', OLD.institution_type), JSON_OBJECT('name', NEW.name, 'institution_type', NEW.institution_type));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_institution_soft_delete;

CREATE TRIGGER trg_log_institution_soft_delete
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'DELETE', 'institution', NEW.id, JSON_OBJECT('name', OLD.name, 'deleted_at', OLD.deleted_at), JSON_OBJECT('name', NEW.name, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_institution_recover;

CREATE TRIGGER trg_log_institution_recover
AFTER UPDATE ON institution
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'RECOVER', 'institution', NEW.id, JSON_OBJECT('name', OLD.name, 'deleted_at', OLD.deleted_at), JSON_OBJECT('name', NEW.name, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS institution_soft_delete;

CREATE TRIGGER institution_soft_delete
BEFORE DELETE ON institution
    FOR EACH ROW BEGIN
    IF @hard_delete_institution IS NOT TRUE THEN
        UPDATE institution SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_institution for permanent deletion');
    END IF;
END;

-- ============================================================
-- VISIT Triggers (5: INSERT, UPDATE, soft-delete, recover, BEFORE DELETE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_visit_insert;

CREATE TRIGGER trg_log_visit_insert
AFTER INSERT ON visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.patient_id, 'CREATE', 'visit', NEW.id, JSON_OBJECT('patient_id', NEW.patient_id, 'institution_id', NEW.institution_id, 'visit_type', NEW.visit_type, 'scheduled_at', NEW.scheduled_at, 'status', NEW.status));
END;

DROP TRIGGER IF EXISTS trg_log_visit_update;

CREATE TRIGGER trg_log_visit_update
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (NEW.patient_id, 'UPDATE', 'visit', NEW.id, JSON_OBJECT('status', OLD.status), JSON_OBJECT('status', NEW.status));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_visit_soft_delete;

CREATE TRIGGER trg_log_visit_soft_delete
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (NEW.patient_id, 'DELETE', 'visit', NEW.id, JSON_OBJECT('status', OLD.status, 'deleted_at', OLD.deleted_at), JSON_OBJECT('status', NEW.status, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_visit_recover;

CREATE TRIGGER trg_log_visit_recover
AFTER UPDATE ON visit
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (NEW.patient_id, 'RECOVER', 'visit', NEW.id, JSON_OBJECT('status', OLD.status, 'deleted_at', OLD.deleted_at), JSON_OBJECT('status', NEW.status, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS visit_soft_delete;

CREATE TRIGGER visit_soft_delete
BEFORE DELETE ON visit
    FOR EACH ROW BEGIN
    IF @hard_delete_visit IS NOT TRUE THEN
        UPDATE visit SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_visit for permanent deletion');
    END IF;
END;

-- ============================================================
-- ALLERGY Triggers (5: INSERT, UPDATE, soft-delete, recover, BEFORE DELETE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_allergy_insert;

CREATE TRIGGER trg_log_allergy_insert
AFTER INSERT ON allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (1, 'CREATE', 'allergy', NEW.id, JSON_OBJECT('allergy_name', NEW.allergy_name, 'allergy_type', NEW.allergy_type, 'description', NEW.description));
END;

DROP TRIGGER IF EXISTS trg_log_allergy_update;

CREATE TRIGGER trg_log_allergy_update
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'UPDATE', 'allergy', NEW.id, JSON_OBJECT('allergy_name', OLD.allergy_name), JSON_OBJECT('allergy_name', NEW.allergy_name));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_allergy_soft_delete;

CREATE TRIGGER trg_log_allergy_soft_delete
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'DELETE', 'allergy', NEW.id, JSON_OBJECT('allergy_name', OLD.allergy_name, 'deleted_at', OLD.deleted_at), JSON_OBJECT('allergy_name', NEW.allergy_name, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_allergy_recover;

CREATE TRIGGER trg_log_allergy_recover
AFTER UPDATE ON allergy
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'RECOVER', 'allergy', NEW.id, JSON_OBJECT('allergy_name', OLD.allergy_name, 'deleted_at', OLD.deleted_at), JSON_OBJECT('allergy_name', NEW.allergy_name, 'deleted_at', NEW.deleted_at));
    END IF;
END;

DROP TRIGGER IF EXISTS allergy_soft_delete;

CREATE TRIGGER allergy_soft_delete
BEFORE DELETE ON allergy
    FOR EACH ROW BEGIN
    IF @hard_delete_allergy IS NOT TRUE THEN
        UPDATE allergy SET deleted_at = NOW() WHERE id = OLD.id AND deleted_at IS NULL;
        CALL throw('Use hard_delete_allergy for permanent deletion');
    END IF;
END;

-- ============================================================
-- PRESCRIPTION Triggers (INSERT, UPDATE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_prescription_insert;

CREATE TRIGGER trg_log_prescription_insert
AFTER INSERT ON prescription
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.doctor_id, 'CREATE', 'prescription', NEW.id, JSON_OBJECT('patient_id', NEW.patient_id, 'doctor_id', NEW.doctor_id, 'status', NEW.status));
END;

DROP TRIGGER IF EXISTS trg_log_prescription_update;

CREATE TRIGGER trg_log_prescription_update
AFTER UPDATE ON prescription
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (NEW.doctor_id, 'UPDATE', 'prescription', NEW.id, JSON_OBJECT('status', OLD.status), JSON_OBJECT('status', NEW.status));
END;

-- ============================================================
-- PRESCRIPTION_ITEM Triggers (INSERT)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_prescription_item_insert;

CREATE TRIGGER trg_log_prescription_item_insert
AFTER INSERT ON prescription_item
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (1, 'CREATE', 'prescription_item', NEW.id, JSON_OBJECT('prescription_id', NEW.prescription_id, 'medicine_id', NEW.medicine_id, 'dosage', NEW.dosage));
END;

-- ============================================================
-- MEDICINE Triggers (INSERT, stock update)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_medicine_insert;

CREATE TRIGGER trg_log_medicine_insert
AFTER INSERT ON medicine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (1, 'CREATE', 'medicine', NEW.id, JSON_OBJECT('generic_name', NEW.generic_name, 'brand_name', NEW.brand_name, 'stock_quantity', NEW.stock_quantity));
END;

DROP TRIGGER IF EXISTS trg_log_medicine_stock_update;

CREATE TRIGGER trg_log_medicine_stock_update
AFTER UPDATE ON medicine
    FOR EACH ROW BEGIN
    IF OLD.stock_quantity != NEW.stock_quantity THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (1, 'UPDATE', 'medicine', NEW.id, JSON_OBJECT('stock_quantity', OLD.stock_quantity), JSON_OBJECT('stock_quantity', NEW.stock_quantity));
    END IF;
END;

-- ============================================================
-- DOCTOR_VISIT Triggers (INSERT, UPDATE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_doctor_visit_insert;

CREATE TRIGGER trg_log_doctor_visit_insert
AFTER INSERT ON doctor_visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.doctor_id, 'CREATE', 'doctor_visit', NEW.id, JSON_OBJECT('visit_id', NEW.visit_id, 'doctor_id', NEW.doctor_id));
END;

DROP TRIGGER IF EXISTS trg_log_doctor_visit_update;

CREATE TRIGGER trg_log_doctor_visit_update
AFTER UPDATE ON doctor_visit
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
    VALUES (NEW.doctor_id, 'UPDATE', 'doctor_visit', NEW.id, JSON_OBJECT('doctor_notes', OLD.doctor_notes), JSON_OBJECT('doctor_notes', NEW.doctor_notes));
END;

-- ============================================================
-- USER_ALLERGY Triggers (INSERT, DELETE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_user_allergy_insert;

CREATE TRIGGER trg_log_user_allergy_insert
AFTER INSERT ON user_allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.user_id, 'CREATE', 'user_allergy', NEW.id, JSON_OBJECT('user_id', NEW.user_id, 'allergy_id', NEW.allergy_id, 'severity', NEW.severity));
END;

DROP TRIGGER IF EXISTS trg_log_user_allergy_delete;

CREATE TRIGGER trg_log_user_allergy_delete
AFTER DELETE ON user_allergy
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (OLD.user_id, 'DELETE', 'user_allergy', OLD.id, JSON_OBJECT('user_id', OLD.user_id, 'allergy_id', OLD.allergy_id));
END;

-- ============================================================
-- PARENT_RELATIONSHIP Triggers (INSERT, DELETE)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_parent_relationship_insert;

CREATE TRIGGER trg_log_parent_relationship_insert
AFTER INSERT ON parent_relationship
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.parent_id, 'CREATE', 'parent_relationship', NEW.parent_relationship_id, JSON_OBJECT('parent_id', NEW.parent_id, 'patient_id', NEW.patient_id, 'relationship', NEW.relationship));
END;

DROP TRIGGER IF EXISTS trg_log_parent_relationship_delete;

CREATE TRIGGER trg_log_parent_relationship_delete
AFTER DELETE ON parent_relationship
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (OLD.parent_id, 'DELETE', 'parent_relationship', OLD.parent_relationship_id, JSON_OBJECT('parent_id', OLD.parent_id, 'patient_id', OLD.patient_id));
END;

-- ============================================================
-- VACCINE Triggers (INSERT, UPDATE, DELETE, RECOVER)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_vaccine_insert;

CREATE TRIGGER trg_log_vaccine_insert
AFTER INSERT ON vaccine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.id, 'CREATE', 'vaccine', NEW.id, JSON_OBJECT('name', NEW.name, 'cvx_code', NEW.cvx_code, 'type', NEW.type, 'development', NEW.development));
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_update;

CREATE TRIGGER trg_log_vaccine_update
AFTER UPDATE ON vaccine
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (NEW.id, 'UPDATE', 'vaccine', NEW.id, JSON_OBJECT('name', OLD.name, 'cvx_code', OLD.cvx_code), JSON_OBJECT('name', NEW.name, 'cvx_code', NEW.cvx_code));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_delete;

CREATE TRIGGER trg_log_vaccine_delete
AFTER DELETE ON vaccine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (OLD.id, 'HARD_DELETE', 'vaccine', OLD.id, JSON_OBJECT('name', OLD.name, 'cvx_code', OLD.cvx_code));
END;

-- ============================================================
-- VACCINE Triggers (INSERT, UPDATE, DELETE, SOFT_DELETE, RECOVER)
-- ============================================================
DROP TRIGGER IF EXISTS trg_log_vaccine_insert;

CREATE TRIGGER trg_log_vaccine_insert
AFTER INSERT ON vaccine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, new_data)
    VALUES (NEW.id, 'CREATE', 'vaccine', NEW.id, JSON_OBJECT('name', NEW.name, 'cvx_code', NEW.cvx_code, 'type', NEW.type));
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_update;

CREATE TRIGGER trg_log_vaccine_update
AFTER UPDATE ON vaccine
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data)
        VALUES (NEW.id, 'UPDATE', 'vaccine', NEW.id,
            JSON_OBJECT('name', OLD.name, 'cvx_code', OLD.cvx_code, 'type', OLD.type, 'status', OLD.status),
            JSON_OBJECT('name', NEW.name, 'cvx_code', NEW.cvx_code, 'type', NEW.type, 'status', NEW.status));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_delete;

CREATE TRIGGER trg_log_vaccine_delete
AFTER DELETE ON vaccine
    FOR EACH ROW BEGIN
    INSERT INTO logs (user_id, action, table_name, record_id, old_data)
    VALUES (OLD.id, 'HARD_DELETE', 'vaccine', OLD.id, JSON_OBJECT('name', OLD.name, 'cvx_code', OLD.cvx_code));
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_soft_delete;

CREATE TRIGGER trg_log_vaccine_soft_delete
AFTER UPDATE ON vaccine
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, old_data)
        VALUES (NEW.id, 'DELETE', 'vaccine', NEW.id, JSON_OBJECT('name', OLD.name, 'cvx_code', OLD.cvx_code));
    END IF;
END;

DROP TRIGGER IF EXISTS trg_log_vaccine_recover;

CREATE TRIGGER trg_log_vaccine_recover
AFTER UPDATE ON vaccine
    FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO logs (user_id, action, table_name, record_id, new_data)
        VALUES (NEW.id, 'RECOVER', 'vaccine', NEW.id, JSON_OBJECT('name', NEW.name, 'cvx_code', NEW.cvx_code));
    END IF;
END;
