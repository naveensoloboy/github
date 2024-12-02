<?php
// Database connection
$servername = "localhost"; // Change to your server's name
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "student_details"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $student_name = $_POST['student_name'];
    $rollno = $_POST['rollno'];
    $ug_course = $_POST['ug_course'];
    $college_higher = $_POST['college_higher'];
    $pg_course = $_POST['pg_course'];

    // Handle file upload
    $upload_dir = "uploads/";
    $proof_file = $upload_dir . basename($_FILES["proof"]["name"]);
    $proof_file_type = strtolower(pathinfo($proof_file, PATHINFO_EXTENSION));
    $upload_ok = 1;

    
    // Allow only certain file formats
    if (!in_array($proof_file_type, ["jpg", "jpeg", "pdf"])) {
        echo "Error: Only PDF and JPG files are allowed.";
        $upload_ok = 0;
    }

    // Move the file to the uploads directory
    if ($upload_ok && move_uploaded_file($_FILES["proof"]["tmp_name"], $proof_file)) {
        // Insert data into the database
        $stmt = $conn->prepare("
            INSERT INTO student_data 
            (student_name, rollno, ug_course, college_higher, pg_course, proof_file) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $student_name, $rollno, $ug_course, $college_higher, $pg_course, $proof_file);

        if ($stmt->execute()) {
            echo "Student details submitted successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Unable to upload the file.";
    }
}

$conn->close();
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details Form</title>
    <style>
        /* General Reset and Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f8ff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }

        h2 {
            text-align: center;
            color: #4a90e2;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 2px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }

        input[type="file"] {
            font-size: 0.9em;
        }

        input[type="submit"] {
            background-color: #4a90e2;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px;
            display: block;
            width: 100%;
            text-align: center;
        }

        input[type="submit"]:hover {
            background-color: #357ABD;
        }
    </style>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        <h2>Student Details Form</h2>

        <!-- Name of the Student -->
        <div class="form-group">
            <label for="student_name">Name of the Student:</label>
            <input type="text" id="student_name" name="student_name" placeholder="Enter student name" required>
        </div>

        <!-- Roll Number -->
        <div class="form-group">
            <label for="rollno">Roll Number:</label>
            <input type="text" id="rollno" name="rollno" placeholder="Enter roll number" required>
        </div>

        <!-- Name of the UG Course -->
        <div class="form-group">
            <label for="ug_course">Name of the UG Course:</label>
            <input type="text" id="ug_course" name="ug_course" placeholder="Enter UG course name" required>
        </div>

        <!-- Name of the College of Higher Studies -->
        <div class="form-group">
            <label for="college_higher">Name of the College of Higher Studies:</label>
            <input type="text" id="college_higher" name="college_higher" placeholder="Enter college name" required>
        </div>

        <!-- Name of the PG Course -->
        <div class="form-group">
            <label for="pg_course">Name of the PG Course:</label>
            <input type="text" id="pg_course" name="pg_course" placeholder="Enter PG course name" required>
        </div>

        <!-- Upload Proof -->
        <div class="form-group">
            <label for="proof">Attach Proof (PDF/JPG, max 150KB):</label>
            <input type="file" id="proof" name="proof" accept=".pdf, .jpg, .jpeg" required>
        </div>

        <!-- Submit Button -->
        <input type="submit" value="Submit">
    </form>
</body>
</html>
