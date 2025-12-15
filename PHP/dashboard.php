<?php
session_start();
require_once 'db_connect.php';

// --- SECURITY GATE ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php"); 
    exit();
}

$msg = "";

// --- DELETE LOGIC ---
if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
    $msg = "Product deleted.";
}

// --- CREATE / UPDATE PRODUCT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $cat = $_POST['category'];
    $desc = $_POST['description'];
    $sizes = $_POST['sizes'];
    
    // Default image
    $image_url = "https://placehold.co/400x400?text=New+Product"; 

    // 1. Check Local File Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../Img/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["image"]["name"]); 
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = "/WebDev_Project/Img/" . $filename;
        }
    } 
    // 2. Check Link Input
    else if (!empty($_POST['image_url_input'])) {
        $image_url = trim($_POST['image_url_input']);
    }
    // 3. Keep Existing if Editing
    else if (!empty($_POST['existing_image'])) {
        $image_url = $_POST['existing_image'];
    }

    if (!empty($_POST['product_id'])) {
        // Update
        $sql = "UPDATE products SET name=?, price=?, category=?, description=?, sizes=?, image_url=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $cat, $desc, $sizes, $image_url, $_POST['product_id']]);
        $msg = "Product updated.";
    } else {
        // Insert
        $sql = "INSERT INTO products (name, price, category, description, sizes, image_url) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $cat, $desc, $sizes, $image_url]);
        $msg = "Product created.";
    }
}

// --- UPDATE ORDER STATUS LOGIC ---
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    $msg = "Order #" . $_POST['order_id'] . " status updated.";
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Propulse</title>
    <link rel="stylesheet" href="/WebDev_Project/CSS/style.css"> 
    <style>
        body { background-color: #f4f6f8; }
        .admin-header { background: #121212; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .admin-header a { color: white; text-decoration: none; font-weight: bold; }
        
        .dashboard-container { display: flex; max-width: 1200px; margin: 40px auto; gap: 30px; padding: 0 20px; }
        
        .sidebar { width: 250px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; }
        .sidebar a { display: block; padding: 12px; color: #333; text-decoration: none; border-bottom: 1px solid #eee; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #121212; color: white; border-radius: 4px; border-bottom: none; }
        
        .main-content { flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-select { padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
        .alert { background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { font-size: 0.9rem; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>

    <div class="admin-header">
        <div style="font-size: 1.5rem; font-weight: bold;">PROPULSE <span style="font-weight:300; font-size: 1rem;">ADMIN</span></div>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> | 
            <a href="index.php" style="margin-left: 10px; color: #aaa;">View Site</a> |
            <a href="logout.php" style="margin-left: 10px; color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="dashboard-container">
        
        <div class="sidebar">
            <a href="dashboard.php?tab=products" class="<?php echo $tab=='products'?'active':''; ?>">📦 Products</a>
            <a href="dashboard.php?tab=orders" class="<?php echo $tab=='orders'?'active':''; ?>">🚚 Orders</a>
            <a href="dashboard.php?tab=users" class="<?php echo $tab=='users'?'active':''; ?>">👥 Users</a>
        </div>

        <div class="main-content">
            <?php if($msg) echo "<div class='alert'>$msg</div>"; ?>

            <?php if ($tab == 'products'): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2>Product Management</h2>
                    <button onclick="document.getElementById('productForm').style.display='block'" class="btn">Add New Product</button>
                </div>

                <div id="productForm" class="form-box" style="display:none; margin: 0 0 20px 0; max-width: 100%; border: 2px solid #121212;">
                    <h3 style="margin-bottom:15px;">Product Details</h3>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" id="p_id">
                        <input type="hidden" name="existing_image" id="p_image">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label>Name</label> <input type="text" name="name" id="p_name" required>
                                <label>Price</label> <input type="number" step="0.01" name="price" id="p_price" required>
                                <label>Category</label> <input type="text" name="category" id="p_cat">
                            </div>
                            <div>
                                <label>Sizes (e.g. US 8,US 9)</label> <input type="text" name="sizes" id="p_sizes" required>
                                
                                <label>Image (Upload)</label> 
                                <input type="file" name="image" accept="image/*">
                                
                                <label>Or Image URL</label> 
                                <input type="text" name="image_url_input" id="p_img_url" placeholder="https://...">
                            </div>
                        </div>
                        <label>Description</label> <textarea name="description" id="p_desc" rows="3"></textarea>
                        
                        <div style="margin-top:15px;">
                            <button type="submit" name="save_product" class="btn">Save</button>
                            <button type="button" onclick="document.getElementById('productForm').style.display='none'" class="btn" style="background:#ccc; color:#333;">Cancel</button>
                        </div>
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Img</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
                        while ($row = $stmt->fetch()) {
                            $jsonData = htmlspecialchars(json_encode($row));
                            echo "<tr>";
                            echo "<td><img src='{$row['image_url']}' width='40'></td>";
                            echo "<td>{$row['name']}</td>";
                            echo "<td>₱".number_format($row['price'], 2)."</td>";
                            echo "<td>{$row['stock']}</td>";
                            echo "<td>
                                    <button onclick='editProduct($jsonData)' style='cursor:pointer; color:blue; background:none; border:none; text-decoration:underline;'>Edit</button> | 
                                    <a href='dashboard.php?tab=products&delete_product={$row['id']}' style='color:red;' onclick='return confirm(\"Delete?\")'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <script>
                    function editProduct(data) {
                        document.getElementById('productForm').style.display = 'block';
                        document.getElementById('p_id').value = data.id;
                        document.getElementById('p_name').value = data.name;
                        document.getElementById('p_price').value = data.price;
                        document.getElementById('p_cat').value = data.category;
                        document.getElementById('p_sizes').value = data.sizes;
                        document.getElementById('p_desc').value = data.description;
                        document.getElementById('p_image').value = data.image_url;
                        document.getElementById('p_img_url').value = data.image_url; 
                        window.scrollTo(0,0);
                    }
                </script>

            <?php elseif ($tab == 'orders'): ?>
                <h2>Order Management</h2>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Update</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT o.*, u.name as customer FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC";
                        $stmt = $pdo->query($sql);
                        while ($row = $stmt->fetch()) {
                            $istmt = $pdo->prepare("SELECT p.name, oi.quantity, oi.size FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=?");
                            $istmt->execute([$row['id']]);
                            $items = $istmt->fetchAll(PDO::FETCH_ASSOC);
                            $itemList = "";
                            foreach($items as $i) $itemList .= "{$i['quantity']}x {$i['name']} ({$i['size']})<br>";

                            echo "<tr>";
                            echo "<td>#{$row['id']}</td>";
                            echo "<td>{$row['customer']}</td>";
                            echo "<td style='font-size:0.8rem; color:#555;'>$itemList</td>";
                            echo "<td>₱".number_format($row['total_price'], 2)."</td>";
                            echo "<td>
                                    <form method='post'>
                                        <input type='hidden' name='order_id' value='{$row['id']}'>
                                        <select name='status' class='status-select'>
                                            <option value='Pending' ".($row['status']=='Pending'?'selected':'').">Pending</option>
                                            <option value='Shipped' ".($row['status']=='Shipped'?'selected':'').">Shipped</option>
                                            <option value='Delivered' ".($row['status']=='Delivered'?'selected':'').">Delivered</option>
                                        </select>
                                </td>";
                            echo "<td>
                                        <button type='submit' name='update_status' class='btn' style='padding:5px 10px; font-size:0.8rem;'>Update</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>