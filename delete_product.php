<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'login_register');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if product_id is set in POST request (form submission)
if (isset($_POST['product_id'])) {
    // Sanitize and retrieve the product ID
    $product_id = $_POST['product_id'];

    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id); // "i" stands for integer type
    
    // Execute the deletion query
    if ($stmt->execute()) {
        // Product deleted successfully
        header("Location: admin_dashboard.php?status=success");
        exit();
    } else {
        // Error deleting product
        echo "Error deleting product: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
