# Documentación API - Loypi

## Estructura de la API

Todas las rutas están organizadas por slug del negocio. Ejemplo: `/api/{slug}/customer/auth/request-otp`

## Autenticación

### Clientes (OTP vía WhatsApp)

#### Solicitar OTP
```
POST /api/{slug}/customer/auth/request-otp
Body: { "phone": "+1234567890" }
```

#### Verificar OTP y Login
```
POST /api/{slug}/customer/auth/verify-otp
Body: { "phone": "+1234567890", "otp": "123456" }
Response: { "customer": {...}, "token": "...", "customer_token": "...", "expires_at": "..." }
```

#### Obtener información del cliente autenticado
```
GET /api/{slug}/customer/auth/me
Headers: Authorization: Bearer {token}
```

#### Logout
```
POST /api/{slug}/customer/auth/logout
Headers: Authorization: Bearer {token}
```

### Staff (PIN)

#### Login
```
POST /api/{slug}/staff/auth/login
Body: { "pin": "1234" }
Response: { "staff": {...}, "token": "...", "business": {...} }
```

#### Obtener información del staff autenticado
```
GET /api/{slug}/staff/auth/me
Headers: Authorization: Bearer {token}
```

#### Logout
```
POST /api/{slug}/staff/auth/logout
Headers: Authorization: Bearer {token}
```

## Endpoints de Staff (requieren autenticación)

### Campañas

- `GET /api/{slug}/staff/campaigns` - Listar campañas
- `POST /api/{slug}/staff/campaigns` - Crear campaña
- `GET /api/{slug}/staff/campaigns/{id}` - Ver campaña
- `PUT /api/{slug}/staff/campaigns/{id}` - Actualizar campaña
- `DELETE /api/{slug}/staff/campaigns/{id}` - Eliminar campaña

### Premios

- `GET /api/{slug}/staff/campaigns/{campaignId}/rewards` - Listar premios
- `POST /api/{slug}/staff/campaigns/{campaignId}/rewards` - Crear premio
- `GET /api/{slug}/staff/campaigns/{campaignId}/rewards/{id}` - Ver premio
- `PUT /api/{slug}/staff/campaigns/{campaignId}/rewards/{id}` - Actualizar premio
- `DELETE /api/{slug}/staff/campaigns/{campaignId}/rewards/{id}` - Eliminar premio

### Sellos

- `POST /api/{slug}/staff/stamps` - Agregar sello
  Body: { "customer_id": "...", "campaign_id": "...", "meta": {...} }
- `GET /api/{slug}/staff/stamps` - Listar sellos

### Canjes

- `POST /api/{slug}/staff/redemptions` - Canjear premio
  Body: { "customer_campaign_id": "...", "reward_id": "...", "meta": {...} }
- `GET /api/{slug}/staff/redemptions` - Listar canjes

### Clientes

- `GET /api/{slug}/staff/customers` - Listar clientes
- `GET /api/{slug}/staff/customers/{id}` - Ver cliente
- `PUT /api/{slug}/staff/customers/{id}` - Actualizar cliente

### Staff

- `GET /api/{slug}/staff/staff` - Listar staff
- `POST /api/{slug}/staff/staff` - Crear staff
  Body: { "name": "...", "passcode": "1234", "active": true }
- `GET /api/{slug}/staff/staff/{id}` - Ver staff
- `PUT /api/{slug}/staff/staff/{id}` - Actualizar staff
- `DELETE /api/{slug}/staff/staff/{id}` - Eliminar staff

## Endpoints de Clientes (requieren autenticación)

- `GET /api/{slug}/customer/campaigns` - Ver campañas activas
- `GET /api/{slug}/customer/campaigns/{id}` - Ver detalles de campaña
- `GET /api/{slug}/customer/rewards/unlocked` - Ver premios desbloqueados
- `GET /api/{slug}/customer/progress` - Ver progreso (sellos, rachas, premios)

## Modelos y Relaciones

### Business
- Tiene muchos: Campaigns, Staff, CustomerTokens, CustomFields
- Pertenece a: User

### Campaign
- Tiene muchos: Rewards, CustomerCampaigns, CustomerStreaks
- Pertenece a: Business

### Customer
- Tiene muchos: CustomerTokens, CustomerCampaigns, CustomerStreaks
- Se autentica con: OTP vía WhatsApp

### Staff
- Tiene muchos: Stamps, Redemptions
- Pertenece a: Business
- Se autentica con: PIN de 4 dígitos

### CustomerCampaign
- Relaciona: Customer con Campaign
- Almacena: Contador de stamps
- Tiene muchos: Stamps, RewardUnlocks, Redemptions

### Stamp
- Registra cada sello agregado
- Pertenece a: CustomerCampaign, Staff

### Reward
- Tipos: punch, streak, points
- Tiene límites: per_customer_limit, global_limit
- Tiene muchos: RewardUnlocks, Redemptions

### RewardUnlock
- Se crea cuando un cliente cumple condiciones para un premio
- Puede expirar
- Se marca como redeemed cuando se canjea

### Redemption
- Registra el canje de un premio
- Pertenece a: CustomerCampaign, Reward, Staff

## Servicios

### OtpService
- `generateOtp(phone)`: Genera y envía OTP
- `verifyOtp(phone, otp)`: Verifica OTP

### StampService
- `addStamp(customer, campaign, staff, meta)`: Agrega sello y actualiza streaks/rewards

### RedemptionService
- `redeem(customerCampaign, reward, staff, meta)`: Procesa canje de premio
- `generateRedemptionPin(redemption)`: Genera PIN para canje

### ShortCodeService
- `generate()`: Genera código único de 6 caracteres para cliente

## Seguridad

- Todos los endpoints requieren autenticación (excepto login/OTP)
- Los tokens Sanctum expiran según configuración
- Los PINs de staff se hashean con bcrypt
- Los OTPs tienen rate limiting (1 minuto entre solicitudes)
- Validación de pertenencia a negocio en todos los endpoints

## Notas de Implementación

1. **OTP WhatsApp**: El servicio `OtpService` tiene un método `sendWhatsAppOtp` que actualmente solo loguea. Debes integrar con tu proveedor de WhatsApp (Twilio, MessageBird, etc.)

2. **Tokens de Cliente**: Los clientes tienen dos tipos de tokens:
   - Token Sanctum: Para autenticación API
   - CustomerToken: Token personalizado para tracking por negocio

3. **Rachas (Streaks)**: Se actualizan automáticamente cuando se agrega un sello. La racha se rompe si pasan más de 1 día sin actividad.

4. **Desbloqueo de Premios**: Se verifica automáticamente al agregar sellos. Los premios se desbloquean cuando se cumplen las condiciones (punch count, streak days, etc.)

