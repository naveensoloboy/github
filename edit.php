<?php
session_start();

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "junior_project";

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);

    // Escape input data
    $department = $conn->real_escape_string($_POST['department']);
    $course_id = $conn->real_escape_string($_POST['course_id']);
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $mode_of_teaching = $conn->real_escape_string($_POST['mode_of_teaching']);
    $other_mode = $conn->real_escape_string($_POST['other_mode']);
    $times_used = intval($_POST['times_used']);
    $filePath = $_POST['existing_proof']; // Default to existing proof

    // Handle file upload (optional during edit)
    if (isset($_FILES['proof_path']['name']) && $_FILES['proof_path']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        $fileName = basename($_FILES['proof_path']['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        move_uploaded_file($_FILES['proof_path']['tmp_name'], $filePath);
    }

    // Update the record
    $updateSQL = "UPDATE teaching_methods 
        SET 
            department = '$department', 
            course_id = '$course_id', 
            course_name = '$course_name', 
            mode_of_teaching = '$mode_of_teaching', 
            other_mode = '$other_mode', 
            times_used = $times_used, 
            proof_path = '$filePath'
        WHERE id = $edit_id";

    if ($conn->query($updateSQL)) {
        echo "<script>alert('Entry updated successfully.');</script>";
    } else {
        echo "<script>alert('Error updating entry: " . $conn->error . "');</script>";
    }
}

// Fetch teaching methods for the logged-in staff
$gridSQL = "SELECT * FROM teaching_methods WHERE staffid = '{$_SESSION['staffid']}'";
$gridResult = $conn->query($gridSQL);

// Close the connection
$conn->close();
?>
