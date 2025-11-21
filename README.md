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

- **Token de Usuario**: Se obtiene al hacer login como owner/admin. Usado para gestionar negocios, usuarios, staff, campaigns, rewards, custom fields y customers.
- **Token de Staff**: Se obtiene al hacer login como staff. Usado para operaciones del staff (punto de venta, escaneo, etc.).
- **Token de Customer**: Se obtiene al hacer login o registro como customer. Usado para que los customers accedan a su información y campaigns.

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
        "user": {
            /* objeto user */
        },
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
    "data": {
        /* objeto user */
    }
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
    "data": {
        /* objeto user */
    }
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
    "data": {
        /* objeto user actualizado */
    }
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
    "data": {
        /* objeto business */
    }
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
    "data": {
        /* objeto business */
    }
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
    "data": {
        /* objeto business */
    }
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
    "data": {
        /* objeto business actualizado */
    }
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
    "data": {
        /* objeto staff */
    }
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
    "data": {
        /* objeto staff */
    }
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
    "data": {
        /* objeto staff actualizado */
    }
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
    "data": {
        /* objeto staff actualizado */
    }
}
```

---

### 🎯 Campaigns (Solo Owners con Token de Usuario)

**Nota:** Estas rutas requieren autenticación con token de **usuario (owner/admin)**, no token de staff.

#### 23. Listar Campaigns

**GET** `/api/v1/campaigns`

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
            "type": "punch|streak",
            "name": "string",
            "description": "string|null",
            "limit": "integer|null",
            "redeemed_count": "integer",
            "reward_json": "object|null",
            "required_stamps": "integer|null",
            "active": "boolean",
            "cover_image": "string|null",
            "cover_color": "string|null",
            "logo_url": "string|null",
            "streak_time_limit_hours": "integer|null",
            "streak_reset_time": "string|null (HH:mm:ss)",
            "per_customer_limit": "integer|null",
            "per_week_limit": "integer|null",
            "per_month_limit": "integer|null",
            "max_redemptions_per_day": "integer|null",
            "created_at": "datetime",
            "updated_at": "datetime",
            "business": {
                "id": "uuid",
                "slug": "string",
                "name": "string"
            },
            "rewards": [
                {
                    "id": "uuid",
                    "name": "string",
                    "type": "punch|streak",
                    "description": "string|null",
                    "image_url": "string|null",
                    "reward_json": "object|null",
                    "pivot": {
                        "threshold_int": "integer",
                        "per_customer_limit": "integer|null",
                        "global_limit": "integer|null",
                        "redeemed_count": "integer",
                        "active": "boolean",
                        "sort_order": "integer"
                    }
                }
            ]
        }
    ]
}
```

---

#### 24. Crear Campaign

**POST** `/api/v1/campaigns`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:** (Puedes usar `reward_ids` para reutilizar rewards existentes O `rewards` para crear nuevos)

**Opción 1: Reutilizar rewards existentes (recomendado)**

```json
{
  "type": "punch|streak (required)",
  "name": "string (required, max:255)",
  "description": "string|null",
  "limit": "integer|null (min:1)",
  "reward_json": "object|null",
  "required_stamps": "integer|null (min:1, solo para tipo punch)",
  "active": "boolean|null (default: true)",
  "cover_image": "string|null (URL válida, max:500)",
  "cover_color": "string|null (hexadecimal: #FF5733)",
  "logo_url": "string|null (URL válida, max:500)",
  "streak_time_limit_hours": "integer|null (min:1, solo para tipo streak)",
  "streak_reset_time": "string|null (formato: HH:mm:ss, solo para tipo streak)",
  "per_customer_limit": "integer|null (min:1)",
  "per_week_limit": "integer|null (min:1)",
  "per_month_limit": "integer|null (min:1)",
  "max_redemptions_per_day": "integer|null (min:1)",
  "reward_ids": ["uuid", "uuid", ...],
  "reward_pivot_data": [
    {
      "threshold_int": "integer (required, min:1)",
      "per_customer_limit": "integer|null (min:1)",
      "global_limit": "integer|null (min:1)",
      "active": "boolean|null (default: true)",
      "sort_order": "integer|null (default: 0)"
    }
  ]
}
```

**Opción 2: Crear nuevos rewards**

