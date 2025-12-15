<?php
require_once 'db_connect.php';

// If already logged in as admin, auto-redirect to dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // SECURITY: Specifically query for role='admin'. 
    // Regular users cannot log in here even with correct passwords.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Secure Session Start
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = 'admin'; // Explicitly set role
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Access Denied. Invalid Admin Credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Propulse</title>
    <link rel="stylesheet" href="/WebDev_Project/CSS/style.css">
    <style>
        /* Dark Theme Exclusively for Admin Login */
        body.admin-body {
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Roboto', sans-serif;
        }
        .admin-box {
            background: #1e1e1e;
            padding: 50px 40px;
            border: 1px solid #333;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border-radius: 8px;
        }
        .admin-box h2 { 
            color: #fff; 
            text-transform: uppercase; 
            letter-spacing: 3px; 
            margin-bottom: 5px; 
            font-size: 1.5rem;
        }
        .admin-box p { color: #888; margin-bottom: 30px; font-size: 0.9rem; }
        
        /* Dark Inputs */
        .admin-box input {
            background: #2c2c2c;
            border: 1px solid #444;
            color: white;
            padding: 15px;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 20px;
            outline: none;
            transition: 0.3s;
        }
        .admin-box input:focus { 
            border-color: #fff; 
            background: #333; 
        }
        .admin-box label { 
            color: #aaa; 
            display: block; 
            text-align: left; 
            margin-bottom: 8px; 
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        /* Action Button */
        .btn-admin {
            background-color: #ffffff;
            color: #121212;
            width: 100%;
            padding: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn-admin:hover { 
            background-color: #ccc; 
            transform: translateY(-2px);
        }
        
        .error-msg { 
            background: rgba(220, 53, 69, 0.2); 
            color: #ff6b6b; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
            display: block; 
            font-size: 0.9rem;
            border: 1px solid #ff6b6b;
        }

        .back-link {
            margin-top: 30px;
            display: block;
            color: #555;
            font-size: 0.8rem;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body class="admin-body">

    <div class="admin-box">
        <h2>Admin Portal</h2>
        <p>Authorized Personnel Only</p>

        <?php if(isset($error)) echo "<div class='error-msg'>⚠️ $error</div>"; ?>

        <form method="post">
            <div style="text-align: left;">
                <label>Administrator Email</label>
                <input type="email" name="email" required placeholder="admin@propulse.com">
            </div>

            <div style="text-align: left;">
                <label>Security Key (Password)</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-admin">Enter Dashboard</button>
        </form>
        
        <a href="index.php" class="back-link">← Return to Storefront</a>
    </div>

</body>
</html>