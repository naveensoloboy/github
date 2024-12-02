<?php
// Start session
session_start();

// Database connection parameters
$host = 'localhost'; // Database host
$user = 'root'; // Database username
$password = ''; // Database password
$dbname = 'junior_project'; // Database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staffid = $_POST['staffid'];
    $password = $_POST['password'];

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT password FROM login_staff WHERE staffid = ?");
    if (!$stmt) {
        // Check if the statement failed and show an error
        die("Preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $staffid);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // User exists, fetch the password
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashedPassword)) {
            // Password is correct, set session variables
            $_SESSION['staffid'] = $staffid;
            // Redirect to the dashboard or another page
            header("Location: optional.html"); // Change to your desired location
            exit();
        } else {
            // Incorrect password
            echo "<script>alert('Invalid password. Please try again.');</script>";
        }
    } else {
        // Staff ID does not exist
        echo "<script>alert('Invalid Staff ID. Please try again.');</script>";
    }

    // Close the prepared statement
    $stmt->close();
}

// Close connection
$conn->close();
?>
