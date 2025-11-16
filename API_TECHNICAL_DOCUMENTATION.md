# Documentación Técnica API - Loypi

## 📋 Índice

1. [Configuración Base](#configuración-base)
2. [Autenticación](#autenticación)
3. [Estructura de URLs](#estructura-de-urls)
4. [Endpoints Completos](#endpoints-completos)
5. [Modelos de Datos](#modelos-de-datos)
6. [Códigos de Estado HTTP](#códigos-de-estado-http)
7. [Manejo de Errores](#manejo-de-errores)
8. [Flujos de Trabajo](#flujos-de-trabajo)
9. [Ejemplos de Integración](#ejemplos-de-integración)
10. [Mejores Prácticas](#mejores-prácticas)

---

## Configuración Base

### URL Base
```
http://loypi-api.test/api
```

### Headers Requeridos
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  // Para endpoints protegidos
```

### Formato de Respuesta
Todas las respuestas exitosas retornan JSON. Las respuestas de error también son JSON con el siguiente formato:

```json
{
    "message": "Mensaje de error descriptivo",
    "errors": {
        "campo": ["Error específico del campo"]
    }
}
```

---

## Autenticación

### Tipos de Usuario

1. **Owner** - Dueño del negocio (email/password)
2. **Staff** - Empleado del negocio (PIN de 4 dígitos)
3. **Customer** - Cliente final (OTP vía WhatsApp)

### Tokens

- Todos los tokens se obtienen mediante autenticación
- Los tokens se envían en el header: `Authorization: Bearer {token}`
- Los tokens expiran según configuración (default: 30 días para owners/staff, 6 meses para customers)
- Los tokens se invalidan al hacer logout

---

## Estructura de URLs

Todas las URLs siguen el patrón:
```
{base_url}/{slug}/{tipo_usuario}/{recurso}
```

Ejemplos:
- `POST /api/owner/auth/login` - Login de owner (sin slug)
- `GET /api/negocio-1/owner/campaigns` - Campañas del owner
- `POST /api/negocio-1/staff/auth/login` - Login de staff
- `GET /api/negocio-1/customer/campaigns` - Campañas para cliente

---

## Endpoints Completos

### 🔐 Autenticación Owner

#### Login
```http
POST /api/owner/auth/login
Content-Type: application/json

{
    "email": "owner1@example.com",
    "password": "password"
}
```

**Respuesta 200:**
```json
{
    "user": {
        "id": "uuid",
        "name": "Owner 1",
        "email": "owner1@example.com",
        "role": "owner"
    },
    "token": "1|xxxxxxxxxxxxx",
    "businesses": [
        {
            "id": "uuid",
            "slug": "negocio-1",
            "name": "Negocio 1",
            "branding_json": {}
        }
    ]
}
```

#### Obtener Info
```http
GET /api/owner/auth/me
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/owner/auth/logout
Authorization: Bearer {token}
```

---

### 🔐 Autenticación Staff

#### Login
```http
POST /api/{slug}/staff/auth/login
Content-Type: application/json

{
    "pin": "1234"
}
```

**Respuesta 200:**
```json
{
    "staff": {
        "id": "uuid",
        "name": "Staff Name",
        "active": true
    },
    "token": "2|xxxxxxxxxxxxx",
    "business": {
        "id": "uuid",
        "slug": "negocio-1",
        "name": "Negocio 1"
    }
}
```

#### Obtener Info
```http
GET /api/{slug}/staff/auth/me
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/{slug}/staff/auth/logout
Authorization: Bearer {token}
```

---

### 🔐 Autenticación Customer

#### Solicitar OTP
```http
POST /api/{slug}/customer/auth/request-otp
Content-Type: application/json

{
    "phone": "+1234567890"
}
```

**Respuesta 200:**
```json
{
    "message": "Código OTP enviado",
    "expires_in": 600
}
```

#### Verificar OTP y Login
```http
POST /api/{slug}/customer/auth/verify-otp
Content-Type: application/json

{
    "phone": "+1234567890",
    "otp": "123456"
}
```

**Respuesta 200:**
```json
{
    "customer": {
        "id": "uuid",
        "short_code": "ABC123",
        "phone": "+1234567890",
        "name": "Customer Name"
    },
    "token": "3|xxxxxxxxxxxxx",
    "customer_token": "token_string",
    "expires_at": "2025-12-11T10:00:00Z"
}
```

#### Obtener Info
```http
GET /api/{slug}/customer/auth/me
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/{slug}/customer/auth/logout
Authorization: Bearer {token}
```

---

### 📊 Campañas (Owner)

#### Listar Campañas
```http
GET /api/{slug}/owner/campaigns?active=true&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `active` (boolean, opcional) - Filtrar por estado
- `per_page` (integer, opcional) - Resultados por página (default: 15)

**Respuesta 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "name": "Campaña de Verano",
            "description": "Descripción",
            "limit": 1000,
            "redeemed_count": 50,
            "required_stamps": 10,
            "active": true,
            "cover_image": null,
            "cover_color": "#FF5733",
            "logo_url": null,
            "rewards": [],
            "customer_campaigns": []
        }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 10
}
```

#### Ver Campaña con Estadísticas
```http
GET /api/{slug}/owner/campaigns/{campaign_id}
Authorization: Bearer {token}
```

**Respuesta 200:**
```json
{
    "id": "uuid",
    "name": "Campaña de Verano",
    "description": "Descripción",
    "limit": 1000,
    "redeemed_count": 50,
    "required_stamps": 10,
    "active": true,
    "rewards": [...],
    "customer_campaigns": [...],
    "customer_streaks": [...],
    "stats": {
        "total_customers": 25,
        "total_stamps": 150,
        "total_redemptions": 50,
        "active_streaks": 5
    }
}
```

#### Ver Clientes en Campaña
```http
GET /api/{slug}/owner/campaigns/{campaign_id}/customers?search=&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (string, opcional) - Buscar por nombre, teléfono o código
- `per_page` (integer, opcional) - Resultados por página

**Respuesta 200:**
```json
{
    "campaign": {
        "id": "uuid",
        "name": "Campaña de Verano"
    },
    "customers": {
        "data": [
            {
                "id": "uuid",
                "customer_id": "uuid",
                "campaign_id": "uuid",
                "stamps": 8,
                "redeemed_at": null,
                "customer": {
                    "id": "uuid",
                    "short_code": "ABC123",
                    "phone": "+1234567890",
                    "name": "Customer Name"
                },
                "stamps": [
                    {
                        "id": "uuid",
                        "created_at": "2025-01-01T10:00:00Z",
                        "staff": {
                            "id": "uuid",
                            "name": "Staff Name"
                        }
                    }
                ],
                "reward_unlocks": []
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 25
    }
}
```

---

### 🎯 Campañas (Staff)

#### Listar Campañas
```http
GET /api/{slug}/staff/campaigns?active=true&per_page=15
Authorization: Bearer {token}
```

#### Crear Campaña
```http
POST /api/{slug}/staff/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Campaña de Verano",
    "description": "Obtén descuentos especiales",
    "limit": 1000,
    "required_stamps": 10,
    "active": true,
    "cover_color": "#FF5733",
    "reward_json": {
        "title": "Descuento del 20%"
    }
}
```

#### Ver Campaña
```http
GET /api/{slug}/staff/campaigns/{campaign_id}
Authorization: Bearer {token}
```

#### Actualizar Campaña
```http
PUT /api/{slug}/staff/campaigns/{campaign_id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Campaña Actualizada",
    "active": false
}
```

#### Eliminar Campaña
```http
DELETE /api/{slug}/staff/campaigns/{campaign_id}
Authorization: Bearer {token}
```

---

### 🎁 Premios (Staff)

#### Listar Premios
```http
GET /api/{slug}/staff/campaigns/{campaign_id}/rewards?active=true
Authorization: Bearer {token}
```

#### Crear Premio Punch
```http
POST /api/{slug}/staff/campaigns/{campaign_id}/rewards
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Premio por 10 Sellos",
    "type": "punch",
    "threshold_int": 10,
    "description": "Obtén 10 sellos para desbloquear",
    "per_customer_limit": 1,
    "global_limit": 100,
    "active": true,
    "reward_json": {
        "title": "Descuento del 20%",
        "value": 20
    }
}
```

#### Crear Premio Streak
```http
POST /api/{slug}/staff/campaigns/{campaign_id}/rewards
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Premio por 7 Días de Racha",
    "type": "streak",
    "threshold_int": 7,
    "description": "Visita 7 días seguidos",
    "per_customer_limit": null,
    "global_limit": null,
    "active": true,
    "reward_json": {
        "title": "Producto Gratis"
    }
}
```

#### Ver Premio
```http
GET /api/{slug}/staff/campaigns/{campaign_id}/rewards/{reward_id}
Authorization: Bearer {token}
```

#### Actualizar Premio
```http
PUT /api/{slug}/staff/campaigns/{campaign_id}/rewards/{reward_id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Premio Actualizado",
    "active": false
}
```

#### Eliminar Premio
```http
DELETE /api/{slug}/staff/campaigns/{campaign_id}/rewards/{reward_id}
Authorization: Bearer {token}
```

---

### 🎫 Sellos (Staff)

#### Agregar Sello
```http
POST /api/{slug}/staff/stamps
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_id": "uuid",
    "campaign_id": "uuid",
    "meta": {
        "notes": "Cliente visitó el local"
    }
}
```

**Respuesta 200:**
```json
{
    "stamp": {
        "id": "uuid",
        "customer_campaign_id": "uuid",
        "staff_id": "uuid",
        "meta": {},
        "created_at": "2025-01-01T10:00:00Z"
    },
    "customer_campaign": {
        "id": "uuid",
        "stamps": 9,
        "campaign": {
            "id": "uuid",
            "name": "Campaña de Verano"
        }
    }
}
```

**Nota:** Este endpoint automáticamente:
- Incrementa el contador de sellos
- Actualiza las rachas si aplica
- Verifica y desbloquea premios si se cumplen las condiciones

#### Listar Sellos
```http
GET /api/{slug}/staff/stamps?customer_id={uuid}&campaign_id={uuid}&per_page=15
Authorization: Bearer {token}
```

---

### 💰 Canjes (Staff)

#### Canjear Premio
```http
POST /api/{slug}/staff/redemptions
Authorization: Bearer {token}
Content-Type: application/json

{
    "customer_campaign_id": "uuid",
    "reward_id": "uuid",
    "meta": {
        "require_pin": false,
        "notes": "Canje realizado en caja"
    }
}
```

**Respuesta 200:**
```json
{
    "redemption": {
        "id": "uuid",
        "customer_campaign_id": "uuid",
        "reward_id": "uuid",
        "staff_id": "uuid",
        "confirmed_at": "2025-01-01T10:00:00Z",
        "meta": {},
        "customer_campaign": {...},
        "reward": {...},
        "staff": {...}
    }
}
```

**Errores posibles:**
- `400`: Premio no desbloqueado o ya canjeado
- `400`: Premio expirado
- `400`: Límite global alcanzado

#### Listar Canjes
```http
GET /api/{slug}/staff/redemptions?customer_id={uuid}&reward_id={uuid}&per_page=15
Authorization: Bearer {token}
```

---

### 👥 Clientes (Staff)

#### Listar Clientes
```http
GET /api/{slug}/staff/customers?search=&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (string, opcional) - Buscar por nombre, teléfono o código

#### Ver Cliente
```http
GET /api/{slug}/staff/customers/{customer_id}
Authorization: Bearer {token}
```

**Respuesta 200:**
```json
{
    "id": "uuid",
    "short_code": "ABC123",
    "phone": "+1234567890",
    "name": "Customer Name",
    "customer_campaigns": [
        {
            "id": "uuid",
            "campaign": {...},
            "stamps": 8,
            "stamps": [...]
        }
    ],
    "customer_streaks": [...],
    "customer_field_values": [...]
}
```

#### Actualizar Cliente
```http
PUT /api/{slug}/staff/customers/{customer_id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Nombre Actualizado",
    "phone": "+1234567890"
}
```

---

### 👨‍💼 Staff Management

#### Listar Staff
```http
GET /api/{slug}/staff/staff?active=true&per_page=15
Authorization: Bearer {token}
```

#### Crear Staff
```http
POST /api/{slug}/staff/staff
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Nuevo Staff",
    "passcode": "5678",
    "active": true
}
```

#### Ver Staff
```http
GET /api/{slug}/staff/staff/{staff_id}
Authorization: Bearer {token}
```

#### Actualizar Staff
```http
PUT /api/{slug}/staff/staff/{staff_id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Staff Actualizado",
    "passcode": "9999",
    "active": false
}
```

#### Eliminar Staff
```http
DELETE /api/{slug}/staff/staff/{staff_id}
Authorization: Bearer {token}
```

---

### 📱 Campañas (Customer)

#### Listar Campañas Activas
```http
GET /api/{slug}/customer/campaigns?active=true
Authorization: Bearer {token}
```

#### Ver Campaña
```http
GET /api/{slug}/customer/campaigns/{campaign_id}
Authorization: Bearer {token}
```

---

### 🎁 Premios (Customer)

#### Ver Premios Desbloqueados
```http
GET /api/{slug}/customer/rewards/unlocked?redeemed=false&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `redeemed` (boolean, opcional) - `true` para solo canjeados, `false` para no canjeados

**Respuesta 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "reward_id": "uuid",
            "customer_campaign_id": "uuid",
            "unlocked_at": "2025-01-01T10:00:00Z",
            "expires_at": null,
            "redeemed_at": null,
            "reward": {
                "id": "uuid",
                "name": "Premio por 10 Sellos",
                "type": "punch",
                "campaign": {...}
            },
            "customer_campaign": {...}
        }
    ]
}
```

---

### 📊 Progreso (Customer)

#### Ver Mi Progreso
```http
GET /api/{slug}/customer/progress
Authorization: Bearer {token}
```

**Respuesta 200:**
```json
{
    "customer_campaigns": [
        {
            "id": "uuid",
            "stamps": 8,
            "campaign": {
                "id": "uuid",
                "name": "Campaña de Verano",
                "required_stamps": 10
            },
            "stamps": [...]
        }
    ],
    "streaks": [
        {
            "id": "uuid",
            "current_streak": 5,
            "longest_streak": 7,
            "last_event_date": "2025-01-01",
            "campaign": {...}
        }
    ],
    "unlocked_rewards": [...]
}
```

---

## Modelos de Datos

### Campaign
```typescript
interface Campaign {
    id: string;                    // UUID
    business_id: string;            // UUID
    name: string;
    description: string | null;
    limit: number | null;           // Límite global de canjes
    redeemed_count: number;         // Cache de canjes
    reward_json: object | null;     // Detalle libre del premio
    required_stamps: number | null; // Para punch card
    active: boolean;
    cover_image: string | null;
    cover_color: string | null;
    logo_url: string | null;
    created_at: string;             // ISO 8601
    updated_at: string;             // ISO 8601
}
```

### Reward
```typescript
interface Reward {
    id: string;                    // UUID
    campaign_id: string;            // UUID
    name: string;
    type: 'punch' | 'streak' | 'points';
    threshold_int: number;          // Sellos o días requeridos
    description: string | null;
    per_customer_limit: number | null;
    global_limit: number | null;
    redeemed_count: number;
    active: boolean;
    reward_json: object | null;
    created_at: string;
    updated_at: string;
}
```

### Customer
```typescript
interface Customer {
    id: string;                    // UUID
    short_code: string;             // 6 caracteres, único
    phone: string | null;
    name: string | null;
    created_at: string;
    updated_at: string;
}
```

### CustomerCampaign
```typescript
interface CustomerCampaign {
    id: string;                    // UUID
    customer_id: string;            // UUID
    campaign_id: string;             // UUID
    stamps: number;                 // Contador de sellos
    redeemed_at: string | null;      // ISO 8601
    created_at: string;
    updated_at: string;
}
```

### Stamp
```typescript
interface Stamp {
    id: string;                    // UUID
    customer_campaign_id: string;   // UUID
    staff_id: string;               // UUID
    meta: object | null;
    created_at: string;
    updated_at: string;
}
```

### RewardUnlock
```typescript
interface RewardUnlock {
    id: string;                    // UUID
    reward_id: string;              // UUID
    customer_campaign_id: string;   // UUID
    unlocked_at: string;            // ISO 8601
    expires_at: string | null;     // ISO 8601
    redeemed_at: string | null;     // ISO 8601
    redemption_id: string | null;   // UUID
}
```

### Redemption
```typescript
interface Redemption {
    id: string;                    // UUID
    customer_campaign_id: string;   // UUID
    reward_id: string;              // UUID
    staff_id: string;               // UUID
    confirmed_at: string;           // ISO 8601
    meta: object | null;
    created_at: string;
    updated_at: string;
}
```

### CustomerStreak
```typescript
interface CustomerStreak {
    id: string;                    // UUID
    customer_id: string;            // UUID
    campaign_id: string;            // UUID
    current_streak: number;
    longest_streak: number;
    last_event_date: string | null; // YYYY-MM-DD
    created_at: string;
    updated_at: string;
}
```

---

## Códigos de Estado HTTP

### Éxito
- `200 OK` - Petición exitosa
- `201 Created` - Recurso creado exitosamente

### Errores del Cliente
- `400 Bad Request` - Solicitud mal formada
- `401 Unauthorized` - No autenticado
- `403 Forbidden` - No autorizado
- `404 Not Found` - Recurso no encontrado
- `422 Unprocessable Entity` - Error de validación
- `429 Too Many Requests` - Rate limit excedido

### Errores del Servidor
- `500 Internal Server Error` - Error interno

---

## Manejo de Errores

### Formato de Error
```json
{
    "message": "Mensaje de error general",
    "errors": {
        "campo": [
            "Error específico del campo 1",
            "Error específico del campo 2"
        ]
    }
}
```

### Errores Comunes

#### 401 Unauthorized
```json
{
    "message": "No autenticado"
}
```
**Solución:** Verificar que el token esté presente y sea válido.

#### 403 Forbidden
```json
{
    "message": "No autorizado para este negocio"
}
```
**Solución:** Verificar que el usuario pertenezca al negocio del slug.

#### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password must be at least 8 characters."
        ]
    }
}
```

#### 404 Not Found
```json
{
    "message": "Campaña no encontrada"
}
```

---

## Flujos de Trabajo

### Flujo: Owner ve Campañas y Clientes

1. **Login Owner**
   ```
   POST /api/owner/auth/login
   → Guardar token y slug del negocio
   ```

2. **Listar Campañas**
   ```
   GET /api/{slug}/owner/campaigns
   → Mostrar lista de campañas
   ```

3. **Ver Detalles de Campaña**
   ```
   GET /api/{slug}/owner/campaigns/{campaign_id}
   → Mostrar estadísticas y detalles
   ```

4. **Ver Clientes en Campaña**
   ```
   GET /api/{slug}/owner/campaigns/{campaign_id}/customers
   → Mostrar clientes participantes
   ```

### Flujo: Staff Agrega Sello y Canjea Premio

1. **Login Staff**
   ```
   POST /api/{slug}/staff/auth/login
   → Guardar token
   ```

2. **Buscar Cliente**
   ```
   GET /api/{slug}/staff/customers?search={codigo_o_telefono}
   → Seleccionar cliente
   ```

3. **Agregar Sello**
   ```
   POST /api/{slug}/staff/stamps
   {
       "customer_id": "...",
       "campaign_id": "..."
   }
   → Sello agregado, verificar si se desbloqueó premio
   ```

4. **Canjear Premio (si está desbloqueado)**
   ```
   POST /api/{slug}/staff/redemptions
   {
       "customer_campaign_id": "...",
       "reward_id": "..."
   }
   → Premio canjeado
   ```

### Flujo: Cliente Verifica Progreso

1. **Solicitar OTP**
   ```
   POST /api/{slug}/customer/auth/request-otp
   {
       "phone": "+1234567890"
   }
   → OTP enviado (en desarrollo: ver logs)
   ```

2. **Verificar OTP**
   ```
   POST /api/{slug}/customer/auth/verify-otp
   {
       "phone": "+1234567890",
       "otp": "123456"
   }
   → Guardar token
   ```

3. **Ver Progreso**
   ```
   GET /api/{slug}/customer/progress
   → Mostrar sellos, rachas y premios
   ```

4. **Ver Premios Desbloqueados**
   ```
   GET /api/{slug}/customer/rewards/unlocked?redeemed=false
   → Mostrar premios disponibles para canjear
   ```

---

## Ejemplos de Integración

### React/TypeScript - Hook de Autenticación

```typescript
// hooks/useAuth.ts
import { useState, useEffect } from 'react';

interface AuthState {
    token: string | null;
    user: any | null;
    loading: boolean;
}

export function useAuth() {
    const [auth, setAuth] = useState<AuthState>({
        token: localStorage.getItem('token'),
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        loading: false,
    });

    const login = async (email: string, password: string) => {
        setAuth(prev => ({ ...prev, loading: true }));
        try {
            const response = await fetch('http://loypi-api.test/api/owner/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            
            if (!response.ok) throw new Error('Login failed');
            
            const data = await response.json();
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            setAuth({ token: data.token, user: data.user, loading: false });
            return data;
        } catch (error) {
            setAuth(prev => ({ ...prev, loading: false }));
            throw error;
        }
    };

    const logout = async () => {
        if (auth.token) {
            await fetch('http://loypi-api.test/api/owner/auth/logout', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${auth.token}` },
            });
        }
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setAuth({ token: null, user: null, loading: false });
    };

    return { ...auth, login, logout };
}
```

### React/TypeScript - API Client

```typescript
// services/api.ts
const API_BASE = 'http://loypi-api.test/api';

