const express = require('express');
const session = require('express-session');
const { Issuer, generators } = require('openid-client');
require('dotenv').config();

const app = express();
const port = process.env.PORT || 3000;

app.use(
    session({
        secret: process.env.SESSION_SECRET || 'fallback-secret',
        resave: false,
        saveUninitialized: true,
    })
);

let keycloakIssuer;
let client;

async function setupKeycloak() {
    try {
        keycloakIssuer = await Issuer.discover(process.env.KEYCLOAK_URL);
        client = new keycloakIssuer.Client({
            client_id: process.env.CLIENT_ID,
            client_secret: process.env.CLIENT_SECRET,
            redirect_uris: [process.env.REDIRECT_URL],
            response_types: ['code'],
        });
        console.log('✅ Keycloak configurado exitosamente para Node.js');
    } catch (error) {
        console.error('❌ Error configurando Keycloak. Asegúrate de que el contenedor de Docker esté corriendo.', error.message);
    }
}

setupKeycloak();

app.get('/', (req, res) => {
    if (req.session.userInfo) {
        res.send(`
      <div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
        <h1>👑 Bienvenido a la App en Node.js (SSO)</h1>
        <p>Hola, <b>${req.session.userInfo.preferred_username}</b>!</p>
        <p>Tu correo es: ${req.session.userInfo.email}</p>
        <a href="/logout" style="padding: 10px 20px; background-color: #f44336; color: white; text-decoration: none; border-radius: 5px;">Cerrar Sesión</a>
        <br><br>
        <div style="text-align: left; display: inline-block; background: #f4f4f4; padding: 20px; border-radius: 10px;">
          <h3>Datos del Token (UserInfo):</h3>
          <pre>${JSON.stringify(req.session.userInfo, null, 2)}</pre>
        </div>
      </div>
    `);
    } else {
        res.send(`
      <div style="font-family: sans-serif; text-align: center; margin-top: 100px;">
        <h1>App Node.js (Sin sesión)</h1>
        <p>Esta aplicación está protegida por Keycloak.</p>
        <a href="/login" style="padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">Iniciar Sesión con SSO</a>
      </div>
    `);
    }
});

app.get('/login', (req, res) => {
    if (!client) return res.status(500).send('Keycloak no está listo aún.');
    const nonce = generators.nonce();
    const state = generators.state();

    req.session.nonce = nonce;
    req.session.state = state;

    const url = client.authorizationUrl({
        scope: 'openid profile email',
        state: state,
        nonce: nonce,
    });

    res.redirect(url);
});

app.get('/callback', async (req, res) => {
    try {
        const params = client.callbackParams(req);
        const tokenSet = await client.callback('http://localhost:3000/callback', params, {
            nonce: req.session.nonce,
            state: req.session.state,
        });

        req.session.tokenSet = tokenSet;
        req.session.userInfo = await client.userinfo(tokenSet.access_token);
        res.redirect('/');
    } catch (error) {
        res.status(500).send('Error de Autenticación: ' + error.message);
    }
});

app.get('/logout', (req, res) => {
    if (!client) return res.redirect('/');
    const logoutUrl = client.endSessionUrl({
        id_token_hint: req.session.tokenSet ? req.session.tokenSet.id_token : undefined,
        post_logout_redirect_uri: 'http://localhost:3000/'
    });

    req.session.destroy();
    res.redirect(logoutUrl);
});

app.listen(port, () => {
    console.log(`🚀 Node.js App corriendo en http://localhost:${port}`);
});
