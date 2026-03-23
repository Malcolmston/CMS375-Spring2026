# Database & Application Diagrams

---
---


### SQL Functions

```mermaid
flowchart TB
    subgraph "SQL Functions"
        HAS_USER["has_user(p_id INT)<br/>RETURNS BOOLEAN<br/>(checks if user exists)"]
        IS_DELETED["is_deleted(p_id INT)<br/>RETURNS BOOLEAN<br/>(checks soft delete status)"]
        HAS_ROLE["has_role(p_user_id, p_role)<br/>RETURNS BOOLEAN<br/>(checks user role)"]
        MY_DIAGNOSIS["my_diagnosis(user_id)<br/>RETURNS JSON<br/>(gets patient diagnoses)"]
    end

    HAS_USER --> VIEW_USERS
    HAS_USER --> VIEW_DELETED
    IS_DELETED --> VIEW_DELETED
    HAS_ROLE --> USER_ROLE
    MY_DIAGNOSIS --> DIAGNOSIS["diagnosis table"]
```

---

### SQL Stored Procedures

```mermaid
flowchart TB
    subgraph "User Management Procedures"
        INSERT_USER["insert_user(...)<br/>Insert new user with role"]
        ASSIGN_ROLE["assign_role(p_user_id, p_role)<br/>Assign role to user"]
        REVOKE_ROLE["revoke_role(p_user_id, p_role)<br/>Remove role from user"]
        GET_USER["get_user(user_id)<br/>Get user details"]
        SOFT_DELETE["soft_delete_user(p_user_id)<br/>Soft delete user"]
        RESTORE_USER["restore_user(p_user_id)<br/>Restore soft-deleted user"]
        HARD_DELETE["hard_delete_user(p_user_id)<br/>Permanently delete user"]
    end

    subgraph "Diagnosis Procedures"
        CREATE_DIAGNOSIS["create_diagnosis(...)<br/>Create patient diagnosis"]
    end

    subgraph "Utility Procedures"
        THROW["throw(p_message)<br/>Raise custom error"]
    end

    INSERT_USER --> USER_ROLE
    ASSIGN_ROLE --> USER_ROLE
    REVOKE_ROLE --> USER_ROLE
    GET_USER --> VIEW_USERS
    GET_USER --> VIEW_DELETED
    SOFT_DELETE --> USERS
    RESTORE_USER --> USERS
    HARD_DELETE --> USERS
    CREATE_DIAGNOSIS --> DIAGNOSIS["diagnosis table"]
```

---

## Account PHP Class Hierarchy

### Class Diagram

