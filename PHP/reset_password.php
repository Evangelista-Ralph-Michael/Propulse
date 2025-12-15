<?php
include 'header.php';
if (isset($_POST['reset'])) {
    $token = $_GET['token'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password_hash='$pass', reset_token=NULL WHERE reset_token='$token'");
    echo "<script>alert('Password Changed!'); window.location='login.php';</script>";
}
?>
<div class="container">
    <div class="form-box">
        <h2>Reset Password</h2>
        <form method="post">
            <input type="password" name="password" placeholder="New Password" required>
            <button type="submit" name="reset" class="btn" style="width:100%">Update Password</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>