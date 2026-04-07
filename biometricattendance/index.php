<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";  
$user = "root";  
$password = "";  
$dbname = "attendance_db1";  

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

// Handle incoming POST request for attendance
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !isset($data["fingerprint_id"]) || !isset($data["action"])) {
        die(json_encode(["status" => "error", "message" => "Invalid Input!"]));
    }

    $fingerprint_id = intval($data["fingerprint_id"]);
    $action = $data["action"];

    // Fetch student details using fingerprint_id
    $query = "SELECT * FROM students WHERE fingerprint_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die(json_encode(["status" => "error", "message" => "No student found for this fingerprint ID"]));
    }

    $student = $result->fetch_assoc();
    $name = $student["name"];
    
    // Fetch latest attendance record for today
    $query = "SELECT * FROM attendance WHERE fingerprint_id = ? AND DATE(recorded_at) = CURDATE() ORDER BY recorded_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingAttendance = $result->fetch_assoc();

    // Mark In-Time
    if ($action === "in_time") {
        if ($existingAttendance && $existingAttendance["in_time"]) {
            die(json_encode(["status" => "error", "message" => "In-Time already marked"]));
        }

        $query = "INSERT INTO attendance (fingerprint_id, name, in_time, recorded_at) 
                  VALUES (?, ?, NOW(), NOW()) 
                  ON DUPLICATE KEY UPDATE in_time = NOW(), recorded_at = NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $fingerprint_id, $name);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "In-Time marked successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark In-Time"]);
        }
    }
    // Mark Out-Time
    elseif ($action === "out_time") {
        if (!$existingAttendance || !$existingAttendance["in_time"]) {
            die(json_encode(["status" => "error", "message" => "Cannot mark Out-Time before In-Time"]));
        }

        if ($existingAttendance["out_time"]) {
            die(json_encode(["status" => "error", "message" => "Out-Time already marked"]));
        }

        $query = "UPDATE attendance SET out_time = NOW(), status = 'Present' WHERE fingerprint_id = ? AND in_time IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $fingerprint_id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Out-Time marked successfully and status updated to Present"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark Out-Time"]);
        }
    }
    else {
        die(json_encode(["status" => "error", "message" => "Invalid action"]));
    }

    $conn->close();
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Biometric Attendance</title>
    <script>
        async function getFingerprint() {
            try {
                let response = await fetch("http://192.168.248.56/get_fingerprint");
                if (!response.ok) throw new Error("Fingerprint scan failed.");
                let fingerprintID = await response.text();
                fingerprintID = parseInt(fingerprintID.trim());
                if (fingerprintID > 0) {
                    console.log("✅ Fingerprint ID Received:", fingerprintID);
                    return fingerprintID;
                } else {
                    alert("⚠️ Fingerprint not recognized. Please try again.");
                    return null;
                }
            } catch (error) {
                console.error("❌ Error:", error);
                alert("Error communicating with fingerprint scanner.");
                return null;
            }
        }

        function showButtons() {
            document.getElementById("markInTimeButton").style.display = "block";
            document.getElementById("markOutTimeButton").style.display = "block";
        }

        async function markAttendance(action) {
            let fingerprintID = await getFingerprint();
            if (!fingerprintID) return;

            try {
                let response = await fetch("index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ fingerprint_id: fingerprintID, action: action })
                });
                let data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error("Error:", error);
            }
        }
    </script>
</head>
<body style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; font-family: Arial, sans-serif; text-align: center;">

    <h2 style="color: #2c3e50; margin-bottom: 20px;">Fingerprint Biometric Attendance</h2>

    <button onclick="showButtons()" 
        style="background-color: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 5px; font-size: 16px; cursor: pointer; transition: 0.3s;">
        Mark Attendance
    </button>

    <p id="statusMessage" style="margin-top: 20px; font-size: 18px; color: #555; font-weight: bold;">
        Attendance Status: Not Checked
    </p>

    <button id="markInTimeButton" style="display:none; background-color: #27ae60; color: white; border: none; padding: 10px 15px; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; transition: 0.3s;" 
        onclick="markAttendance('in_time')">
        Mark In-Time
    </button>

    <button id="markOutTimeButton" style="display:none; background-color: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; transition: 0.3s;" 
        onclick="markAttendance('out_time')">
        Mark Out-Time
    </button>

</body>

</html>
