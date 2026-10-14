
<?php
session_start();

if (
    !isset($_SESSION["admin_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/vendor/autoload.php";

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

date_default_timezone_set("Asia/Kolkata");

$message = "";
$error = "";
$activeToken = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $start = $_POST["start_time"] ?? "";
    $end = $_POST["end_time"] ?? "";

    if (
        !preg_match('/^\d{2}:\d{2}$/', $start) ||
        !preg_match('/^\d{2}:\d{2}$/', $end)
    ) {
        $error = "Please select valid start and end times.";
    } elseif ($end <= $start) {
        $error = "End time must be later than start time.";
    } else {
        $token = bin2hex(random_bytes(32));
        $today = date("Y-m-d");
        $startDateTime = $today . " " . $start . ":00";
        $endDateTime = $today . " " . $end . ":00";

        try {
            $conn->begin_transaction();

            $conn->query(
                "UPDATE attendance_sessions
                 SET status = 'closed'
                 WHERE status = 'active'"
            );

            $stmt = $conn->prepare(
                "INSERT INTO attendance_sessions
                 (session_token, start_time, end_time, status)
                 VALUES (?, ?, ?, 'active')"
            );

            $stmt->bind_param(
                "sss",
                $token,
                $startDateTime,
                $endDateTime
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $activeToken = $token;
            $message = "Attendance session created successfully!";
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Unable to create the session. Please try again.";
        }
    }
}

/* Find today's active session when the page is reopened. */
if ($activeToken === "") {
    $stmt = $conn->prepare(
        "SELECT session_token
         FROM attendance_sessions
         WHERE status = 'active'
           AND DATE(start_time) = CURDATE()
         ORDER BY id DESC
         LIMIT 1"
    );

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $activeToken = $row["session_token"];
    }

    $stmt->close();
}

$qrData = "";
$qrImage = "";

if ($activeToken !== "") {
    /*
     * This URL is for the teacher attendance page.
     * We will create mark_attendance.php in a later step.
     */
    $qrData = "http://localhost:8080/teachers_attendance/mark_attendance.php?token="
        . urlencode($activeToken);

    $options = new QROptions([
        "outputType" => QRCode::OUTPUT_IMAGE_PNG,
        "scale" => 8,
        "imageBase64" => true
    ]);

    $qrImage = (new QRCode($options))->render($qrData);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>QR Attendance</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    margin: 0;
    color: #1e293b;
}
header {
    background: #1d4ed8;
    color: white;
    padding: 20px;
}
main {
    max-width: 650px;
    margin: 35px auto;
    padding: 20px;
}
.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px #00000012;
}
label {
    display: block;
    margin: 18px 0 7px;
    font-weight: bold;
}
input, button {
    box-sizing: border-box;
    width: 100%;
    padding: 12px;
    border-radius: 7px;
    font-size: 16px;
}
input {
    border: 1px solid #cbd5e1;
}
button {
    margin-top: 22px;
    border: 0;
    background: #2563eb;
    color: white;
    cursor: pointer;
}
.message {
    padding: 12px;
    background: #dcfce7;
    color: #166534;
    border-radius: 7px;
}
.error {
    padding: 12px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 7px;
}
.qr {
    display: block;
    width: 260px;
    max-width: 100%;
    height: auto;
    margin: 20px auto;
}
a { color: #1d4ed8; }
</style>
</head>

<body>
<header>
    <h2>QR Attendance Session</h2>
</header>

<main>
<div class="card">
    <h3>Create Attendance Session</h3>
    <p>Choose when teachers can mark their attendance.</p>

    <?php if ($message !== ""): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="start_time">Attendance Start Time</label>
        <input type="time" id="start_time" name="start_time" required>

        <label for="end_time">Attendance End Time</label>
        <input type="time" id="end_time" name="end_time" required>

        <button type="submit">Create Attendance Session</button>
    </form>

    <?php if ($qrImage !== ""): ?>
        <hr>
        <h3 style="text-align:center;">Attendance QR Code</h3>
        <p style="text-align:center;">
            Scan this code to open the attendance page.
        </p>

        <img class="qr"
             src="<?= htmlspecialchars($qrImage, ENT_QUOTES) ?>"
             alt="Attendance QR Code">

        <p style="text-align:center;">
            <strong>Session token:</strong><br>
            <small>Keep this token private. Do not share it publicly.</small>
        </p>
    <?php endif; ?>

    <p><a href="admin_dashboard.php">← Back to Admin Dashboard</a></p>
</div>
</main>
</body>
</html>