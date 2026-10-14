```php
<?php
session_start();

if (
    !isset($_SESSION["admin_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$total_teachers = 0;
$present_today = 0;
$late_today = 0;

$today = date("Y-m-d");

// Total teachers
$result = $conn->query("SELECT COUNT(*) AS total FROM teachers");

if ($result) {
    $row = $result->fetch_assoc();
    $total_teachers = (int)$row["total"];
}

// Present today
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM attendance
     WHERE attendance_date = ? AND status = 'Present'"
);

if ($stmt) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $present_today = (int)$row["total"];
    $stmt->close();
}

// Late today
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM attendance
     WHERE attendance_date = ? AND status = 'Late'"
);

if ($stmt) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $late_today = (int)$row["total"];
    $stmt->close();
}

$absent_today = max(
    0,
    $total_teachers - ($present_today + $late_today)
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Teacher Attendance</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .navbar {
            min-height: 70px;
            background: #0f172a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 30px;
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
        }

        .logo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-name {
            font-size: 14px;
            color: #cbd5e1;
        }

        .logout {
            text-decoration: none;
            color: white;
            background: #dc2626;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .logout:hover {
            background: #b91c1c;
        }

        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h1 {
            font-size: 28px;
            margin-bottom: 6px;
        }

        .welcome p {
            color: #64748b;
            font-size: 14px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.06);
        }

        .card-title {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .card-number {
            font-size: 32px;
            font-weight: 800;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .menu-card {
            display: block;
            background: white;
            padding: 25px;
            border-radius: 16px;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0 5px 20px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.10);
        }

        .menu-card:focus-visible,
        .logout:focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 3px;
        }

        .menu-icon {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 17px;
            margin-bottom: 7px;
        }

        .menu-card p {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 12px 15px;
            }

            .brand {
                font-size: 16px;
            }

            .admin-name {
                display: none;
            }

            .cards,
            .menu-grid {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="brand">
            <div class="logo">S</div>
            <span>Teacher Attendance</span>
        </div>

        <div class="admin-info">
            <span class="admin-name">
                Admin:
                <?php
                echo htmlspecialchars(
                    $_SESSION["admin_username"] ?? "Admin",
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </span>

            <a class="logout" href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">

        <div class="welcome">
            <h1>Admin Dashboard</h1>
            <p>Manage teacher attendance and system activities.</p>
        </div>

        <div class="cards">

            <div class="card">
                <div class="card-title">TOTAL TEACHERS</div>
                <div class="card-number">
                    <?php echo $total_teachers; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">PRESENT TODAY</div>
                <div class="card-number">
                    <?php echo $present_today; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">LATE TODAY</div>
                <div class="card-number">
                    <?php echo $late_today; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">ABSENT TODAY</div>
                <div class="card-number">
                    <?php echo $absent_today; ?>
                </div>
            </div>

        </div>

        <div class="menu-grid">

            <a class="menu-card" href="teachers.php">
                <div class="menu-icon">👨‍🏫</div>
                <h3>Teacher Management</h3>
                <p>Add, edit and manage teacher accounts.</p>
            </a>

            <a class="menu-card" href="attendance_management.php">
                <div class="menu-icon">📋</div>
                <h3>Attendance Management</h3>
                <p>View and correct teacher attendance.</p>
            </a>

            <a class="menu-card" href="qr_attendance.php">
                <div class="menu-icon">📱</div>
                <h3>QR Attendance</h3>
                <p>Create and activate attendance QR sessions.</p>
            </a>

            <a class="menu-card" href="attendance_reports.php">
                <div class="menu-icon">📊</div>
                <h3>Attendance Reports</h3>
                <p>View and generate attendance reports.</p>
            </a>

            <a class="menu-card" href="attendance_settings.php">
                <div class="menu-icon">⚙️</div>
                <h3>Attendance Settings</h3>
                <p>Configure school location and attendance settings.</p>
            </a>

            <a class="menu-card" href="admin_settings.php">
                <div class="menu-icon">🔐</div>
                <h3>Admin Settings</h3>
                <p>Manage administrator account settings.</p>
            </a>

        </div>
    </div>

</body>
</html>
```