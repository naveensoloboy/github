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
    $module_name = "CMS Modules";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 10; // Maximum allowed submissions

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM cms_module_submissions WHERE staffid = '$staffid'";
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

    // Fetch form data
    $module_names = $_POST['module_names'] ?? [];
    $dates = $_POST['date'] ?? []; // Ensure $date is an array
    $times_used = $_POST['times_used'] ?? [];
    $specific_outcomes = $_POST['specific_outcome'] ?? [];

    // Validate inputs
    if (!is_array($module_names) || !is_array($times_used) || !is_array($specific_outcomes) || !is_array($dates)) {
        echo "Invalid input.";
        exit;
    }

    // File upload directory
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Process each module entry
    for ($i = 0; $i < count($module_names); $i++) {
        $module_name_entry = $conn->real_escape_string($module_names[$i]); // Use a new variable
        $time_used = intval($conn->real_escape_string($times_used[$i]));
        $date = $conn->real_escape_string($dates[$i]); // Use $dates array
        $specific_outcome = $conn->real_escape_string($specific_outcomes[$i]);

        $role_mark = $time_used * 10; // 10 marks per hour
        $total_marks = $submit_mark + $role_mark;

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

        // Insert data into cms_module_submissions table
        $moduleSQL = "INSERT INTO cms_module_submissions (staffid, module_names, times_used, date, specific_outcome, proof_path)
                      VALUES ('$staffid', '$module_name_entry', '$time_used', '$date', '$specific_outcome', '$filePath')";

        if (!$conn->query($moduleSQL)) {
            echo "Error inserting data into cms_module_submissions: " . $conn->error;
            continue;
        }

        // Update or insert into marks table
        $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                     VALUES ('$staffid', '$module_name', 1, $submit_mark, $role_mark, $total_marks)
                     ON DUPLICATE KEY UPDATE 
                     submit_count = submit_count + 1, 
                     submit_mark = submit_mark + $submit_mark, 
                     role_mark = role_mark + $role_mark, 
                     total_marks = total_marks + $submit_mark + $role_mark";

        if (!$conn->query($marksSQL)) {
            echo "Error updating marks: " . $conn->error;
            continue;
        }
    }

    echo "<script>alert('CMS Module details successfully submitted!');</script>";
}
// Fetch the existing submissions to display in the table
$fetchSQL = "SELECT * FROM cms_module_submissions WHERE staffid = '{$_SESSION['staffid']}'";
$fetchResult = $conn->query($fetchSQL);

// Close the connection
$conn->close();
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Module Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            max-width: 600px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input, select, textarea, button {
            padding: 10px;
            font-size: 1rem;
        }
        textarea {
            resize: vertical;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            max-width: 900px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input, select, textarea, button {
            padding: 10px;
            font-size: 1rem;
        }
        textarea {
            resize: vertical;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>CMS Module Form</h1>
    <p>(Except timetable,Assignment,Quiz)</p>
    <form method="POST" enctype="multipart/form-data">
    <!-- CMS Module Name -->
    <div>
        <label for="moduleName">CMS Module Name:</label>
        <input type="text" id="moduleName" name="module_names[]" placeholder="Enter the module name" required>
    </div>
    <div>
            <label for="date">Date:</label>
            <input type="date" id="date" name="date[]" required>
    </div>
    <!-- Number of Times Used -->
    <div>
        <label for="timesUsed">Number of Times Used:</label>
        <select id="timesUsed" name="times_used[]" required>
            <option value="">--Select--</option>
            <!-- Generating options from 1 to 20 hours -->
            <?php for ($i = 1; $i <= 20; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?> hours</option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Specific Outcome -->
    <div>
        <label for="specificOutcome">Specific Outcome:</label>
        <textarea id="specificOutcome" name="specific_outcome[]" placeholder="Enter specific outcome here..." rows="4" required></textarea>
    </div>

    <!-- Proof Upload -->
    <div>
        <label for="proof">Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</label>
        <input type="file" id="proof" name="proofs[]" required>
    </div>

    <!-- Submit Button -->
    <button type="submit">Submit</button>
</form>
<h2>Your Submitted CMS Modules</h2>
    <?php if ($fetchResult->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Module Name</th>
                    <th>Date</th>
                    <th>Times Used</th>
                    <th>Specific Outcome</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $fetchResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['module_names']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['times_used']) ?> hours</td>
                        <td><?= htmlspecialchars($row['specific_outcome']) ?></td>
                        <td>
                            <?php if ($row['proof_path']): ?>
                                <a href="<?= $row['proof_path'] ?>" target="_blank">View Proof</a>
                            <?php else: ?>
                                No proof uploaded
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No submissions found.</p>
    <?php endif; ?>

</body>
</html>
