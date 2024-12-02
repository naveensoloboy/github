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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['staffid'])) {
        echo "You must be logged in to submit.";
        exit;
    }

    $staffid = $_SESSION['staffid']; // Retrieve logged-in staff ID
    $module_name = "Student Scholarship";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 3; // Maximum allowed submissions

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM scholarship_records WHERE staffid = '$staffid'";
    $submissionResult = $conn->query($submissionCheckSQL);

    if (!$submissionResult) {
        echo "Error checking submission count: " . $conn->error;
        exit;
    }

    $submissionData = $submissionResult->fetch_assoc();
    $current_submission_count = $submissionData['submission_count'];

    if ($current_submission_count >= $max_submissions) {
        echo "Maximum submission limit of $max_submissions reached.";
        exit;
    }

    // Role-to-mark mapping
    $level_marks_map = [
        "Excellent" => 5,
        "Very Good" => 5,
        "Good" => 4,
        "Fair" => 3,
        "Satisfactory" => 2,
        "Not Satisfactory" => 0
    ];

    // Fetch form data
    $scholarship_names = $_POST['scholarship_names'];
    $student_names = $_POST['student_names'];
    $dates = $_POST['dates'];
    $source = $_POST['source'];
    $level_involvements = $_POST['level_involvement'];
    $involvements = $_POST['involvements'];
    $amounts = $_POST['amounts'];

    // File upload directory
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Process each scholarship entry
    for ($i = 0; $i < count($scholarship_names); $i++) {
        $scholarship_name = $conn->real_escape_string($scholarship_names[$i]);
        $student_name = $conn->real_escape_string($student_names[$i]);
        $date = $conn->real_escape_string($dates[$i]);
        $source = $conn->real_escape_string($source[$i]);
        $level_involvement = $conn->real_escape_string($level_involvements[$i]);
        $involvement = $conn->real_escape_string($involvements[$i]);
        $amount = $conn->real_escape_string($amounts[$i]);

        $role_mark = isset($level_marks_map[$level_involvement]) ? $level_marks_map[$level_involvement] : 0;
        $total_marks = $role_mark + $submit_mark;

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['proofs']['name'][$i]) && $_FILES['proofs']['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['proofs']['name'][$i]);
            $filePath = $uploadDir . uniqid() . "_" . $fileName;
            if (!move_uploaded_file($_FILES['proofs']['tmp_name'][$i], $filePath)) {
                echo "Error uploading file for entry " . ($i + 1);
                continue;
            }
        }

        // Insert data into scholarship_records table
        $scholarshipSQL = "INSERT INTO scholarship_records (staffid, scholarship_name, student_name, date,source, level_involvement, involvement, amount, proof_path)
                           VALUES ('$staffid', '$scholarship_name', '$student_name', '$date','$source', '$level_involvement', '$involvement', '$amount', '$filePath')";

        if (!$conn->query($scholarshipSQL)) {
            echo "Error inserting data into scholarship_records: " . $conn->error;
            continue;
        }

        // Update or insert into marks table
        $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                     VALUES ('$staffid', '$module_name', 1, $submit_mark, $role_mark, $total_marks)
                     ON DUPLICATE KEY UPDATE 
                     submit_count = submit_count + 1, 
                     submit_mark = submit_mark + $submit_mark, 
                     role_mark = role_mark + $role_mark, 
                     total_marks = total_marks + $total_marks";

        if (!$conn->query($marksSQL)) {
            echo "Error updating marks: " . $conn->error;
            continue;
        }
    }

    echo "<script>alert('Data has been successfully stored!');</script>";
}

