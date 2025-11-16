# Guía de Importación de Postman

## Archivos Incluidos

1. **Loypi_API.postman_collection.json** - Colección completa de endpoints
2. **Loypi_API.postman_environment.json** - Variables de entorno

## Cómo Importar

### Paso 1: Importar la Colección

1. Abre Postman
2. Click en **Import** (botón superior izquierdo)
3. Selecciona el archivo `Loypi_API.postman_collection.json`
4. Click en **Import**

### Paso 2: Importar el Entorno

1. Click en el ícono de **Environments** (lado izquierdo)
2. Click en **Import**
3. Selecciona el archivo `Loypi_API.postman_environment.json`
4. Click en **Import**
5. Selecciona el entorno **"Loypi API - Local"** en el dropdown superior derecho

## Configuración Inicial

### Variables del Entorno

Las siguientes variables están preconfiguradas:

- **base_url**: `http://localhost:8000/api` (ajusta según tu servidor)
- **slug**: `negocio-1` (cambia según el negocio que quieras probar)
- **customer_token**: Se llena automáticamente al hacer login
- **staff_token**: Se llena automáticamente al hacer login
- **customer_id**: Se llena automáticamente al hacer login
- **staff_id**: Se llena automáticamente al hacer login
- **campaign_id**: Debes llenarlo manualmente después de crear una campaña
- **reward_id**: Debes llenarlo manualmente después de crear un premio
- **customer_campaign_id**: Debes llenarlo manualmente después de obtener participaciones

## Flujo de Prueba Recomendado

### Para Staff:

1. **Login Staff** → Obtiene `staff_token` automáticamente
    - Usa PIN: `1234` (de los datos de prueba)

2. **Crear Campaña** → Copia el `id` de la respuesta y actualiza `campaign_id` en variables

3. **Crear Premio** → Copia el `id` de la respuesta y actualiza `reward_id` en variables

4. **Listar Clientes** → Copia un `customer_id` y actualiza la variable

5. **Agregar Sello** → Usa `customer_id` y `campaign_id` de las variables

6. **Canjear Premio** → Necesitas `customer_campaign_id` (obtenerlo de la respuesta de "Ver Cliente")

### Para Clientes:

1. **Solicitar OTP** → Envía tu número de teléfono
    - Nota: En desarrollo, el OTP se loguea en Laravel logs

2. **Verificar OTP** → Usa el OTP del log (o implementa integración real de WhatsApp)
    - Obtiene `customer_token` automáticamente

3. **Ver Campañas** → Lista campañas activas

4. **Ver Mi Progreso** → Muestra sellos, rachas y premios desbloqueados

5. **Ver Premios Desbloqueados** → Lista premios disponibles para canjear

## Endpoints Disponibles

### Autenticación

- ✅ Solicitar OTP (Cliente)
- ✅ Verificar OTP y Login (Cliente)
- ✅ Login Staff (PIN)
- ✅ Obtener Info (Cliente/Staff)
- ✅ Logout (Cliente/Staff)

### Campañas (Staff)

- ✅ Listar Campañas
- ✅ Crear Campaña
- ✅ Ver Campaña
- ✅ Actualizar Campaña
- ✅ Eliminar Campaña

### Premios (Staff)

- ✅ Listar Premios
- ✅ Crear Premio (Punch/Streak)
- ✅ Ver Premio
- ✅ Actualizar Premio
- ✅ Eliminar Premio

### Sellos (Staff)

- ✅ Agregar Sello
- ✅ Listar Sellos

### Canjes (Staff)

- ✅ Canjear Premio
- ✅ Listar Canjes

### Clientes (Staff)

- ✅ Listar Clientes
- ✅ Ver Cliente
- ✅ Actualizar Cliente

### Staff Management

- ✅ Listar Staff
- ✅ Crear Staff
- ✅ Ver Staff
- ✅ Actualizar Staff
- ✅ Eliminar Staff

### Cliente (Endpoints Públicos)

- ✅ Ver Campañas Activas
- ✅ Ver Campaña
- ✅ Ver Premios Desbloqueados
- ✅ Ver Mi Progreso

## Notas Importantes

1. **Tokens**: Los tokens se guardan automáticamente en las variables al hacer login
2. **Slug**: Cambia el slug en el entorno para probar diferentes negocios
3. **IDs**: Algunos IDs necesitan ser copiados manualmente de las respuestas
4. **OTP**: En desarrollo, revisa los logs de Laravel para ver el código OTP generado

## Datos de Prueba

Con el seeder ejecutado, tienes:

- **Slugs disponibles**: `negocio-1`, `negocio-2`, `negocio-3`, `negocio-4`
- **Staff PIN**: `1234` (para todos los staff)
- **40 clientes** con números de teléfono generados
- **40 campañas** (20 punch card + 20 streak)
- **40 premios** listos para usar

## Solución de Problemas

### Error 401 (No autenticado)

- Verifica que el token esté en la variable `customer_token` o `staff_token`
- Asegúrate de haber hecho login primero

### Error 403 (No autorizado)

- Verifica que el staff/cliente pertenezca al negocio del slug
- Verifica que el token no haya expirado

### Error 404 (No encontrado)

- Verifica que el `slug` sea correcto
- Verifica que los IDs (campaign_id, reward_id, etc.) existan

### Error 422 (Validación)

- Revisa el cuerpo de la petición
- Verifica que todos los campos requeridos estén presentes
