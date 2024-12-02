<?php
session_start();
if (!isset($_SESSION['staffid'])) {
    header("Location: staff_login.html"); // Redirect if not logged in
}
$staffid = $_SESSION['staffid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($staffid); ?>!</h1>
    <a href=".html">Submit Marks</a>
    <a href="logout.php">Logout</a>
</body>
</html>
