# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Para autenticarte, obtén un token usando el endpoint de login correspondiente y úsalo en el header `Authorization: Bearer {token}`.

**Obtener token:**
- `POST /api/v1/auth/login` - Para usuarios (owners/admins)
- `POST /api/v1/staff/login` - Para staff