```json
{
    "type": "punch|streak (required)",
    "name": "string (required, max:255)",
    "description": "string|null",
    "limit": "integer|null (min:1)",
    "reward_json": "object|null",
    "required_stamps": "integer|null (min:1, solo para tipo punch)",
    "active": "boolean|null (default: true)",
    "cover_image": "string|null (URL válida, max:500)",
    "cover_color": "string|null (hexadecimal: #FF5733)",
    "logo_url": "string|null (URL válida, max:500)",
    "streak_time_limit_hours": "integer|null (min:1, solo para tipo streak)",
    "streak_reset_time": "string|null (formato: HH:mm:ss, solo para tipo streak)",
    "per_customer_limit": "integer|null (min:1)",
    "per_week_limit": "integer|null (min:1)",
    "per_month_limit": "integer|null (min:1)",
    "max_redemptions_per_day": "integer|null (min:1)",
    "rewards": [
        {
            "name": "string (required, max:255)",
            "type": "punch|streak (required, debe coincidir con campaign.type)",
            "description": "string|null",
            "image_url": "string|null (URL válida, max:500)",
            "reward_json": "object|null",
            "threshold_int": "integer (required, min:1)",
            "per_customer_limit": "integer|null (min:1)",
            "global_limit": "integer|null (min:1)",
            "active": "boolean|null (default: true)",
            "sort_order": "integer|null (default: 0)"
        }
    ]
}
```

**Reglas de validación:**

- **NO** puedes enviar `reward_ids` y `rewards` al mismo tiempo
- Si `type = "punch"`: Solo puede haber **1 reward** tipo punch
- Si `type = "streak"`: Puede haber **N rewards** tipo streak
- Los rewards deben coincidir con el tipo de la campaign
- El `business_id` se asigna automáticamente (no se envía en el request)
- Los rewards son **templates independientes** que se reutilizan entre campaigns

**Ejemplo 1: Tipo "punch" reutilizando reward existente**

```json
{
    "type": "punch",
    "name": "Campaña de Café",
    "description": "Después de 8 visitas obtén un café gratis",
    "required_stamps": 8,
    "reward_ids": ["uuid-del-reward-cafe-gratis"],
    "reward_pivot_data": [
        {
            "threshold_int": 8,
            "per_customer_limit": 1,
            "global_limit": 100,
            "active": true
        }
    ]
}
```

**Ejemplo 2: Tipo "punch" creando nuevo reward**

```json
{
    "type": "punch",
    "name": "Campaña de Café",
    "description": "Después de 8 visitas obtén un café gratis",
    "required_stamps": 8,
    "rewards": [
        {
            "name": "Café Gratis",
            "type": "punch",
            "description": "Un café gratis de cualquier tamaño",
            "image_url": "https://example.com/cafe.jpg",
            "threshold_int": 8,
            "per_customer_limit": 1,
            "global_limit": 100,
            "active": true
        }
    ]
}
```

**Ejemplo 3: Tipo "streak" reutilizando rewards existentes**

```json
{
    "type": "streak",
    "name": "Desafío de Rachas",
    "description": "Mantén tu racha y gana premios",
    "streak_time_limit_hours": 24,
    "streak_reset_time": "00:00:00",
    "reward_ids": ["uuid-reward-10", "uuid-reward-20", "uuid-reward-50"],
    "reward_pivot_data": [
        {
            "threshold_int": 10,
            "per_customer_limit": 1,
            "global_limit": 50,
            "active": true,
            "sort_order": 0
        },
        {
            "threshold_int": 20,
            "per_customer_limit": 1,
            "global_limit": 30,
            "active": true,
            "sort_order": 1
        },
        {
            "threshold_int": 50,
            "per_customer_limit": 1,
            "global_limit": 20,
            "active": true,
            "sort_order": 2
        }
    ]
}
```

**Ejemplo 4: Tipo "streak" creando nuevos rewards**

```json
{
    "type": "streak",
    "name": "Desafío de Rachas",
    "description": "Mantén tu racha y gana premios",
    "streak_time_limit_hours": 24,
    "streak_reset_time": "00:00:00",
    "rewards": [
        {
            "name": "Descuento 10%",
            "type": "streak",
            "description": "Después de 10 días consecutivos",
            "threshold_int": 10,
            "per_customer_limit": 1,
            "global_limit": 50,
            "active": true,
            "sort_order": 0
        },
        {
            "name": "Descuento 20%",
            "type": "streak",
            "description": "Después de 20 días consecutivos",
            "threshold_int": 20,
            "per_customer_limit": 1,
            "global_limit": 30,
            "active": true,
            "sort_order": 1
        }
    ]
}
```

