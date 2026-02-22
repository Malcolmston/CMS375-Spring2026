CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,


    firstname VARCHAR(255) NOT NULL COMMENT 'first name of a user',
    lastname VARCHAR(255) COMMENT 'last name of a user',
    middlename VARCHAR(255) DEFAULT NULL COMMENT 'middle name of a user',

    prefix ENUM(
     'Mr','Ms','Mrs','Miss','Dr','Prof','Mx','Sir','Lady','Rev','Hon',
     'Sgt','Cpl','Col','Fr','Sr'
     ) DEFAULT NULL COMMENT 'title of a users e.g. Mr, Ms, Mrs, Dr, Prof',

    suffix ENUM(
     'Jr','Sr','II','III','IV','V','PhD','MD','DO','DDS','DMD','JD','Esq',
     'RN','CPA','MBA','MS','MA','BA','BS','OBE','MBE','KBE'
     ) DEFAULT NULL COMMENT 'suffix of a user e.g. Jr, Sr, II, III, IV, V',

    gender VARCHAR(30) NOT NULL COMMENT 'gender of a user e.g. male, female, other',

    phone VARCHAR(45) NOT NULL COMMENT 'phone number of a user e.g. +254712345678 or 0712345678',

    location POINT SRID 4326 NOT NULL COMMENT 'location of a user e.g. POINT(12.345678 89.012345)',
    SPATIAL INDEX (location),

    email VARCHAR(255) NOT NULL UNIQUE
        COMMENT 'email address of a user e.g. account@location.place',
     CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),

    age INTEGER NOT NULL CHECK (age BETWEEN 1 AND 200) COMMENT 'age of a user in years between 1 and 200',

    status ENUM(
     'NEWBORN','CHILD','YOUNG_ADULT','ADULT','MIDDLE_AGE','OLD','AT_RISK'
     ) AS (
     CASE
         WHEN age = 1 THEN 'NEWBORN'
         WHEN age BETWEEN 2 AND 12 THEN 'CHILD'
         WHEN age BETWEEN 13 AND 17 THEN 'YOUNG_ADULT'
         WHEN age BETWEEN 18 AND 45 THEN 'ADULT'
         WHEN age BETWEEN 46 AND 65 THEN 'MIDDLE_AGE'
         WHEN age BETWEEN 66 AND 200 THEN 'OLD'
         ELSE 'AT_RISK'
         END
     ) STORED COMMENT 'the status of ones age, and groups age',

    blood ENUM(
     'O','O+','O-',
     'A','A+','A-',
     'B','B+','B-',
     'AB','AB+','AB-'
     ) NOT NULL COMMENT 'the users blood type',

    password VARCHAR(255) NOT NULL COMMENT 'users password NOT IN PLAIN TEXT',
    extra VARCHAR(2000) DEFAULT NULL COMMENT 'any extra info about a user, e.g. medical history',

    employid VARCHAR(255) DEFAULT NULL COMMENT 'users employment id but is left blank for patients',
    adminid  VARCHAR(255) DEFAULT NULL COMMENT 'any admin users id, but is left blank for non-admin users',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'date and time a user was created',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'date and time a user was last updated',
    deleted_at TIMESTAMP DEFAULT NULL COMMENT 'date and time a user was deleted',

    CONSTRAINT chk_employid_role CHECK (
     (user_role IN ('ADMIN', 'PATIENT') AND employid IS NULL) OR
     (user_role NOT IN ('ADMIN', 'PATIENT') AND employid IS NOT NULL)
     ),
    CONSTRAINT chk_adminid_role CHECK (adminid IS NULL OR user_role = 'ADMIN')
);
