<?php
include 'header.php';
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $token = bin2hex(random_bytes(16));
        $conn->query("UPDATE users SET reset_token = '$token' WHERE email = '$email'");
        // Simulation Link
        $msg = "<div style='background:#d4edda; padding:15px; margin-top:10px; color:green;'>
                <strong>Simulation:</strong> <a href='reset_password.php?token=$token'>Click here to reset password</a>
                </div>";
    } else {
        $msg = "<p style='color:red'>Email not found.</p>";
    }
}
?>
<div class="container">
    <div class="form-box">
        <h2>Forgot Password</h2>
        <form method="post">
            <input type="email" name="email" placeholder="Enter Email" required>
            <button type="submit" class="btn" style="width:100%">Send Reset Link</button>
        </form>
        <?php echo $msg; ?>
    </div>
</div>
<?php include 'footer.php'; ?>