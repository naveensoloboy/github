<?php
session_start();

// Database connection parameters
$host = 'localhost'; 
$user = 'root'; 
$password = ''; 
$dbname = 'mark';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in and get their ID
if (!isset($_SESSION['staffid'])) {
    echo "<script>alert('Please log in to submit.'); window.location.href='login.html';</script>";
    exit();
}

$staffid = $_SESSION['staffid'];  // Logged-in staff ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $module_name = '19.php';      // Module name for this form
    $marks_per_submission = 5;     // Fixed marks per submission

    // Check the number of submissions and total marks for this staff and module
    $stmt = $conn->prepare("SELECT count, total_marks FROM marks WHERE staffid = ? AND module_name = ?");
    $stmt->bind_param("ss", $staffid, $module_name);
    $stmt->execute();
    $stmt->bind_result($submission_count, $total_marks);
    $stmt->fetch();
    $stmt->close();

    // Initialize if no previous submission found
    if (!$submission_count) {
        $submission_count = 0;
        $total_marks = 0;
    }

    // Check if the staff can submit (max 2 times)
    if ($submission_count < 2) {
        // Increment submission count and total marks
        $submission_count++;
        $total_marks += $marks_per_submission;

        // Insert or update the record in the marks table
        $stmt = $conn->prepare(
            "INSERT INTO marks (staffid, module_name, count, marks_per_submission, total_marks) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE count = ?, total_marks = ?"
        );

        $stmt->bind_param(
            "ssiiiii", 
            $staffid, $module_name, $submission_count, $marks_per_submission, $total_marks, 
            $submission_count, $total_marks
        );

        if ($stmt->execute()) {
            echo "<script>alert('Submission successful!'); window.location.href='19.html';</script>";
        } else {
            echo "<script>alert('Error submitting marks.'); window.location.href='19.html';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('You have reached the maximum submission limit.'); window.location.href='19.html';</script>";
    }
}

$conn->close();
?>
