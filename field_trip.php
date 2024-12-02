<?php
session_start();

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "junior_project";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['staffid'])) {
        echo "You must be logged in to submit.";
        exit;
    }

    $staffid = $_SESSION['staffid']; // Retrieve logged-in staff ID
    $module_name = "Industrial/Field Visit";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 5;

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM field_visits WHERE staffid = '$staffid'";
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

    $company_names = $_POST['company_name'];
    $roles = $_POST['role'];
    $total_students = $_POST['total_students'];
    $date_froms = $_POST['date_from'];
    $date_tos = $_POST['date_to'];
    $student_outcomes = $_POST['student_outcome'];

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $role_marks_map = [
        "organizer" => 5,
        "member" => 4,
        "visitor" => 3,
    ];

    for ($i = 0; $i < count($company_names); $i++) {
        $company_name = $conn->real_escape_string($company_names[$i]);
        $role = $conn->real_escape_string($roles[$i]);
        $total_students_count = intval($total_students[$i]);
        $date_from = $conn->real_escape_string($date_froms[$i]);
        $date_to = $conn->real_escape_string($date_tos[$i]);
        $student_outcome = $conn->real_escape_string($student_outcomes[$i]);

        $role_mark = isset($role_marks_map[$role]) ? $role_marks_map[$role] : 0;
        $total_mark = $role_mark + $submit_mark;

        // Handle file upload
        if (isset($_FILES['proof_path']['name'][$i]) && $_FILES['proof_path']['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['proof_path']['name'][$i]);
            $filePath = $uploadDir . uniqid() . "_" . $fileName;
            move_uploaded_file($_FILES['proof_path']['tmp_name'][$i], $filePath);
        } else {
            $filePath = null;
        }

        // Insert data into field_visits table
        $visitSQL = "INSERT INTO field_visits (staffid, company_name, role, total_students, date_from, date_to, student_outcome, proof_path)
                     VALUES ('$staffid', '$company_name', '$role', $total_students_count, '$date_from', '$date_to', '$student_outcome', '$filePath')";

        if (!$conn->query($visitSQL)) {
            echo "Error inserting data into field_visits: " . $conn->error;
            continue;
        }

        // Update or insert into marks table
        $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                     VALUES ('$staffid', '$module_name', 1, $submit_mark, $role_mark, $total_mark)
                     ON DUPLICATE KEY UPDATE 
                     submit_count = submit_count + 1, 
                     role_mark = role_mark + $role_mark, 
                     total_marks = total_marks + $total_mark";

        if (!$conn->query($marksSQL)) {
            echo "Error updating marks: " . $conn->error;
        }
    }

    echo "<script>alert('Data has been successfully stored!');</script>";
}

// Handle delete request
if (isset($_GET['delete_id']) && isset($_SESSION['staffid'])) {
    $delete_id = intval($_GET['delete_id']);
    $staffid = $_SESSION['staffid'];

    // Get the role and mark details of the record to be deleted
    $getDetailsSQL = "SELECT role FROM field_visits WHERE id = $delete_id AND staffid = '$staffid'";
    $detailsResult = $conn->query($getDetailsSQL);

    if ($detailsResult && $detailsResult->num_rows > 0) {
        $details = $detailsResult->fetch_assoc();
        $role = $details['role'];

        // Map role to mark
        $role_marks_map = [
            "organizer" => 5,
            "member" => 4,
            "visitor" => 3,
        ];

        $role_mark = $role_marks_map[$role] ?? 0;

        // Delete the record from field_visits table
        $deleteSQL = "DELETE FROM field_visits WHERE id = $delete_id AND staffid = '$staffid'";
        if ($conn->query($deleteSQL)) {
            // Update marks table
            $updateMarksSQL = "UPDATE marks 
                               SET submit_count = submit_count - 1, 
                                   role_mark = role_mark - $role_mark, 
                                   total_marks = total_marks - ($role_mark + 2)
                               WHERE staffid = '$staffid' AND module_name = 'Industrial/Field Visit'";
            $conn->query($updateMarksSQL);

            echo "<script>alert('Record deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting record: " . $conn->error . "');</script>";
        }
    }
}

