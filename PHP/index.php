<?php include 'header.php'; ?>

<div style="background-color: var(--accent-light); padding: 80px 5%; display: flex; align-items: center; justify-content: space-between;">
    <div style="max-width: 500px;">
        <h1 style="font-size: 3rem; line-height: 1.2;">UNLEASH YOUR SPEED.</h1>
        <p style="margin: 20px 0; font-size: 1.1rem;">Fuel Your Passion. Gear Up With Propulse.</p>
        <a href="products.php" class="btn">Shop Now</a>
    </div>
    <img src="https://i.pinimg.com/1200x/8c/1c/9f/8c1c9fffadb47ababfd53d92f7ba1784.jpg" alt="Featured" style="max-width: 50%;">
</div>

<div class="container">
    <h2 style="text-align: center; margin-bottom: 40px;">Featured Products</h2>
    <div class="product-grid">
        <?php
        // Fetch 3 random products
        $stmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 3");
        
        while($row = $stmt->fetch()) {
            ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($row['image_url']); ?>">
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <span class="price">₱<?php echo number_format($row['price'], 2); ?></span>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn" style="margin-top: 10px;">View</a>
            </div>
            <?php
        }
        ?>
    </div>
</div>
<?php include 'footer.php'; ?>