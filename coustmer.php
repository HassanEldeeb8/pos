<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Enter your MySQL password here
$database = "fresh";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Function to add a new customer
function addCustomer($name, $email, $phone, $conn) {
    $sql = "CALL AddCustomer(?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $phone);

    if (mysqli_stmt_execute($stmt)) {
        echo "New customer added successfully";
    } else {
        echo "Error adding customer: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

function updateCustomerDetails($customerId, $newName, $newEmail, $newPhoneNumber, $conn) {
    $sql = "CALL UpdateCustomerDetails(?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $customerId, $newName, $newEmail, $newPhoneNumber);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        echo $row['Message'];
    } else {
        echo "Error updating customer details: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Function to display all existing customers
function displayCustomers($conn) {
    $sql = "SELECT * FROM Customers";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "<h2>Existing Customers</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Customer ID</th><th>Name</th><th>Email</th><th>Phone Number</th><th>Points</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr><td>" . $row["customer_id"] . "</td><td>" . $row["full_name"] . "</td><td>" . $row["email"] . "</td><td>" . $row["phone_number"] . "</td><td>" . $row["points"] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No customers found";
    }
}

// Process incoming requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    if ($_POST["action"] == "add_customer") {
        $name = $_POST["name"];
        $email = $_POST["email"];
        $phone = $_POST["phone"];
        if (!empty($name) && !empty($email) && !empty($phone)) {
            addCustomer($name, $email, $phone, $conn);
        } else {
            echo "Please provide name, email, and phone";
        }
    } elseif ($_POST["action"] == "update_customer_details") {
        $customerId = $_POST["customer_id"];
        $newName = $_POST["new_name"];
        $newEmail = $_POST["new_email"];
        $newPhoneNumber = $_POST["new_phone_number"];
        updateCustomerDetails($customerId, $newName, $newEmail, $newPhoneNumber, $conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
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
        input[type="text"], input[type="email"] {
            width: calc(50% - 10px);
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="email"]:focus {
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
            margin-top: 20px;
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
    <h2>Add New Customer</h2>
    <form action="" method="post">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br>
        <label for="phone">Phone:</label><br>
        <input type="text" id="phone" name="phone" required><br><br>
        <input type="hidden" name="action" value="add_customer">
        <input type="submit" value="Add Customer">
    </form>

    <h2>Update Customer Details</h2>
    <form action="" method="post">
        <label for="customer_id">Customer ID:</label><br>
        <input type="number" id="customer_id" name="customer_id" required><br>
        <label for="new_name">New Name:</label><br>
        <input type="text" id="new_name" name="new_name"><br>
        <label for="new_email">New Email:</label><br>
        <input type="email" id="new_email" name="new_email"><br>
        <label for="new_phone_number">New Phone Number:</label><br>
        <input type="text" id="new_phone_number" name="new_phone_number"><br><br>
        <input type="hidden" name="action" value="update_customer_details">
        <input type="submit" value="Update Customer Details">
    </form>

    <!-- Display existing customers -->
    <?php
    displayCustomers($conn);
    mysqli_close($conn);

    ?>
</body>
</html>
