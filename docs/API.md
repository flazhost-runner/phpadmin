# PHPAdmin API Reference

Base URL: `/api/v1`

All endpoints return `Content-Type: application/json`. Authentication via `Authorization: Bearer <jwt>` header.

A ready-to-import Postman collection is available at [`postman/PHPAdmin.postman_collection.json`](postman/PHPAdmin.postman_collection.json). Set the `base_url` variable to `http://localhost:8001` (the port used by `composer start`) and `access_token` to a JWT obtained from the login endpoint.

> **File storage / uploads:** the media library switches between `local` and `oss`/`s3` backends via `.env` only. The DB stores object **keys** and the render URL is built per driver (`/storage/<key>` for local, a presigned redirect for OSS/S3). See the **"Storage & switching backends"** section in [`../README.md`](../README.md#storage--switching-backends).

---

## Auth

### POST /api/v1/auth/login
Authenticate and receive a JWT.

**Request Body**
```json
{ "email": "admin@admin.com", "password": "password" }
```

**Response 200**
```json
{ "token": "<jwt>", "expires_in": 3600 }
```

**Response 401**
```json
{ "error": "Invalid credentials" }
```

---

### POST /api/v1/auth/logout
Blacklist the current JWT (requires auth).

**Response 200**
```json
{ "message": "Logged out" }
```

---

### GET /api/v1/auth/me
Return the authenticated user's profile.

**Response 200**
```json
{
  "id": 1,
  "name": "Admin",
  "email": "admin@admin.com",
  "roles": ["superadmin"]
}
```

---

## Users

### GET /api/v1/users
List all users (paginated).

**Query Params**: `page`, `per_page`, `search`

**Response 200**
```json
{
  "data": [ { "id": 1, "name": "Admin", "email": "admin@admin.com", "status": "active" } ],
  "meta": { "total": 1, "page": 1, "per_page": 15 }
}
```

---

### POST /api/v1/users
Create a new user.

**Request Body**
```json
{ "name": "Alice", "email": "alice@example.com", "password": "secret", "role_ids": [2] }
```

**Response 201**
```json
{ "id": 2, "name": "Alice", "email": "alice@example.com" }
```

---

### GET /api/v1/users/{id}
Get a single user.

**Response 200**
```json
{ "id": 2, "name": "Alice", "email": "alice@example.com", "status": "active", "roles": [] }
```

---

### PUT /api/v1/users/{id}
Update a user.

**Request Body** (partial allowed)
```json
{ "name": "Alice Updated" }
```

**Response 200**
```json
{ "id": 2, "name": "Alice Updated", "email": "alice@example.com" }
```

---

### DELETE /api/v1/users/{id}
Soft-delete a user.

**Response 200**
```json
{ "message": "User deleted" }
```

---

## Roles

### GET /api/v1/roles
List all roles.

**Response 200**
```json
{ "data": [ { "id": 1, "name": "superadmin", "guard_name": "web" } ] }
```

---

### POST /api/v1/roles
Create a new role.

**Request Body**
```json
{ "name": "editor", "guard_name": "web", "permission_ids": [1, 2] }
```

**Response 201**
```json
{ "id": 3, "name": "editor", "guard_name": "web" }
```

---

### GET /api/v1/roles/{id}
Get a single role with its permissions.

**Response 200**
```json
{ "id": 3, "name": "editor", "guard_name": "web", "permissions": [] }
```

---

### PUT /api/v1/roles/{id}
Update a role.

**Response 200**
```json
{ "id": 3, "name": "editor", "guard_name": "web" }
```

---

### DELETE /api/v1/roles/{id}
Delete a role.

**Response 200**
```json
{ "message": "Role deleted" }
```

---

## Permissions

### GET /api/v1/permissions
List all permissions.

**Response 200**
```json
{ "data": [ { "id": 1, "name": "users.view", "guard_name": "web" } ] }
```

---

### POST /api/v1/permissions
Create a permission.

**Request Body**
```json
{ "name": "users.create", "guard_name": "web" }
```

**Response 201**
```json
{ "id": 4, "name": "users.create", "guard_name": "web" }
```

---

### DELETE /api/v1/permissions/{id}
Delete a permission.

**Response 200**
```json
{ "message": "Permission deleted" }
```

---

## Error Responses

All errors follow this shape:

```json
{ "error": "Human-readable message", "code": "MACHINE_CODE" }
```

| HTTP Status | Meaning |
|---|---|
| 400 | Validation error |
| 401 | Unauthenticated |
| 403 | Forbidden (missing permission) |
| 404 | Resource not found |
| 422 | Unprocessable entity |
| 429 | Rate limit exceeded |
| 500 | Internal server error |
