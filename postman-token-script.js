// Script para Postman - Extraer token del login y guardarlo en variable
// Coloca este script en la pestaña "Tests" de tu request de login

// Parsear la respuesta JSON
const responseJson = pm.response.json();

// Verificar que la respuesta sea exitosa y tenga el token
if (responseJson.success && responseJson.data && responseJson.data.token) {
    // Extraer el token
    const token = responseJson.data.token;
    
    // Guardar el token en una variable de entorno
    pm.environment.set("auth_token", token);
    
    // También puedes guardarlo en una variable de colección si prefieres
    // pm.collectionVariables.set("auth_token", token);
    
    // Mostrar mensaje en la consola de Postman
    console.log("Token guardado exitosamente:", token);
    
    // Opcional: Mostrar notificación en Postman
    pm.test("Token guardado correctamente", function () {
        pm.expect(token).to.not.be.empty;
    });
} else {
    // Si no hay token, mostrar error
    console.error("No se pudo extraer el token de la respuesta");
    pm.test("Error: No se encontró token en la respuesta", function () {
        pm.expect(responseJson.data.token).to.exist;
    });
}

