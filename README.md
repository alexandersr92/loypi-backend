# Loypi API

API REST para el sistema Loypi. Esta documentación describe todos los endpoints disponibles y los objetos requeridos para cada uno.

## Base URL

```
/api/v1
```

## Autenticación

La mayoría de los endpoints requieren autenticación mediante **Sanctum**. Para autenticarte, incluye el token en el header:

```
Authorization: Bearer {token}
```

**Tipos de tokens:**
- **Token de Usuario**: Se obtiene al hacer login como owner/admin. Usado para gestionar negocios, usuarios y staff.
- **Token de Staff**: Se obtiene al hacer login como staff. Usado para operaciones del staff (punto de venta, escaneo, etc.).

Cada tipo de token solo puede acceder a sus rutas correspondientes.

---

## Endpoints

### 🔐 Autenticación

#### 1. Login con Email y Contraseña

**POST** `/api/v1/auth/login`

**Autenticación:** No requerida

**Body:**
```json
{
  "email": "string (required)",
  "password": "string (required)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": "uuid",
      "name": "string",
      "email": "string",
      "phone": "string|null",
      "role": "admin|owner",
      "avatar": "string|null",
      "status": "active|inactive|suspended",
      "timezone": "string|null",
      "locale": "string|null",
      "last_login_at": "datetime|null"
    },
    "token": "string"
  }
}
```

---

#### 2. Login con OTP

**POST** `/api/v1/auth/login-with-otp`

**Autenticación:** No requerida

**Body:**
```json
{
  "phone": "string (required, formato: +521234567890)",
  "code": "string (required, 6 dígitos)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": { /* objeto user */ },
    "token": "string"
  }
}
```

---

#### 3. Logout

**POST** `/api/v1/auth/logout`

**Autenticación:** Requerida

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

#### 4. Obtener Usuario Autenticado

**GET** `/api/v1/auth/me`

**Autenticación:** Requerida

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "string",
    "email": "string",
    "phone": "string|null",
    "role": "admin|owner",
    "avatar": "string|null",
    "status": "active|inactive|suspended",
    "timezone": "string|null",
    "locale": "string|null",
    "last_login_at": "datetime|null"
  }
}
```

---

### 📱 OTP (One-Time Password)

#### 5. Enviar OTP

**POST** `/api/v1/otp/send`

**Autenticación:** No requerida

**Body:**
```json
{
  "phone": "string (required, formato: +521234567890)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "OTP sent successfully."
}
```

---

#### 6. Verificar OTP

**POST** `/api/v1/otp/verify`

**Autenticación:** No requerida

**Body:**
```json
{
  "phone": "string (required, formato: +521234567890)",
  "code": "string (required, 6 dígitos)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "OTP verified successfully."
}
```

---

### 👤 Usuarios

#### 7. Crear Usuario

**POST** `/api/v1/users`

**Autenticación:** No requerida

**Body:**
```json
{
  "name": "string (required, max:255)",
  "email": "string (required, email válido, único, max:255)",
  "password": "string (required, debe cumplir reglas de contraseña)",
  "password_confirmation": "string (required, debe coincidir con password)",
  "phone": "string|null (formato: +521234567890)",
  "avatar": "string|null (URL válida, max:500)",
  "status": "string|null (valores: active|inactive|suspended)",
  "timezone": "string|null (max:50)",
  "locale": "string|null (2 caracteres, ej: es, en)"
}
```

**Nota:** El campo `role` se asigna automáticamente como `owner` para todos los usuarios registrados por API.

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "User created successfully.",
  "data": { /* objeto user */ }
}
```

---

#### 8. Obtener Usuario

**GET** `/api/v1/users/{id}`

**Autenticación:** Requerida

