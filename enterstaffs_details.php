<?php
// Start session
session_start();

// Database connection
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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $academicyear = $_POST['acadamicyear'];
    $staffid = $_POST['staffid'];
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $department = $_POST['department'];
    $mobile = $_POST['mobile'];
    $email = $_POST['gmail'];
    $password = $_POST['password'];

    // Check if staffid or email already exists
    $checkQuery = "SELECT * FROM login_staff WHERE staffid = '$staffid' OR gmail = '$email'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult && $checkResult->num_rows > 0) {
        echo "Error: Staff ID or email already exists. Please use a different one.";
    } else {
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database
        $insertQuery = "INSERT INTO login_staff (acadamicyear, staffid, name, dob, gender, department, mobile, gmail, password) 
                        VALUES ('$academicyear', '$staffid', '$name', '$dob', '$gender', '$department', '$mobile', '$email', '$hashedPassword')";

        if ($conn->query($insertQuery) === TRUE) {
            echo "New record created successfully. <a href='staff_login.html'>Login here</a>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Close connection
$conn->close();
?>
