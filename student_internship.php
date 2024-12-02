<?php
session_start();

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "junior_project";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['staffid'])) {
        echo "You must be logged in to submit.";
        exit;
    }

    $staffid = $_SESSION['staffid'];
    $module_name = "Student Internship";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 10; // Maximum allowed submissions

    // Role-to-mark mapping
    $duration_marks_map = [
        "6 month" => 10,
        "4 month" => 8,
        "2 month" => 5,
        "1 week" => 3,
        "2 week" => 3,
        "3 week" => 3
    ];

    // Check current submission count
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM student_internships WHERE staffid = '$staffid'";
    $submissionResult = $conn->query($submissionCheckSQL);

    if (!$submissionResult) {
        die("Error executing submission count query: " . $conn->error);
    }

    $submissionData = $submissionResult->fetch_assoc();
    $current_submission_count = $submissionData['submission_count'] ?? 0;

    if ($current_submission_count >= $max_submissions) {
        echo "Maximum submission limit of $max_submissions reached.";
        exit;
    }

    // Get form data
    $student_name = $conn->real_escape_string($_POST['studentName']);
    $class = $conn->real_escape_string($_POST['class']);
    $company_name = $conn->real_escape_string($_POST['companyName']);
    $companytype = $conn->real_escape_string($_POST['companytype']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $marks = $duration_marks_map[$duration] ?? 0;

    // File upload
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = null;
    if (isset($_FILES['fileUpload']['name']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['fileUpload']['name']);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'], $filePath)) {
            echo "Error uploading file.";
            exit;
        }
    }

    // Insert into student_internships
    $internshipSQL = "INSERT INTO student_internships (staffid, student_name, class, company_name, companytype, duration, proof_path)
                      VALUES ('$staffid', '$student_name', '$class', '$company_name', '$companytype', '$duration', '$filePath')";

    if (!$conn->query($internshipSQL)) {
        die("Error inserting data into student_internships: " . $conn->error);
    }

    // Update or insert into marks table
    $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                 VALUES ('$staffid', '$module_name', 1, $submit_mark, $marks, $submit_mark + $marks)
                 ON DUPLICATE KEY UPDATE 
                 submit_count = submit_count + 1, 
                 submit_mark = submit_mark + $submit_mark, 
                 role_mark = role_mark + $marks, 
                 total_marks = total_marks + $submit_mark + $marks";

    if (!$conn->query($marksSQL)) {
        die("Error updating marks: " . $conn->error);
    }

    echo "<script>alert('Internship details successfully submitted!');</script>";
}

// Fetch data from student_internships table
$studentInternshipSQL = "SELECT student_name, class, company_name, companytype, duration, proof_path FROM student_internships WHERE staffid = '{$_SESSION['staffid']}'";
$internshipResults = $conn->query($studentInternshipSQL);

$conn->close();
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Form</title>
    <link rel="stylesheet" href="styles.css">
    <style>/* styles.css */

/* Reset some default styles */
body, h2, form, div {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Set a font for the entire body */
body {
    font-family: Arial, sans-serif;
    background-color: #add8e6; 
    color: #333;
}

/* Center the title */
h1 {
    text-align: center;
    margin-bottom: 20px;
}

/* Style the form */
form {
    max-width: 800px;
    margin: 0 auto;
    background: #fff5f5;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Style for inline groups */
.inline-group {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

/* Style for individual fields */
.field {
    flex: 1;
    margin-right: 10px;
}

.field:last-child {
    margin-right: 0; /* Remove right margin from the last item */
}

/* Labels */
label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

/* Inputs and select elements */
input[type="text"],
select,
input[type="file"] {
    width: 80%;
    padding: 8px;
    border: 3px solid gray;
    border-radius: 4px;
    font-size: 14px;
}

/* Buttons */
button {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #0056b3;
}

/* Add More button styling */
#addMoreBtn {
    display: none; /* Initially hidden */
    background-color: #28a745; /* Green color */
}

#addMoreBtn:hover {
    background-color: #218838; /* Darker green on hover */
}
/* Grid View Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #f9f9f9; /* Light background */
    box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

thead {
    background-color: #007bff; /* Header background color */
    color: white; /* Header text color */
}

th, td {
    padding: 10px 15px;
    text-align: left;
    border: 1px solid #ddd; /* Light border between cells */
    font-size: 14px;
}

th {
    text-transform: uppercase;
    font-weight: bold;
}

tr:nth-child(even) {
    background-color: #f2f2f2; /* Alternate row color */
}

tr:hover {
    background-color: #d1ecf1; /* Row hover effect */
}

a {
    color: #007bff; /* Link color */
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

td:last-child a {
    color: #28a745; /* Green for the proof link */
}


</style>
<script>
document.getElementById('file-upload').addEventListener('change', function(event) {
    const maxFileSize = 150 * 1024; // 150 KB in bytes
    const allowedExtensions = /(\.jpg|\.jpeg|\.pdf)$/i;
    const files = event.target.files;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // Check file size
        if (file.size > maxFileSize) {
            alert('File size must be 150 KB or less.');
            event.target.value = ''; // Clear the file input
            return;
        }

        // Check file type
        if (!allowedExtensions.exec(file.name)) {
            alert('Only JPG and PDF files are allowed.');
            event.target.value = ''; // Clear the file input
            return;
        }
    }
});
</script>
</head>
<body>

    <h1>STUDENT INTERNSHIP</h1>
    <form id="internshipForm" action="#" method="POST" enctype="multipart/form-data">

        <!-- Name of the Student and Class in Same Line -->
        <div class="inline-group">
            <div class="field">
                <label for="studentName">Name of the Rollno:</label>
                <input type="text" id="studentName" name="studentName" required>
            </div><br>

            <div class="field">
                <label for="class">Class:</label>
                <input type="text" id="class" name="class" required>
            </div><br>
        </div>

        <!-- Company Name, Internship Duration, and Upload Proof in Same Line -->
        <div class="inline-group">
            <div class="field">
                <label for="companyName">Name of the Company</label>
                <input type="text" id="companyName" name="companyName" required>
            </div><br>
            <div>
            <label for="companyType">Type of the Company</label>
                <select id="companyType" name="companytype">
                <option value="" disabled selected>Select a type</option>
                <option value="private">Private</option>
                <option value="public">Public</option>
                <option value="mnc">MNC</option>
            </select>
            </div>

            <div class="field">
            <label for="duration">Internship Duration (in months/weeks):</label>
            <select id="duration" name="duration" required>
                <option value="">--Select--</option>
                <option value="6 month">6 month</option>
                <option value="4 month">4 month</option>
                <option value="2 month">2 month</option>
                <option value="1 week">1 week</option>
                <option value="2 week">2 week</option>
                <option value="3 week">3 week</option>
            </select>
        </div>

        </div>

        <div class="field">
                 <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                <input type="file" id="file-upload" name="fileUpload" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">

            
        </div><br>

        <!-- Submit Button -->
        <button type="submit" onclick="checkForm()">Submit</button>


        
        <a href="optional.html">Back</a>

    </form>

    <h2>Internship Submissions</h2>
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-top: 20px;">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Class</th>
                <th>Company Name</th>
                <th>Company Type</th>
                <th>Duration</th>
                <th>Proof</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($internshipResults && $internshipResults->num_rows > 0): ?>
                <?php while ($row = $internshipResults->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['class']); ?></td>
                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['companytype']); ?></td>
                        <td><?php echo htmlspecialchars($row['duration']); ?></td>
                        <td><a href="<?php echo htmlspecialchars($row['proof_path']); ?>" target="_blank">View Proof</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No submissions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>



