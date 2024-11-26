<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'login_register');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['product_image'])) {

    // Sanitize and validate input data
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
    
    // Handle image upload
    $product_image = $_FILES['product_image']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($product_image);
    
    // Check if the upload directory exists, otherwise create it
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);  // create directory with proper permissions
    }

    // Check if the file is a valid image
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $valid_extensions = array("jpg", "jpeg", "png", "gif");
    
    if (in_array($imageFileType, $valid_extensions)) {
        // Move uploaded file to the target directory
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            
            // Prepared statement to insert the product into the database
            $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $product_name, $product_price, $product_image);

            // Execute the query
            if ($stmt->execute()) {
                // Redirect to avoid resubmission and show success message
                header("Location: admin_dashboard.php?status=success");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error uploading image.";
        }
    } else {
        echo "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}

// Close the connection
$conn->close();
?>
