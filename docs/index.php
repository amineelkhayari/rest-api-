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
        url: "openapi.yaml", // your OpenAPI YAML file
        dom_id: '#swagger',
        presets: [
          SwaggerUIBundle.presets.apis
        ],
        layout: "BaseLayout",
        deepLinking: true
      });

      // OAuth2 configuration for Manager SPA
      ui.initOAuth({
        clientId: "manager-spa", // IdentityServer Manager SPA client
        clientSecret: "", // empty for public client
        appName: "Manager API Swagger",
        realm: "manager_api_realm",
        scopeSeparator: " ",
        scopes: "openid profile manager-api", // scopes matching client
        useBasicAuthenticationWithAccessCodeGrant: false,
        usePkceWithAuthorizationCodeGrant: true, // <--- this enables PKC
        redirectUri: "http://localhost/api/callback.php" // must match client redirect
      });
    };
  </script>
</body>

</html>