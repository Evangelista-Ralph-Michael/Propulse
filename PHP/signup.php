<?php
require_once 'db_connect.php'; 
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // 1. Password Validation
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } 
    else {
        // 2. Check email existence
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, address, phone) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hash, $address, $phone])) {
                echo "<script>alert('Account Created!'); window.location.href='login.php';</script>";
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="form-box">
        <h2>Become a Member</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label>Full Name</label>
            <input type="text" name="name" required>

            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Phone Number</label>
            <input type="text" name="phone" required>

            <label>Password (Min 8 chars)</label>
            <input type="password" name="password" required>

            <label>Shipping Address</label>
            <textarea name="address" rows="3" required></textarea>

            <button type="submit" class="btn">Create Account</button>
        </form>
        <div class="form-footer">
            Already a member? <a href="login.php">Login here</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>