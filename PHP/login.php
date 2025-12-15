<?php
require_once 'db_connect.php';
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate ID to prevent Session Fixation attacks
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<div class="auth-container">
    <div class="form-box">
        <h2>Welcome Back</h2>
        <?php if(isset($error)) echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>

        <form method="post">
            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <div style="text-align: right; margin-bottom: 20px;">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>