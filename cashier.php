<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Enter your MySQL password here
$database = "fresh"; // Change to your actual database name

$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to retrieve all products
function getAllProducts($conn) {
    $sql = "CALL GetAllProducts()";
    $result = mysqli_query($conn, $sql);

    if ($result) {
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
        
        // Free result set
        mysqli_free_result($result);
        
        // Advance to the next result set
        mysqli_next_result($conn);
    } else {
        echo "Error retrieving products: " . mysqli_error($conn);
    }
}

// Function to retrieve customer by customer number
function getCustomerByNumber($customerNumber, $conn) {
    $sql = "SELECT * FROM customers WHERE phone_number = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $customerNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result); // Return the fetched customer row
    } else {
        echo "No customer found";
        return null;
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}

// Function to create a new sale
function createSale($customerId, $paymentType, $conn) {
    $sql = "INSERT INTO sales (customer_id, sale_date, paymentT) VALUES (?, NOW(), ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $customerId, $paymentType);

    if (mysqli_stmt_execute($stmt)) {
        $saleId = mysqli_insert_id($conn);
        echo "New sale created successfully with ID: " . $saleId;
        return $saleId;
    } else {
        echo "Error creating sale: " . mysqli_error($conn);
        return null;
    }

    mysqli_stmt_close($stmt);
}

// Function to fetch the price of a product from the database
function getProductPrice($productId, $conn) {
    $sql = "SELECT price FROM Products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['price'];
    } else {
        return null;
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}

// Function to calculate the total amount for a sale
function calculateTotal($saleId, $conn) {
    $sql = "SELECT SUM(si.quantity_sold * p.price) AS total
            FROM SaleItems si
            INNER JOIN Products p ON si.product_id = p.product_id
            WHERE si.sale_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $saleId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    } else {
        return 0;
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}

