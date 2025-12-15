<?php
require_once 'db_connect.php'; // Use require_once for safety

// Calculate Cart Count using PDO
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $cart_count = $row['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propulse | Fuel Your Passion</title>
    <link rel="stylesheet" href="/WebDev_Project/CSS/style.css">
    <script src="/WebDev_Project/JS/main.js" defer></script>
</head>
<body>

<header>
    <a href="index.php" class="logo-link">
       <img src="/WebDev_Project/Img/logo3.png" alt="Propulse Logo">
    </a>

    <form method="GET" action="products.php" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Search...">
        <button type="submit" class="search-btn">🔍</button>
    </form>
    
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Shop</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="cart.php">Cart (<span id="nav-cart-count"><?php echo $cart_count; ?></span>)</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>