**Respuesta exitosa (201):**

```json
{
    "success": true,
    "message": "Campaign created successfully.",
    "data": {
        /* objeto campaign con rewards */
    }
}
```

---

#### 25. Obtener Campaign

**GET** `/api/v1/campaigns/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID de la campaign

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": {
        /* objeto campaign con rewards */
    }
}
```

---

#### 26. Actualizar Campaign

**PUT/PATCH** `/api/v1/campaigns/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID de la campaign

**Body:** (todos los campos son opcionales)

```json
{
    "type": "punch|streak (sometimes)",
    "name": "string (sometimes, max:255)",
    "description": "string|null",
    "limit": "integer|null (min:1)",
    "reward_json": "object|null",
    "required_stamps": "integer|null (min:1)",
    "active": "boolean|null",
    "cover_image": "string|null (URL válida, max:500)",
    "cover_color": "string|null (hexadecimal: #FF5733)",
    "logo_url": "string|null (URL válida, max:500)",
    "streak_time_limit_hours": "integer|null (min:1)",
    "streak_reset_time": "string|null (formato: HH:mm:ss)",
    "per_customer_limit": "integer|null (min:1)",
    "per_week_limit": "integer|null (min:1)",
    "per_month_limit": "integer|null (min:1)",
    "max_redemptions_per_day": "integer|null (min:1)"
}
```

**Nota:** Los rewards se gestionan a través de los endpoints de Rewards. Para asociar/desasociar rewards de una campaign, usa los endpoints de Rewards.

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Campaign updated successfully.",
    "data": {
        /* objeto campaign actualizado */
    }
}
```

---

#### 27. Eliminar Campaign

**DELETE** `/api/v1/campaigns/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID de la campaign

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Campaign deleted successfully."
}
```

---

### 🏆 Rewards (Solo Owners con Token de Usuario)

**Nota:** Los rewards son **templates independientes** que pueden reutilizarse en múltiples campaigns. Los datos específicos de cada asociación (threshold, límites, contadores) se guardan en la tabla pivot `campaign_reward`.

#### 28. Listar Rewards

**GET** `/api/v1/rewards`

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
            "name": "string",
            "type": "punch|streak",
            "description": "string|null",
            "image_url": "string|null",
            "reward_json": "object|null",
            "created_at": "datetime",
            "updated_at": "datetime",
            "business": {
                "id": "uuid",
                "slug": "string",
                "name": "string"
            },
            "campaigns": [
                {
                    "id": "uuid",
                    "name": "string",
                    "type": "punch|streak",
                    "pivot": {
                        "threshold_int": "integer",
                        "per_customer_limit": "integer|null",
                        "global_limit": "integer|null",
                        "redeemed_count": "integer",
                        "active": "boolean",
                        "sort_order": "integer"
                    }
                }
            ]
        }
    ]
}
```

---

#### 29. Crear Reward (Template)

**POST** `/api/v1/rewards`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:**

```json
{
    "name": "string (required, max:255)",
    "type": "punch|streak (required)",
    "description": "string|null",
    "image_url": "string|null (URL válida, max:500)",
    "reward_json": "object|null"
}
```

**Nota:** Los rewards son templates independientes. No tienen `campaign_id`. Se asocian a campaigns a través de la tabla pivot cuando se crea/actualiza una campaign.

**Ejemplo:**

```json
{
    "name": "Café Gratis",
    "type": "punch",
    "description": "Un café gratis de cualquier tamaño",
    "image_url": "https://example.com/cafe.jpg"
}
```

**Respuesta exitosa (201):**

```json
{
    "success": true,
    "message": "Reward created successfully.",
    "data": {
        "id": "uuid",
        "business_id": "uuid",
        "name": "string",
        "type": "punch|streak",
        "description": "string|null",
        "image_url": "string|null",
        "reward_json": "object|null",
        "created_at": "datetime",
        "updated_at": "datetime",
        "business": {
            /* objeto business */
        },
        "campaigns": []
    }
}
```

---

#### 30. Obtener Reward

**GET** `/api/v1/rewards/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del reward

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "business_id": "uuid",
        "name": "string",
        "type": "punch|streak",
        "description": "string|null",
        "image_url": "string|null",
        "reward_json": "object|null",
        "created_at": "datetime",
        "updated_at": "datetime",
        "business": {
            /* objeto business */
        },
        "campaigns": [
            /* array de campaigns con datos del pivot */
        ]
    }
}
```

---

#### 31. Actualizar Reward

**PUT/PATCH** `/api/v1/rewards/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del reward

