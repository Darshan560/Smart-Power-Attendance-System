<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *"); // Allow requests from any origin
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli("localhost", "root", "", "attendance_db1");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed!"]));
}

// Process Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("Received POST data: " . print_r($_POST, true));

    $name = $_POST['name'] ?? '';
    $roll_no = $_POST['roll_no'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';
    $fingerprint_id = $_POST['fingerprint_id'] ?? '';

    if (empty($name) || empty($roll_no) || empty($mobile) || empty($email) || empty($fingerprint_id)) {
        error_log("Error: Missing fields!");
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
        exit;
    }

    // Check if fingerprint ID already exists
    $checkSQL = "SELECT id FROM students WHERE fingerprint_id = ?";
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Fingerprint ID already registered!"]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Insert new student record
    $sql = "INSERT INTO students (name, roll_no, mobile, email, fingerprint_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssi", $name, $roll_no, $mobile, $email, $fingerprint_id);
        if ($stmt->execute()) {
            error_log("✅ Student Registered: $name ($roll_no) - Fingerprint ID: $fingerprint_id");
            echo json_encode(["status" => "success", "message" => "Student Registered Successfully!"]);
        } else {
            error_log("SQL Error: " . $stmt->error);
            echo json_encode(["status" => "error", "message" => "Database Error!"]);
        }
        $stmt->close();
    } else {
        error_log("Error preparing query: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Query preparation failed!"]);
    }
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Registration</title>
    <script>
        async function registerStudent() {
            let name = document.getElementById("name").value.trim();
            let roll_no = document.getElementById("roll_no").value.trim();
            let mobile = document.getElementById("mobile").value.trim();
            let email = document.getElementById("email").value.trim();
            let button = document.getElementById("registerBtn");
            let fingerprintField = document.getElementById("fingerprint_id");

            if (!name || !roll_no || !mobile || !email) {
                alert("⚠️ Please fill in all fields.");
                return;
            }

            button.disabled = true;
            button.innerText = "Registering... ⏳";

            console.log("🔍 Requesting Fingerprint ID from ESP8266...");

            try {
                let response = await fetch("http://192.168.248.56/register_fingerprint");
                if (!response.ok) {
                    throw new Error("ESP8266 fingerprint registration failed.");
                }

                let fingerprintID = await response.text();
                fingerprintID = parseInt(fingerprintID.trim());

                if (fingerprintID > 0) {
                    fingerprintField.value = fingerprintID;
                    console.log("✅ Fingerprint ID Received:", fingerprintID);

                    let formData = new FormData(document.getElementById("registerForm"));

                    let registerResponse = await fetch("register.php", {
                        method: "POST",
                        body: formData
                    });

                    let result = await registerResponse.json();
                    alert(result.message);
                } else {
                    alert("⚠️ Fingerprint registration failed. Try again.");
                }
            } catch (error) {
                console.error("❌ Error:", error);
                alert("Could not communicate with the fingerprint scanner. Check ESP8266 connection.");
            } finally {
                button.disabled = false;
                button.innerText = "Register";
            }
        }
    </script>
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; font-family: Arial, sans-serif;">

    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); width: 350px; text-align: center;">
        <h2 style="color: #2c3e50;">Register Student</h2>
        <form id="registerForm">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Name:</label>
            <input type="text" id="name" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc;">
            
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Roll No:</label>
            <input type="text" id="roll_no" name="roll_no" required style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc;">
            
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Mobile:</label>
            <input type="text" id="mobile" name="mobile" required style="width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc;">
            
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
            <input type="text" id="email" name="email" required style="width: 100%; padding: 8px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;">
            
            <input type="hidden" id="fingerprint_id" name="fingerprint_id">
            
            <button type="button" id="registerBtn" onclick="registerStudent()" 
                style="background-color: #27ae60; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; transition: 0.3s;">
                Register
            </button>
        </form>
    </div>

</body>

</html>
