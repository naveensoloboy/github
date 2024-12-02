<?php
session_start();

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "junior_project";

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
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
    $module_name = "Project Consultancy";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 2;

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM consultancy_records WHERE staffid = '$staffid'";
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
    $agency_name = $conn->real_escape_string($_POST['agency_name']);
    $from_date = $conn->real_escape_string($_POST['from_date']);
    $to_date = $conn->real_escape_string($_POST['to_date']);
    $project_title = $conn->real_escape_string($_POST['project_title']);
    $consultancy_type = $conn->real_escape_string($_POST['consultancy_type']);
    $consultancy_nature = $conn->real_escape_string($_POST['consultancy_nature']);
    $rate_of_consultancy = $conn->real_escape_string($_POST['rate_of_consultancy']);
    $outcome = $conn->real_escape_string($_POST['outcome']);
    $relationship = $conn->real_escape_string($_POST['relationship']);

    // Define marks based on consultancy nature
    $nature_marks_map = [
        "active" => 8,
        "inactive" => 1
    ];

    $nature_mark = isset($nature_marks_map[$consultancy_nature]) ? $nature_marks_map[$consultancy_nature] : 0;
    $total_marks = $nature_mark + $submit_mark;

    // Handle file upload
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['proof']['name']);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        if (!move_uploaded_file($_FILES['proof']['tmp_name'], $filePath)) {
            echo "Error uploading the file.";
            exit;
        }
    }

    // Insert data into consultancy_records table
    $consultancySQL = "INSERT INTO consultancy_records (
        staffid, agency_name, from_date, to_date, project_title, consultancy_type, consultancy_nature, 
        rate_of_consultancy, outcome, relationship, proof_path
    ) VALUES (
        '$staffid', '$agency_name', '$from_date', '$to_date', '$project_title', '$consultancy_type', '$consultancy_nature', 
        '$rate_of_consultancy', '$outcome', '$relationship', '$filePath'
    )";

    if (!$conn->query($consultancySQL)) {
        echo "Error inserting data into consultancy_records: " . $conn->error;
        exit;
    }

    // Update or insert into marks table
    $marksSQL = "INSERT INTO marks (staffid, module_name, submit_count, submit_mark, role_mark, total_marks)
                 VALUES ('$staffid', '$module_name', 1, $submit_mark, $nature_mark, $total_marks)
                 ON DUPLICATE KEY UPDATE 
                 submit_count = submit_count + 1, 
                 submit_mark = submit_mark + $submit_mark, 
                 role_mark = role_mark + $nature_mark, 
                 total_marks = total_marks + $total_marks";

    if (!$conn->query($marksSQL)) {
        echo "Error updating marks: " . $conn->error;
        exit;
    }

    echo "<script>alert('Data has been successfully stored!');</script>";
}

// Fetch consultancy records
$records = [];
if (isset($_SESSION['staffid'])) {
    $staffid = $_SESSION['staffid'];
    $fetchSQL = "SELECT * FROM consultancy_records WHERE staffid = '$staffid'";
    $result = $conn->query($fetchSQL);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
}

?>





