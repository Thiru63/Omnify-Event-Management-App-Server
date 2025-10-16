<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .card h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .card p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status {
            background: #e8f5e8;
            border: 2px solid #4caf50;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .tech-stack {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .tech-badge {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🎪</div>
        <h1>Event Management API</h1>
        <p class="subtitle">A robust Laravel backend for managing events and attendees</p>
        
        <div class="status">
            <strong>🚀 Status: Operational</strong>
            <p>API is running successfully on Render</p>
        </div>
        
        <div class="tech-stack">
            <span class="tech-badge">Laravel 12</span>
            <span class="tech-badge">PHP 8.2</span>
            <span class="tech-badge">PostgreSQL</span>
            <span class="tech-badge">Swagger/OpenAPI</span>
        </div>
        
        <div class="links">
            <a href="/swagger-live" class="card">
                <h3>📚 API Documentation</h3>
                <p>Interactive Swagger UI with full API reference and testing</p>
            </a>
            
            <a href="/api" class="card">
                <h3>🔗 API Endpoints</h3>
                <p>View all available API routes and methods</p>
            </a>
            
            <a href="https://omnify-event-management-app.vercel.app" class="card" target="_blank">
                <h3>🎨 Frontend App</h3>
                <p>Live Next.js frontend application</p>
            </a>
            
            <a href="https://github.com/Thiru63/Omnify-Event-Management-App-Server" class="card" target="_blank">
                <h3>💻 Source Code</h3>
                <p>Backend repository on GitHub</p>
            </a>
        </div>
        
        <div class="footer">
            <p>Built with ❤️ using Laravel | Deployed on Render</p>
            <p>API Version 1.0.0 | Laravel {{ app()->version() }} | PHP {{ PHP_VERSION }}</p>
        </div>
    </div>
</body>
</html>