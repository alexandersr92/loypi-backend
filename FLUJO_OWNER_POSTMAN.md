# Flujo de Owner en Postman

## 📋 Resumen del Flujo

Como **owner** de un negocio, puedes:
1. Iniciar sesión con email y password
2. Ver todas tus campañas
3. Ver detalles de una campaña con estadísticas
4. Ver todos los clientes que participan en una campaña específica

## 🚀 Paso a Paso

### Paso 1: Login como Owner

**Endpoint:** `POST /api/owner/auth/login`

**Body:**
```json
{
    "email": "owner1@example.com",
    "password": "password"
}
```

**Respuesta:**
- Guarda automáticamente el `owner_token` en las variables
- Guarda el `slug` del primer negocio (puedes cambiarlo después)
- Muestra la lista de tus negocios

**Datos de prueba disponibles:**
- `owner1@example.com` / `password` → Negocio: `negocio-1`
- `owner2@example.com` / `password` → Negocio: `negocio-2`
- `owner3@example.com` / `password` → Negocio: `negocio-3`
- `owner4@example.com` / `password` → Negocio: `negocio-4`

### Paso 2: Ver Lista de Campañas

**Endpoint:** `GET /api/{slug}/owner/campaigns`

**Headers:**
```
Authorization: Bearer {{owner_token}}
```

**Query Parameters:**
- `active=true` (opcional) - Filtrar solo campañas activas
- `per_page=15` (opcional) - Número de resultados por página

**Respuesta incluye:**
- Lista de todas las campañas del negocio
- Información de premios asociados
- Número de clientes participantes
- **Automáticamente guarda el `campaign_id` del primer resultado**

### Paso 3: Ver Detalles de una Campaña

**Endpoint:** `GET /api/{slug}/owner/campaigns/{campaign_id}`

**Headers:**
```
Authorization: Bearer {{owner_token}}
```

**Respuesta incluye:**
- Detalles completos de la campaña
- Lista de premios (rewards)
- Participaciones de clientes (customer_campaigns)
- Rachas de clientes (customer_streaks)
- **Estadísticas agregadas:**
  - `total_customers`: Total de clientes participantes
  - `total_stamps`: Total de sellos otorgados
  - `total_redemptions`: Total de canjes realizados
  - `active_streaks`: Número de rachas activas

### Paso 4: Ver Clientes en una Campaña

**Endpoint:** `GET /api/{slug}/owner/campaigns/{campaign_id}/customers`

**Headers:**
```
Authorization: Bearer {{owner_token}}
```

**Query Parameters:**
- `search=` (opcional) - Buscar por nombre, teléfono o código
- `per_page=15` (opcional) - Número de resultados por página

**Respuesta incluye:**
- Información de la campaña
- Lista paginada de clientes participantes con:
  - Datos del cliente (nombre, teléfono, código)
  - Número de sellos acumulados
  - Últimos 5 sellos otorgados
  - Premios desbloqueados
  - Ordenados por número de sellos (descendente)

## 📝 Ejemplo Completo

### 1. Login
```http
POST http://localhost:8000/api/owner/auth/login
Content-Type: application/json

{
    "email": "owner1@example.com",
    "password": "password"
}
```

### 2. Ver Campañas
```http
GET http://localhost:8000/api/negocio-1/owner/campaigns?active=true
Authorization: Bearer {tu_token_aqui}
```

### 3. Ver Detalles de Campaña
```http
GET http://localhost:8000/api/negocio-1/owner/campaigns/{campaign_id}
Authorization: Bearer {tu_token_aqui}
```

### 4. Ver Clientes en Campaña
```http
GET http://localhost:8000/api/negocio-1/owner/campaigns/{campaign_id}/customers?search=
Authorization: Bearer {tu_token_aqui}
```

## 🔐 Seguridad

- Todos los endpoints requieren autenticación con token
- Solo puedes acceder a negocios que te pertenecen
- Los admins pueden acceder a cualquier negocio
- El middleware valida automáticamente la pertenencia al negocio

## 💡 Tips

1. **Cambiar de negocio**: Si tienes múltiples negocios, cambia la variable `slug` en Postman
2. **Buscar clientes**: Usa el parámetro `search` para encontrar clientes específicos
3. **Filtrar campañas**: Usa `active=true` para ver solo campañas activas
4. **Estadísticas**: El endpoint de detalles de campaña incluye estadísticas útiles

## 🎯 Casos de Uso

- **Dashboard del Owner**: Usa "Listar Campañas" para ver el resumen
- **Análisis de Campaña**: Usa "Ver Campaña" para estadísticas detalladas
- **Gestión de Clientes**: Usa "Ver Clientes en Campaña" para ver quién participa
- **Búsqueda**: Usa el parámetro `search` para encontrar clientes específicos

