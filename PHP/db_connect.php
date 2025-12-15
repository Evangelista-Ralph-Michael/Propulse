<?php
// Enterprise Grade: Centralized Database Connection using PDO
$host = 'localhost:3325';
$db   = 'propulse';
$user = 'root'; // In a real enterprise env, use environment variables
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log this error to a file, don't show it to the user
    error_log($e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Start Session Securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generation (Security Best Practice)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>