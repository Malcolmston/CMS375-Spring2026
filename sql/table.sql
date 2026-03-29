CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,


    firstname VARCHAR(255) NOT NULL COMMENT 'first name of a user',
    lastname VARCHAR(255) COMMENT 'last name of a user',
    middlename VARCHAR(255) DEFAULT NULL COMMENT 'middle name of a user',

    prefix ENUM(
     'Mr','Ms','Mrs','Miss','Dr','Prof','Mx','Sir','Lady','Rev','Hon',
     'Sgt','Cpl','Col','Fr','Sr'
     ) DEFAULT NULL COMMENT 'title of a user e.g. Mr, Ms, Mrs, Dr, Prof',

    suffix ENUM(
     'Jr','Sr','II','III','IV','V','PhD','MD','DO','DDS','DMD','JD','Esq',
     'RN','CPA','MBA','MS','MA','BA','BS','OBE','MBE','KBE'
     ) DEFAULT NULL COMMENT 'suffix of a user e.g. Jr, Sr, II, III, IV, V',

    gender VARCHAR(30) NOT NULL COMMENT 'gender of a user e.g. male, female, other',

    phone VARCHAR(45) NOT NULL COMMENT 'phone number of a user e.g. +254712345678 or 0712345678',

    location POINT SRID 4326 NOT NULL COMMENT 'location of a user e.g. POINT(12.345678 89.012345)',
    SPATIAL INDEX (location),

 email VARCHAR(255) NOT NULL UNIQUE COMMENT 'email address of a user e.g. account@location.place',
 CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),

 age INTEGER NOT NULL COMMENT 'age of a user in years between 1 and 200',
 CHECK (age BETWEEN 1 AND 200),

 status VARCHAR(11) AS (
     CASE
         WHEN age = 1 THEN 'NEWBORN'
         WHEN age BETWEEN 2 AND 12 THEN 'CHILD'
         WHEN age BETWEEN 13 AND 17 THEN 'YOUNG_ADULT'
         WHEN age BETWEEN 18 AND 45 THEN 'ADULT'
         WHEN age BETWEEN 46 AND 65 THEN 'MIDDLE_AGE'
         WHEN age BETWEEN 66 AND 200 THEN 'OLD'
         ELSE 'AT_RISK'
         END
     ) STORED COMMENT 'computed age group status',

    blood ENUM(
     'O','O+','O-',
     'A','A+','A-',
     'B','B+','B-',
     'AB','AB+','AB-'
     ) NOT NULL COMMENT 'blood type of the user',

 password   VARCHAR(255)            NOT NULL    COMMENT 'hashed password, never plain text',
 extra      VARCHAR(2000) DEFAULT NULL           COMMENT 'extra info e.g. medical history',
 employid   VARCHAR(255)  DEFAULT NULL           COMMENT 'employment id, NULL for patients',
 adminid    VARCHAR(255)  DEFAULT NULL           COMMENT 'admin id, NULL for non-admins',

 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP                         COMMENT 'row creation time',
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'last update time',
 deleted_at TIMESTAMP DEFAULT NULL                                                COMMENT 'soft delete timestamp'
);

