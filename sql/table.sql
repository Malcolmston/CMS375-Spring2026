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
 user_id INTEGER NOT NULL COMMENT 'id of the user',
 role ENUM(
     'PATIENT','PHYSICIAN','NURSE','PHARMACIST','RADIOLOGIST','LAB_TECH',
     'SURGEON','RECEPTIONIST','ADMIN','BILLING','EMS','THERAPIST'
     ) NOT NULL COMMENT 'role assigned to the user',

 assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when the role was assigned',

 PRIMARY KEY (user_id, role),
 CONSTRAINT fk_user_role_user FOREIGN KEY (user_id)
     REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,

    user_id INTEGER NOT NULL COMMENT 'user who performed the action',
    action ENUM(
        'CREATE','UPDATE','DELETE','LOGIN','LOGOUT',
        'ROLE_ASSIGNED','ROLE_REMOVED','PASSWORD_CHANGE',
        'HARD_DELETE','FAILED'
        ) NOT NULL COMMENT 'action performed',

    severity INT AS (
        CASE
            WHEN action IN ('CREATE','UPDATE','DELETE')    THEN 1
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
         'HARD_DELETE','FAILED'
         ) NOT NULL,
     severity TINYINT NOT NULL,
     table_name VARCHAR(64) NOT NULL,
     record_id INTEGER NOT NULL,
     old_data TEXT,
     new_data TEXT,
     created_at TIMESTAMP NOT NULL,
     deleted_at TIMESTAMP DEFAULT NULL
)  ENGINE=ARCHIVE;
