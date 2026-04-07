<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$dbname = "attendance_db1";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$view = $_GET['view'] ?? 'students'; // Default to 'students'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px;">

    <h2 style="color: #2c3e50;">Attendance Management System</h2>

    <!-- Navigation Buttons -->
    <div style="margin-bottom: 20px;">
        <a href="?view=students" style="text-decoration: none;">
            <button style="background-color: #3498db; color: white; border: none; padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer; border-radius: 5px;">
                View Student List
            </button>
        </a>
        <a href="?view=attendance" style="text-decoration: none;">
            <button style="background-color: #27ae60; color: white; border: none; padding: 10px 20px; margin: 5px; font-size: 16px; cursor: pointer; border-radius: 5px;">
                View Attendance Records
            </button>
        </a>
    </div>

    <!-- Display Student List -->
    <?php if ($view === 'students') : ?>
        <h3 style="color: #34495e;">Student List</h3>
        <table border="1" style="width: 80%; margin: auto; border-collapse: collapse; background: white; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
            <thead>
                <tr style="background-color: #3498db; color: white;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Fingerprint ID</th>
                    <th style="padding: 10px;">Name</th>
                    <th style="padding: 10px;">Roll No</th>
                    <th style="padding: 10px;">Mobile</th>
                    <th style="padding: 10px;">Email</th>
                    <th style="padding: 10px;">Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM students ORDER BY id DESC");
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td style='padding: 10px;'>{$row['id']}</td>
                            <td style='padding: 10px;'>{$row['fingerprint_id']}</td>
                            <td style='padding: 10px;'>{$row['name']}</td>
                            <td style='padding: 10px;'>{$row['roll_no']}</td>
                            <td style='padding: 10px;'>{$row['mobile']}</td>
                            <td style='padding: 10px;'>{$row['email']}</td>
                            <td style='padding: 10px;'>{$row['registered_at']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='padding: 10px; text-align: center;'>No students found</td></tr>";
                }
                ?>
            </tbody>
        </table>

    <!-- Display Attendance Records -->
    <?php elseif ($view === 'attendance') : ?>
        <h3 style="color: #34495e;">Attendance Records</h3>
        <table border="1" style="width: 80%; margin: auto; border-collapse: collapse; background: white; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
            <thead>
                <tr style="background-color: #27ae60; color: white;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Fingerprint ID</th>
                    <th style="padding: 10px;">Name</th>
                    <th style="padding: 10px;">In-Time</th>
                    <th style="padding: 10px;">Out-Time</th>
                    <th style="padding: 10px;">Status</th>
                    <th style="padding: 10px;">Recorded At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM attendance ORDER BY recorded_at DESC");
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td style='padding: 10px;'>{$row['id']}</td>
                            <td style='padding: 10px;'>{$row['fingerprint_id']}</td>
                            <td style='padding: 10px;'>{$row['name']}</td>
                            <td style='padding: 10px;'>{$row['in_time']}</td>
                            <td style='padding: 10px;'>{$row['out_time']}</td>
                            <td style='padding: 10px; color: " . ($row['status'] === 'Present' ? 'green' : 'red') . "; font-weight: bold;'>{$row['status']}</td>
                            <td style='padding: 10px;'>{$row['recorded_at']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='padding: 10px; text-align: center;'>No attendance records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
