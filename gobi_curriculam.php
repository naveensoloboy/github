<?php
session_start();
include('db.php'); // Include your database connection file

// Check if the user is logged in
if (!isset($_SESSION['staffid'])) {
    header("Location: staff_login.html"); // Redirect if not logged in
    exit();
}

$staffid = $_SESSION['staffid'];
$module_name = 'Curriculum Gobi College'; // Define module name for tracking
$max_submissions = 2; // Maximum submissions allowed

// Role-based marks mapping
$role_marks = [
    'chairman' => 5,
    'member' => 3,
    'subject_expert' => 3,
    'university_nomini' => 5,
    'special_invitee' => 3
];

// Level-based marks mapping
$level_marks = [
    'excellent' => 5,
    'very_good' => 4,
    'good' => 4,
    'fair' => 3,
    'satisfactory' => 2,
    'not_satisfactory' => 0
];

// Create database connection
$conn = new mysqli('localhost', 'root', '', 'junior_project');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check current submission count
    $stmt = $conn->prepare("SELECT count FROM marks WHERE staffid = ? AND module_name = ?");
    $stmt->bind_param("ss", $staffid, $module_name);
    $stmt->execute();
    $stmt->bind_result($submission_count);
    $stmt->fetch();
    $stmt->close();

    if (!$submission_count) {
        $submission_count = 0;
    }

    // Proceed if submission limit not reached
    if ($submission_count < $max_submissions) {
        // Initial curriculum design data
        $date = isset($_POST['gobi_date'][0]) ? $_POST['gobi_date'][0] : null;
        $board = isset($_POST['gobi_board'][0]) ? $_POST['gobi_board'][0] : null;
        $role = isset($_POST['gobi_role'][0]) ? $_POST['gobi_role'][0] : null;
        $contribution_level = isset($_POST['contribution_level'][0]) ? $_POST['contribution_level'][0] : null;
        $outcome = isset($_POST['gobi_previous_con'][0]) ? $_POST['gobi_previous_con'][0] : null;

        // File upload for the initial design
        $attachment = null;
        if (isset($_FILES['gobi_attachment']['name'][0]) && $_FILES['gobi_attachment']['name'][0] != '') {
            $attachment = 'uploads/' . basename($_FILES['gobi_attachment']['name'][0]);
            move_uploaded_file($_FILES['gobi_attachment']['tmp_name'][0], $attachment);
        }

        // Curriculum revision data
        $revision_date = isset($_POST['gobi_date'][1]) ? $_POST['gobi_date'][1] : null;
        $revision_board = isset($_POST['gobi_board'][1]) ? $_POST['gobi_board'][1] : null;
        $revision_role = isset($_POST['gobi_role'][1]) ? $_POST['gobi_role'][1] : null;
        $revision_percentage = isset($_POST['revision_percentage']) ? $_POST['revision_percentage'] : null;
        $revision_outcome = isset($_POST['gobi_previous_con'][1]) ? $_POST['gobi_previous_con'][1] : null;

        // File upload for the revision
        $revision_attachment = null;
        if (isset($_FILES['gobi_attachment']['name'][1]) && $_FILES['gobi_attachment']['name'][1] != '') {
            $revision_attachment = 'uploads/' . basename($_FILES['gobi_attachment']['name'][1]);
            move_uploaded_file($_FILES['gobi_attachment']['tmp_name'][1], $revision_attachment);
        }

        // Get marks based on role and contribution level
        $roleMark = isset($role_marks[$role]) ? $role_marks[$role] : 0;
        $levelMark = isset($level_marks[$contribution_level]) ? $level_marks[$contribution_level] : 0;
        $total_marks = $roleMark + $levelMark;

        // Insert curriculum data into the database
        $stmt = $conn->prepare("
            INSERT INTO gobi_data 
            (date, board, role, contribution_level, outcome, attachment, revision_date, revision_board, revision_role, revision_percentage, revision_outcome, revision_attachment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssss",
                $date,
                $board,
                $role,
                $contribution_level,
                $outcome,
                $attachment,
                $revision_date,
                $revision_board,
                $revision_role,
                $revision_percentage,
                $revision_outcome,
                $revision_attachment
            );
            if ($stmt->execute()) {
                echo "<script>alert('Curriculum data successfully stored.');</script>";
            } else {
                echo "<h2>Error: " . $stmt->error . "</h2>";
            }
            $stmt->close();
        }

        // Update submission count and total marks
        $submission_count++;
        $stmt = $conn->prepare("
            INSERT INTO marks (staffid, module_name, count, role_mark, level_mark, total_marks) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE count = ?, role_mark = ?, level_mark = ?, total_marks = ?
        ");
        if ($stmt) {
            $stmt->bind_param(
                "ssiiiiiiii",
                $staffid,
                $module_name,
                $submission_count,
                $roleMark,
                $levelMark,
                $total_marks,
                $submission_count,
                $roleMark,
                $levelMark,
                $total_marks
            );
            if ($stmt->execute()) {
                echo "<script>alert('Marks successfully updated.');</script>";
            } else {
                echo "<h2>Error: " . $stmt->error . "</h2>";
            }
            $stmt->close();
        }
    } else {
        echo "<h2>You have reached the maximum submission limit.</h2>";
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
    <title>Curriculum Design Form</title>
    <style>
        .hidden {
            display: none;
        }
        .full-width {
            width: 100%;
            box-sizing: border-box;
        }
        .inline-fields {
            display: flex;
            gap: 10px;
        }
        .field-group {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        /* Reset some basic styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body and form styling */
/* Body and form styling */
body {
    font-family: Arial, sans-serif;
    background-color: lightskyblue;
    color: #333;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 110vh;
}

form {
    background-color:linen;
    padding: 2em;
    max-width: 900px;
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.1);
}

/* Centered header */
h1, h2 {
    color: #333;
    text-align: center;
    margin-bottom: 1em;
}

/* Field group styling */
.field-group {
    margin-bottom: 1.5em;
    padding: 1em;
    border: 3px solid gray;
    border-radius: 5px;
}

/* Inline fields styling */
.inline-fields {
    display: flex;
    gap: 1em;
    flex-wrap: wrap;
    margin-bottom: 1em;
}

/* Label and input styling */
label {
    font-weight: bold;
    margin-bottom: 0.5em;
    display: block;
}

input[type="text"],
input[type="date"],
select,
textarea {
    width: 100%;
    padding: 0.5em;
    border: 3px solid grey;
    border-radius: 5px;
    outline: none;
    transition: border-color 0.2s ease;
}

/* Focus styling */
input[type="text"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    border-color: #007bff;
}

/* Full width styling for elements */
.full-width {
    width: 100%;
}

textarea {
    resize: vertical;
    min-height: 80px;
}

/* File upload styling */
.file-upload {
    margin-top: 0.5em;
    font-size: 0.9em;
}

/* Button styling */
input[type="submit"],
a {
    display: inline-block;
    text-decoration: none;
    color: #fff;
    background-color: #007bff;
    padding: 0.5em 1.5em;
    margin-top: 1em;
    border-radius: 5px;
    text-align: center;
    transition: background-color 0.2s ease;
}

input[type="submit"]:hover,
a:hover {
    background-color: #0056b3;
}

/* Link styling for the back button */
a {
    background-color: #6c757d;
    margin-right: 1em;
}

a:hover {
    background-color: #5a6268;
}

/* Mobile responsive adjustments */
@media (max-width: 600px) {
    .inline-fields {
        flex-direction: column;
    }

    form {
        padding: 1.5em;
    }
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
        <center><h1>Designing of Curriculum</h1></center>

        <!-- Fields for Gobi Arts and Science College -->
        <div>
            <h2>Gobi Arts & Science College</h2>
            <div class="field-group">
                <div class="inline-fields">
                    <div>
                        <label for="gobiDate">Date:</label>
                        <input type="date" name="gobi_date[]" class="full-width">
                    </div>
                    <div>
                        <label for="gobiBoard">Name of the Board:</label>
                        <select name="gobi_board[]" class="full-width">
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
                    <div>
                        <label for="gobiRole">Role:</label>
                        <select name="gobi_role[]" class="full-width">
                        <option>--Role--</option>
                        <option value="chairman">Chairman</option>
                                <option value="member">Member</option>
                                <option value="subject_expert">Subject Expert</option>
                                <option value="university_nomini">University Nominnee</option>
                                <option value="special_invitee">Special Inivitee</option>
                        </select>
                    </div>
                </div>
                <div>
    <label for="contribution_level">Contribution Level</label>
    <select name="contribution_level" class="full-width" required>
        <option value="">--Select Level--</option>
        <option value="excellent">Excellent</option>
        <option value="very_good">Very Good</option>
        <option value="good">Good</option>
        <option value="fair">Fair</option>
        <option value="satisfactory">Satisfactory</option>
        <option value="not_satisfactory">Not Satisfactory</option>
    </select>
</div>
                <div>
                    <label for="gobiOutcome">Specify Outcome:</label>
                    <textarea name="gobi_previous_con[]" class="full-width"></textarea>
                </div>
                <div>
                        <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                        <input type="file" id="file-upload" name="gobi_attachment[]" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">

                </div><br>
                
            </div>
        
        
            <a href="optional.html">Back</a>
            <input type="submit" value="Save">
        </div>
    </form>
</body>
</html>






