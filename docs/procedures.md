# SQL Stored Procedures

## Tables & Relationships

```mermaid
erDiagram
    USERS ||--o{ USER_ROLE : has
    USERS ||--o{ DIAGNOSIS : has

    USERS {
        int id PK
        string firstname
        string lastname
        string middlename
        enum prefix
        enum suffix
        string gender
        string phone
        point location
        string email
        int age
        computed status
        enum blood
        string password
        string extra
        string employid
        string adminid
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    USER_ROLE {
        int user_id PK, FK
        enum role PK
        timestamp assigned_at
    }

    DIAGNOSIS {
        int id PK
        text notes
        int patient_id FK
        string condition
        int severity
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }
```

## Procedure Flow

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

## Procedure Return Types

### insert_user() Returns User ID

```mermaid
classDiagram
    class InsertUserResult {
        <<OUT Parameter>>
        +int p_user_id
    }

    note for InsertUserResult "Returns the auto-increment ID<br/>of the newly created user"
```

### get_user() Returns User Details

```mermaid
classDiagram
    class GetUserResult {
        <<OUT Parameters>>
        +string user_name
        +string user_email
        +string user_phone
        +boolean is_deleted
    }

    note for GetUserResult "Multiple OUT parameters:<br/>- user_name: first name<br/>- user_email: email address<br/>- user_phone: phone number<br/>- is_deleted: soft delete status"
```

### assign_role() / revoke_role() Return Boolean

```mermaid
classDiagram
    class RoleResult {
        <<OUT Parameter>>
        +boolean result
    }

    note for RoleResult "Returns TRUE if role was<br/>assigned/revoked, FALSE otherwise"
```

### create_diagnosis() Returns Diagnosis ID

```mermaid
classDiagram
    class CreateDiagnosisResult {
        <<OUT Parameter>>
        +int p_diagnosis_id
    }

    note for CreateDiagnosisResult "Returns the auto-increment ID<br/>of the newly created diagnosis"
```