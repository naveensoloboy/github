<?php
session_start();

// Check if the staffid is set in the session; if not, redirect to the login page
if (!isset($_SESSION['staffid'])) {
    header('Location: staff_login.html');
    exit();
}

$staffid = $_SESSION['staffid'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'junior_project');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch staff details from the database
$sql = "SELECT * FROM login_staff WHERE staffid = '$staffid'";
$result = $conn->query($sql);

// Check if the query was successful
if ($result === false) {
    die("Error executing query: " . $conn->error);
}

// Check if any record was found
if ($result->num_rows > 0) {
    $staffData = $result->fetch_assoc();
} else {
    die("No records found for staffid: " . $staffid);
}
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <style>* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color:#add8e6;
    margin: 0;
    padding: 20px;
}

form {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    max-width: 800px;
    margin: 0 auto;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

blockquote {
    margin: 0;
    padding: 0;
}

label {
    display: inline-block;
    width: 100px;
    font-weight: bold;
}

input, textarea {
    width: calc(100% - 120px);
    padding: 10px;
    margin-bottom: 20px;
    border: 3px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

input[type="submit"] {
    width: auto;
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

input[type="submit"]:hover {
    background-color: #45a049;
}

.row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.row .column {
    flex: 0 0 48%; /* Each input field takes about 48% of the row's width */
}

textarea {
    width: 100%;
    resize: vertical;
}

@media (max-width: 768px) {
    .row {
        flex-direction: column;
    }
    
    .row .column {
        flex: 1 1 100%;
    }

    input, textarea {
        width: calc(100% - 20px);
    }
}
</style>
</head>
<body>
    <form action="register.php" method="POST">
    <blockquote>    
        <h1><center>REGISTERATION FORM</center></h1>
        <div class="row">
            <div class="column">
                <label for="staffid">CMS ID:</label>
                <input type="text" id="staffid" name="staffid" value="<?php echo $staffid; ?>" readonly>
            </div>
            <div class="column">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $staffData['name']; ?>" readonly>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" value="<?php echo $staffData['dob']; ?>" readonly>
            </div>
            <div class="column">
                <label for="gender">Gender:</label>
                <input type="text" id="gender" name="gender" value="<?php echo $staffData['gender']; ?>" readonly>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" value="<?php echo $staffData['department']; ?>" readonly>
            </div>
            <div class="column">
                <label for="designation">Designation:</label>
                <input type="text" id="designation" name="designation" required>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <label for="mobile">Mobile:</label>
                <input type="text" id="mobile" name="mobile" value="<?php echo $staffData['mobile']; ?>" readonly>
            </div>
            <div class="column">
                <label for="gmail">Gmail:</label>
                <input type="email" id="gmail" name="gmail" value="<?php echo $staffData['gmail']; ?>" readonly>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <label for="address">Address:</label><br>
                <textarea id="address" name="address" rows="4" cols="50" required></textarea>
            </div>
            <div class="column">
                <label for="area_of_specification">Area of Specification:</label>
                <input type="text" id="area_of_specification" name="area_of_specification" required>
            </div>
        </div>

        <div class="row">
            <div class="column">
                <label for="additional_qualification">Additional Qualification:</label>
                <input type="text" id="additional_qualification" name="additional_qualification" required>
            </div>
        </div>

        <input type="submit" value="Register">
        <a href="optional.html">Back</a>
    </blockquote>
    </form>
</body>
</html>