class ApiClient {
    private token: string | null = null;

    setToken(token: string) {
        this.token = token;
    }

    private async request<T>(
        endpoint: string,
        options: RequestInit = {}
    ): Promise<T> {
        const headers: HeadersInit = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers,
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers,
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Request failed');
        }

        return response.json();
    }

    // Owner endpoints
    async getCampaigns(slug: string, params?: { active?: boolean }) {
        const query = new URLSearchParams();
        if (params?.active !== undefined) {
            query.append('active', params.active.toString());
        }
        return this.request(`/${slug}/owner/campaigns?${query}`);
    }

    async getCampaign(slug: string, campaignId: string) {
        return this.request(`/${slug}/owner/campaigns/${campaignId}`);
    }

    async getCampaignCustomers(
        slug: string,
        campaignId: string,
        params?: { search?: string; per_page?: number }
    ) {
        const query = new URLSearchParams();
        if (params?.search) query.append('search', params.search);
        if (params?.per_page) query.append('per_page', params.per_page.toString());
        return this.request(`/${slug}/owner/campaigns/${campaignId}/customers?${query}`);
    }

    // Staff endpoints
    async addStamp(slug: string, data: { customer_id: string; campaign_id: string; meta?: object }) {
        return this.request(`/${slug}/staff/stamps`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async redeemReward(
        slug: string,
        data: { customer_campaign_id: string; reward_id: string; meta?: object }
    ) {
        return this.request(`/${slug}/staff/redemptions`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    // Customer endpoints
    async requestOtp(slug: string, phone: string) {
        return this.request(`/${slug}/customer/auth/request-otp`, {
            method: 'POST',
            body: JSON.stringify({ phone }),
        });
    }

    async verifyOtp(slug: string, phone: string, otp: string) {
        return this.request(`/${slug}/customer/auth/verify-otp`, {
            method: 'POST',
            body: JSON.stringify({ phone, otp }),
        });
    }

    async getCustomerProgress(slug: string) {
        return this.request(`/${slug}/customer/progress`);
    }

    async getUnlockedRewards(slug: string, redeemed?: boolean) {
        const query = redeemed !== undefined ? `?redeemed=${redeemed}` : '';
        return this.request(`/${slug}/customer/rewards/unlocked${query}`);
    }
}

export const apiClient = new ApiClient();
```

### React Component - Lista de Campañas

```typescript
// components/CampaignList.tsx
import { useEffect, useState } from 'react';
import { apiClient } from '../services/api';

interface Campaign {
    id: string;
    name: string;
    description: string;
    active: boolean;
    stats?: {
        total_customers: number;
        total_stamps: number;
    };
}

export function CampaignList({ slug }: { slug: string }) {
    const [campaigns, setCampaigns] = useState<Campaign[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadCampaigns();
    }, [slug]);

    const loadCampaigns = async () => {
        try {
            setLoading(true);
            const response = await apiClient.getCampaigns(slug, { active: true });
            setCampaigns(response.data);
        } catch (error) {
            console.error('Error loading campaigns:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div>Loading...</div>;

    return (
        <div>
            <h2>Campañas</h2>
            {campaigns.map(campaign => (
                <div key={campaign.id}>
                    <h3>{campaign.name}</h3>
                    <p>{campaign.description}</p>
                    {campaign.stats && (
                        <div>
                            <p>Clientes: {campaign.stats.total_customers}</p>
                            <p>Sellos: {campaign.stats.total_stamps}</p>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}
```

---

## Mejores Prácticas

### 1. Manejo de Tokens
- Guardar tokens en `localStorage` o `sessionStorage`
- Incluir token en todas las peticiones autenticadas
- Refrescar token antes de expirar
- Limpiar token al hacer logout

### 2. Manejo de Errores
- Siempre verificar `response.ok` antes de procesar
- Mostrar mensajes de error amigables al usuario
- Loggear errores para debugging
- Manejar errores de red (timeout, sin conexión)

### 3. Paginación
- Siempre usar `per_page` para limitar resultados
- Implementar paginación en el frontend
- Mostrar indicadores de carga

### 4. Optimización
- Cachear respuestas cuando sea apropiado
- Usar debounce en búsquedas
- Lazy load de datos pesados
- Prefetch de datos relacionados

### 5. Seguridad
- Nunca exponer tokens en URLs
- Validar datos en el frontend antes de enviar
- Sanitizar inputs del usuario
- Usar HTTPS en producción

### 6. UX
- Mostrar estados de carga
- Feedback inmediato en acciones
- Mensajes de error claros
- Confirmaciones para acciones destructivas

---

## Variables de Entorno Recomendadas

```env
VITE_API_BASE_URL=http://loypi-api.test/api
VITE_APP_NAME=Loypi
```

---

## Testing

### Endpoints de Prueba
- Base URL: `http://loypi-api.test/api`
- Owner: `owner1@example.com` / `password`
- Staff PIN: `1234`
- Slugs: `negocio-1`, `negocio-2`, `negocio-3`, `negocio-4`

### Datos de Prueba Disponibles
- 4 negocios
- 40 clientes
- 40 campañas (20 punch + 20 streak)
- 40 premios
- Staff con PIN: 1234

---

## Notas Importantes

1. **UUIDs**: Todos los IDs son UUIDs (string), no números
2. **Fechas**: Todas las fechas están en formato ISO 8601
3. **Slug**: El slug del negocio es parte de la URL para todas las operaciones
4. **Paginación**: Todos los listados soportan paginación
5. **Búsqueda**: Los endpoints de listado soportan filtros y búsqueda
6. **Relaciones**: Las respuestas incluyen relaciones cuando es relevante
7. **Validación**: Todos los inputs son validados en el backend

---

## Soporte

Para dudas o problemas:
1. Revisar logs de Laravel: `storage/logs/laravel.log`
2. Verificar que las migraciones estén ejecutadas
3. Verificar que los tokens sean válidos
4. Verificar que el slug del negocio sea correcto

