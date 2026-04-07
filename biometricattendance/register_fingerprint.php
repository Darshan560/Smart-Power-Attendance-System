<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow CORS for API requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$database = "attendance_db1"; // Database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit;
}

// Ensure it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input fields
    $finger_id = isset($_POST['finger_id']) ? intval($_POST['finger_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : "";
    $roll_no = isset($_POST['roll_no']) ? trim($_POST['roll_no']) : "";

    // Validate input fields
    if ($finger_id <= 0 || empty($name) || empty($roll_no)) {
        echo json_encode(["status" => "error", "message" => "Missing or invalid input fields!"]);
        exit;
    }

    // Check if fingerprint ID already exists
    $sql_check = "SELECT id FROM students WHERE fingerprint_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $finger_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Fingerprint ID already exists!"]);
    } else {
        // Insert new student data
        $sql = "INSERT INTO students (fingerprint_id, name, roll_no) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $finger_id, $name, $roll_no);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Fingerprint Registered Successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Insert Error: " . $stmt->error]);
        }

        $stmt->close();
    }

    $stmt_check->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request!"]);
}

$conn->close();
?>
