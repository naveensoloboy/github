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
    $module_name = "Development Committee"; // Module name for this form
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 10;

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM development_committees WHERE staffid = '$staffid'";
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

    // Retrieve form data
    $committees = isset($_POST['committee']) ? $_POST['committee'] : []; // Array of committee names
    $roles = isset($_POST['role']) ? $_POST['role'] : []; // Array of roles
    $benefits = isset($_POST['benefit']) ? $_POST['benefit'] : []; // Array of benefits

    // Role marks mapping
    $role_marks_map = [
        "convener" => 5,
        "president" => 5,
        "secretary" => 5,
        "patron" => 4,
        "chief patron" => 4,
        "organizing secretary" => 4,
        "coordinator" => 3,
        "co-coordinator" => 3,
        "member" => 2,
    ];

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Loop through committees to handle data for each committee
    for ($i = 0; $i < count($committees); $i++) {
        $committee_name = $conn->real_escape_string($committees[$i]);
        $role = $conn->real_escape_string($roles[$i]);
        $benefit = $conn->real_escape_string($benefits[$i]);

        // Get role marks from the mapping
        $role_mark = isset($role_marks_map[$role]) ? $role_marks_map[$role] : 0;
        $total_marks = $role_mark + $submit_mark; // Total marks: role mark + submit mark

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['proof_path']['name'][$i]) && $_FILES['proof_path']['error'][$i] === UPLOAD_ERR_OK) {
            // Validate file size and type
            $fileName = basename($_FILES['proof_path']['name'][$i]);
            $fileSize = $_FILES['proof_path']['size'][$i];
            $fileTmpPath = $_FILES['proof_path']['tmp_name'][$i];

            // Maximum file size: 150KB
            if ($fileSize > 150 * 1024) {
                echo "File size must be less than 150KB.";
                continue;
            }

            $allowedExtensions = ['jpg', 'jpeg', 'pdf'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedExtensions)) {
                echo "Only JPG, JPEG, or PDF files are allowed.";
                continue;
            }

            // Generate a unique file name
            $filePath = $uploadDir . uniqid() . "_" . $fileName;
            if (!move_uploaded_file($fileTmpPath, $filePath)) {
                echo "Error uploading file: " . $_FILES['proof_path']['error'][$i];
                continue;
            }
        }

        // Insert data into development_committees table
        $committeeSQL = "INSERT INTO development_committees (staffid, committee_name, role, benefit, proof_path)
                         VALUES ('$staffid', '$committee_name', '$role', '$benefit', '$filePath')";

        if (!$conn->query($committeeSQL)) {
            echo "Error inserting data into development_committees: " . $conn->error;
            continue;
        }

        // Insert or update marks in the marks table
        $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                     VALUES ('$staffid', '$module_name', 1, $submit_mark, $role_mark, $total_marks)
                     ON DUPLICATE KEY UPDATE 
                     submit_count = submit_count + 1, 
                     role_mark = role_mark + $role_mark, 
                     total_marks = total_marks + $total_marks";

        if (!$conn->query($marksSQL)) {
            echo "Error updating marks: " . $conn->error;
        }
    }

    echo "<script>alert('Data has been successfully stored!');</script>";
}

// Fetch data to display in grid view
$gridData = [];
$gridSQL = "SELECT * FROM development_committees WHERE staffid = '" . $_SESSION['staffid'] . "'";
$gridResult = $conn->query($gridSQL);

if ($gridResult && $gridResult->num_rows > 0) {
    while ($row = $gridResult->fetch_assoc()) {
        $gridData[] = $row;
    }
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College or Department Development Committee</title>
    <style>
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .form-row > div {
            margin-right: 20px;
        }
        /* Reset some default styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #add8e6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        form {
            background-color: seashell;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
        }

        h2 {
            color: #4a90e2;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .form-row > div {
            flex: 1;
            padding: 5px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 3px solid gray;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
        }

        textarea {
            resize: vertical;
        }

        input[type="file"] {
            font-size: 0.9em;
        }

        input[type="submit"],
        button,
        a {
            background-color: #4a90e2;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            margin-top: 10px;
        }

        input[type="submit"]:hover,
        button:hover,
        a:hover {
            background-color: #357ABD;
        }

        #committee_fields_container .form-group {
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input[type="submit"],
        a {
            display: block;
            width: 20%;
            text-align: center;
        }
        /* Add styles for the grid view */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background-color: #f4f4f4;
            text-align: left;
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

    <form method="post" enctype="multipart/form-data">
        <center><h2>College or Department Development Committee</h2></center> 

        <!-- Button to add more committee fields -->
        <div class="form-row">
            <div>
                <label for="committee"><b>Name of the Committee:</b></label>
                <input type="text" id="committee" name="committee[]" required>
            </div>
    </div>
        

        <!-- Common fields: Role, Outcome, and Attach Proof -->
        <div class="form-row">
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role[]" required>
                    <option value="">--Select Role--</option>
                    <option value="convener">Convener</option>
                    <option value="president">President</option>
                    <option value="secretary">Secretary</option>
                    <option value="patron">Patron</option>
                    <option value="chief patron">Chief Patron</option>
                    <option value="organizing secretary">Organizing Secretary</option>
                    <option value="coordinator">Coordinator</option>
                    <option value="co-coordinator">Co-coordinator</option>
                    <option value="member">Member</option>
                </select>
            </div>

            <div class="form-group">
                <label for="benefit">Describe the Benefit:</label>
                <textarea id="benefit" name="benefit[]" rows="2" placeholder="Enter the benefits here..." required></textarea>
            </div>

            <div class="form-group">
                <label for="file-upload"><b>Attach Proof: (File size should be maximum 150kb, pdf, jpeg)</b></label>
                <input type="file" name="proof_path[]" accept=".jpg, .jpeg, .pdf" required>

            </div>
        </div>

        <div class="form-row">
            <div>
                <input type="submit" value="Submit">
            </div>
            <a href="optional.html">Back</a>
        </div>
    </form>
    <!-- Grid View Section -->
    <section>
        <h2>Development Committees</h2>
        <div>
            <table>
                <thead>
                    <tr>
                        <th>Committee Name</th>
                        <th>Role</th>
                        <th>Number of Beneficiaries</th>
                        <th>Proof</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gridData)): ?>
                        <tr>
                            <td colspan="4" class="center-text">No data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gridData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['committee_name']) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td><?= htmlspecialchars($row['benefit']) ?></td>
                                <td>
                                    <?php if ($row['proof_path']): ?>
                                        <a href="<?= htmlspecialchars($row['proof_path']) ?>" target="_blank">View Proof</a>
                                    <?php else: ?>
                                        No Proof
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>

</html>