**Parámetros de URL:**
- `id`: UUID del usuario

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { /* objeto user */ }
}
```

---

#### 9. Actualizar Usuario

**PUT/PATCH** `/api/v1/users/{id}`

**Autenticación:** Requerida

**Parámetros de URL:**
- `id`: UUID del usuario

**Body:** (todos los campos son opcionales, usar `sometimes` para campos que se envían)
```json
{
  "name": "string (sometimes, max:255)",
  "email": "string (sometimes, email válido, único, max:255)",
  "password": "string|null (sometimes, debe cumplir reglas de contraseña)",
  "phone": "string|null (formato: +521234567890)",
  "role": "string (sometimes, valores: admin|owner)",
  "avatar": "string|null (URL válida, max:500)",
  "status": "string|null (valores: active|inactive|suspended)",
  "timezone": "string|null (max:50)",
  "locale": "string|null (2 caracteres, ej: es, en)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "User updated successfully.",
  "data": { /* objeto user actualizado */ }
}
```

---

### 🏢 Negocios (Businesses)

#### 10. Crear Negocio

**POST** `/api/v1/businesses`

**Autenticación:** Requerida

**Body:**
```json
{
  "user_id": "uuid (required, debe existir en users, único en businesses)",
  "name": "string (required, max:255)",
  "slug": "string|null (max:255, único, formato: a-z0-9 con guiones)",
  "description": "string|null (max:1000)",
  "logo": "string|null (URL válida, max:500)",
  "branding_json": {
    "primary_color": "string|null (formato hexadecimal: #FF5733)",
    "secondary_color": "string|null (formato hexadecimal: #FF5733)"
  },
  "address": "string|null (max:500)",
  "phone": "string|null (formato: +521234567890)",
  "email": "string|null (email válido, max:255)",
  "website": "string|null (URL válida, max:500)",
  "city": "string|null (max:100)",
  "state": "string|null (max:100)",
  "country": "string|null (max:100)"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Business created successfully.",
  "data": { /* objeto business */ }
}
```

---

#### 11. Obtener Negocio por ID

**GET** `/api/v1/businesses/{id}`

**Autenticación:** Requerida

**Parámetros de URL:**
- `id`: UUID del negocio

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { /* objeto business */ }
}
```

---

#### 12. Obtener Negocio por Slug

**GET** `/api/v1/businesses/slug/{slug}`

**Autenticación:** Requerida

**Parámetros de URL:**
- `slug`: Slug del negocio

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { /* objeto business */ }
}
```

---

#### 13. Actualizar Negocio

**PUT/PATCH** `/api/v1/businesses/{id}`

**Autenticación:** Requerida

**Parámetros de URL:**
- `id`: UUID del negocio

**Body:** (todos los campos son opcionales, usar `sometimes` para campos que se envían)
```json
{
  "user_id": "uuid (sometimes, debe existir en users, único en businesses)",
  "name": "string (sometimes, max:255)",
  "slug": "string|null (max:255, único, formato: a-z0-9 con guiones)",
  "description": "string|null (max:1000)",
  "logo": "string|null (URL válida, max:500)",
  "branding_json": {
    "primary_color": "string|null (formato hexadecimal: #FF5733)",
    "secondary_color": "string|null (formato hexadecimal: #FF5733)"
  },
  "address": "string|null (max:500)",
  "phone": "string|null (formato: +521234567890)",
  "email": "string|null (email válido, max:255)",
  "website": "string|null (URL válida, max:500)",
  "city": "string|null (max:100)",
  "state": "string|null (max:100)",
  "country": "string|null (max:100)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Business updated successfully.",
  "data": { /* objeto business actualizado */ }
}
```

---

### 👨‍💼 Staff (Autenticación)

#### 14. Login de Staff

**POST** `/api/v1/staff/login`

**Autenticación:** No requerida

**Body:**
```json
{
  "business_slug": "string (required, debe existir en businesses)",
  "code": "string (required, código único del staff dentro del negocio)",
  "pin": "string (required, 4-6 caracteres)"
}
```

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "staff": {
      "id": "uuid",
      "code": "string",
      "name": "string",
      "active": "boolean"
    },
    "business": {
      "id": "uuid",
      "slug": "string",
      "name": "string"
    },
    "token": "string"
  }
}
```

**Errores posibles:**
- `403`: Staff inactivo o bloqueado
- `422`: PIN incorrecto (muestra intentos restantes)
- `403`: Cuenta bloqueada (requiere desbloqueo por owner)

---

#### 15. Logout de Staff

**POST** `/api/v1/staff/logout`

**Autenticación:** Requerida (Token de staff)

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

#### 16. Obtener Staff Autenticado

**GET** `/api/v1/staff/me`

**Autenticación:** Requerida (Token de staff)

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "staff": {
      "id": "uuid",
      "code": "string",
      "name": "string",
      "active": "boolean",
      "last_login_at": "datetime|null"
    },
    "business": {
      "id": "uuid",
      "slug": "string",
      "name": "string"
    }
  }
}
```

---

### 👨‍💼 Staff (CRUD - Solo Owners con Token de Usuario)

**Nota:** Estas rutas requieren autenticación con token de **usuario (owner/admin)**, no token de staff.

#### 17. Listar Staff

**GET** `/api/v1/staff`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "business_id": "uuid",
      "code": "string",
      "name": "string",
      "active": "boolean",
      "failed_login_attempts": "integer",
      "locked_until": "datetime|null",
      "last_login_at": "datetime|null",
      "created_at": "datetime",
      "updated_at": "datetime",
      "business": {
        "id": "uuid",
        "slug": "string",
        "name": "string"
      }
    }
  ]
}
```