**Body:** (todos los campos son opcionales)

```json
{
    "name": "string (sometimes, max:255)",
    "type": "punch|streak (sometimes)",
    "description": "string|null",
    "image_url": "string|null (URL válida, max:500)",
    "reward_json": "object|null"
}
```

**Nota:** Si cambias el `type` de un reward, verifica que no esté asociado a campaigns con tipo diferente.

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Reward updated successfully.",
    "data": {
        /* objeto reward actualizado */
    }
}
```

---

#### 32. Eliminar Reward

**DELETE** `/api/v1/rewards/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del reward

**Body:** Ninguno

**Nota:** Al eliminar un reward, se eliminan automáticamente todas sus asociaciones con campaigns (registros en `campaign_reward`).

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Reward deleted successfully."
}
```

---

### 📋 Custom Fields (Solo Owners con Token de Usuario)

Los Custom Fields son campos personalizados que los owners pueden crear para sus campaigns. Estos campos se pueden reutilizar en múltiples campaigns.

#### 33. Listar Custom Fields

**GET** `/api/v1/custom-fields`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:** Ninguno

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "business_id": "uuid",
            "key": "customer_name",
            "label": "Nombre del Cliente",
            "description": "Ingresa tu nombre completo",
            "type": "text",
            "required": true,
            "extra": {
                "placeholder": "Ej: Juan Pérez",
                "max_length": 100
            },
            "active": true,
            "created_at": "2025-11-20T06:11:31.000000Z",
            "updated_at": "2025-11-20T06:11:31.000000Z",
            "options": [],
            "validations": []
        }
    ]
}
```

---

#### 34. Crear Custom Field

**POST** `/api/v1/custom-fields`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:**

```json
{
    "key": "string (required, único por business, solo minúsculas, números y guiones bajos)",
    "label": "string (required, max:255)",
    "description": "string (opcional)",
    "type": "text|number|date|boolean|select (required)",
    "required": "boolean (opcional, default: false)",
    "extra": "object (opcional, configuraciones según el tipo)",
    "active": "boolean (opcional, default: true)",
    "options": "array (required si type=select)",
    "options[].value": "string (required)",
    "options[].label": "string (required)",
    "options[].sort_order": "integer (opcional)",
    "validations": "array (opcional)",
    "validations[].operator": "string (required, =|!=|>|>=|<|<=|in|not_in|regex)",
    "validations[].value_string": "string (opcional)",
    "validations[].value_number": "number (opcional)",
    "validations[].value_date": "date (opcional)",
    "validations[].message": "string (opcional, max:500)"
}
```

**Ejemplo para campo tipo text:**

```json
{
    "key": "customer_name",
    "label": "Nombre del Cliente",
    "description": "Ingresa tu nombre completo",
    "type": "text",
    "required": true,
    "extra": {
        "placeholder": "Ej: Juan Pérez",
        "max_length": 100
    }
}
```

**Ejemplo para campo tipo select:**

```json
{
    "key": "gender",
    "label": "Género",
    "type": "select",
    "required": false,
    "options": [
        { "value": "male", "label": "Masculino", "sort_order": 0 },
        { "value": "female", "label": "Femenino", "sort_order": 1 },
        { "value": "other", "label": "Otro", "sort_order": 2 }
    ]
}
```

**Respuesta exitosa (201):**

```json
{
  "success": true,
  "message": "Custom field created successfully.",
  "data": {
    "id": "uuid",
    "business_id": "uuid",
    "key": "customer_name",
    "label": "Nombre del Cliente",
    "description": "Ingresa tu nombre completo",
    "type": "text",
    "required": true,
    "extra": {...},
    "active": true,
    "created_at": "2025-11-20T06:11:31.000000Z",
    "updated_at": "2025-11-20T06:11:31.000000Z",
    "options": [],
    "validations": []
  }
}
```

**Errores comunes:**

- `422`: Key duplicado en el business
- `422`: Campo tipo select sin opciones
- `422`: Key con formato inválido (solo minúsculas, números y guiones bajos)

---

#### 35. Obtener Custom Field

