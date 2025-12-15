<?php
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Total & Cart Items
$stmt = $pdo->prepare("SELECT SUM(products.price * cart.quantity) as total FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
$stmt->execute([$user_id]);
$cart_data = $stmt->fetch();
$total_price = $cart_data['total'] ?? 0;

if ($total_price == 0) {
    echo "<script>alert('Cart is empty'); window.location='products.php';</script>";
    exit();
}

// Get User's Default Address
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$default_address = $user_data['address'];

// Place Order Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    
    // CSRF Check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("Invalid Request");

    $address_option = $_POST['address_option'];
    
    if ($address_option == 'default') {
        $final_address = $default_address;
    } else {
        $final_address = trim($_POST['new_address']);
        if (empty($final_address)) $error = "Please enter a new address.";
    }

    if (!isset($error)) {
        try {
            // Enterprise Grade: Database Transaction
            $pdo->beginTransaction();

            // 1. Fetch cart items again to process inventory
            $cart_stmt = $pdo->prepare("SELECT c.product_id, c.quantity, c.size, p.price, p.stock 
                                        FROM cart c 
                                        JOIN products p ON c.product_id = p.id 
                                        WHERE c.user_id = ?");
            $cart_stmt->execute([$user_id]);
            $items = $cart_stmt->fetchAll();

            // Check if stock is sufficient for all items
            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("One or more items in your cart are out of stock.");
                }
            }

            // 2. Create Order Record
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, shipping_address, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $total_price, $final_address]);
            $order_id = $pdo->lastInsertId();

            // 3. Move Items to Order_Items and Decrease Stock
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, size, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
            $stock_update = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($items as $item) {
                // Record Item
                $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['size'], $item['price']]);
                
                // Deduct Stock
                $stock_update->execute([$item['quantity'], $item['product_id']]);
            }

            // 4. Empty Cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // 5. Commit Transaction
            $pdo->commit();

            echo "<script>alert('Order Placed Successfully!'); window.location.href='profile.php';</script>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Order failed: " . $e->getMessage(); 
        }
    }
}
?>

<div class="container">
    <h1>Checkout</h1>
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex:1;">
            <div class="form-box" style="width:100%;">
                <h3>Order Summary</h3>
                <h2 style="margin: 20px 0;">Total: ₱<?php echo number_format($total_price, 2); ?></h2>
            </div>
        </div>

        <div style="flex:1;">
            <form method="post" class="form-box" style="width:100%;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <h3>Shipping Address</h3>
                <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

                <div class="address-option" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
                    <input type="radio" name="address_option" value="default" checked>
                    <label><strong>Use Profile Address:</strong></label>
                    <p style="color: gray; margin-left: 20px;"><?php echo htmlspecialchars($default_address ?: "No address set."); ?></p>
                </div>

                <div class="address-option" style="padding: 10px; border: 1px solid #ddd;">
                    <input type="radio" name="address_option" value="new">
                    <label><strong>Enter New Address:</strong></label>
                    <textarea name="new_address" placeholder="Type address here..." style="width:100%; margin-top:10px;"></textarea>
                </div>

                <button type="submit" name="place_order" class="btn" style="width:100%; margin-top: 20px;">Confirm Order</button>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>