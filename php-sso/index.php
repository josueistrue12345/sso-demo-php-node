<?php
require __DIR__ . '/vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

$keycloak_url = 'http://localhost:8080/realms/mobo';
$client_id = 'php-app';
$client_secret = 'php-secret';

$oidc = new OpenIDConnectClient($keycloak_url, $client_id, $client_secret);

// Configuración para desarrollo local sin HTTPS
$oidc->setHttpUpgradeInsecureRequests(false);
$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);

session_start();

// Si se recibe `logout`, cerramos sesión localmente y redijimos a Keycloak
if (isset($_GET['logout'])) {
    $idToken = $_SESSION['id_token'] ?? '';
    session_destroy();
    if ($idToken) {
        $logoutUrl = $keycloak_url . '/protocol/openid-connect/logout?post_logout_redirect_uri=' . urlencode('http://localhost:8001') . '&id_token_hint=' . $idToken;
        header("Location: $logoutUrl");
    } else {
        header("Location: /");
    }
    exit;
}

// Configurar la URL de redirección explícita antes de cualquier autenticación
// Así la librería sabe exactamente a dónde regresar después del login en Keycloak
$oidc->setRedirectURL('http://localhost:8001/');

// Si presionan el botón de login manual o si Keycloak nos está mandando de regreso un "code", iniciamos el flujo
if (isset($_GET['login']) || isset($_GET['code'])) {
    try {
        $oidc->authenticate(); // Esto redirige a Keycloak automáticamente si no hay code, o verifica el code si lo hay
        
        $user = $oidc->requestUserInfo();
        $_SESSION['sso_user'] = $user;
        $_SESSION['id_token'] = $oidc->getIdToken();
        
        // Una vez autenticado y guardados los datos en sesión, recargamos limpio la página
        header("Location: /");
        exit;
    } catch (Exception $e) {
        die("Error en la autenticación SSO: " . $e->getMessage());
    }
}

// Pantalla principal si no hay sesión iniciada
if (!isset($_SESSION['sso_user'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>App en PHP (Sin Sesión)</title>
        <style>
            body { font-family: sans-serif; text-align: center; margin-top: 100px; }
            .btn { padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>App PHP (Sin sesión)</h1>
        <p>Esta aplicación está protegida por Keycloak.</p>
        <a href="?login=true" class="btn">Iniciar Sesión con SSO</a>
    </body>
    </html>
    <?php
    exit;
}

$user = $_SESSION['sso_user'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>App en PHP (SSO)</title>
    <style>
        body { font-family: sans-serif; text-align: center; margin-top: 50px; }
        .btn { padding: 10px 20px; background-color: #f44336; color: white; text-decoration: none; border-radius: 5px; }
        .data-box { text-align: left; display: inline-block; background: #f4f4f4; padding: 20px; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🐘 Bienvenido a la App en PHP (SSO)</h1>
    <p>Has iniciado sesión exitosamente con Keycloak.</p>
    <p>Hola, <b><?php echo htmlspecialchars($user->preferred_username ?? 'Usuario'); ?></b>!</p>
    <p>Tu correo es: <?php echo htmlspecialchars($user->email ?? 'N/A'); ?></p>
    
    <a href="?logout=true" class="btn">Cerrar Sesión</a>
    
    <div class="data-box">
        <h3>Datos del Token (UserInfo):</h3>
        <pre><?php print_r($user); ?></pre>
    </div>
</body>
</html>
