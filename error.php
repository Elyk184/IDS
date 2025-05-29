<!DOCTYPE html>
<html>
<head>
    <title>Error - Security System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 500px;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="text-danger mb-4">
            <i class="bi bi-exclamation-triangle"></i>
            Internal Server Error
        </h1>
        <p class="lead">We apologize, but something went wrong while processing your request.</p>
        <p class="text-muted">Our team has been notified and is working to resolve the issue.</p>
        <div class="mt-4">
            <a href="/IDS/login.php" class="btn btn-primary">Return to Login</a>
        </div>
    </div>
</body>
</html> 