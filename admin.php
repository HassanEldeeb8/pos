<?php
session_start();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "fresh";

$mysqli = new mysqli($host, $username, $password, $database);

// Check if database connection is successful
if($mysqli->connect_errno) {
    die("Error: Failed to connect to database: " . $mysqli->connect_error);
}

// Call procedures to generate sales reports
$query = "CALL SalesByProductReport(); CALL SalesByCustomerReport();";
if($mysqli->multi_query($query)) {
    // Fetch results of the first query (SalesByProductReport)
    if ($result = $mysqli->store_result()) {
        $sales_by_product = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    
    // Move to the next result set
    $mysqli->next_result();
    
// Fetch results of the second query (SalesByCustomerReport)
if ($mysqli->more_results()) {
    $mysqli->next_result();
    if ($result = $mysqli->store_result()) {
        $sales_by_customer = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
}
}
 else {
    echo "Error: Unable to retrieve sales reports from database.";
    exit; // Exit the script if there's an error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
        }
        h1, h2, h3 {
            text-align: center;
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin: 20px 0;
            text-align: center;
        }
        li {
            display: inline-block;
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : "Admin"; ?>!</h1>
        <h2>Dashboard</h2>
        <ul>
            <li><a href="product.php">Product Mangment</a></li>
            <li><a href="cashier.php">POS SYStem</a></li>
            <li><a href="coustmer.php">Coustmer Mangment</a></li>
            <li><a href="staff.php">Staff Mangment</a></li>


        </ul>
        <!-- Display sales reports here -->
        <h3>Sales by Product</h3>
        <table>
            <tr>
                <th>Product Name</th>
                <th>Total Quantity Sold</th>
                <th>Total Sales Amount</th>
            </tr>
            <?php if(isset($sales_by_product) && !empty($sales_by_product)) {
                foreach ($sales_by_product as $row) { ?>
                    <tr>
                        <td><?php echo $row['product_name']; ?></td>
                        <td><?php echo $row['total_quantity_sold']; ?></td>
                        <td><?php echo $row['total_sales_amount']; ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="3">No sales by product data available.</td></tr>
            <?php } ?>
        </table>

        <h3>Sales by Customer</h3>
        <table>
            <tr>
                <th>Customer Name</th>
                <th>Total Sales Count</th>
                <th>Total Sales Amount</th>
            </tr>
            <?php if(isset($sales_by_customer) && !empty($sales_by_customer)) {
                foreach ($sales_by_customer as $row) { ?>
                    <tr>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['total_sales_count']; ?></td>
                        <td><?php echo $row['total_sales_amount']; ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="3">No sales by customer data available.</td></tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>
