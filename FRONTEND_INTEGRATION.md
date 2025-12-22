# Integración Frontend: Registro a Campaña

## Flujo

1. Usuario escanea QR → obtiene `campaign_code`
2. Frontend muestra formulario con campos (name, phone, + custom fields)
3. `POST /api/v1/campaigns/register` → Recibe token + mensaje OTP
4. Frontend muestra ventana OTP
5. `POST /api/v1/otp/verify` → Valida OTP, status cambia a `validated`

## Endpoints

### POST /api/v1/campaigns/register

**Request:**

```json
{
    "campaign_code": "ABCD",
    "business_slug": "mi-negocio",
    "field_values": [
        {
            "custom_field_id": "uuid",
            "string_value": "valor" // o number_value, date_value, boolean_value según tipo
        }
    ]
}
```

**Requisitos:**

- `field_values` debe incluir campos para `name` y `phone`
- Puedes usar:
    - `"custom_field_id": "default-name-field"` para name
    - `"custom_field_id": "default-phone-field"` para phone
    - O el UUID real del custom field (si lo conoces)
- El sistema crea automáticamente estos campos si no existen en la campaña

**Response (201):**

```json
{
    "success": true,
    "message": "Customer registrado. Se ha enviado un código OTP.",
    "data": {
        "token": "1|abc...",
        "otp_sent": true,
        "otp_message": "Código OTP enviado exitosamente. (Modo desarrollo: usar código 123456)",
        "customer_id": "uuid",
        "campaign_id": "uuid",
        "status": "pending"
    }
}
```

### POST /api/v1/otp/verify

**Request:**

```json
{
    "phone": "+521234567890",
    "code": "123456"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Código OTP verificado exitosamente.",
    "data": {
        "verified_at": "2025-11-28T10:05:00Z"
    }
}
```

**Efecto:** Actualiza automáticamente `customer_campaign.status` de `pending` a `validated`

## Implementación

### 1. Obtener custom fields de la campaña (Opcional)

Puedes usar directamente `"default-name-field"` y `"default-phone-field"` sin necesidad de obtener los UUIDs:

```javascript
// Opción 1: Usar campos default (RECOMENDADO)
const fieldValues = [
    {
        custom_field_id: 'default-name-field',
        string_value: 'Juan Pérez',
    },
    {
        custom_field_id: 'default-phone-field',
        string_value: '+521234567890',
    },
    // ... otros campos con UUID real
];

// Opción 2: Obtener UUIDs reales (si prefieres)
const campaign = await fetch(`/api/v1/campaigns/code/${campaignCode}`).then(
    (r) => r.json(),
);
const nameField = campaign.data.custom_fields.find((f) => f.key === 'name');
const phoneField = campaign.data.custom_fields.find((f) => f.key === 'phone');
```

### 2. Registrar customer

```javascript
// Usar campos default para name y phone
const fieldValues = [
    {
        custom_field_id: 'default-name-field',
        string_value: formData.name,
    },
    {
        custom_field_id: 'default-phone-field',
        string_value: formData.phone,
    },
    // ... otros campos con UUID real
    {
        custom_field_id: '019aa527-e1e0-71f5-ad1f-2b3f9d1667e4',
        number_value: formData.age,
    },
];

const response = await fetch('/api/v1/campaigns/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        campaign_code: campaignCode,
        business_slug: businessSlug,
        field_values: fieldValues,
    }),
});

const data = await response.json();
if (data.success) {
    localStorage.setItem('customer_token', data.data.token);
    // Mostrar ventana OTP
}
```

### 3. Verificar OTP

```javascript
const response = await fetch('/api/v1/otp/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        phone: phone,
        code: otpCode, // En desarrollo: siempre "123456"
    }),
});

const data = await response.json();
// Si success: customer_campaign.status ahora es "validated"
```

## Puntos Importantes

- **Campos name y phone**: Usa `"default-name-field"` y `"default-phone-field"` como `custom_field_id` (el sistema los crea automáticamente si no existen)
- **Token**: Se retorna en el paso 1, guardarlo para autenticación
- **Modo desarrollo**: OTP siempre es `123456`, no se envía SMS
- **Expiración OTP**: 3 minutos
- **Estados**: `pending` → `validated` (automático al verificar OTP)
- **Customer existente**: Si existe (mismo phone + business), se actualiza y crea nuevo registro en campaña
- **Validación**: Si customer ya está `validated` en la campaña, retorna error 422

## Errores Comunes

- **422**: Campaign no pertenece al negocio
- **422**: Name o phone faltantes en field_values
- **422**: Customer ya validado en esta campaña
- **400**: OTP inválido o expirado (3 minutos)
