<?php
// Database Configuration
$host = "localhost";  
$user = "root";       
$pass = "";           
$dbname = "attendance_db1";

// Connect to Database
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle Attendance Marking
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fingerprint_id = isset($_POST["fingerprint_id"]) ? intval($_POST["fingerprint_id"]) : 0;
    $action = isset($_POST["action"]) ? $_POST["action"] : "";

    if ($fingerprint_id > 0 && in_array($action, ["in_time", "out_time"])) {
        // Check if fingerprint exists in the students table
        $checkStudent = $conn->prepare("SELECT name FROM students WHERE fingerprint_id = ?");
        $checkStudent->bind_param("i", $fingerprint_id);
        $checkStudent->execute();
        $studentResult = $checkStudent->get_result();

        if ($studentResult->num_rows > 0) {
            $student = $studentResult->fetch_assoc();
            $student_name = $student["name"];

            // Check if attendance exists for today
            $todayDate = date("Y-m-d");
            $checkAttendance = $conn->prepare("SELECT id, in_time, out_time FROM attendance WHERE fingerprint_id = ? AND DATE(recorded_at) = ?");
            $checkAttendance->bind_param("is", $fingerprint_id, $todayDate);
            $checkAttendance->execute();
            $attendanceResult = $checkAttendance->get_result();

            if ($attendanceResult->num_rows > 0) {
                // Update existing record
                $attendance = $attendanceResult->fetch_assoc();
                $attendance_id = $attendance["id"];
                $in_time = $attendance["in_time"];
                $out_time = $attendance["out_time"];

                if ($action === "in_time" && !empty($in_time)) {
                    $message = "In-Time already marked!";
                } elseif ($action === "out_time" && !empty($out_time)) {
                    $message = "Out-Time already marked!";
                } else {
                    // Update attendance
                    $updateField = ($action === "in_time") ? "in_time" : "out_time";
                    $updateQuery = $conn->prepare("UPDATE attendance SET $updateField = NOW(), status = IF(in_time IS NOT NULL AND out_time IS NOT NULL, 'Present', 'Absent') WHERE id = ?");
                    $updateQuery->bind_param("i", $attendance_id);
                    
                    if ($updateQuery->execute()) {
                        $message = ucfirst(str_replace("_", " ", $action)) . " marked successfully!";
                    } else {
                        $message = "Failed to mark $action.";
                    }
                }
            } else {
                // Insert new attendance record
                if ($action === "in_time") {
                    $insertQuery = $conn->prepare("INSERT INTO attendance (fingerprint_id, name, in_time, recorded_at, status) VALUES (?, ?, NOW(), NOW(), 'Absent')");
                    $insertQuery->bind_param("is", $fingerprint_id, $student_name);
                    
                    if ($insertQuery->execute()) {
                        $message = "In-Time marked successfully!";
                    } else {
                        $message = "Failed to mark In-Time.";
                    }
                } else {
                    $message = "Mark In-Time first before marking Out-Time!";
                }
            }
        } else {
            $message = "Fingerprint ID not registered!";
        }
    } else {
        $message = "Invalid input!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Attendance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        form {
            display: inline-block;
            background: #f4f4f4;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
        }
        input, select, button {
            margin: 10px;
            padding: 10px;
            width: 100%;
            max-width: 300px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

    <h2>Mark Attendance</h2>
    
    <form method="post">
        <label for="fingerprint_id">Fingerprint ID:</label>
        <input type="number" name="fingerprint_id" id="fingerprint_id" required>

        <label for="action">Select Action:</label>
        <select name="action" id="action">
            <option value="in_time">Mark In-Time</option>
            <option value="out_time">Mark Out-Time</option>
        </select>

        <button type="submit">Submit</button>
    </form>

    <?php if (!empty($message)): ?>
        <p class="message <?= strpos($message, 'error') !== false ? 'error' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

</body>
</html>
