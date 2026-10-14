<?php

session_start();

if (!isset($_SESSION["teacher_user_id"]) || $_SESSION["role"] !== "teacher") {
    header("Location: teacher_login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Teacher Dashboard</title>

<style>

body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f4f7fb;
}

.header {
    background: #2563eb;
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h2 {
    margin: 0;
}

.logout {
    color: white;
    text-decoration: none;
    background: #dc2626;
    padding: 10px 16px;
    border-radius: 7px;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.card h2 {
    margin-bottom: 10px;
}

.card p {
    color: #666;
}

.scan-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 14px 25px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
}

</style>

</head>

<body>

<div class="header">

    <h2>Teacher Attendance</h2>

    <a href="teacher_logout.php" class="logout">Logout</a>

</div>

<div class="container">

    <div class="welcome">

        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["teacher_username"]); ?>!</h2>

        <p>You are successfully logged in as a teacher.</p>

    </div>

    <div class="card">

        <h2>Mark Attendance</h2>

        <p>Scan the attendance QR code to continue.</p>

        <a href="#" onclick="alert('Please scan the current attendance QR code provided by the school.'); return false;">Scan QR / Continue</a>
    </div>

</div>

</body>
</html>