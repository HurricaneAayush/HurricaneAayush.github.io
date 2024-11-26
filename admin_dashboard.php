<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'login_register');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Check for form submission for adding a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['product_image'])) {
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];
    $productImage = $_FILES['product_image'];

    // Handle image upload
    $imageName = time() . '_' . basename($productImage['name']);
    $targetDir = 'uploads/';
    $targetFile = $targetDir . $imageName;
    
    if (move_uploaded_file($productImage['tmp_name'], $targetFile)) {
        // Insert new product into the database
        $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $productName, $productPrice, $imageName);
        $stmt->execute();
        $stmt->close();
    }
}

// Check for form submission for deleting a product
if (isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->close();
}

// Fetch updated product list for AJAX request
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_products') {
    $result = $conn->query("SELECT * FROM products");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles/responsive.css">
    <style>
        /* Same CSS styling as before */
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            transition: margin-left 0.3s ease; /* Smooth transition for main content */
        }

        /* Topbar Styling */
        .topbar {
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            background-color: #333;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
        }

        .topbar h1 {
            font-size: 20px;
            margin-left: 10px;
        }

        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            width: 50px; /* Width of the button */
            height: 40px; /* Height of the button */
        }

        .bar {
            display: block;
            width: 100%;
            height: 4px;
            background-color: white;
            border-radius: 2px;
            transition: 0.3s; /* Smooth transition effect for the bars */
        }

        /* Sidebar Styling */
        .sidebar {
            width: 200px;
            height: 100vh;
            background-color: #2196f3cc;
            padding-top: 80px; /* To align below topbar */
            position: fixed;
            left: -200px; /* Initially hidden */
            transition: left 0.3s ease;
        }

        .sidebar.open {
            left: 0; /* Sidebar is visible */
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 15px;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar a:hover {
            background-color: #45a049;
        }

        /* Main Content Styling */
        .main {
            margin-left: 0; /* Adjust for sidebar visibility */
            padding: 80px 20px 20px 20px;
            width: 100%;
            transition: margin-left 0.3s ease; /* Smooth transition for main content */
        }

        .product-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }

        .product-item {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 200px;
        }

        .product-item img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .product-item h6 {
            margin: 10px 0 5px;
            font-size: 18px;
        }

        .product-item p {
            color: #555;
        }

        form label {
            margin-top: 10px;
        }

        form input,
        form button {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <h1>Admin Dashboard</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#">Dashboard</a>
        <a href="#">Manage Products</a>
        <a href="#">Orders</a>
        <a href="#">Settings</a>
        <a href="index.php">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h2>Add New Product</h2>
        <form id="addProductForm" enctype="multipart/form-data">
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" id="product_name" required>

            <label for="product_price">Product Price:</label>
            <input type="number" step="0.01" name="product_price" id="product_price" required>

            <label for="product_image">Product Image:</label>
            <input type="file" name="product_image" id="product_image" required>

            <button type="submit">Add Product</button>
        </form>

        <h2>Product List</h2>
        <div class="product-grid" id="productGrid">
            <!-- Products will be injected here via AJAX -->
        </div>
    </div>

    <!-- JavaScript to Toggle Sidebar -->
    <script>
        // Toggle sidebar visibility
        function toggleSidebar() {
            var sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("open");

            var main = document.querySelector(".main");
            if (sidebar.classList.contains("open")) {
                main.style.marginLeft = "200px";
            } else {
                main.style.marginLeft = "0";
            }
        }

        // Handle Add Product Form Submission via AJAX
        document.getElementById("addProductForm").addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission

            var formData = new FormData(this);

            // Send data via AJAX
            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Dynamically update product list after form submission
                loadProducts();
            })
            .catch(error => console.error('Error:', error));
        });

        // Function to Load Products via AJAX
        function loadProducts() {
            fetch('admin_dashboard.php?action=get_products')
                .then(response => response.json())
                .then(data => {
                    const productGrid = document.getElementById('productGrid');
                    productGrid.innerHTML = ''; // Clear current product list

                    // Loop through products and display them
                    data.forEach(product => {
                        const productDiv = document.createElement('div');
                        productDiv.classList.add('product-item');
                        productDiv.innerHTML = `
                            <img src="uploads/${product.image}" alt="${product.name}">
                            <h6>${product.name}</h6>
                            <p>${product.price}</p>
                            <button class="delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                        `;
                        productGrid.appendChild(productDiv);
                    });
                })
                .catch(error => console.error('Error loading products:', error));
        }
        
        function deleteProduct(productId) {
            // Send a POST request to delete the product
            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `delete_product=true&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the products list after successful deletion
                    loadProducts();
                } else {
                    console.error('Failed to delete product');
                }
            })
            .catch(error => console.error('Error deleting product:', error));
        }

        // Load products on page load
        window.onload = loadProducts;
    </script>

</body>
</html>