```mermaid
classDiagram
    class Connect {
        +getConnection(): mysqli
    }

    class Point {
        +float x
        +float y
    }

    class blood {
        <<enum>>
        +O, O+, O-
        +A, A+, A-
        +B, B+, B-
        +AB, AB+, AB-
    }

    class prefix {
        <<enum>>
        Mr, Ms, Mrs, Miss, Dr, Prof, Mx, Sir, Lady, Rev, Hon, Sgt, Cpl, Col, Fr, Sr
    }

    class suffix {
        <<enum>>
        Jr, Sr, II, III, IV, V, PhD, MD, DO, DDS, DMD, JD, Esq, RN, CPA, MBA, MS, MA, BA, BS, OBE, MBE, KBE
    }

    class role {
        <<enum>>
        PATIENT, PHYSICIAN, NURSE, PHARMACIST, RADIOLOGIST, LAB_TECH
        SURGEON, RECEPTIONIST, ADMIN, BILLING, EMS, THERAPIST
    }

    class Account {
        <<abstract>>
        +int id
        +string firstName
        +string lastName
        +string middleName
        +suffix suffix
        +prefix prefix
        +string gender
        +string phone
        +Point location
        +string email
        +int age
        +blood blood
        +string password
        +string extra
        +role role
        +string status
        +DateTime createdAt
        +DateTime updatedAt
        +DateTime deletedAt
        +bool isDeleted
        +getId(): int
        +getFirstName(): string
        +getLastName(): string
        +getMiddleName(): string
        +getSuffix(): suffix
        +getPrefix(): prefix
        +getGender(): string
        +getPhone(): string
        +getLocation(): Point
        +getEmail(): string
        +getAge(): int
        +getBlood(): blood
        +getPassword(): string
        +getExtra(): string
        +getRole(): role
        +getStatus(): string
        +getCreatedAt(): DateTime
        +getUpdatedAt(): DateTime
        +getDeletedAt(): DateTime
        +getIsDeleted(): bool
        +encryptPassword(string): string
        +verifyPassword(string, string): bool
        +getUserById(int): static
        #hasUser(): bool
        #isDeleted(): bool
        #softDelete(): bool
        #restore(): bool
        #insert(): bool
        <<abstract>> login(string, string): bool
        <<abstract>> register(): bool
    }

    class Patient {
        +login(string, string): bool
        +register(): bool
    }

    class Employed {
        <<interface>>
        +loginWithId(string, string, string): bool
        +resolveRole(string, string): ?string
    }

    class EmployedTrait {
        +loginWithId(string, string, string): bool
        +resolveRole(string, string): ?string
    }

    class Admin {
        +string department
        +login(string, string): bool
        +loginWithId(string, string, string): bool
        +register(): bool
    }

    class LabTech {
        +login(string, string): bool
        +register(): bool
    }

    class Billing {
        +login(string, string): bool
        +register(): bool
    }

    class Diagnosible {
        <<interface>>
    }

    class DiagnosibleTrait {
        +addDiagnosis(string, int, string): bool
        +getDiagnoses(): array
    }

    Connect <|-- Account
    Account *-- Point
    Account *-- blood
    Account *-- prefix
    Account *-- suffix
    Account *-- role

    Account <|-- Patient
    Account <|-- LabTech
    Account <|-- Billing

    Account <|.. Admin
    Admin ..> Employed

    Account <|.. LabTech

    Employed <|.. EmployedTrait
    Admin *-- EmployedTrait

    Diagnosible <|.. DiagnosibleTrait
```

---

### Account Flow: Login Process

```mermaid
sequenceDiagram
    participant U as User Input
    participant P as Patient/Admin/LabTech
    participant A as Account
    participant DB as Database

    U->>P: login(email, password)
    P->>DB: SELECT id, password FROM view_user_role_pwd<br/>WHERE email=? AND role=?
    DB-->>P: userId, hash
    P->>A: verifyPassword(password, hash)
    A-->>P: true/false
    alt Password Valid
        P->>P: set $this->id = userId
        P-->>U: true (logged in)
    else Password Invalid
        P-->>U: false (login failed)
    end
```

---

### Account Flow: Registration Process

```mermaid
sequenceDiagram
    participant U as User Input
    participant P as Patient/Admin/LabTech
    participant A as Account
    participant DB as Database

    U->>P: register()
    P->>A: encryptPassword(password)
    A-->>P: hashedPassword
    P->>P: set $this->role
    P->>P: set $this->password = hashedPassword
    P->>A: insert()
    A->>DB: CALL insert_user(...)
    DB-->>A: p_user_id
    A-->>P: success
    P-->>U: true/false
```

---

### Database to Application Mapping

```mermaid
flowchart LR
    subgraph "Database Layer"
        SQL_TABLES["SQL Tables<br/>(users, user_role, diagnosis, logs)"]
        SQL_VIEWS["SQL Views<br/>(view_user_roles, etc.)"]
        SQL_PROCS["Stored Procedures<br/>(insert_user, soft_delete_user, etc.)"]
        SQL_FUNCS["Functions<br/>(has_user, is_deleted, etc.)"]
    end

    subgraph "Application Layer"
        PHP_ACCOUNT["Account Class<br/>(base class)"]
        PHP_ROLES["role enum<br/>(PATIENT, ADMIN, etc.)"]
        PHP_SPECIFIC["Specific Classes<br/>(Patient, Admin, LabTech)"]
    end

    SQL_TABLES -->|SELECT/INSERT| PHP_ACCOUNT
    SQL_VIEWS -->|getUserById| PHP_ACCOUNT
    SQL_PROCS -->|CALL| PHP_ACCOUNT
    SQL_FUNCS -->|hasUser/isDeleted| PHP_ACCOUNT
    PHP_ROLES -->|role assignment| PHP_SPECIFIC
