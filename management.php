<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remarks Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
            padding: 10px;
            resize: vertical;
        }
        label {
            font-weight: bold;
        }
        .form-section {
            margin-bottom: 20px;
        }
        body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f4f4f4; /* Light background for contrast */
}

h2 {
    text-align: center; /* Center the heading */
    color: #333; /* Darker text for better readability */
}

.form-section {
    background-color: #ffffff; /* White background for form sections */
    border: 1px solid #ccc; /* Light gray border */
    border-radius: 5px; /* Rounded corners */
    padding: 15px; /* Padding inside the sections */
    margin-bottom: 20px; /* Space between sections */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow effect */
}

label {
    font-weight: bold; /* Bold labels */
    color: #555; /* Medium gray for label text */
}

textarea {
    width: 100%; /* Full width */
    height: 100px; /* Fixed height */
    margin-bottom: 10px; /* Space below textareas */
    padding: 10px; /* Inner padding */
    border: 1px solid #ccc; /* Border styling */
    border-radius: 4px; /* Rounded corners */
    resize: vertical; /* Allow vertical resizing */
}

input[type="file"] {
    margin-top: 5px; /* Space above file input */
    padding: 5px; /* Inner padding */
}

button {
    background-color: #28a745; /* Bootstrap success color */
    color: white; /* White text */
    padding: 10px 15px; /* Button padding */
    border: none; /* Remove default border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    font-size: 16px; /* Font size */
    transition: background-color 0.3s; /* Smooth transition */
}

button:hover {
    background-color: #218838; /* Darker green on hover */
}

    </style>
</head>
<body>

<h2>Contribution to the Management</h2>

<form method="POST" enctype="multipart/form-data">

    <div class="form-section">
        <label for="secretary_remarks">Remarks given by the Secretary/Correspondent:</label>
        <textarea id="secretary_remarks" name="secretary_remarks" required></textarea>
    </div>

    <div class="form-section">
        <label for="principal_remarks">Remarks given by the Principal:</label>
        <textarea id="principal_remarks" name="principal_remarks" required></textarea>
    </div>

    <div class="form-section">
        <label for="dean_admin_remarks">Remarks given by the Dean_Admin:</label>
        <textarea id="dean_admin_remarks" name="dean_admin_remarks" required></textarea>
    </div>

    <div class="form-section">
    <label for="dean_pdf">Remarks given by the Dean_PDF:</label>
    <textarea id="dean_pdf" name="dean_pdf" required></textarea>
</div>

    <button type="submit">Submit Remarks</button>

</form>

</body>
</html>

<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "junior_project";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form data is posted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $secretary_remarks = $conn->real_escape_string($_POST['secretary_remarks']);
    $principal_remarks = $conn->real_escape_string($_POST['principal_remarks']);
    $dean_admin_remarks = $conn->real_escape_string($_POST['dean_admin_remarks']);
    $dean_pdf = $conn->real_escape_string($_POST['dean_pdf']);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO remarks (secretary_remarks, principal_remarks, dean_admin_remarks, dean_pdf) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $secretary_remarks, $principal_remarks, $dean_admin_remarks, $dean_pdf);

    if ($stmt->execute()) {
        echo "<script>Remarks submitted successfully.</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
