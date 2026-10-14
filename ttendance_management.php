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

require_once __DIR__ . "/config/database.php";

$sql = "SELECT
            a.id,
            t.employee_id,
            t.full_name,
            t.department,
            a.attendance_date,
            a.check_in_time,
            a.status
        FROM attendance a
        INNER JOIN teachers t ON a.teacher_id = t.id
        ORDER BY a.attendance_date DESC, a.check_in_time DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 8px;
        }

        .subtitle {
            color: #64748b;
            margin: 0;
        }

        .button {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            background: #2563eb;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, .06);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #dcfce7;
            color: #166534;
            font-weight: bold;
            font-size: 12px;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 600px) {
            body {
                padding: 14px;
            }

            .panel {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div>
            <h1>Attendance Management</h1>
            <p class="subtitle">
                View teacher attendance records saved in the database.
            </p>
        </div>

        <a class="button" href="admin_dashboard.php">
            Back to Dashboard
        </a>
    </div>

    <div class="panel">
        <?php if (!$result): ?>

            <p class="empty">
                Unable to load attendance records.
                Please check the database configuration.
            </p>

        <?php elseif ($result->num_rows === 0): ?>

            <p class="empty">No attendance records found yet.</p>

        <?php else: ?>

            <table>
                <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Teacher Name</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Check-in Time</th>
                    <th>Status</th>
                </tr>
                </thead>

                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($row["employee_id"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row["full_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row["department"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row["attendance_date"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row["check_in_time"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </td>
                        <td>
                            <span class="status">
                                <?= htmlspecialchars($row["status"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

</div>
</body>
</html>
```