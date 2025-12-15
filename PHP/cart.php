<?php 
include 'header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Remove Item via GET (Ideally should be POST for security, but keeping simple for now)
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: cart.php"); // Refresh to clear URL
    exit();
}
?>

<div class="container">
    <h1>Your Shopping Cart</h1>
    <table>
        <tr>
            <th>Product</th>
            <th>Size</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
        <?php
        $sql = "SELECT cart.id as cart_id, products.name, products.price, cart.quantity, cart.size 
                FROM cart 
                JOIN products ON cart.product_id = products.id 
                WHERE cart.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $grand_total = 0;

        while($row = $stmt->fetch()) {
            $subtotal = $row['price'] * $row['quantity'];
            $grand_total += $subtotal;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['size']) . "</td>"; // Added Size display
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            echo "<td>" . $row['quantity'] . "</td>";
            echo "<td>₱" . number_format($subtotal, 2) . "</td>";
            echo "<td><a href='cart.php?remove=" . $row['cart_id'] . "' style='color:red;'>Remove</a></td>";
            echo "</tr>";
        }
        ?>
    </table>

    <div style="text-align: right; margin-top: 20px;">
        <h3>Grand Total: ₱<?php echo number_format($grand_total, 2); ?></h3>
        <br>
        <?php if($grand_total > 0): ?>
            <a href="checkout.php" class="btn">Proceed to Checkout</a>
        <?php else: ?>
            <a href="products.php" class="btn">Cart is Empty - Go Shop</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>