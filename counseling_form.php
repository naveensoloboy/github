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
    $module_name = "Student Counseling";
    $submit_mark = 2; // Fixed submit mark
    $max_submissions = 5;

    // Check submission count for this staff and module
    $submissionCheckSQL = "SELECT COUNT(*) AS submission_count FROM counseling_records WHERE staffid = '$staffid'";
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
    $department = $conn->real_escape_string($_POST['department'][0]); // Assuming first department is selected
    $role = $conn->real_escape_string($_POST['mode']); // 'mode' is treated as role name now
    $student_rollno = $conn->real_escape_string($_POST['student_rollno']);
    $from_date = $conn->real_escape_string($_POST['from_date']);
    $to_date = $conn->real_escape_string($_POST['to_date']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $outcome = $conn->real_escape_string($_POST['outcome']);

    // Define marks based on role
    $role_marks_map = [
        "face-to-face" => 8,
        "online" => 3
    ];

    $role_mark = isset($role_marks_map[$role]) ? $role_marks_map[$role] : 0;
    $total_marks = $role_mark + $submit_mark;

    // Handle file upload
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = null;
    if (isset($_FILES['proof_path']) && $_FILES['proof_path']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['proof_path']['name']);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        if (!move_uploaded_file($_FILES['proof_path']['tmp_name'], $filePath)) {
            echo "Error uploading the file.";
            exit;
        }
    }

    // Insert data into counseling_records table
    $counselingSQL = "INSERT INTO counseling_records (staffid, department, mode, student_rollno, from_date, to_date, duration, outcome, proof_path)
                      VALUES ('$staffid', '$department', '$role', '$student_rollno', '$from_date', '$to_date', '$duration', '$outcome', '$filePath')";

    if (!$conn->query($counselingSQL)) {
        echo "Error inserting data into counseling_records: " . $conn->error;
        exit;
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
        exit;
    }

    echo "<script>alert('Data has been successfully stored!');</script>";
}
// Handle delete request
if (isset($_GET['delete_id']) && isset($_SESSION['staffid'])) {
    $delete_id = intval($_GET['delete_id']);
    $staffid = $_SESSION['staffid'];

    // Get the role and mark details of the record to be deleted
    $getDetailsSQL = "SELECT mode FROM counseling_records WHERE id = $delete_id AND staffid = '$staffid'";
    $detailsResult = $conn->query($getDetailsSQL);

    if ($detailsResult && $detailsResult->num_rows > 0) {
        $details = $detailsResult->fetch_assoc();
        $mode = $details['mode'];

        // Map mode to mark
        $role_marks_map = [
            "face-to-face" => 8,
            "online" => 3,
        ];

        $role_mark = $role_marks_map[$mode] ?? 0;

        // Delete the record from counseling_records table
        $deleteSQL = "DELETE FROM counseling_records WHERE id = $delete_id AND staffid = '$staffid'";
        if ($conn->query($deleteSQL)) {
            // Update marks table
            $updateMarksSQL = "UPDATE marks 
                               SET submit_count = submit_count - 1, 
                                   role_mark = role_mark - $role_mark, 
                                   total_marks = total_marks - ($role_mark + 2)
                               WHERE staffid = '$staffid' AND module_name = 'Student Counseling'";
            $conn->query($updateMarksSQL);

            echo "<script>alert('Record deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting record: " . $conn->error . "');</script>";
        }
    }
}