// Function to update the quantity of a product in the database
function updateProductQuantity($productId, $quantitySold, $conn) {
    $sql = "UPDATE Products SET quantity_in_stock = quantity_in_stock - ? WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $quantitySold, $productId);

    if (mysqli_stmt_execute($stmt)) {
        // Quantity updated successfully
    } else {
        echo "Error updating product quantity: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to add a sale item
function addSaleItem($saleId, $productId, $quantitySold, $unitPrice, $conn) {
    $sql = "INSERT INTO SaleItems (sale_id, product_id, quantity_sold, unit_price) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiid", $saleId, $productId, $quantitySold, $unitPrice);

    if (mysqli_stmt_execute($stmt)) {
        echo "Sale item added successfully";
    } else {
        echo "Error adding sale item: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to get product details including price
function getProductDetails($productId, $conn) {
    $sql = "SELECT price FROM Products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['price'];
    } else {
        return null;
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    if ($_POST["action"] == "create_and_add_SaleItems") {
        // Validate customer number and sale items (you can add more validation as needed)
        $customerNumber = $_POST["phone_number"];
        
        // Get customer details using customer number
        $customer = getCustomerByNumber($customerNumber, $conn);
        if ($customer) {
            $customerId = $customer["customer_id"]; // Access "customer_id" from the fetched row

            // Get payment type
            $paymentType = $_POST["payment_type"];
            
            // Create sale
            $saleId = createSale($customerId, $paymentType, $conn);
            if ($saleId) {
                // Add sale items
                if (isset($_POST["product_id"]) && isset($_POST["quantity_sold"])) {
                    $productIds = $_POST["product_id"];
                    $quantities = $_POST["quantity_sold"];
                    $totalSaleAmount = 0; // Initialize total sale amount
                    foreach ($productIds as $key => $productId) {
                        $quantitySold = $quantities[$key];
                        $unitPrice = getProductDetails($productId, $conn);
                        if ($unitPrice !== null) {
                            $totalSaleAmount += $quantitySold * $unitPrice; // Accumulate total sale amount
                            updateProductQuantity($productId, $quantitySold, $conn);
                            addSaleItem($saleId, $productId, $quantitySold, $unitPrice, $conn);
                        } else {
                            echo "Error: Product price not found for product with ID: " . $productId;
                        }
                    }
                    // Update total sale amount
                    $sql = "UPDATE sales SET total_amount = ? WHERE sale_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "di", $totalSaleAmount, $saleId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
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
        .sale-item {
            margin-bottom: 10px;
        }
        .unit-price {
            color: #888;
            margin-left: 10px;
        }
        button[type="button"], button[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button[type="button"]:hover, button[type="submit"]:hover {
            background-color: #0056b3;
        }
        #totalAmount {
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
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
    <script>
        function addSaleItemField() {
            var container = document.getElementById("SaleItemsContainer");
            var newDiv = document.createElement("div");
            newDiv.className = "sale-item";
            newDiv.innerHTML = `
                <label for="product_id">Product ID:</label>
                <input type="text" name="product_id[]" required onchange="getProductDetails(this)">
                <span class="unit-price"></span>
                <label for="quantity_sold">Quantity Sold:</label>
                <input type="number" name="quantity_sold[]" required onchange="calculateTotal()">
                <button type="button" onclick="removeSaleItem(this)">Remove</button>
                <br><br>`;
            container.appendChild(newDiv);
            calculateTotal(); // Recalculate total after adding a new sale item
        }
        
        // Function to remove sale item fields
        function removeSaleItem(button) {
            var saleItem = button.parentNode;
            saleItem.parentNode.removeChild(saleItem);
            calculateTotal(); // Recalculate total after removing item
        }

        // Function to get product details including price using AJAX
        function getProductDetails(input) {
            var productId = input.value;
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "get_product_details.php?id=" + productId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        input.parentNode.querySelector(".unit-price").innerText = "Unit Price: $" + response.price;
                        calculateTotal();
                    } else {
                        input.parentNode.querySelector(".unit-price").innerText = "";
                    }
                }
            };
            xhr.send();
        }

        // Function to calculate total amount
        function calculateTotal() {
            var totalAmount = 0;
            var unitPrices = document.querySelectorAll(".unit-price");
            var quantities = document.getElementsByName("quantity_sold[]");
            for (var i = 0; i < unitPrices.length; i++) {
                var unitPriceText = unitPrices[i].innerText;
                var price = parseFloat(unitPriceText.replace("Unit Price: $", ""));
                var quantity = parseInt(quantities[i].value);
                totalAmount += isNaN(price) || isNaN(quantity) ? 0 : price * quantity;
            }
            document.getElementById("totalAmount").innerText = "Total Amount: $" + totalAmount.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Cashier Interface</h1>
        <h2>Create Sale and Add Sale Items</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_and_add_SaleItems">
            <label for="phone_number">Customer Number:</label>
            <input type="text" id="phone_number" name="phone_number" required>
            <br><br>
            <div id="SaleItemsContainer">
                <div class="sale-item">
                    <label for="product_id">Product ID:</label>
                    <input type="text" name="product_id[]" required onchange="getProductDetails(this)">
                    <span class="unit-price"></span>
                    <label for="quantity_sold">Quantity Sold:</label>
                    <input type="number" name="quantity_sold[]" required onchange="calculateTotal()">
                    <button type="button" onclick="removeSaleItem(this)">Remove</button>
                    <br><br>
                </div>
            </div>
            <button type="button" onclick="addSaleItemField()">Add Another Item</button>
            <br><br>
            <label for="payment_type">Payment Type:</label>
            <select id="payment_type" name="payment_type" required>
                <option value="cash">Cash</option>
                <option value="visa">Visa</option>
            </select>
            <br><br>
            <span id="totalAmount"></span>
            <br><br>
            <button type="submit">Create Sale and Add Sale Items</button>
        </form>
        <table>
            <?php getAllProducts($conn);
    mysqli_close($conn); // Close the database connection at the end of the file
    ?>
        </table>
    </div>
</body>
</html>
