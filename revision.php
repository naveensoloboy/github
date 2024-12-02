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
                <h2>Revision of Curriculum</h2>
            <div class="field-group">
                <div class="inline-fields">
                    <div>
                        <label for="gobiDate">Date of Revision:</label>
                        <input type="date" name="gobi_date[]" class="full-width">
                    </div>
                    <div>
                        <label for="gobiBoard">Name of the Revision Board:</label>
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
                        <label for="gobiRole">Role of Revision:</label>
                        <select name="gobi_role[]" class="full-width">
                                <option value="">--Role--</option>
                                <option value="chairman">Chairman</option>
                                <option value="member">Member</option>
                                <option value="subject_expert">Subject Expert</option>
                                <option value="university_nomini">University Nominy</option>
                                <option value="special_invitee">Special Inivitee</option>
                        </select>
                    </div>
                    <div>
                    <label for="percentage">Percentage of Revision:</label>
                    <select id="percentage" name="revision_percentage">
                            <option value="">--Select Percentage--</option>
                            <option value="5">5%</option>
                                <option value="10">10%</option>
                                <option value="15">15%</option>
                                <option value="25">25%</option>
                                <option value="30">30%</option>
                                <option value="35">35%</option>
                                <option value="40">40%</option>
                                <option value="45">45%</option>
                                <option value="50">50%</option>
                            
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="gobiOutcome">Specify Outcome of Revision:</label>
                    <textarea name="gobi_previous_con[]" class="full-width"></textarea>
                </div>
                <div>
                <label for="file-upload"><b>Attach Proof:(File size should be maximum 150kb,pdf,jpeg)</b></label>
                <input type="file" id="file-upload" name="gobi_attachment[]" class="file-upload" multiple accept=".jpg, .jpeg, .pdf">

                </div>
                
            </div>
        
        
            <a href="optional.html">Back</a>
            <input type="submit" value="Save">
        </div>
    </form>
</body>
</html>