**GET** `/api/v1/custom-fields/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del custom field

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "business_id": "uuid",
    "key": "customer_name",
    "label": "Nombre del Cliente",
    "description": "Ingresa tu nombre completo",
    "type": "text",
    "required": true,
    "extra": {...},
    "active": true,
    "created_at": "2025-11-20T06:11:31.000000Z",
    "updated_at": "2025-11-20T06:11:31.000000Z",
    "options": [...],
    "validations": [...]
  }
}
```

---

#### 36. Actualizar Custom Field

**PUT/PATCH** `/api/v1/custom-fields/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del custom field

**Body:**

```json
{
    "label": "string (opcional)",
    "description": "string (opcional)",
    "required": "boolean (opcional)",
    "extra": "object (opcional)",
    "active": "boolean (opcional)",
    "options": "array (opcional, para campos select)",
    "validations": "array (opcional)"
}
```

**Nota:** El `type` y `key` **NO se pueden editar** después de crear el field.

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "message": "Custom field updated successfully.",
  "data": {...}
}
```

---

#### 37. Eliminar Custom Field

**DELETE** `/api/v1/custom-fields/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del custom field

**Body:** Ninguno

**Nota:** Solo se puede eliminar si **no tiene valores de customers**. Si tiene valores, solo se puede desactivar usando el endpoint de toggle.

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Custom field deleted successfully."
}
```

**Error si tiene valores (422):**

```json
{
    "success": false,
    "message": "Cannot delete custom field that has customer values. You can only disable it."
}
```

---

#### 38. Activar/Desactivar Custom Field

**PATCH** `/api/v1/custom-fields/{id}/toggle`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del custom field

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Custom field status updated successfully.",
    "data": {
        "id": "uuid",
        "label": "Nombre del Cliente",
        "active": false
    }
}
```

---

### 📋 Campaigns - Custom Fields (Asociación)

#### 39. Asociar Custom Fields a Campaign

**POST** `/api/v1/campaigns/{campaign_id}/custom-fields`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `campaign_id`: UUID de la campaign

**Body:**

```json
{
    "custom_field_ids": ["uuid1", "uuid2", "uuid3"],
    "sort_orders": [0, 1, 2],
    "required_overrides": [true, false, null]
}
```

**Nota:** Los `sort_orders` y `required_overrides` son opcionales. Si no se proporcionan, se usan los valores por defecto del field.

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "message": "Custom fields associated successfully.",
  "data": [...]
}
```

---

#### 40. Listar Custom Fields de Campaign

**GET** `/api/v1/campaigns/{campaign_id}/custom-fields`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `campaign_id`: UUID de la campaign

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "key": "customer_name",
      "label": "Nombre del Cliente",
      "type": "text",
      "required": true,
      "pivot": {
        "sort_order": 0,
        "required_override": null
      },
      "options": [...],
      "validations": [...]
    }
  ]
}
```

---

#### 41. Desasociar Custom Field de Campaign

**DELETE** `/api/v1/campaigns/{campaign_id}/custom-fields/{field_id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `campaign_id`: UUID de la campaign
- `field_id`: UUID del custom field

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Custom field disassociated successfully."
}
```

---

### 📋 Campaigns - Crear con Custom Fields

Al crear una campaign, puedes incluir custom fields de dos formas:

#### Opción 1: Usar Custom Fields Existentes

```json
{
    "type": "punch",
    "name": "Mi Campaign",
    "custom_field_ids": ["uuid1", "uuid2"]
}
```

#### Opción 2: Crear Custom Fields Inline

```json
{
    "type": "punch",
    "name": "Mi Campaign",
    "custom_fields": [
        {
            "key": "phone_number",
            "label": "Teléfono",
            "type": "text",
            "required": true,
            "extra": {
                "placeholder": "+52 123 456 7890"
            }
        },
        {
            "key": "gender",
            "label": "Género",
            "type": "select",
            "required": false,
            "options": [
                { "value": "male", "label": "Masculino", "sort_order": 0 },
                { "value": "female", "label": "Femenino", "sort_order": 1 }
            ]
        }
    ]
}
```

**Nota:** Los custom fields creados inline se crean primero, luego se asocian automáticamente a la campaign.

---

## 👥 Customers

### 42. Verificar Teléfono

**POST** `/api/v1/customers/check-phone`

**Autenticación:** No requerida (Público)

**Body:**

```json
{
    "phone": "string (required, formato: +521234567890)",
    "business_slug": "string (required)"
}
```

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "exists": true,
    "message": "Este número ya está registrado."
}
```

