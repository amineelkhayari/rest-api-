<!doctype html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
</head>

<body>
  <div id="swagger"></div>
  <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
  <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: "swagger.json", // your OpenAPI YAML file
        dom_id: '#swagger',
        presets: [
          SwaggerUIBundle.presets.apis
        ],
        layout: "BaseLayout",
        deepLinking: true
      });

      // OAuth2 configuration for accident SPA
      ui.initOAuth({
        clientId: "accident-spa", // IdentityServer accident SPA client
        clientSecret: "", // empty for public client
        appName: "accident API Swagger",
        scopeSeparator: " ",
        scopes: "openid profile accident-api", // scopes matching client
        useBasicAuthenticationWithAccessCodeGrant: false,
        usePkceWithAuthorizationCodeGrant: true, // <--- this enables PKC
        redirectUri: "http://localhost:8080/pub-api/public/oauth2-redirect.html" // must match client redirect
      });
    };
  </script>
</body>
</html>