<!DOCTYPE html>
<html>
<head>
    <title>Consultancy Details Form</title>
    <script>
        function toggleProof() {
            var consultancyNature = document.getElementById("consultancy_nature").value;
            var proofSection = document.getElementById("proof_section");
            
            if (consultancyNature === "active") {
                proofSection.style.display = "block";
            } else {
                proofSection.style.display = "none";
            }
        }
    </script>
    <style>
        /* General body styles */
        body {
            font-family: Arial, sans-serif;
            background-color:#add8e6; 
            margin: 0;
            padding: 20px;
        }

        /* Form container styles */
        form {
            background-color:#fff5f5;
            padding: 20px;
            border-radius: 8px;
            max-width: 1000px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Form Grid Styling */
        .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        .form-group > div {
            flex: 0 0 32%; /* Each field will take up about 1/3 of the row */
        }

        /* Input and select styling */
        input[type="text"],
        input[type="date"],
        input[type="file"],
        textarea,
        select {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: 3px solid gray;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* Label styles */
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }

        /* Submit button styles */
        input[type="submit"] {
            width: 20%;
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Section for file upload (Proof) */
        #proof_section {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #e9f7ef;
            border: 1px solid #b2d8b2;
            border-radius: 4px;
        }

        /* Responsive Design */
        @media screen and (max-width: 800px) {
            .form-group > div {
                flex: 0 0 100%; /* Stack fields vertically on smaller screens */
            }

            input[type="submit"] {
                width: 100%;
                font-size: 22px;
            }
        }
        .conditional-section {
            display: none; /* Initially hidden */
        }
        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
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

        function toggleSections() {
            const consultancyNature = document.getElementById("consultancy_nature").value;
            const conditionalSection = document.getElementById("conditional_section");

            if (consultancyNature === "active") {
                conditionalSection.style.display = "block";
            } else {
                conditionalSection.style.display = "none";
            }
        }

</script>
</head>
<body>

    <form method="post" enctype="multipart/form-data">
        <center><h1>Project Consultancy</h1></center>

        <div class="form-group">
            <!-- Name of the agency -->
            <div>
                <label for="agency_name">Name of the Industry/Institution:</label>
                <input type="text" id="agency_name" name="agency_name" required>
            </div>

            <!-- Period of Consultancy From Date -->
            <div>
                <label for="from_date">Period of Consultancy (From):</label>
                <input type="date" id="from_date" name="from_date" required>
            </div>

            <!-- Period of Consultancy To Date -->
            <div>
                <label for="to_date">Period of Consultancy (To):</label>
                <input type="date" id="to_date" name="to_date" required>
            </div>
        </div>

        <div class="form-group">
            <!-- Title of the project -->
            <div>
                <label for="project_title">Title of the Project:</label>
                <input type="text" id="project_title" name="project_title" required>
            </div>
            <div>
                <label for="consultancy_type">Consultancy Type:</label>
                <select id="consultancy_type" name="consultancy_type" required>
                    <option value="select form">--select option--</option>
                    <option value="face to face">Face to Face</option>
                    <option value="online">Online</option>
                    <option value="digital">Digital</option>
                </select>
            </div>
            <!-- Nature of Consultancy -->
            <div>
                <label for="consultancy_nature">Nature of Consultancy:</label>
                <select id="consultancy_nature" name="consultancy_nature" onchange="toggleSections()" required>
                    <option value="select form">--select option--</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Consultancy Type -->
            
        </div>

        <!-- Conditional Section for Rate, Outcome, Relationship, and Attach Proof -->
        <div id="conditional_section" class="conditional-section">
            <div class="form-group">
                <!-- Rate of Consultancy -->
                <div>
                    <label for="rate_of_consultancy">Worth of Project Consultancy(in Rupees):</label>
                    <input type="text" id="rate_of_consultancy" name="rate_of_consultancy">
                </div>

                <!-- Outcome -->
                <div>
                    <label for="outcome">Specify Outcome:</label>
                    <textarea id="outcome" name="outcome" rows="2"></textarea>
                </div>

                <!-- Relationship -->
                <div>
                    <label for="relationship">Involvement:</label>
                    <select id="relationship" name="relationship">
                        <option value="select form">--select option--</option>
                        <option value="directly involved">Directly Involved</option>
                        <option value="guide">Guide</option>
                        <option value="partnership">Partnership</option>
                    </select>
                </div>
            </div>

            <!-- Upload Proof -->
            <div class="form-group">
                <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                <input type="file" id="file-upload" name="proof" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">
            </div>
        </div>

        <input type="submit" value="Submit">
        <a href="optional.html">Back</a>
    </form>
    <h2>Consultancy Records</h2>

<table>
    <thead>
        <tr>
            <th>Agency Name</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Project Title</th>
            <th>Type</th>
            <th>Nature</th>
            <th>Rate</th>
            <th>Outcome</th>
            <th>Relationship</th>
            <th>Proof</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($records)): ?>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['agency_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['from_date']); ?></td>
                    <td><?php echo htmlspecialchars($record['to_date']); ?></td>
                    <td><?php echo htmlspecialchars($record['project_title']); ?></td>
                    <td><?php echo htmlspecialchars($record['consultancy_type']); ?></td>
                    <td><?php echo htmlspecialchars($record['consultancy_nature']); ?></td>
                    <td><?php echo htmlspecialchars($record['rate_of_consultancy']); ?></td>
                    <td><?php echo htmlspecialchars($record['outcome']); ?></td>
                    <td><?php echo htmlspecialchars($record['relationship']); ?></td>
                    <td>
                        <?php if (!empty($record['proof_path'])): ?>
                            <a href="<?php echo htmlspecialchars($record['proof_path']); ?>" target="_blank">View Proof</a>
                        <?php else: ?>
                            No Proof
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10">No records found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>