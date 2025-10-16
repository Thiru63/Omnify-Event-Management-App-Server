#!/bin/bash

# Remove existing Swagger view
rm -f resources/views/vendor/l5-swagger/index.blade.php

# Create new Swagger view with CDN assets
cat > resources/views/vendor/l5-swagger/index.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Management API</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.9.0/favicon-32x32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.9.0/favicon-16x16.png" sizes="16x16"/>
    <style>
    html {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *, *:before, *:after {
        box-sizing: inherit;
    }
    body {
      margin:0;
      background: #fafafa;
    }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
<script>
    window.onload = function() {
        const ui = SwaggerUIBundle({
            url: "https://omnify-event-management-app-server.onrender.com/docs?api-docs.json",
            dom_id: "#swagger-ui",
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            docExpansion: "none",
            filter: true,
            persistAuthorization: false
        });
        window.ui = ui;
    };
</script>
</body>
</html>
EOF

echo "Swagger view updated with CDN assets"