# Configuración de Postman para Loypi API

## Script para Extraer Token Automáticamente

### Opción 1: Script Completo (Recomendado)

Copia y pega este script en la pestaña **"Tests"** de tu request de login:

```javascript
// Parsear la respuesta JSON
const responseJson = pm.response.json();

// Verificar que la respuesta sea exitosa y tenga el token
if (responseJson.success && responseJson.data && responseJson.data.token) {
    // Extraer el token
    const token = responseJson.data.token;
    
    // Guardar el token en una variable de entorno
    pm.environment.set("auth_token", token);
    
    // Mostrar mensaje en la consola
    console.log("✅ Token guardado:", token);
    
    // Test para verificar
    pm.test("Token guardado correctamente", function () {
        pm.expect(token).to.not.be.empty;
    });
} else {
    console.error("❌ No se pudo extraer el token");
    pm.test("Error: Token no encontrado", function () {
        pm.expect(responseJson.data.token).to.exist;
    });
}
```

### Opción 2: Script Simplificado

Si prefieres algo más simple:

```javascript
const token = pm.response.json().data.token;
pm.environment.set("auth_token", token);
console.log("Token:", token);
```

---

## Cómo Usar el Token en Otros Requests

Una vez que el token esté guardado, úsalo en otros requests agregando un header:

**Header:**
- **Key:** `Authorization`
- **Value:** `Bearer {{auth_token}}`

O si prefieres usar la variable directamente:

**Header:**
- **Key:** `Authorization`
- **Value:** `Bearer ` + `{{auth_token}}`

---

## Pasos para Configurar

1. **Abre Postman** y crea o abre tu colección

2. **Crea un Environment** (opcional pero recomendado):
   - Click en el ícono de engranaje (⚙️) arriba a la derecha
   - Click en "Add" para crear un nuevo environment
   - Nómbralo "Loypi API Local" o similar
   - Agrega una variable `auth_token` (puede estar vacía inicialmente)

3. **Selecciona el Environment** que acabas de crear

4. **Ve a tu request de Login** (`POST /api/v1/auth/login`)

5. **Ve a la pestaña "Tests"** (al lado de "Body", "Headers", etc.)

6. **Pega el script** de la Opción 1 o 2

7. **Ejecuta el request de login**

8. **Verifica que el token se guardó**:
   - Ve al environment
   - Deberías ver `auth_token` con el valor del token

9. **En otros requests protegidos**, agrega el header:
   ```
   Authorization: Bearer {{auth_token}}
   ```

---

## Usar Variable de Colección en Lugar de Environment

Si prefieres usar variables de colección (se aplican a toda la colección):

```javascript
// Cambiar esta línea:
pm.environment.set("auth_token", token);

// Por esta:
pm.collectionVariables.set("auth_token", token);
```

---

## Ejemplo de Request con Token

**Request:** `GET /api/v1/auth/me`

**Headers:**
```
Authorization: Bearer {{auth_token}}
Accept: application/json
```

---

## Troubleshooting

### El token no se guarda
- Verifica que estés usando un Environment activo
- Revisa la consola de Postman (View → Show Postman Console)
- Asegúrate de que la respuesta tenga el formato correcto

### El token expiró
- Simplemente ejecuta el request de login nuevamente
- El script actualizará automáticamente el token

### Quieres usar un nombre diferente para la variable
- Cambia `auth_token` por el nombre que prefieras en el script
- Actualiza también el header `Authorization` en otros requests