// Fetch scholarship records
$records = [];
if (isset($_SESSION['staffid'])) {
    $staffid = $_SESSION['staffid'];
    $fetchSQL = "SELECT * FROM scholarship_records WHERE staffid = '$staffid'";
    $result = $conn->query($fetchSQL);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
}
// Close the connection
$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Form</title>
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #add8e6; 
            margin: 20px;
        }

        /* Form container */
        form {
            background-color: #fff5f5;
            padding: 20px;
            max-width: 850px;
            margin: 0 auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Form labels */
        label {
            font-weight: bold;
            color: #333;
        }

        /* Flexbox layout for two fields per line */
        .form-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .form-group > div {
            flex: 0 0 48%; /* Each field takes up 48% of the row */
        }

        /* Input fields and select */
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 3px solid gray;
            border-radius: 4px;
            font-size: 16px;
            background-color: #f9f9f9;
        }

        /* File input styling */
        input[type="file"] {
            padding: 5px;
            border: none;
            background-color: transparent;
        }

        /* Buttons */
        button,
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        button:hover,
        input[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Hidden by default */
        #addMoreScholarship {
            display: none;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 600px) {
            .form-group > div {
                flex: 0 0 100%;
            }

            button,
            input[type="submit"] {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        .inline-fields {
    display: flex;
    gap: 10px; /* Adjust spacing as needed */
    align-items: center;
}

.inline-fields div {
    flex: 1; /* Equal width for each field */
}
 /* Table styling */
 table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #f9f9f9;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
        }

        thead {
            background-color: #007bff;
            color: white;
        }

        th, td {
            padding: 10px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #d1ecf1;
        }

        a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
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
    <form method="POST" enctype="multipart/form-data">
        <center><h1>Student's Scholarship</h1>
            <p>(The data related to your own involvement entered to getting this scholarship, apart from regular scholarships such as BC, MBC, SC/ST)</p>
        </center><br>

        <div id="scholarships_container">
            <div class="scholarship_entry">
                <div class="form-group">
                    <div>
                        <label for="scholarship_name_1">Name of the Scholarship:</label>
                        <input type="text" id="scholarship_name_1" name="scholarship_names[]" required>
                    </div>
                    <div>
                        <label for="student_name_1">Name of the Rollno:</label>
                        <input type="text" id="student_name_1" name="student_names[]" required>
                    </div><br>
                    <div><br>
                        <label for="date_1">Date:</label>
                        <input type="date" id="date_1" name="dates[]" required>
                    </div>
                </div>

                <!-- Inline Fields for Date, Level of Involvement, and Involvement -->
                <div class="form-group inline-fields">
                    <div>
                        <label for="source">Source of Scholarship:</label>
                        <input type="text" id="source" name="source[]" required>
                    </div>
                    <div>
                        <label for="level_involvement_1">Level of Involvement:</label>
                        <select id="level_involvement_1" name="level_involvement[]" required>
                            <option value="">--Select Level--</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Very Good">Very Good</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Satisfactory">Satisfactory</option>
                            <option value="Not Satisfactory">Not Satisfactory</option>
                        </select>
                    </div>
                    <div>
                        <label for="involvement_1">Involvement:</label>
                        <input type="text" id="involvement_1" name="involvements[]" required>
                    </div>
                </div>

                <!-- Fields for Amount and File Upload -->
                <div class="form-group">
                    <div>
                        <label for="amount_1">Amount:</label>
                        <input type="text" id="amount_1" name="amounts[]" required>
                    </div>
                    <div>
                        <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                        <input type="file" id="file-upload" name="proofs[]" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">
                    </div>
                </div>
            </div>
        </div>

        <input type="submit" value="Save">
        <a href="optional.html">Back</a>
    </form>

    <h2>Your Scholarship Records</h2>
    <?php if (count($records) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Scholarship Name</th>
                    <th>Student Rollno</th>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Level of Involvement</th>
                    <th>Involvement</th>
                    <th>Amount</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $index => $record): ?>
                    <tr>
                        <td><?= $index + 1; ?></td>
                        <td><?= htmlspecialchars($record['scholarship_name']); ?></td>
                        <td><?= htmlspecialchars($record['student_name']); ?></td>
                        <td><?= htmlspecialchars($record['date']); ?></td>
                        <td><?= htmlspecialchars($record['source']); ?></td>
                        <td><?= htmlspecialchars($record['level_involvement']); ?></td>
                        <td><?= htmlspecialchars($record['involvement']); ?></td>
                        <td><?= htmlspecialchars($record['amount']); ?></td>
                        <td>
                            <?php if (!empty($record['proof_path'])): ?>
                                <a href="<?= htmlspecialchars($record['proof_path']); ?>" target="_blank">View Proof</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No scholarship records found.</p>
    <?php endif; ?>
</body>


</html>


