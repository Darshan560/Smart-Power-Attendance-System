<?php
$conn = new mysqli("localhost", "root", "", "attendance_db1");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $finger_id = $_POST['FingerID'];

    // Check if any students are registered
    $students_exist = $conn->query("SELECT COUNT(*) AS count FROM students");
    $students_count = $students_exist->fetch_assoc()["count"];

    if ($students_count == 0) {
        echo "No students registered in the database.";
        exit();
    }

    // Check if fingerprint exists in students table
    $result = $conn->query("SELECT * FROM students WHERE fingerprint_id = '$finger_id'");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['name'];

        // Check if in-time is already recorded
        $check = $conn->query("SELECT * FROM attendance WHERE fingerprint_id = '$finger_id' AND out_time IS NULL");

        if ($check->num_rows > 0) {
            // Record out-time
            $conn->query("UPDATE attendance SET out_time = NOW(), status = 'Present' WHERE fingerprint_id = '$finger_id' AND out_time IS NULL");
            echo "logout " . $name;
        } else {
            // Record in-time
            $conn->query("INSERT INTO attendance (fingerprint_id, name, in_time) VALUES ('$finger_id', '$name', NOW())");
            echo "login " . $name;
        }
    } else {
        echo "error: User not found";
    }
}
?>
