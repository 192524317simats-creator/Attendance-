<?php

session_start();
require_once __DIR__ . '/config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT id, username, password, role
            FROM users
            WHERE username = ? AND role = 'admin' AND status = 1
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["admin_id"] = $user["id"];
            $_SESSION["admin_username"] = $user["username"];
            $_SESSION["role"] = "admin";

            header("Location: admin_dashboard.php");
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

    <title>Admin Login | Teacher Attendance</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(135deg, #0f172a, #1e3a8a, #2563eb);

            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);

            padding: 40px;

            border-radius: 22px;

            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.25);

            text-align: center;
        }

        .logo {
            width: 75px;
            height: 75px;

            margin: 0 auto 20px;

            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            background: #2563eb;

            color: white;

            font-size: 32px;
            font-weight: 800;
        }

        h1 {
            color: #0f172a;
            font-size: 25px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;

            font-size: 14px;
            font-weight: 600;

            color: #334155;

            margin-bottom: 8px;
        }

        .input-group input {
            width: 100%;

            padding: 14px 15px;

            border: 1px solid #cbd5e1;

            border-radius: 10px;

            font-size: 15px;

            outline: none;

            transition: 0.2s;
        }

        .input-group input:focus {
            border-color: #2563eb;

            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .login-btn {
            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 10px;

            background: #2563eb;

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;
        }

        .login-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }

        .error {
            background: #fee2e2;

            color: #b91c1c;

            padding: 12px;

            border-radius: 8px;

            font-size: 14px;

            margin-bottom: 20px;
        }

        .footer {
            margin-top: 25px;

            color: #94a3b8;

            font-size: 12px;
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="logo">
            S
        </div>

        <h1>Teacher Attendance</h1>

        <p class="subtitle">
            Administrator Login
        </p>

        <?php if ($error != ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="input-group">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    placeholder="Enter your username"
                    required
                >

            </div>

            <div class="input-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button class="login-btn" type="submit">
                Login to Dashboard
            </button>

        </form>

        <div class="footer">
            Teacher Attendance Management System
        </div>

    </div>

</div>

</body>

</html>