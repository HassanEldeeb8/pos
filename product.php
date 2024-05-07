<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Enter your MySQL password here
$database = "fresh"; // Change to your actual database name

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Function to retrieve and display all products
function displayProducts($conn) {
    $sql = "SELECT * FROM Products";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "<h2>Products</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Product ID</th><th>Product Name</th><th>Brand</th><th>Price</th><th>Quantity</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr><td>" . $row["product_id"] . "</td><td>" . $row["product_name"] . "</td><td>" . $row["brand"] . "</td><td>" . $row["price"] . "</td><td>" . $row["quantity_in_stock"] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No products found";
    }
}
function generateBarcode($productId, $productName, $brand) {
    // Concatenate a prefix, product ID, product name, and brand for barcode representation
    $barcode = "PROD-" . str_pad($productId, 6, "0", STR_PAD_LEFT) . "-" . substr($productName, 0, 3) . "-" . substr($brand, 0, 3);
    return $barcode;
}

// Function to add a new product with a generated barcode
function addProduct($productName, $brand, $price, $quantity, $conn) {
    // Generate barcode for the product
    $barcodeData = generateBarcode(mysqli_insert_id($conn), $productName, $brand);
    
    // Insert the product into the database with the generated barcode
    $sql = "CALL CreateProduct(?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssdis", $productName, $brand, $price, $quantity, $barcodeData);

    if (mysqli_stmt_execute($stmt)) {
        echo "New product added successfully";
    } else {
        echo "Error adding product: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to restock product quantity
function restockProduct($productId, $quantity, $conn) {
    $sql = "CALL RestockProduct(?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $productId, $quantity);

    if (mysqli_stmt_execute($stmt)) {
        echo "Product restocked successfully";
    } else {
        echo "Error restocking product: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to delete a product from inventory
function deleteProduct($productId, $conn) {
    $sql = "CALL DeleteProduct(?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);

    if (mysqli_stmt_execute($stmt)) {
        echo "Product deleted successfully";
    } else {
        echo "Error deleting product: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}
// Function to update product details
function updateProduct($productId, $productName, $brand, $price, $quantity, $conn) {
    // Retrieve existing product details
    $existingDetails = getProductDetails($productId, $conn);

    // Check which fields are empty and replace them with existing values
    if (empty($productName)) {
        $productName = $existingDetails['product_name'];
    }
    if (empty($brand)) {
        $brand = $existingDetails['brand'];
    }
    if (empty($price)) {
        $price = $existingDetails['price'];
    }
    if (empty($quantity)) {
        $quantity = $existingDetails['quantity_in_stock'];
    }

    // Update the product with the new details
    $sql = "CALL UpdateProduct(?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isdis", $productId, $productName, $brand, $price, $quantity);

    if (mysqli_stmt_execute($stmt)) {
        echo "Product details updated successfully";
    } else {
        echo "Error updating product details: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to retrieve product details by ID
function getProductDetails($productId, $conn) {
    $sql = "SELECT * FROM Products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $details = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $details;
}

// Process incoming requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    if ($_POST["action"] == "add_product") {
        $productName = $_POST["product_name"];
        $brand = $_POST["brand"];
        $price = $_POST["price"];
        $quantity = $_POST["quantity"];
        addProduct($productName, $brand, $price, $quantity, $conn);
    } elseif ($_POST["action"] == "update_product") {
        $productId = $_POST["product_id"];
        $productName = $_POST["product_name"];
        $brand = $_POST["brand"];
        $price = $_POST["price"];
        $quantity = $_POST["quantity"];

        // Check which fields are filled by the user and update only those fields
        if (!empty($productName) || !empty($brand) || !empty($price) || !empty($quantity)) {
            updateProduct($productId, $productName, $brand, $price, $quantity, $conn);
        } else {
            echo "No fields provided for update";
        }}
        elseif ($_POST["action"] == "restock_product") {
        $productId = $_POST["product_id"];
        $quantity = $_POST["quantity"];

        restockProduct($productId, $quantity, $conn);
    } elseif ($_POST["action"] == "delete_product") {
        $productId = $_POST["product_id"];
        deleteProduct($productId, $conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h2 {
            margin-top: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"] {
            width: calc(50% - 10px);
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #007bff;
        }
        input[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>Add New Product</h2>
    <form action="" method="post">
        <label for="product_name">Product Name:</label><br>
        <input type="text" id="product_name" name="product_name" required><br>
        <label for="brand">Brand:</label><br>
        <input type="text" id="brand" name="brand" required><br>
        <label for="price">Price:</label><br>
        <input type="number" id="price" name="price" min="0" step="0.01" required><br>
        <label for="quantity">Quantity:</label><br>
        <input type="number" id="quantity" name="quantity" min="0" required><br><br>
        <input type="hidden" name="action" value="add_product">
        <input type="submit" value="Add Product">
    </form>
    <h2>Update Product</h2>
    <form action="" method="post">
        <label for="update_product_id">Product ID:</label><br>
        <input type="number" id="update_product_id" name="product_id" required><br>
        <label for="update_product_name">Product Name (optional):</label><br>
        <input type="text" id="update_product_name" name="product_name"><br>
        <label for="update_brand">Brand (optional):</label><br>
        <input type="text" id="update_brand" name="brand"><br>
        <label for="update_price">Price (optional):</label><br>
        <input type="number" id="update_price" name="price" min="0" step="0.01"><br>
        <input type="hidden" name="action" value="update_product">
        <input type="submit" value="Update Product">
    </form>
    <h2>Restock Product</h2>
    <form action="" method="post">
        <label for="product_id">Product ID:</label><br>
        <input type="number" id="product_id" name="product_id" required><br>
        <label for="quantity">Quantity:</label><br>
        <input type="number" id="quantity" name="quantity" min="0" required><br><br>
        <input type="hidden" name="action" value="restock_product">
        <input type="submit" value="Restock Product">
    </form>

    <h2>Delete Product</h2>
    <form action="" method="post">
        <label for="product_id">Product ID:</label><br>
        <input type="number" id="product_id" name="product_id" required><br><br>
        <input type="hidden" name="action" value="delete_product">
        <input type="submit" value="Delete Product">
    </form>
    <?php
    // Display products
    displayProducts($conn);

    mysqli_close($conn);
    ?>
</body>
</html>