// Fetch data for grid view
$counselingRecordsSQL = "SELECT * FROM counseling_records WHERE staffid = '$staffid' ORDER BY id DESC";
$counselingRecordsResult = $conn->query($counselingRecordsSQL);
$conn->close();
?>







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counseling Form</title>
    <style>
        /* Global Styles */
        body {
            font-family: Arial, sans-serif;
            background-color:  #add8e6;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        /* Form Styles */
        form {
            background-color:#fff5f5;
            border-radius: 8px;
            padding: 20px;
            max-width: 750px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Flex container for two columns */
        .form-group {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        /* Ensures each label-input pair takes half the space */
        .form-group div {
            flex: 1;
        }

        label {
            font-size: 1rem;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"], input[type="date"], select, textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 4px;
            margin-bottom: 6px;
            border: 3px solid gray;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus, input[type="date"]:focus, select:focus, textarea:focus, input[type="file"]:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Buttons */
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        #addMore {
            display: none; /* Hide "Add More" initially */
            margin-top: 10px;
            background-color:  #007bff;
        }

        #addMore:hover {
            background-color: #218838;
        }

        /* Media Queries for smaller screens */
        @media (max-width: 600px) {
            .form-group {
                flex-direction: column;
            }

            button, #addMore {
                width: 100%;
            }
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            border: 1px solid #ddd;
        }

        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
        }

        /* Buttons */
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        button:hover {
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

    <h1>STUDENT's COUNSELLING </h1><center>(you are enter give only individual counselling given by you, please do not enter group counselling)</center><br>
    <form id="counselingForm" action="#" method="POST" enctype="multipart/form-data">

        <!-- Department and Mode of Counseling in one row -->
        <div class="form-group">
            <div>
                <label for="department"><b>Name of the Department:</b></label>
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
                        </select>
            </div>

            <div>
                <label for="mode"><b>Mode of Counseling:</b></label>
                <select id="mode" name="mode" required>
                    <option value="">--Select Mode--</option>
                    <option value="face-to-face">Face to Face</option>
                    <option value="online">Online</option>
                </select>
            </div>
        </div>

        <!-- Student Name and Date in one row -->
        <div class="form-group">
            <div>
                <label for="studentName"><b>Rollno of the Student:</b></label>
                <input type="text" id="studentName" name="student_rollno">
            </div>

            <div>
                <label for="date"><b>From Date:</b></label>
                <input type="date" id="date" name="from_date" required>
            </div>
            <div>
                <label for="date"><b>To Date:</b></label>
                <input type="date" id="date" name="to_date" required>
            </div>
        </div>

        <!-- Outcome of Counseling (full width) -->
        <div class="form-group">
            <div>
                <label for="duration"><b>Duration of Counselling:</b></label>
                <select id="duration" name="duration" required>
                    <option value="">--Select duration--</option>
                    <option value="5 minutes">5 minutes</option>
                    <option value="6 minutes">6 minutes</option>
                    <option value="7 minutes">7 minutes</option>
                    <option value="8 minutes">8 minutes</option>
                    <option value="9 minutes">9 minutes</option>
                    <option value="10 minutes">10 minutes</option>
                </select>
            </div>
            <div>
                <label for="outcome"><b>Outcome of the Counselling:</b></label>
                <textarea id="outcome" name="outcome" rows="4" cols="50" required></textarea>
            </div>
            
        </div>

        <!-- File Upload (full width) -->
        <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
        <input type="file" id="file-upload" name="proof_path" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">

        <!-- Buttons -->
        <button type="submit">Submit</button>
        
        <a href="optional.html">Back</a>

    </form>
    
    <h1>All Counseling Records</h1>

<?php if ($counselingRecordsResult && $counselingRecordsResult->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Department</th>
                <th>Mode</th>
                <th>Student Roll No</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Duration</th>
                <th>Outcome</th>
                <th>Proof</th>
                <th>Actions</th> <!-- Added Actions column -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $counselingRecordsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td><?php echo $row['mode']; ?></td>
                    <td><?php echo $row['student_rollno']; ?></td>
                    <td><?php echo $row['from_date']; ?></td>
                    <td><?php echo $row['to_date']; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td><?php echo $row['outcome']; ?></td>
                    <td>
                        <?php if ($row['proof_path']): ?>
                            <a href="<?php echo $row['proof_path']; ?>" target="_blank">View Proof</a>
                        <?php else: ?>
                            No Proof
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No counseling records found.</p>
<?php endif; ?>


</body>
</html>