CREATE TABLE IF NOT EXISTS user_role (
 id INTEGER PRIMARY KEY AUTO_INCREMENT COMMENT 'unique role assignment id',
 user_id INTEGER NOT NULL COMMENT 'id of the user',
 role ENUM(
     'PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH',
     'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'
     ) NOT NULL COMMENT 'role assigned to the user',

 assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when the role was assigned',

 UNIQUE KEY uk_user_role (user_id, role),
 CONSTRAINT fk_user_role_user FOREIGN KEY (user_id)
     REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS diagnosis (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    notes TEXT DEFAULT NULL,
    patient_id INTEGER NOT NULL,
    `condition` VARCHAR(255) NOT NULL,
    severity INTEGER DEFAULT 0
        CHECK ( severity BETWEEN 0 AND 5 ),
     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     deleted_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT fk_diagnosis_patient FOREIGN KEY (patient_id)
         REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,

    user_id INTEGER NOT NULL COMMENT 'user who performed the action',
    action ENUM(
        'CREATE','UPDATE','DELETE','LOGIN','LOGOUT',
        'ROLE_ASSIGNED','ROLE_REMOVED','PASSWORD_CHANGE',
        'HARD_DELETE','FAILED','RECOVER'
        ) NOT NULL COMMENT 'action performed',

    severity INT AS (
        CASE
            WHEN action IN ('CREATE','UPDATE','DELETE','RECOVER')    THEN 1
            WHEN action IN ('LOGIN','LOGOUT')              THEN 2
            WHEN action IN ('ROLE_ASSIGNED','ROLE_REMOVED') THEN 3
            WHEN action IN ('PASSWORD_CHANGE')             THEN 4
            WHEN action IN ('HARD_DELETE','FAILED')        THEN 5
            ELSE -1  -- unreachable given ENUM, but safe to keep
            END
        ) STORED COMMENT 'severity level of the action (1=low, 5=critical)',


    table_name VARCHAR(64) NOT NULL COMMENT 'table the action was performed on',
    record_id INTEGER NOT NULL COMMENT 'id of the affected record',

    old_data JSON DEFAULT NULL COMMENT 'previous state of the record',
    new_data JSON DEFAULT NULL COMMENT 'new state of the record',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when the action occurred',

    CONSTRAINT fk_logs_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE NO ACTION ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS backup_logs (
     id INTEGER NOT NULL,
     user_id INTEGER NOT NULL,
    action ENUM(
         'CREATE','UPDATE','DELETE','LOGIN','LOGOUT',
         'ROLE_ASSIGNED','ROLE_REMOVED','PASSWORD_CHANGE',
         'HARD_DELETE','FAILED','RECOVER'
         ) NOT NULL,
     severity TINYINT NOT NULL,
     table_name VARCHAR(64) NOT NULL,
     record_id INTEGER NOT NULL,
     old_data TEXT,
     new_data TEXT,
     created_at TIMESTAMP NOT NULL,
     deleted_at TIMESTAMP DEFAULT NULL
)  ENGINE=ARCHIVE;

CREATE TABLE IF NOT EXISTS prescription(
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    patient_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    issue_date DATE NOT NULL,
    expire_date DATE,
    status ENUM('active', 'filled', 'partially filled', 'cancelled', 'expired') NOT NULL DEFAULT 'active',
    notes VARCHAR(500),

    CONSTRAINT fk_prescription_patient8
        FOREIGN KEY (patient_id) REFERENCES users(id),
    CONSTRAINT fk_prescription_doctor
        FOREIGN KEY (doctor_id) REFERENCES users(id)
    );

CREATE TABLE IF NOT EXISTS medicine(
                                       id INTEGER PRIMARY KEY AUTO_INCREMENT,
                                       generic_name VARCHAR(50),
                                       brand_name VARCHAR(50),
                                       drug_class VARCHAR(50),
                                       form ENUM(
                                           'tablet',
                                           'capsule',
                                           'liquid',
                                           'injection',
                                           'patch',
                                           'inhaler',
                                           'cream',
                                           'ointment',
                                           'drops',
                                           'suppository'
                                           ),
                                       standard_dose VARCHAR(20),
                                       controlled_substance BOOLEAN,
                                       requires_prescription BOOLEAN,
                                       stock_quantity INTEGER,
                                       unit_of_measure ENUM(
                                           'mg',
                                           'mcg',
                                           'g',
                                           'ml',
                                           'units',
                                           'tablets',
                                           'capsules',
                                           'puffs'
                                           ),
                                       manufacturer VARCHAR(50),
                                       storage_requirements ENUM(
                                           'room temperature',
                                           'refrigerate',
                                           'freeze',
                                           'keep away from light',
                                           'keep dry',
                                           'refrigerate, keep away from light'
                                           ),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     deleted_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS prescription_item(
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    prescription_id INTEGER NOT NULL,
    medicine_id INTEGER DEFAULT NULL,
    vaccine_id  INTEGER DEFAULT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency ENUM(
                    'once daily',
                    'twice daily',
                    'three times daily',
                    'four times daily',
                    'every 4 hours',
                    'every 6 hours',
                    'every 8 hours',
                    'every 12 hours',
                    'as needed',
                    'weekly',
                    'monthly'
                ) NOT NULL,
    route ENUM(
                'oral',
                'intravenous',
                'intramuscular',
                'subcutaneous',
                'topical',
                'inhalation',
                'sublingual',
                'rectal',
                'nasal',
                'ophthalmic',
                'otic'
            ) NOT NULL,
    duration_days INTEGER NOT NULL,
    quantity_prescribed INTEGER NOT NULL,
    instructions VARCHAR(500),
    filled_date DATE,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT chk_medicine_or_vaccine
        CHECK (medicine_id IS NOT NULL OR vaccine_id IS NOT NULL),
    CONSTRAINT fk_prescription_id
        FOREIGN KEY (prescription_id) REFERENCES prescription(id),
    CONSTRAINT fk_medicine_id
        FOREIGN KEY (medicine_id) REFERENCES medicine(id),
    CONSTRAINT fk_vaccine_id
        FOREIGN KEY (vaccine_id) REFERENCES vaccine(id)
    );

CREATE TABLE IF NOT EXISTS parent_relationship(
    parent_relationship_id INTEGER PRIMARY KEY AUTO_INCREMENT,
    parent_id INTEGER NOT NULL,
    patient_id INTEGER NOT NULL,
    relationship ENUM('Mother', 'Father', 'Legal Guardian') NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     deleted_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT fk_parent_id
        FOREIGN KEY (parent_id) REFERENCES users(id),
    CONSTRAINT fk_patient_id
        FOREIGN KEY (patient_id) REFERENCES users(id)
    );

CREATE TABLE IF NOT EXISTS drug_interaction (
    id             INTEGER PRIMARY KEY AUTO_INCREMENT,
    agent_1_type   ENUM('medicine','vaccine') NOT NULL,
    agent_1_id     INTEGER NOT NULL,
    agent_2_type   ENUM('medicine','vaccine') NOT NULL,
    agent_2_id     INTEGER NOT NULL,
    severity       VARCHAR(50),
    description    VARCHAR(500),
    recommendation VARCHAR(500),

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP DEFAULT NULL,

    -- Prevent reversed duplicates: 'medicine' < 'vaccine' alphabetically,
    -- so medicine always occupies agent_1 in mixed pairs;
    -- same-type pairs enforce agent_1_id < agent_2_id.
    CONSTRAINT uq_drug_interaction
        UNIQUE (agent_1_type, agent_1_id, agent_2_type, agent_2_id),
    CONSTRAINT chk_interaction_order
        CHECK (
            agent_1_type < agent_2_type OR
            (agent_1_type = agent_2_type AND agent_1_id < agent_2_id)
        )
);
    
CREATE TABLE IF NOT EXISTS institution (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    institution_type ENUM(
        'HOSPITAL',
        'CLINIC',
        'URGENT_CARE',
        'PHARMACY',
        'LAB',
        'OTHER'
    ) NOT NULL DEFAULT 'OTHER',
    phone VARCHAR(45) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS institution_user
(
    id             INTEGER PRIMARY KEY AUTO_INCREMENT,
    institution_id INTEGER   NOT NULL,
    user_id        INTEGER   NOT NULL,
    role           ENUM (
        'PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH',
        'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'
        )                    NOT NULL,

    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP          DEFAULT NULL,

    CONSTRAINT fk_institution
        FOREIGN KEY (institution_id) REFERENCES institution (id)
            ON DELETE CASCADE ON UPDATE RESTRICT,

    CONSTRAINT fk_user_institution
        FOREIGN KEY (user_id) REFERENCES users (id)
            ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS visit (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    patient_id INTEGER NOT NULL,
    institution_id INTEGER NOT NULL,
    visit_type ENUM(
        'CHECKUP',
        'FOLLOW_UP',
        'EMERGENCY',
        'SPECIALIST',
        'LAB',
        'THERAPY',
        'OTHER'
    ) NOT NULL DEFAULT 'OTHER',
    scheduled_at DATETIME NOT NULL,
    status ENUM(
        'SCHEDULED',
        'COMPLETED',
        'CANCELLED',
        'NO_SHOW'
    ) NOT NULL DEFAULT 'SCHEDULED',
    reason VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT fk_visit_patient
        FOREIGN KEY (patient_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_visit_institution
        FOREIGN KEY (institution_id) REFERENCES institution(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS doctor_visit (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    visit_id INTEGER NOT NULL,
    doctor_id INTEGER NOT NULL,
    doctor_notes TEXT DEFAULT NULL,
    diagnosis_summary VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL,

    CONSTRAINT fk_doctor_visit_visit
        FOREIGN KEY (visit_id) REFERENCES visit(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_doctor_visit_doctor
        FOREIGN KEY (doctor_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT uq_doctor_visit UNIQUE (visit_id, doctor_id)
);

CREATE TABLE IF NOT EXISTS allergy (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    allergy_name VARCHAR(255) NOT NULL UNIQUE,
    allergy_type ENUM(
        'MEDICATION',
        'FOOD',
        'ENVIRONMENTAL',
        'INSECT',
        'LATEX',
        'OTHER'
    ) NOT NULL DEFAULT 'OTHER',
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS user_allergy (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    user_id INTEGER NOT NULL,
    allergy_id INTEGER NOT NULL,
    reaction VARCHAR(255) DEFAULT NULL,
    severity ENUM(
        'MILD',
        'MODERATE',
        'SEVERE'
    ) NOT NULL DEFAULT 'MILD',
    notes VARCHAR(255) DEFAULT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_allergy_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_user_allergy_allergy
        FOREIGN KEY (allergy_id) REFERENCES allergy(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT uq_user_allergy UNIQUE (user_id, allergy_id)
);

CREATE TABLE IF NOT EXISTS vaccine (
                                       id INTEGER PRIMARY KEY AUTO_INCREMENT,

                                       name VARCHAR(1024) NOT NULL,
                                       cvx_code VARCHAR(20) DEFAULT NULL,
                                       status VARCHAR(50) DEFAULT NULL,
                                       last_updated_date DATE DEFAULT NULL,
                                       manufacturer VARCHAR(255) DEFAULT NULL,

                                       type ENUM(
                                           'MRNA',
                                           'LIVE_ATTENUATED',
                                           'INACTIVATED',
                                           'TOXOID',
                                           'SUBUNIT',
                                           'VECTOR',
                                           'DNA',
                                           'PROTEIN',
                                           'UNKNOWN'
                                           ) NOT NULL DEFAULT 'UNKNOWN',

                                       development ENUM(
                                           'RELEASED',
                                           'TESTING',
                                           'PRECLINICAL',
                                           'DISCONTINUED',
                                           'UNKNOWN'
                                           ) NOT NULL DEFAULT 'UNKNOWN',

                                       recommended_age VARCHAR(100) DEFAULT NULL,  -- e.g., "2 months+", "Adults", etc.
                                       dose_count INTEGER DEFAULT NULL,            -- e.g., 2 doses, 3 doses

                                       lethal_dose_mg_per_kg DECIMAL(10,3) DEFAULT NULL,
                                       lethal_dose_route ENUM('ORAL','IV','IM','INHALATION','DERMAL','UNKNOWN') DEFAULT 'UNKNOWN',
                                       lethal_dose_source VARCHAR(255) DEFAULT NULL,       -- citation/source label

                                       extra VARCHAR(2000) DEFAULT NULL,

                                       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                       deleted_at TIMESTAMP DEFAULT NULL
);
