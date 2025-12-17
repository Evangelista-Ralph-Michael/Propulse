<?php include 'header.php'; ?>

<div class="container">
    <h1 style="text-align: center; margin-bottom: 20px;">All Products</h1>

    <div class="category-tabs">
        <a href="products.php" class="tab-link">All</a>
        <a href="products.php?category=Nike" class="tab-link">Nike</a>
        <a href="products.php?category=Under Armour" class="tab-link">Under Armour</a>
        <a href="products.php?category=Adidas" class="tab-link">Adidas</a>
    </div>

    <div class="product-grid">
        <?php
// ... inside product-grid div ...
    
    // Enterprise SQL: Join with reviews to get average rating and count
    $sql = "SELECT p.*, 
            COALESCE(AVG(r.rating), 0) as avg_rating, 
            COUNT(r.id) as review_count 
            FROM products p 
            LEFT JOIN product_reviews r ON p.id = r.product_id";

    $params = [];

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $sql .= " WHERE p.name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
    } elseif (isset($_GET['category'])) {
        $sql .= " WHERE p.category = ?";
        $params[] = $_GET['category'];
    }

    $sql .= " GROUP BY p.id"; // Grouping is required when using aggregate functions like AVG

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        while($row = $stmt->fetch()) {
            // Render Stars Helper
            $stars = round($row['avg_rating']); 
            $star_str = str_repeat("⭐", $stars) . str_repeat("☆", 5 - $stars);
            ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Product">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p><?php echo htmlspecialchars($row['category']); ?></p>
                
                <div style="color: #f39c12; margin: 5px 0;">
                    <small><?php echo $star_str; ?> (<?php echo $row['review_count']; ?>)</small>
                </div>

                <span class="price">₱<?php echo number_format($row['price'], 2); ?></span>

                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn" style="margin-top:10px;">View</a>
            </div>
            <?php
        }
    } else { 
        echo "<p>No products found.</p>"; 
    }
        ?>
    </div>
</div>
<?php include 'footer.php'; ?>