<?php

session_start();
require_once __DIR__ . '/config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT id, username, password, role
            FROM users
            WHERE username = ? AND role = 'teacher' AND status = 1
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

    $_SESSION["teacher_user_id"] = $user["id"];
    $_SESSION["teacher_username"] = $user["username"];
    $_SESSION["role"] = "teacher";

    if (
        isset($_SESSION["pending_attendance_token"]) &&
        is_string($_SESSION["pending_attendance_token"]) &&
        preg_match('/^[a-f0-9]{64}$/', $_SESSION["pending_attendance_token"])
    ) {
        $token = $_SESSION["pending_attendance_token"];
        unset($_SESSION["pending_attendance_token"]);

        header(
            "Location: mark_attendance.php?token=" . urlencode($token)
        );
        exit;
    }

     header("Location: teacher_dashboard.php");
    exit;

        } else {
            $error = "Invalid username or password.";
        }

    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Teacher Login</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #2563eb, #1e40af);
}

.login-box {
    width: 380px;
    background: white;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

.logo {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 30px;
    font-weight: bold;
}

h2 {
    text-align: center;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #777;
    margin-bottom: 25px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 18px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
}

input:focus {
    outline: none;
    border-color: #2563eb;
}

button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #1d4ed8;
}

.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 18px;
    text-align: center;
}

</style>

</head>

<body>

<div class="login-box">

    <div class="logo">S</div>

    <h2>Teacher Login</h2>

    <p class="subtitle">Teacher Attendance System</p>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input
            type="text"
            name="username"
            placeholder="Enter username"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="Enter password"
            required
        >

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>