**Respuesta si no existe (200):**

```json
{
    "success": true,
    "exists": false,
    "message": "Número disponible para registro."
}
```

---

### 43. Registro de Customer

**POST** `/api/v1/customers/register`

**Autenticación:** No requerida (Público)

**Body:**

```json
{
    "phone": "string (required, formato: +521234567890)",
    "name": "string (required, max:255)",
    "business_slug": "string (required)",
    "otp_code": "string (required, 6 dígitos)"
}
```

**Nota:** Debes enviar el OTP primero usando `/api/v1/otp/send` y luego verificar con `/api/v1/otp/verify` antes de registrar.

**Respuesta exitosa (201):**

```json
{
    "success": true,
    "message": "Customer registered successfully.",
    "data": {
        "customer": {
            "id": "uuid",
            "short_code": "ABC123",
            "phone": "+521234567890",
            "name": "Juan Pérez"
        },
        "token": "1|token_hash_here"
    }
}
```

---

### 44. Login de Customer

**POST** `/api/v1/customers/login`

**Autenticación:** No requerida (Público)

**Body:**

```json
{
    "phone": "string (required, formato: +521234567890)",
    "business_slug": "string (required)",
    "otp_code": "string (required, 6 dígitos)"
}
```

**Nota:** Debes enviar el OTP primero usando `/api/v1/otp/send` y luego verificar con `/api/v1/otp/verify` antes de hacer login.

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "customer": {
            "id": "uuid",
            "short_code": "ABC123",
            "phone": "+521234567890",
            "name": "Juan Pérez"
        },
        "token": "1|token_hash_here"
    }
}
```

---

### 45. Logout de Customer

**POST** `/api/v1/customers/logout`

**Autenticación:** Requerida (Token de customer)

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Logout successful."
}
```

---

### 46. Obtener Customer Autenticado

**GET** `/api/v1/customers/me`

**Autenticación:** Requerida (Token de customer)

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "short_code": "ABC123",
        "phone": "+521234567890",
        "name": "Juan Pérez",
        "business": {
            "id": "uuid",
            "slug": "mi-negocio",
            "name": "Mi Negocio"
        },
        "campaigns": []
    }
}
```

**Nota:** El campo `campaigns` se implementará cuando se cree el módulo de CustomerCampaign.

---

### 47. Listar Customers (Owner)

**GET** `/api/v1/customers`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "short_code": "ABC123",
            "phone": "+521234567890",
            "name": "Juan Pérez",
            "business": {
                "id": "uuid",
                "slug": "mi-negocio",
                "name": "Mi Negocio"
            },
            "created_at": "2025-11-20T12:00:00+00:00",
            "updated_at": "2025-11-20T12:00:00+00:00"
        }
    ]
}
```

---

### 48. Obtener Customer por ID (Owner)

**GET** `/api/v1/customers/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del customer

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "short_code": "ABC123",
        "phone": "+521234567890",
        "name": "Juan Pérez",
        "business": {
            "id": "uuid",
            "slug": "mi-negocio",
            "name": "Mi Negocio"
        },
        "created_at": "2025-11-20T12:00:00+00:00",
        "updated_at": "2025-11-20T12:00:00+00:00"
    }
}
```

---

### 49. Obtener Customer por Short Code (Owner)

**GET** `/api/v1/customers/code/{code}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `code`: Short code del customer (6 caracteres alfanuméricos)

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "short_code": "ABC123",
        "phone": "+521234567890",
        "name": "Juan Pérez",
        "business": {
            "id": "uuid",
            "slug": "mi-negocio",
            "name": "Mi Negocio"
        },
        "created_at": "2025-11-20T12:00:00+00:00",
        "updated_at": "2025-11-20T12:00:00+00:00"
    }
}
```

---

### 50. Actualizar Customer (Owner)

**PUT/PATCH** `/api/v1/customers/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del customer

**Body:**

```json
{
    "name": "string (optional, max:255)",
    "phone": "string (optional, formato: +521234567890)"
}
```

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Customer updated successfully.",
    "data": {
        "id": "uuid",
        "short_code": "ABC123",
        "phone": "+521234567890",
        "name": "Juan Pérez Actualizado",
        "updated_at": "2025-11-20T12:00:00+00:00"
    }
}
```

---

### 51. Eliminar Customer (Owner)

