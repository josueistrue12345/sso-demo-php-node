# Demo SSO Keycloak 🔐

Este proyecto es una demo completa para implementar **Single Sign-On (SSO)** utilizando Keycloak como Identity Provider (IdP) junto con dos aplicaciones demostrativas: Una en **Node.js** y otra en **PHP**.

## 🚀 1. Levantar Keycloak

La configuración de Docker Compose ha sido actualizada para que Keycloak se levante por defecto importando el *Realm* `mobo`, junto con los clientes y un usuario de demostración.

```bash
cd /Users/josuehernandez/Desktop/mobo/SSO
docker compose up -d
```

> **Nota:** Keycloak tomará un momento en iniciar. Puedes verificar que está corriendo visitando [http://localhost:8080](http://localhost:8080).
> - **Admin Consola:** `admin` / `admin`
> - **Usuario Demo para las apps:** `demo` / `12345`

---

## 🟢 2. Ejecutar la App en Node.js

Esta aplicación utiliza Express y conecta mediante la librería oficial de OpenID.

1. Abre una nueva terminal.
2. Ve al directorio e instala las dependencias:
   ```bash
   cd /Users/josuehernandez/Desktop/mobo/SSO/node-sso
   npm install
   ```
3. Ejecuta la aplicación:
   ```bash
   npm start
   ```
4. Visita en tu navegador: **[http://localhost:3000](http://localhost:3000)**

---

## 🐘 3. Ejecutar la App en PHP

Esta aplicación se vale de PHP nativo (sin frameworks) conectándose a Keycloak vía una librería de OpenID Connect.

1. Abre otra terminal.
2. Ve al directorio e instala las dependencias con Composer:
   ```bash
   cd /Users/josuehernandez/Desktop/mobo/SSO/php-sso
   composer install
   ```
3. Levanta el servidor local integrado de PHP en el puerto 8001:
   ```bash
   php -S localhost:8001
   ```
4. Visita en tu navegador: **[http://localhost:8001](http://localhost:8001)**

---

## 🎭 4. Probando la Magia del SSO (Single Sign-On)

1. Abre de forma paralela la app de **Node ([localhost:3000](http://localhost:3000))** y la app en **PHP ([localhost:8001](http://localhost:8001))**. Verás que en ninguna tienes sesión iniciada.
2. En la app de **Node.js**, haz clic en **Iniciar Sesión con SSO**. Te redirigirá al formulario universal de login de Keycloak.
3. Inicia sesión usando las siguientes credenciales:
   - **Usuario:** `demo`
   - **Contraseña:** `12345`
4. Observarás que ahora gozas de sesión en la app de Node.js.
5. Vuelve a la pestaña de **PHP** y da clic en **Iniciar Sesión con SSO**. No te pedirá contraseña; entrarás inmediatamente. ¡Eso es el **SSO** en acción!
6. Cierra sesión en cualquiera de las 2 plataformas y, al intentar una recarga o validación, observarás que ambas sesiones finalizan globalmente (Single Log-Out).
