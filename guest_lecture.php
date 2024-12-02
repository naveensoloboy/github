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
    $module_name = "Guest Lecture";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 3; // Maximum allowed submissions

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM guest_lectures WHERE staffid = '$staffid'";
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
    $titles = isset($_POST['lecture_title']) ? $_POST['lecture_title'] : [];
    $resource_persons = isset($_POST['name']) ? $_POST['name'] : [];
    $roles = isset($_POST['role']) ? $_POST['role'] : [];
    $other_roles = isset($_POST['other_role']) ? $_POST['other_role'] : [];
    $benefits = isset($_POST['benefit']) ? $_POST['benefit'] : [];
    $from_dates = isset($_POST['from_date']) ? $_POST['from_date'] : [];
    $to_dates = isset($_POST['to_date']) ? $_POST['to_date'] : [];
    $outcomes = isset($_POST['outcome']) ? $_POST['outcome'] : [];

    // Role-to-mark mapping
    $role_marks_map = [
        "convener" => 5,
        "organising secretary" => 5,
        "coordinator" => 4,
        "co-coordinator" => 3,
        "member" => 2,
        "other" => 1, // Custom role mark for "other" role
    ];

    // File upload directory
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);  // Create the directory if it doesn't exist
    }

    // Process each guest lecture entry
    for ($i = 0; $i < count($titles); $i++) {
        $title = $conn->real_escape_string($titles[$i]);
        $resource_person = $conn->real_escape_string($resource_persons[$i]);
        $role = $conn->real_escape_string($roles[$i]);
        $other_role = isset($other_roles[$i]) ? $conn->real_escape_string($other_roles[$i]) : null;
        $benefit = $conn->real_escape_string($benefits[$i]);
        $from_date = $conn->real_escape_string($from_dates[$i]);
        $to_date = $conn->real_escape_string($to_dates[$i]);
        $outcome = $conn->real_escape_string($outcomes[$i]);

        if ($role === 'other' && !empty($other_role)) {
            $role_marks_map[$role] = 1;
        }

        $role_mark = isset($role_marks_map[$role]) ? $role_marks_map[$role] : 0;
        $total_marks = $role_mark + $submit_mark;

        $filePath = null;
        if (isset($_FILES['upload_files']['name'][$i]) && $_FILES['upload_files']['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['upload_files']['name'][$i]);
            $filePath = $uploadDir . uniqid() . "_" . $fileName;
            if (!move_uploaded_file($_FILES['upload_files']['tmp_name'][$i], $filePath)) {
                echo "Error uploading file for entry " . ($i + 1);
                continue;
            }
        }

        $guestLectureSQL = "INSERT INTO guest_lectures (staffid, lecture_title, name, role, other_role, benefit, from_date, to_date, outcome, proof_path)
                            VALUES ('$staffid', '$title', '$resource_person', '$role', '$other_role', '$benefit', '$from_date', '$to_date', '$outcome', '$filePath')";

        if (!$conn->query($guestLectureSQL)) {
            echo "Error inserting data into guest_lectures: " . $conn->error;
            continue;
        }

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

    echo "<script>alert('Guest Lecture data has been successfully stored!');</script>";
}

// Fetch guest lectures for the logged-in staff
$guestLectures = [];
if (isset($_SESSION['staffid'])) {
    $staffid = $_SESSION['staffid'];
    $fetchSQL = "SELECT * FROM guest_lectures WHERE staffid = '$staffid'";
    $result = $conn->query($fetchSQL);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $guestLectures[] = $row;
        }
    }
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <title>Guest Lecture Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #add8e6;
            margin: 0;
            padding: 20px;
        }
        form {
            background-color: #fff5f5;
            padding: 20px;
            border-radius: 8px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="date"], textarea, select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            display: inline-block;
            border: 3px solid gray;
            border-radius: 4px;
            box-sizing: border-box;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }
        button[type="submit"], .add-more {
            width: 20%;
            background-color: #007BFF;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover, .add-more:hover {
            background-color: #0056b3;
        }
        .field-group {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .field-group div {
            flex: 1;
            margin-right: 10px;
        }
        .field-group div:last-child {
            margin-right: 0;
        }
        textarea {
            height: 100px;
            resize: none;
        }
        #custom_role {
            display: none;
        }
        /* Same styles as before */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
    <script>
        function toggleCustomRole() {
            var role = document.getElementById("role").value;
            var customRoleSection = document.getElementById("custom_role");
            if (role === "other") {
                customRoleSection.style.display = "block";
            } else {
                customRoleSection.style.display = "none";
            }
        }
    </script>
</head>
<body>

    <form method="post" enctype="multipart/form-data">
        <center><h1>Guest Lecture/Hands on Training/Organised by Faculty</h1></center>

        <div class="form-section">
            <div class="field-group">
                <div>
                    <label for="lecture_title">Title of the Guest Lecture:</label>
                    <input type="text" id="lecture_title" name="lecture_title[]" required>
                </div>
                <div>
                    <label for="name">Name of the Resource Person:</label>
                    <input type="text" id="name" name="name[]" required>
                </div>
                <div>
                    <label for="role">Role:</label>
                    <select id="role" name="role[]" onchange="toggleCustomRole()" required>
                        <option value="">--Select Role--</option>
                        <option value="convener">Convener</option>
                        <option value="organising secretary">Organizing Secretary</option>
                        <option value="coordinator">Coordinator</option>
                        <option value="co-coordinator">Co-coordinator</option>
                        <option value="member">Member</option>
                        <option value="other">Others</option>
                    </select>
                </div>
            </div>

            <div id="custom_role">
                <label for="other_role">Please specify your role:</label>
                <input type="text" id="other_role" name="other_role[]">
            </div>

            <div class="field-group">
                <div>
                    <label for="benefit">Nunmber of Beneficieres(Participants):</label>
                    <input type="text" id="benefit" name="benefit[]" required>
                </div>
                <div>
                    <label for="from_date">Period (From):</label>
                    <input type="date" id="from_date" name="from_date[]" required>
                </div>
                <div>
                    <label for="to_date">Period (To):</label>
                    <input type="date" id="to_date" name="to_date[]" required>
                </div>
            </div>

            <div class="field-group">
                <div>
                    <label for="outcome">Outcome of the Guest Lecture:</label>
                    <textarea id="outcome" name="outcome[]" required></textarea>
                </div>
                <div>
                    <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                    <input type="file" id="file-upload" name="upload_files[]" multiple accept=".jpg, .jpeg, .pdf">
                </div>
            </div>
        </div>

        <div id="buttons">
            <button type="submit">Save</button>
            <a href="optional.html">Back</a>
        </div>
    </form>
    <h2>Guest Lecture Records</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Resource Person</th>
                <th>Role</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Beneficiaries</th>
                <th>Outcome</th>
                <th>Proof</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($guestLectures)): ?>
                <?php foreach ($guestLectures as $lecture): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lecture['lecture_title']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['name']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['role']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['from_date']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['to_date']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['benefit']); ?></td>
                        <td><?php echo htmlspecialchars($lecture['outcome']); ?></td>
                        <td>
                            <?php if (!empty($lecture['proof_path'])): ?>
                                <a href="<?php echo htmlspecialchars($lecture['proof_path']); ?>" target="_blank">View Proof</a>
                            <?php else: ?>
                                No Proof
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
