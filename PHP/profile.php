<?php 
include 'header.php'; 
if (!isset($_SESSION['user_id'])) exit(header("Location: login.php"));
$user_id = $_SESSION['user_id'];

// AJAX Update Handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
         // Fallback for non-JS
    } else {
        header('Content-Type: application/json');
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
             echo json_encode(['success'=>false, 'error'=>'Invalid Token']); exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, address=?, phone=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['address'], $_POST['phone'], $user_id]);
            echo json_encode(['success'=>true, 'message'=>'Profile Updated Successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false, 'error'=>'Update failed.']);
        }
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="container">
    <h1>My Profile</h1>
    <div style="display: flex; gap: 40px; flex-wrap: wrap;">
        <div style="flex: 1;">
            <div class="form-box" style="width: 100%;">
                <h3>Edit Information</h3>
                <form method="post" class="ajax-form">
                    <input type="hidden" name="update_profile" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <label>Name</label> <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                    <label>Email</label> <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <label>Phone</label> <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    <label>Default Address</label> <textarea name="address" style="width:100%;"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    
                    <button type="submit" class="btn">Save Changes</button>
                </form>
            </div>
        </div>
        <div style="flex: 1.5;">
             <h3>Order History</h3>
             <table>
                <tr><th>ID</th><th>Total</th><th>Status</th></tr>
                <?php 
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                while($o = $stmt->fetch()){
                    echo "<tr><td>#{$o['id']}</td><td>₱".number_format($o['total_price'],2)."</td><td>{$o['status']}</td></tr>";
                }
                ?>
             </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>