---

#### 18. Crear Staff

**POST** `/api/v1/staff`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:**
```json
{
  "business_id": "uuid (required, debe ser el negocio del owner autenticado)",
  "code": "string (required, único dentro del negocio, max:50)",
  "name": "string (required, max:255)",
  "pin": "string (required, 4-6 caracteres)",
  "active": "boolean|null (default: true)"
}
```

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "message": "Staff created successfully.",
  "data": { /* objeto staff */ }
}
```

---

#### 19. Obtener Staff

**GET** `/api/v1/staff/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**
- `id`: UUID del staff

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { /* objeto staff */ }
}
```

---

#### 20. Actualizar Staff

**PUT/PATCH** `/api/v1/staff/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**
- `id`: UUID del staff

**Body:** (todos los campos son opcionales)
```json
{
  "code": "string (sometimes, único dentro del negocio, max:50)",
  "name": "string (sometimes, max:255)",
  "pin": "string (sometimes, 4-6 caracteres)",
  "active": "boolean|null"
}
```

**Nota:** Si se actualiza el `pin`, se hashea automáticamente.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Staff updated successfully.",
  "data": { /* objeto staff actualizado */ }
}
```

---

#### 21. Eliminar Staff

**DELETE** `/api/v1/staff/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**
- `id`: UUID del staff

**Body:** Ninguno

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Staff deleted successfully."
}
```

---

#### 22. Desbloquear Staff

**POST** `/api/v1/staff/{id}/unlock`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**
- `id`: UUID del staff

**Body:** Ninguno

**Descripción:** Desbloquea un staff que fue bloqueado por múltiples intentos fallidos de login. Solo el owner del negocio puede desbloquear staff.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Staff unlocked successfully.",
  "data": { /* objeto staff actualizado */ }
}
```

---

## Separación de Rutas por Tipo de Token

### Rutas Protegidas por Token de Usuario (Owner/Admin)
- `/api/v1/auth/logout` - Logout de usuario
- `/api/v1/auth/me` - Obtener usuario autenticado
- `/api/v1/users/*` - CRUD de usuarios
- `/api/v1/businesses/*` - CRUD de negocios
- `/api/v1/staff/*` - CRUD de staff (solo owners)

### Rutas Protegidas por Token de Staff
- `/api/v1/staff/logout` - Logout de staff
- `/api/v1/staff/me` - Obtener staff autenticado
- Futuras rutas de operaciones del staff (punto de venta, escaneo, etc.)

**Importante:** 
- Un token de usuario **NO** puede acceder a rutas protegidas por token de staff
- Un token de staff **NO** puede acceder a rutas protegidas por token de usuario
- Cada tipo de token solo funciona con sus rutas correspondientes

---

## Códigos de Estado HTTP

- `200` - OK: Solicitud exitosa
- `201` - Created: Recurso creado exitosamente
- `400` - Bad Request: Error en la validación de datos
- `401` - Unauthorized: No autenticado
- `403` - Forbidden: No autorizado o cuenta inactiva
- `404` - Not Found: Recurso no encontrado
- `422` - Unprocessable Entity: Error de validación
- `500` - Internal Server Error: Error del servidor

---

## Formatos y Validaciones

### Teléfono
- Formato: `+521234567890`
- Debe incluir código de país
- Regex: `/^\+?[1-9]\d{1,14}$/`

### Slug
- Solo letras minúsculas, números y guiones
- Formato: `mi-negocio-123`
- Regex: `/^[a-z0-9]+(?:-[a-z0-9]+)*$/`

### Colores Hexadecimales
- Formato: `#FF5733`
- Regex: `/^#[0-9A-Fa-f]{6}$/`

### Locale
- 2 caracteres
- Ejemplos: `es`, `en`

### Roles
- `admin`: Administrador
- `owner`: Propietario

### Staff Security
- **PIN**: 4-6 caracteres, hasheado con bcrypt
- **Failed Login Attempts**: Máximo 5 intentos antes de bloqueo
- **Lock Duration**: 30 minutos después de 5 intentos fallidos
- **Unlock**: Solo el owner puede desbloquear staff bloqueado

### Estados de Usuario
- `active`: Activo
- `inactive`: Inactivo
- `suspended`: Suspendido

---

## Notas

- Todos los endpoints devuelven respuestas en formato JSON
- Los campos marcados como `sometimes` en las actualizaciones solo se validan si están presentes en el request
- Los tokens de autenticación expiran según la configuración de Sanctum