// Fetch data for grid view
$gridSQL = "SELECT * FROM field_visits ORDER BY id DESC";
$gridResult = $conn->query($gridSQL);

$conn->close();
?>







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Field Visit Submission</title>
    <style>
        /* General Body Styling */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #add8e6;
        }

        form {
            background-color:  #fff5f5;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 850px; /* Wider form to fit two fields per row */
            margin: auto;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .field_container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 0px;
        }

        .field_container > div {
            flex: 0 48%; /* Each field takes 48% width to fit two fields in one row */
            display: flex;
            flex-direction: column;
            margin-bottom: 0px;
        }

        .combined_fields_container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            width: 100%;
            margin-bottom: 0px;
        }

        .combined_fields_container > div {
            flex: 0 32%; /* Each field takes 32% width to fit three fields in one row */
            display: flex;
            flex-direction: column;
            margin-bottom: 0px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        textarea {
            width: 90%;
            padding: 10px;
            margin-bottom: 10px;
            border: 3px solid gray;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold; /* This makes the text bold */
}


        textarea {
            resize: vertical;
        }

        button,
        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover,
        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        button[type="button"] {
            background-color: #28a745;
        }

        button[type="button"]:hover {
            background-color: #218838;
        }

        .add_more_btn {
            margin-top: 10px;
            padding: 6px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: none; /* Initially hidden */
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #add8e6;
        }

        form, table {
            margin: 20px auto;
            padding: 20px;
            background-color: #fff5f5;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 850px;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        button, input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover, input[type="submit"]:hover {
            background-color: #0056b3;
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
    <h1><center>Industrial/Field Visit/Others</center></h1>
    <div>
        <label for="company_name">Company Name:</label>
        <input type="text" name="company_name[]" required>
    </div>
    <div>
        <label for="role">Role:</label>
        <select name="role[]" required>
            <option value="">--select role--</option>
            <option value="organizer">As a Organizer</option>
            <option value="member">As a Member</option>
            <option value="visitor">As a Visitor</option>
        </select>
    </div>
    <div>
        <label for="total_students">Total Students:</label>
        <input type="number" name="total_students[]" required>
    </div>
    <div>
        <label for="date_from">Date From:</label>
        <input type="date" name="date_from[]" required>
    </div>
    <div>
        <label for="date_to">Date To:</label>
        <input type="date" name="date_to[]" required>
    </div>
    <div>
        <label for="student_outcome">Student Outcome:</label>
        <textarea name="student_outcome[]" required></textarea>
    </div>
    <div>
        <label for="proof_path">Attach Proof:(File size should be maximum 50kb,pdf,jpeg):</label>
        <input type="file" name="proof_path[]" accept=".jpg,.jpeg,.pdf" required>
    </div>
    
    <button type="submit">Submit</button>
</form>
<h2>Field Visit Records</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Company Name</th>
        <th>Role</th>
        <th>Total Students</th>
        <th>Date From</th>
        <th>Date To</th>
        <th>Student Outcome</th>
        <th>Proof</th>
        <th>Action</th>
    </tr>
    <?php if ($gridResult && $gridResult->num_rows > 0): ?>
        <?php while ($row = $gridResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= $row['total_students'] ?></td>
                <td><?= $row['date_from'] ?></td>
                <td><?= $row['date_to'] ?></td>
                <td><?= htmlspecialchars($row['student_outcome']) ?></td>
                <td>
                    <?php if ($row['proof_path']): ?>
                        <a href="<?= htmlspecialchars($row['proof_path']) ?>" target="_blank">View</a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="9">No records found.</td>
        </tr>
    <?php endif; ?>
</table>
    
</body>
</html>
