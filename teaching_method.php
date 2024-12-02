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

    $staffid = $_SESSION['staffid']; // Logged-in staff ID
    $module_name = "Teaching Methods";
    $submit_mark = 2; // Marks for each submission
    $max_submissions = 5; // Maximum allowed submissions

    // Role-to-mark mapping
    $role_marks_map = [
        "kahoot" => 5,
        "online quiz" => 5,
        "google classroom" => 5,
        "coderbyte" => 4,
        "leetcode" => 3,
        "other" => 1, // Custom role mark for "other" role
    ];

    // Check submission count for the staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM teaching_methods WHERE staffid = '$staffid'";
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

    // Form data initialization
    $departments = isset($_POST['department']) ? $_POST['department'] : [];
    $course_ids = isset($_POST['course_id']) ? $_POST['course_id'] : [];
    $course_names = isset($_POST['course_name']) ? $_POST['course_name'] : [];
    $roles = isset($_POST['mode_of_teaching']) ? $_POST['mode_of_teaching'] : [];
    $other_roles = isset($_POST['other_mode']) ? $_POST['other_mode'] : [];
    $times_used = isset($_POST['times_used']) ? $_POST['times_used'] : [];

    // Directory to store uploaded files
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
    }

    // Process each teaching method entry
    for ($i = 0; $i < count($departments); $i++) {
        // Escape form input data to prevent SQL injection
        $department = $conn->real_escape_string($departments[$i]);
        $course_id = $conn->real_escape_string($course_ids[$i]);
        $course_name = $conn->real_escape_string($course_names[$i]);
        $role = strtolower(trim($conn->real_escape_string($roles[$i]))); // Normalize role to lowercase
        $other_role = isset($other_roles[$i]) ? $conn->real_escape_string($other_roles[$i]) : null;
        $times_used_value = isset($times_used[$i]) ? intval($times_used[$i]) : 0;

        // Handle file upload
$filePath = null;
if (isset($_FILES['proof_path']['name'][$i])) {
    if ($_FILES['proof_path']['error'][$i] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['proof_path']['name'][$i]); // Get original file name
        $fileName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName); // Sanitize file name
        $filePath = $uploadDir . uniqid() . "_" . $fileName; // Generate unique file path

        // Move uploaded file to the target directory
        if (!move_uploaded_file($_FILES['proof_path']['tmp_name'][$i], $filePath)) {
            echo "Error uploading file for entry " . ($i + 1);
            continue; // Skip to the next entry
        }
    } else {
        echo "Error with file upload for entry " . ($i + 1) . ".<br>";
        continue; // Skip to the next entry
    }
} else {
    echo "No file uploaded for entry " . ($i + 1) . ".<br>";
    continue; // Skip to the next entry
}

        // Determine role marks
        $role_mark = $role_marks_map[$role] ?? 0; // Use 0 if role is not found
        $total_role_marks = $role_mark * $times_used_value; // Calculate total marks

        // Insert data into teaching_methods table
        $teachingMethodSQL = "INSERT INTO teaching_methods (staffid, department, course_id, course_name, mode_of_teaching, other_mode, times_used, proof_path)
                              VALUES ('$staffid', '$department', '$course_id', '$course_name', '$role', '$other_role', $times_used_value, '$filePath')";

        if (!$conn->query($teachingMethodSQL)) {
            echo "Error inserting data into teaching_methods: " . $conn->error;
            continue;
        }

        // Insert or update marks table
        $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                     VALUES ('$staffid', '$module_name', 1, $submit_mark, $total_role_marks, $submit_mark + $total_role_marks)
                     ON DUPLICATE KEY UPDATE 
                     submit_count = submit_count + 1, 
                     submit_mark = submit_mark + $submit_mark, 
                     role_mark = role_mark + $total_role_marks, 
                     total_marks = total_marks + $submit_mark + $total_role_marks";

        if (!$conn->query($marksSQL)) {
            echo "Error updating marks: " . $conn->error;
            continue;
        }
    }

    echo "<script>alert('Teaching methods data has been successfully stored!');</script>";
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Fetch the row to adjust marks
    $fetchSQL = "SELECT * FROM teaching_methods WHERE id = $delete_id";
    $fetchResult = $conn->query($fetchSQL);

    if ($fetchResult && $fetchResult->num_rows > 0) {
        $row = $fetchResult->fetch_assoc();

        $staffid = $row['staffid'];
        $role_mark = $row['times_used'] * (
            $role_marks_map[$row['mode_of_teaching']] ?? 1
        );

        // Delete the row
        $deleteSQL = "DELETE FROM teaching_methods WHERE id = $delete_id";
        if ($conn->query($deleteSQL)) {
            // Adjust marks in the `marks` table
            $updateMarksSQL = "UPDATE marks 
                SET 
                    submit_count = submit_count - 1, 
                    submit_mark = submit_mark - 2, 
                    role_mark = role_mark - $role_mark, 
                    total_marks = total_marks - (2 + $role_mark)
                WHERE staffid = '$staffid'";

            $conn->query($updateMarksSQL);
            echo "<script>alert('Entry deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting entry: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('Entry not found.');</script>";
    }
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


// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Fetch the row to adjust marks
    $fetchSQL = "SELECT * FROM teaching_methods WHERE id = $delete_id";
    $fetchResult = $conn->query($fetchSQL);

    if ($fetchResult && $fetchResult->num_rows > 0) {
        $row = $fetchResult->fetch_assoc();

        $staffid = $row['staffid'];
        $role_mark = $row['times_used'] * (
            $role_marks_map[$row['mode_of_teaching']] ?? 1
        );

        // Delete the row from teaching_methods
        $deleteSQL = "DELETE FROM teaching_methods WHERE id = $delete_id";
        if ($conn->query($deleteSQL)) {
            // Adjust marks in the `marks` table
            $updateMarksSQL = "UPDATE marks 
                SET 
                    submit_count = submit_count - 1, 
                    submit_mark = submit_mark - 2, 
                    role_mark = role_mark - $role_mark, 
                    total_marks = total_marks - (2 + $role_mark)
                WHERE staffid = '$staffid'";

            if ($conn->query($updateMarksSQL)) {
                echo "<script>alert('Entry deleted successfully and marks updated.');</script>";
            } else {
                echo "<script>alert('Error updating marks: " . $conn->error . "');</script>";
            }
        } else {
            echo "<script>alert('Error deleting entry: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('Entry not found.');</script>";
    }
}


// Fetch teaching methods for the logged-in staff
$gridSQL = "SELECT * FROM teaching_methods WHERE staffid = '{$_SESSION['staffid']}'";
$gridResult = $conn->query($gridSQL);



// Close the connection
$conn->close();
?>










<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Method Submission</title>
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
            max-width: 800px;
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
            flex: 0 0 48%;
        }

        /* Input fields and select */
        input[type="text"],
        input[type="number"],
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
        #addMore {
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
    </style>
    <script>
        function toggleOtherTextbox(selectElement, index) {
            const otherTextbox = document.getElementById(`other_teaching_mode_${index}`);
            const label = document.getElementById(`other_teaching_label_${index}`);
            if (selectElement.value === 'Other') {
                otherTextbox.style.display = 'block';
                label.style.display = 'block';
            } else {
                otherTextbox.style.display = 'none';
                label.style.display = 'none';
            }
        }

       
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
    <div id="courses_container">
        <h1><center>Teaching Methods</center></h1>
        <div class="course_entry">
            <div class="form-group">
                <label for="department_1">Name of the Department:</label>
                <select id="department_1" name="department[]" required>
                <option value="">--Select Department--</option>
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
                        <option value="CA FOUNDATION">CA FOUNDATION</option>
                        <option value="COMMERCE IT">COMMERCE IT</option>
                </select>
            </div>

            <div class="form-group">
                <label for="course_id_1">Course Code:</label>
                <input type="text" id="course_id_1" name="course_id[]" required>
            </div>

            <div class="form-group">
                <label for="course_name_1">Course Name:</label>
                <input type="text" id="course_name_1" name="course_name[]" required>
            </div>

            <div class="form-group">
                <label for="mode_1">Mode of Teaching:</label>
                <select id="mode_1" name="mode_of_teaching[]" onchange="toggleOtherTextbox(this, 1)" required>
                <option value="">--Select Mode--</option>
                        <option value="Kahoot">Kahoot</option>
                        <option value="Google Classroom">Google Classroom</option>
                        <option value="Online Quiz">Online Quiz</option>
                        <option value="Coderbyte">Coderbyte</option>
                        <option value="HackerRank">HackerRank</option>
                        <option value="LeetCode">LeetCode</option>
                        <option value="Other">Others</option>
                </select>
                <label id="other_teaching_label_1" for="other_teaching_mode_1" style="display: none;">Please specify:</label>
                <input type="text" id="other_teaching_mode_1" name="other_mode[]" style="display: none;">
            </div>

            <div class="form-group">
                <label for="times_used_1">Number of Times Used:</label>
                <input type="number" id="times_used_1" name="times_used[]" min="1" max="5" required>
            </div>

            <div class="form-group">
                <label for="file-upload_1">Attach Proof:(File size should be maximum 50kb,pdf,jpeg)</label>
                <input type="file" id="file-upload_1" name="proof_path[]" accept=".jpg,.jpeg,.pdf" required>
            </div>
        </div>
    </div>
    <button type="submit">Submit</button>
</form>
<?php if ($gridResult && $gridResult->num_rows > 0): ?>
    <h2><center>Teaching Methods Submitted</center></h2>
    <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr>
                <th>Department</th>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Mode of Teaching</th>
                <th>Other Mode</th>
                <th>Times Used</th>
                <th>Proof</th>
                <th>Action</th> <!-- Add a column for actions (delete) -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $gridResult->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['course_id']) ?></td>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['mode_of_teaching']) ?></td>
                    <td><?= htmlspecialchars($row['other_mode'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['times_used']) ?></td>
                    <td>
                        <?php if ($row['proof_path']): ?>
                            <a href="<?= htmlspecialchars($row['proof_path']) ?>" target="_blank">View File</a>
                        <?php else: ?>
                            No File
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Delete button -->
                        <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this entry?');">
                            <button type="button" style="background-color: red; color: white; padding: 5px 10px; border: none; border-radius: 4px;">
                                Delete
                            </button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><center>No teaching methods have been submitted yet.</center></p>
<?php endif; ?>

</body>
</html>
</html>










