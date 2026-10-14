```php
<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

require_once __DIR__ . "/config/database.php";

$error = "";
$success = "";
$sessionInfo = null;
$schoolSettings = null;

/* 1. Require teacher login */
if (
    !isset($_SESSION["teacher_user_id"]) ||
    ($_SESSION["role"] ?? "") !== "teacher"
) {
    $_SESSION["attendance_message"] =
        "Please log in using your teacher account.";

    $incomingToken = $_POST["token"] ?? $_GET["token"] ?? "";

    if (
        is_string($incomingToken) &&
        preg_match('/^[a-f0-9]{64}$/', $incomingToken)
    ) {
        $_SESSION["pending_attendance_token"] = $incomingToken;
    }

    header("Location: teacher_login.php");
    exit;
}

/* 2. Get QR token */
$token = $_POST["token"] ?? $_GET["token"] ?? "";

if (
    !is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)
) {
    $error = "Invalid QR code. Please scan the current QR code.";
}

/* 3. Validate that the admin's session is active */
if ($error === "") {
    $stmt = $conn->prepare(
        "SELECT id, status
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
        $error = "This attendance session is closed by the administrator.";
    }
}

/* 4. Load school GPS settings */
if ($error === "") {
    $result = $conn->query(
        "SELECT school_latitude, school_longitude, allowed_radius
         FROM settings
         ORDER BY id ASC
         LIMIT 1"
    );

    if ($result) {
        $schoolSettings = $result->fetch_assoc();
    }

    if (
        !$schoolSettings ||
        $schoolSettings["school_latitude"] === null ||
        $schoolSettings["school_longitude"] === null
    ) {
        $error = "School GPS settings are missing.";
    }
}

/* 5. Process GPS coordinates submitted by the browser */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $error === "") {
    $latInput = $_POST["latitude"] ?? "";
    $lonInput = $_POST["longitude"] ?? "";

    if (
        !is_numeric($latInput) ||
        !is_numeric($lonInput) ||
        !is_finite((float)$latInput) ||
        !is_finite((float)$lonInput)
    ) {
        $error = "Invalid GPS coordinates. Please verify your location again.";
    } else {
        $latitude = (float)$latInput;
        $longitude = (float)$lonInput;

        if (
            $latitude < -90 || $latitude > 90 ||
            $longitude < -180 || $longitude > 180
        ) {
            $error = "GPS coordinates are outside the valid range.";
        }
    }

    /* Calculate distance on the server */
    if ($error === "") {
        $schoolLat = (float)$schoolSettings["school_latitude"];
        $schoolLon = (float)$schoolSettings["school_longitude"];
        $allowedRadius = max(0, (int)$schoolSettings["allowed_radius"]);

        $earthRadius = 6371000;
        $dLat = deg2rad($schoolLat - $latitude);
        $dLon = deg2rad($schoolLon - $longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitude))
            * cos(deg2rad($schoolLat))
            * sin($dLon / 2) ** 2;

        $a = min(1, max(0, $a));

        $distance = $earthRadius * 2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

        if ($distance > $allowedRadius) {
            $error =
                "You are approximately " . round($distance) .
                " metres from the school. You are outside the allowed radius.";
        }
    }

    /* 6. Find teacher ID belonging to the logged-in user */
    if ($error === "") {
        $userId = (int)$_SESSION["teacher_user_id"];

        $stmt = $conn->prepare(
            "SELECT id
             FROM teachers
             WHERE user_id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $teacher = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$teacher) {
            $error =
                "Your teacher profile was not found. Contact the administrator.";
        }
    }

    /* 7. Save attendance once per day */
    if ($error === "") {
        $teacherId = (int)$teacher["id"];
        $sessionId = (int)$sessionInfo["id"];
        $attendanceDate = date("Y-m-d");
        $checkInTime = date("Y-m-d H:i:s");

        $attendanceStatus = "Present";

        $stmt = $conn->prepare(
            "INSERT INTO attendance
                (teacher_id, session_id, attendance_date,
                 check_in_time, status, latitude, longitude)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iisssdd",
            $teacherId,
            $sessionId,
            $attendanceDate,
            $checkInTime,
            $attendanceStatus,
            $latitude,
            $longitude
        );

        if ($stmt->execute()) {
            $success = "Attendance marked successfully as Present!";
            $token = "";
        } elseif ($stmt->errno === 1062) {
            $error = "Your attendance has already been recorded for today.";
        } else {
            $error =
                "Could not save attendance. Please contact the administrator.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teacher Attendance</title>

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
button:disabled {
    opacity: 0.6;
    cursor: wait;
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
.success {
    background: #dcfce7;
    color: #166534;
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

    <?php elseif ($success !== ""): ?>

        <p class="success">
            <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
        </p>

        <a href="teacher_dashboard.php">Back to Teacher Dashboard</a>

    <?php else: ?>

        <p class="message">
            The admin's attendance session is active.
            Verify your location to mark attendance.
        </p>

        <p id="status">Press the button below to check your location.</p>

        <form id="attendanceForm" method="POST"
              action="mark_attendance.php">

            <input type="hidden" name="token"
                value="<?= htmlspecialchars($token, ENT_QUOTES, "UTF-8") ?>">

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <button type="button" id="gpsButton">
                Verify My Location
            </button>
        </form>

        <script>
        const schoolLat =
            <?= json_encode((float)$schoolSettings["school_latitude"]) ?>;
        const schoolLon =
            <?= json_encode((float)$schoolSettings["school_longitude"]) ?>;
        const allowedRadius =
            <?= json_encode((int)$schoolSettings["allowed_radius"]) ?>;

        const statusBox = document.getElementById("status");
        const gpsButton = document.getElementById("gpsButton");
        const attendanceForm = document.getElementById("attendanceForm");

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
                Math.sqrt(Math.max(0, 1 - a))
            );
        }

        gpsButton.addEventListener("click", function () {
            if (!navigator.geolocation) {
                statusBox.textContent =
                    "This browser does not support GPS location.";
                return;
            }

            gpsButton.disabled = true;
            statusBox.textContent = "Requesting location permission…";

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;

                    const distance = distanceMetres(
                        latitude,
                        longitude,
                        schoolLat,
                        schoolLon
                    );

                    if (distance > allowedRadius) {
                        statusBox.textContent =
                            "You are approximately " +
                            Math.round(distance) +
                            " metres from the school. You are outside the allowed radius.";

                        gpsButton.disabled = false;
                        return;
                    }

                    document.getElementById("latitude").value = latitude;
                    document.getElementById("longitude").value = longitude;

                    statusBox.textContent =
                        "Location check passed. Saving attendance…";

                    attendanceForm.submit();
                },
                function () {
                    statusBox.textContent =
                        "Could not get your location. Allow location access and try again.";
                    gpsButton.disabled = false;
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
```