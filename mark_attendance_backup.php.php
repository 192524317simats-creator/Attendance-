
<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

require_once __DIR__ . "/config/database.php";

// Require teacher login
if (
    !isset($_SESSION["teacher_user_id"]) ||
    ($_SESSION["role"] ?? "") !== "teacher"
) {
    $_SESSION["attendance_message"] =
        "Please log in using your teacher account.";

    if (isset($_GET["token"]) && is_string($_GET["token"])) {
        $_SESSION["pending_attendance_token"] = $_GET["token"];
    }

    header("Location: teacher_login.php");
    exit;
}


$token = $_GET["token"] ?? "";
$error = "";
$sessionInfo = null;
$schoolSettings = null;

if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = "Invalid QR code. Please scan the current QR code.";
} else {
    $stmt = $conn->prepare(
        "SELECT id, start_time, end_time, status
         FROM attendance_sessions
         WHERE session_token = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $sessionInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sessionInfo) {
        $error = "Attendance session not found.";
    } elseif ($sessionInfo["status"] !== "active") {
        $error = "This attendance session is closed.";
    } else {
        $now = new DateTime("now");
        $start = new DateTime($sessionInfo["start_time"]);
        $end = new DateTime($sessionInfo["end_time"]);

        if ($now < $start || $now > $end) {
            $error = "Attendance is available only during the scheduled time.";
        }
    }
}

// Read the saved school coordinates
if ($error === "") {
    $result = $conn->query(
        "SELECT school_latitude, school_longitude, allowed_radius
         FROM settings
         ORDER BY id ASC
         LIMIT 1"
    );

    $schoolSettings = $result->fetch_assoc();

    if (
        !$schoolSettings ||
        $schoolSettings["school_latitude"] === null ||
        $schoolSettings["school_longitude"] === null
    ) {
        $error = "School GPS settings are missing.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teacher GPS Verification</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    margin: 0;
    padding: 20px;
    color: #1e293b;
}
.card {
    max-width: 480px;
    margin: 35px auto;
    padding: 24px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px #00000012;
}
button {
    width: 100%;
    padding: 13px;
    margin-top: 12px;
    background: #2563eb;
    color: white;
    border: 0;
    border-radius: 7px;
    font-size: 16px;
    cursor: pointer;
}
.message {
    padding: 12px;
    border-radius: 7px;
    background: #eff6ff;
}
.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 7px;
}
</style>
</head>
<body>
<div class="card">
    <h2>Teacher Attendance</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php else: ?>
        <p class="message">
            QR session verified. Check your location to continue.
        </p>

        <p id="status">Waiting for location permission…</p>
        <button type="button" id="gpsButton">Verify My Location</button>

        <script>
        const schoolLat = <?= json_encode((float)$schoolSettings["school_latitude"]) ?>;
        const schoolLon = <?= json_encode((float)$schoolSettings["school_longitude"]) ?>;
        const allowedRadius = <?= json_encode((int)$schoolSettings["allowed_radius"]) ?>;

        const statusBox = document.getElementById("status");
        const gpsButton = document.getElementById("gpsButton");

        function distanceMetres(lat1, lon1, lat2, lon2) {
            const radians = value => value * Math.PI / 180;
            const dLat = radians(lat2 - lat1);
            const dLon = radians(lon2 - lon1);

            const a =
                Math.sin(dLat / 2) ** 2 +
                Math.cos(radians(lat1)) *
                Math.cos(radians(lat2)) *
                Math.sin(dLon / 2) ** 2;

            return 6371000 * 2 * Math.atan2(
                Math.sqrt(a),
                Math.sqrt(1 - a)
            );
        }

        gpsButton.addEventListener("click", function () {
            if (!navigator.geolocation) {
                statusBox.textContent =
                    "This browser does not support GPS location.";
                return;
            }

            statusBox.textContent =
                "Requesting location permission…";

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const distance = distanceMetres(
                        position.coords.latitude,
                        position.coords.longitude,
                        schoolLat,
                        schoolLon
                    );

                    if (distance <= allowedRadius) {
                        statusBox.textContent =
                            "Location check passed (" +
                            Math.round(distance) +
                            " metres from the school).";
                    } else {
                        statusBox.textContent =
                            "You are approximately " +
                            Math.round(distance) +
                            " metres from the school. You are outside the allowed radius.";
                    }
                },
                function (error) {
                    statusBox.textContent =
                        "Could not get your location. Allow location access and try again.";
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
        </script>
    <?php endif; ?>
</div>
</body>
</html>
