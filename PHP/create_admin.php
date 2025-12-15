<?php
require_once 'db_connect.php';

$email = 'admin@propulse.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    // Update existing Admin
    $update = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin', name = 'System Admin' WHERE email = ?");
    if ($update->execute([$hash, $email])) {
        echo "<h1>Success!</h1>";
        echo "<p>Admin password reset to: <strong>$password</strong></p>";
        echo "<p>Hash generated: $hash</p>";
    }
} else {
    // Create new Admin
    $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, address, phone) VALUES ('System Admin', ?, ?, 'admin', 'Headquarters', '000-000-0000')");
    if ($insert->execute([$email, $hash])) {
        echo "<h1>Success!</h1>";
        echo "<p>Admin account created.</p>";
        echo "<p>Email: <strong>$email</strong></p>";
        echo "<p>Password: <strong>$password</strong></p>";
    }
}

echo "<br><a href='admin_login.php'>Go to Admin Login</a>";
?>