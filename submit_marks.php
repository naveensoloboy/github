<?php
session_start();
include('db.php'); // Database connection

if (!isset($_SESSION['staffid'])) {
    header("Location: staff_login.html");
    exit();
}

$staffid = $_SESSION['staffid'];

// Fetch user's current submissions
$query = "SELECT * FROM marks WHERE user_id = '$staffid'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if ($data) {
    if ($data['submission_count'] >= 2) {
        echo "You have reached the maximum submission limit.";
        exit();
    } else {
        // Update submission count and total marks
        $new_count = $data['submission_count'] + 1;
        $new_marks = $data['total_marks'] + 5;

        $update_query = "UPDATE marks SET submission_count = $new_count, total_marks = $new_marks WHERE user_id = '$staffid'";
        mysqli_query($conn, $update_query);
        echo "Successfully submitted. Your total marks: $new_marks";
    }
} else {
    // Insert new record for the user
    $insert_query = "INSERT INTO marks (user_id, submission_count, total_marks) VALUES ('$staffid', 1, 5)";
    mysqli_query($conn, $insert_query);
    echo "Successfully submitted. Your total marks: 5";
}
?>
