<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add WHM Server</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Add WHM Server</h1>
        <form action="../app/add_server_action.php" method="post" class="mt-4">
            <div class="form-group">
                <label for="name">Server Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="e.g., My Awesome Server" required>
            </div>
            <div class="form-group">
                <label for="host">Hostname or IP Address</label>
                <input type="text" class="form-control" id="host" name="host" placeholder="e.g., server1.example.com" required>
            </div>
            <div class="form-group">
                <label for="username">WHM Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="api_token">API Token</label>
                <textarea class="form-control" id="api_token" name="api_token" rows="5" placeholder="Your WHM API token" required></textarea>
                <small class="form-text text-muted">You can generate an API token in WHM under "Development" -> "Manage API Tokens".</small>
            </div>
            <button type="submit" class="btn btn-primary">Add Server</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
