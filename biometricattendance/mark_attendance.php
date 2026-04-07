<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli("localhost", "root", "", "attendance_db1");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed!"]));
}

// Handle attendance marking request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $fingerprint_id = $data["fingerprint_id"] ?? "";
    $action = $data["action"] ?? "";

    if (empty($fingerprint_id) || !in_array($action, ["in_time", "out_time"])) {
        echo json_encode(["status" => "error", "message" => "Invalid request!"]);
        exit;
    }

    // Check if student exists
    $checkSQL = "SELECT id FROM students WHERE fingerprint_id = ?";
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "Student not found!"]);
        exit;
    }
    $stmt->close();

    // Update or Insert Attendance Record
    $updateSQL = "UPDATE attendance SET $action = NOW() WHERE fingerprint_id = ? AND DATE(recorded_at) = CURDATE()";
    $stmt = $conn->prepare($updateSQL);
    $stmt->bind_param("i", $fingerprint_id);
    $stmt->execute();

    if ($stmt->affected_rows == 0) {
        // If no existing record for today, insert a new one
        $insertSQL = "INSERT INTO attendance (fingerprint_id, $action, recorded_at) VALUES (?, NOW(), NOW())";
        $stmt = $conn->prepare($insertSQL);
        $stmt->bind_param("i", $fingerprint_id);
        $stmt->execute();
    }

    echo json_encode(["status" => "success", "message" => ucfirst(str_replace("_", " ", $action)) . " marked successfully!"]);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Attendance</title>
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
                    alert("⚠️ Fingerprint not recognized. Try again.");
                    return null;
                }
            } catch (error) {
                console.error("❌ Error:", error);
                alert("Error communicating with fingerprint scanner.");
                return null;
            }
        }

        function showAttendanceButtons() {
            document.getElementById("markInTimeButton").style.display = "block";
            document.getElementById("markOutTimeButton").style.display = "block";
        }

        async function markAttendance(action) {
            let fingerprintID = await getFingerprint();
            if (!fingerprintID) return;

            try {
                let response = await fetch("attendance.php", {
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
<body>
    <h2>Fingerprint Biometric Attendance</h2>
    <button onclick="showAttendanceButtons()">Mark Attendance</button>
    <button id="markInTimeButton" style="display:none;" onclick="markAttendance('in_time')">Mark In-Time</button>
    <button id="markOutTimeButton" style="display:none;" onclick="markAttendance('out_time')">Mark Out-Time</button>
</body>
</html>
