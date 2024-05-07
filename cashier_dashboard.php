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



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <!-- Include any CSS stylesheets here -->
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
        h1, h2 {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : "Cashier"; ?>!</h1>
        <h2>Dashboard</h2>
        <ul>
            <li><a href="cashier.php">Process Sale</a></li>
            <li><a href="coustmer.php">coustmer mangment</a></li>
            <!-- Add more links/buttons as needed -->
        </ul>
    </div>
</body>
</html>
