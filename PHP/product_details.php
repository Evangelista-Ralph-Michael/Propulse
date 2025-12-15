<?php 
// 1. LOAD DATABASE FIRST (No HTML output yet)
require_once 'db_connect.php'; 

// --- 2. HANDLE AJAX ADD TO CART (Must be before header.php) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    // Tell the browser we are sending JSON data, not a webpage
    header('Content-Type: application/json');

    // A. Login Check
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Please login to add items to cart.']);
        exit; // STOP script here
    }

    // B. Validation
    $uid = $_SESSION['user_id'];
    $pid = $_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    $size = isset($_POST['size']) ? $_POST['size'] : null;

    if (!$size) {
        echo json_encode(['success' => false, 'error' => 'Please select a size first!']);
        exit;
    }

    // C. Check Stock
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $product = $stmt->fetch();

    if ($product['stock'] < $qty) {
        echo json_encode(['success' => false, 'error' => 'Not enough stock available.']);
        exit;
    }

    try {
        // D. Insert/Update Cart
        // Check if item with same size exists in cart
        $check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=? AND size=?");
        $check->execute([$uid, $pid, $size]);
        
        if ($check->rowCount() > 0) {
            $row = $check->fetch();
            $new_qty = $row['quantity'] + $qty;
            // Ensure new quantity doesn't exceed stock
            if ($new_qty > $product['stock']) {
                echo json_encode(['success' => false, 'error' => 'Cannot add more. Stock limit reached.']);
                exit;
            }
            $update = $pdo->prepare("UPDATE cart SET quantity=? WHERE id=?");
            $update->execute([$new_qty, $row['id']]);
        } else {
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)");
            $insert->execute([$uid, $pid, $qty, $size]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Item added to cart!', 'reload' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
    }
    exit; // IMPORTANT: Stop the script so no HTML is added to the response
}

// --- 3. HANDLE REVIEW SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) exit;
    
    $uid = $_SESSION['user_id'];
    $pid = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review_text']);

    // Check if user actually bought it and order status is Delivered
    $check_sql = "SELECT oi.id FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'Delivered'";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$pid, $uid]);

    if ($check_stmt->rowCount() > 0) {
        $ins = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $ins->execute([$pid, $uid, $rating, $review]);
        echo "<script>alert('Review Submitted!'); window.location.href='product_details.php?id=$pid';</script>";
    } else {
        echo "<script>alert('You can only review products you have purchased and received.');</script>";
    }
}

// --- 4. NOW LOAD THE HTML HEADER ---
include 'header.php'; 

// --- 5. FETCH PRODUCT DETAILS ---
$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT p.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as review_count 
                       FROM products p 
                       LEFT JOIN product_reviews r ON p.id = r.product_id 
                       WHERE p.id = ? 
                       GROUP BY p.id");
$stmt->execute([$pid]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container'>Product not found.</div>";
    include 'footer.php';
    exit();
}

// --- 6. FETCH REVIEWS ---
$r_stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM product_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$r_stmt->execute([$pid]);
$reviews = $r_stmt->fetchAll();

// --- 7. CHECK REVIEW PERMISSION ---
$can_review = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $chk = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'Delivered'");
    $chk->execute([$pid, $uid]);
    if ($chk->rowCount() > 0) $can_review = true;
}
?>

<div class="container">
    <div class="product-detail-container">
        <div class="detail-image">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" style="width:100%;">
        </div>

        <div class="detail-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div style="color: #f39c12; font-size: 1.2rem; margin-bottom: 10px;">
                <?php 
                $stars = round($product['avg_rating']); 
                echo str_repeat("⭐", $stars) . str_repeat("☆", 5 - $stars);
                ?>
                <span style="color: #666; font-size: 1rem;">(<?php echo $product['review_count']; ?> Reviews)</span>
            </div>

            <p class="category"><?php echo htmlspecialchars($product['category']); ?></p>
            <h2 class="price">₱<?php echo number_format($product['price'], 2); ?></h2>
            <p class="desc"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <p style="margin: 10px 0; font-weight: bold; color: <?php echo $product['stock'] > 0 ? 'green' : 'red'; ?>">
                <?php echo $product['stock'] > 0 ? "In Stock: " . $product['stock'] : "Out of Stock"; ?>
            </p>

            <form method="post" class="ajax-form">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <?php if(isset($_SESSION['csrf_token'])): ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <?php endif; ?>
                
                <label><strong>Select Size</strong></label>
                <div class="size-grid">
                    <?php 
                    $sizes = explode(',', $product['sizes']);
                    foreach($sizes as $size): 
                        $clean_size = trim($size);
                        $id = 'size-' . preg_replace('/[^a-zA-Z0-9]/', '-', $clean_size); 
                    ?>
                        <input type="radio" name="size" id="<?php echo $id; ?>" value="<?php echo $clean_size; ?>" class="size-radio">
                        <label for="<?php echo $id; ?>" class="size-box"><?php echo $clean_size; ?></label>
                    <?php endforeach; ?>
                </div>

                <label><strong>Quantity:</strong></label>
                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 100%; padding: 12px; margin-bottom: 20px;">
                
                <?php if ($product['stock'] > 0): ?>
                    <button type="submit" class="btn" style="width: 100%;">Add to Cart</button>
                <?php else: ?>
                    <button type="button" class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;" disabled>Out of Stock</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div style="margin-top: 50px; border-top: 1px solid #ddd; padding-top: 30px;">
        <h2>Customer Reviews</h2>
        <?php if ($can_review): ?>
            <div class="form-box" style="margin: 20px 0; width: 100%;">
                <h3>Write a Review</h3>
                <form method="post">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                    <label>Rating:</label>
                    <select name="rating" style="padding: 10px; width: 100%; margin-bottom: 10px;">
                        <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                        <option value="4">⭐⭐⭐⭐ (Good)</option>
                        <option value="3">⭐⭐⭐ (Average)</option>
                        <option value="2">⭐⭐ (Poor)</option>
                        <option value="1">⭐ (Terrible)</option>
                    </select>
                    <label>Review:</label>
                    <textarea name="review_text" rows="3" placeholder="How was the product?" required style="width:100%; padding:10px;"></textarea>
                    <button type="submit" name="submit_review" class="btn" style="margin-top:10px;">Submit Review</button>
                </form>
            </div>
        <?php elseif(isset($_SESSION['user_id'])): ?>
            <p style="color: gray; font-style: italic;">You can write a review once your order for this item is delivered.</p>
        <?php endif; ?>

        <div class="reviews-list">
            <?php if (count($reviews) > 0): ?>
                <?php foreach($reviews as $review): ?>
                    <div style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                        <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                        <span style="color: #f39c12; margin-left: 10px;">
                            <?php echo str_repeat("⭐", $review['rating']); ?>
                        </span>
                        <p style="margin-top: 5px;"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <small style="color: #999;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No reviews yet. Be the first to review!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>