**DELETE** `/api/v1/customers/{id}`

**Autenticación:** Requerida (Token de usuario - Owner/Admin)

**Parámetros de URL:**

- `id`: UUID del customer

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
    "success": true,
    "message": "Customer deleted successfully."
}
```

---

### 52. Obtener Campaign por Código (Público)

**GET** `/api/v1/campaigns/code/{code}`

**Autenticación:** No requerida (Público)

**Parámetros de URL:**

- `code`: Código de la campaign (4 caracteres alfanuméricos, único globalmente)

**Body:** Ninguno

**Respuesta exitosa (200):**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "code": "A1B2",
    "type": "punch",
    "name": "Mi Campaign",
    "description": "Descripción de la campaign",
    "business": {
      "id": "uuid",
      "slug": "mi-negocio",
      "name": "Mi Negocio"
    },
    "rewards": [...],
    "custom_fields": [...]
  }
}
```

**Nota:** Este endpoint es público y se usa para que los customers puedan acceder a una campaign usando su código único de 4 caracteres.

---

## Separación de Rutas por Tipo de Token

### Rutas Protegidas por Token de Usuario (Owner/Admin)

- `/api/v1/auth/logout` - Logout de usuario
- `/api/v1/auth/me` - Obtener usuario autenticado
- `/api/v1/users/*` - CRUD de usuarios
- `/api/v1/businesses/*` - CRUD de negocios
- `/api/v1/staff/*` - CRUD de staff (solo owners)
- `/api/v1/campaigns/*` - CRUD de campaigns (solo owners)
- `/api/v1/rewards/*` - CRUD de rewards (templates, solo owners)
- `/api/v1/custom-fields/*` - CRUD de custom fields (solo owners)
- `/api/v1/campaigns/{id}/custom-fields/*` - Gestión de custom fields en campaigns
- `/api/v1/customers` - CRUD de customers (solo owners)

### Rutas Protegidas por Token de Staff

- `/api/v1/staff/logout` - Logout de staff
- `/api/v1/staff/me` - Obtener staff autenticado
- Futuras rutas de operaciones del staff (punto de venta, escaneo, etc.)

### Rutas Públicas (Customers)

- `/api/v1/customers/check-phone` - Verificar si un teléfono está registrado
- `/api/v1/customers/register` - Registro de customer (después de OTP)
- `/api/v1/customers/login` - Login de customer (después de OTP)
- `/api/v1/campaigns/code/{code}` - Obtener campaign por código (4 caracteres)

### Rutas Protegidas por Token de Customer

- `/api/v1/customers/logout` - Logout de customer
- `/api/v1/customers/me` - Obtener customer autenticado con sus campaigns

### Rutas Protegidas por Token de Usuario (Customers CRUD)

- `/api/v1/customers` - Listar customers del negocio
- `/api/v1/customers/code/{code}` - Obtener customer por short_code
- `/api/v1/customers/{id}` - Obtener, actualizar o eliminar customer

**Importante:**

- Un token de usuario **NO** puede acceder a rutas protegidas por token de staff
- Un token de staff **NO** puede acceder a rutas protegidas por token de usuario
- Un token de customer **NO** puede acceder a rutas protegidas por token de usuario o staff
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

### Estructura de Rewards y Campaigns

**Rewards (Templates):**

- Los rewards son **templates independientes** almacenados en la tabla `rewards`
- No tienen `campaign_id` - son reutilizables entre múltiples campaigns
- Cada reward tiene: `id`, `business_id`, `name`, `type`, `description`, `image_url`, `reward_json`

**Campaign-Reward (Pivot):**

- La tabla `campaign_reward` almacena la relación many-to-many entre campaigns y rewards
- Cada registro del pivot contiene los datos específicos de la asociación:
    - `threshold_int`: Cuántos punches/días se necesitan para este reward en esta campaign
    - `per_customer_limit`: Límite por cliente en esta campaign
    - `global_limit`: Límite global en esta campaign
    - `redeemed_count`: Contador de canjes en esta campaign
    - `active`: Si está activo en esta campaign
    - `sort_order`: Orden de visualización

**Ventajas:**

- Un reward "Café Gratis" puede reutilizarse en múltiples campaigns con diferentes thresholds
- Los datos del premio (nombre, foto, descripción) se almacenan una sola vez
- Los datos específicos de cada asociación (threshold, límites) se guardan en el pivot
