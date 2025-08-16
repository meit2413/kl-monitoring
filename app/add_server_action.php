<?php

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $host = $_POST['host'];
    $username = $_POST['username'];
    $api_token = $_POST['api_token'];
    $ssl_verify = isset($_POST['ssl_verify']) ? 1 : 0;

    $conn = get_db_connection();

    $stmt = $conn->prepare("INSERT INTO servers (name, host, username, api_token, ssl_verify) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $name, $host, $username, $api_token, $ssl_verify);

    if ($stmt->execute()) {
        header("Location: ../public/index.php?message=Server added successfully");
    } else {
        header("Location: ../public/add_server.php?error=Failed to add server");
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect if accessed directly
    header("Location: ../public/add_server.php");
}
?>
