<?php
// Database connection
$servername = "localhost"; // Change to your server name
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "junior_project"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $department = implode(", ", $_POST['department']); // Handle multiple departments if selected
    $course_title = $_POST['course_title'];
    $duration = $_POST['duration'];
    $faculty_count = $_POST['faculty_count'];
    $beneficiaries = $_POST['beneficiaries'];
    $outcome = $_POST['outcome'];

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

    if ($upload_ok && move_uploaded_file($_FILES["proof"]["tmp_name"], $proof_file)) {
        // Insert data into database
        $stmt = $conn->prepare("
            INSERT INTO value_courses 
            (department, course_title, duration, faculty_count, beneficiaries, outcome, proof_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssiiiss",
            $department,
            $course_title,
            $duration,
            $faculty_count,
            $beneficiaries,
            $outcome,
            $proof_file
        );

        if ($stmt->execute()) {
            echo "Course details submitted successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: Unable to upload proof file.";
    }
}

$conn->close();
?>







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Course Form</title>
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

        select, 
        input[type="text"], 
        textarea, 
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 2px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }

        textarea {
            resize: vertical;
            height: 80px;
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
        <h2>Course Details Form</h2>

        <!-- Department Name Dropdown -->
        <div class="form-group">
            <label for="department">Name of the Department:</label>
            <select name="department[]" class="full-width">
                        <option value="">--Select Board--</option>
                        <option value="TAMIL_LANGUAGE">TAMIL LANGUAGE</option>
                        <option value="TAMIL_LITERATURE">TAMIL LITERATURE</option>
                        <option value="ENGLISH_LANGUAGE">ENGLISH LANGUAGE</option>
                        <option value="ENGLISH_LITERATURE">ENGLISH LITERATURE</option>
                        <option value="BOTANY">BOTANY</option>
                        <option value="MATHEMATICS">MATHEMATICS</option>
                        <option value="PHYSICS">PHYSICS</option>
                        <option value="CHEMISTRY">CHEMISTRY</option>
                        <option value="COMMERCE">COMMERCE</option>
                        <option value="COMMERCE_COMPUTER_APPLICATION">COMMERCE (COMPUTER APPLICATION)</option>
                        <option value="COMMERCE_PROFESSIONAL_ACCOUNTING">COMMERCE (PROFESSIONAL ACCOUNTING)</option>
                        <option value="COMMERCE_BANKING_INSURANCE">COMMERCE (BANKING INSURANCE)</option>
                        <option value="BUSINESS_ADMINISTRATION_BBA">BUSINESS ADMINISTRATION (BBA)</option>
                        <option value="COMPUTER_SCIENCE">COMPUTER SCIENCE</option>
                        <option value="BCA_COMPUTER_APPLICATION">BCA (COMPUTER APPLICATION)</option>
                        <option value="INFORMATION_TECHNOLOGY">INFORMATION TECHNOLOGY</option>
                        <option value="AI_DS">ARTIFICIAL INTELLIGENCE AND DATA SCIENCE (AI & DS)</option>
                        <option value="IOT">INTERNET OF THINGS (IOT)</option>
                        <option value="MANAGEMENT_STUDIES_MBA">MANAGEMENT STUDIES (MBA)</option>
                        <option value="PHYSICAL_EDUCATION">PHYSICAL EDUCATION</option>
                        <option value="CAREER_GUIDANCE_PLACEMENT_CELL">CAREER GUIDANCE & PLACEMENT CELL</option>

                        </select>
        </div>

        <!-- Title of the Course -->
        <div class="form-group">
            <label for="course_title">Title of the Course:</label>
            <input type="text" id="course_title" name="course_title" placeholder="Enter course title" required>
        </div>

        <!-- Duration Dropdown -->
        <div class="form-group">
            <label for="duration">Duration (in hours):</label>
            <select id="duration" name="duration" required>
                <option value="">--Select Duration--</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Hour<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Number of Faculty Handled -->
        <div class="form-group">
            <label for="faculty_count">Number of Faculty Handled:</label>
            <select id="faculty_count" name="faculty_count" required>
                <option value="">--Select Faculty Count--</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Faculty<?= $i > 1 ? 'ies' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Number of Beneficiaries -->
        <div class="form-group">
            <label for="beneficiaries">Number of Beneficiaries:</label>
            <input type="text" id="beneficiaries" name="beneficiaries" placeholder="Enter number of beneficiaries" required>
        </div>

        <!-- Specific Outcome -->
        <div class="form-group">
            <label for="outcome">Specific Outcome:</label>
            <textarea id="outcome" name="outcome" placeholder="Describe the specific outcome..." required></textarea>
        </div>

        <!-- Upload Proof -->
        <div class="form-group">
            <label for="proof">Upload Proof (PDF/JPG, max 150KB):</label>
            <input type="file" id="proof" name="proof" accept=".pdf, .jpg, .jpeg" required>
        </div>

        <!-- Submit Button -->
        <input type="submit" value="Submit">
    </form>
</body>
</html>
