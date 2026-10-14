<?php

session_start();

if (!isset($_SESSION["admin_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

$message = "";
$error = "";

/* ADD TEACHER */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_teacher"])) {

    $employee_id = trim($_POST["employee_id"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (
        $employee_id === "" ||
        $full_name === "" ||
        $username === "" ||
        $password === ""
    ) {

        $error = "Please fill all required fields.";

    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "teacher";

        $conn->begin_transaction();

        try {

            /* Create user account */
            $sql = "INSERT INTO users
                    (username, password, role, status)
                    VALUES (?, ?, ?, 1)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sss",
                $username,
                $hashed_password,
                $role
            );

            $stmt->execute();

            $user_id = $conn->insert_id;

            $stmt->close();

            /* Create teacher */
            $sql = "INSERT INTO teachers
                    (user_id, employee_id, full_name, email, phone, department)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "isssss",
                $user_id,
                $employee_id,
                $full_name,
                $email,
                $phone,
                $department
            );

            $stmt->execute();

            $stmt->close();

            $conn->commit();

            $message = "Teacher added successfully!";

        } catch (Exception $e) {

            $conn->rollback();

            $error = "Unable to add teacher. Username or Employee ID may already exist.";
        }
    }
}


/* DELETE / DISABLE TEACHER */
if (isset($_GET["disable"])) {

    $teacher_id = intval($_GET["disable"]);

    $sql = "SELECT user_id FROM teachers WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $teacher = $result->fetch_assoc();

        $user_id = $teacher["user_id"];

        $stmt->close();

        $sql = "UPDATE users SET status = 0 WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $stmt->close();

        $message = "Teacher account disabled.";

    } else {

        $stmt->close();

        $error = "Teacher not found.";
    }
}


/* GET TEACHERS */
$sql = "SELECT
            teachers.id,
            teachers.employee_id,
            teachers.full_name,
            teachers.email,
            teachers.phone,
            teachers.department,
            users.username,
            users.status
        FROM teachers
        INNER JOIN users
        ON teachers.user_id = users.id
        ORDER BY teachers.id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Teacher Management</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

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

    height: 70px;

    background: #0f172a;

    color: white;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;
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

    font-weight: 800;

    font-size: 20px;
}

.back {

    color: white;

    text-decoration: none;

    background: #334155;

    padding: 9px 15px;

    border-radius: 8px;

    font-size: 13px;
}

.container {

    max-width: 1200px;

    margin: 35px auto;

    padding: 0 20px;
}

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.header h1 {

    font-size: 28px;
}

.header p {

    color: #64748b;

    font-size: 14px;

    margin-top: 5px;
}

.form-card {

    background: white;

    padding: 25px;

    border-radius: 16px;

    box-shadow: 0 5px 20px rgba(15,23,42,0.06);

    margin-bottom: 30px;
}

.form-card h2 {

    font-size: 20px;

    margin-bottom: 20px;
}

.form-grid {

    display: grid;

    grid-template-columns: repeat(2, 1fr);

    gap: 18px;
}

.input-group label {

    display: block;

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 7px;
}

.input-group input {

    width: 100%;

    padding: 12px;

    border: 1px solid #cbd5e1;

    border-radius: 8px;

    outline: none;

    font-size: 14px;
}

.input-group input:focus {

    border-color: #2563eb;

    box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
}

.add-btn {

    margin-top: 20px;

    padding: 12px 20px;

    border: none;

    border-radius: 8px;

    background: #2563eb;

    color: white;

    font-weight: 700;

    cursor: pointer;
}

.add-btn:hover {

    background: #1d4ed8;
}

.message {

    background: #dcfce7;

    color: #166534;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
}

.error {

    background: #fee2e2;

    color: #b91c1c;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 14px;
}

.table-card {

    background: white;

    padding: 25px;

    border-radius: 16px;

    box-shadow: 0 5px 20px rgba(15,23,42,0.06);

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;
}

th {

    text-align: left;

    background: #f8fafc;

    padding: 13px;

    font-size: 12px;

    color: #475569;
}

td {

    padding: 13px;

    border-top: 1px solid #e2e8f0;

    font-size: 13px;
}

.active {

    color: #15803d;

    font-weight: 700;
}

.disabled {

    color: #dc2626;

    font-weight: 700;
}

.disable-btn {

    color: white;

    background: #dc2626;

    text-decoration: none;

    padding: 7px 10px;

    border-radius: 6px;

    font-size: 12px;
}

@media(max-width:700px) {

    .form-grid {

        grid-template-columns: 1fr;
    }

    .header {

        display: block;
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

    <a class="back" href="admin_dashboard.php">
        ← Dashboard
    </a>

</div>


<div class="container">


    <div class="header">

        <div>

            <h1>Teacher Management</h1>

            <p>
                Add and manage teacher accounts.
            </p>

        </div>

    </div>


    <?php if ($message != ""): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($error != ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- ADD TEACHER -->

    <div class="form-card">

        <h2>Add New Teacher</h2>

        <form method="POST">

            <div class="form-grid">


                <div class="input-group">

                    <label>
                        Employee ID *
                    </label>

                    <input
                        type="text"
                        name="employee_id"
                        placeholder="Example: EMP001"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Teacher full name"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="teacher@example.com"
                    >

                </div>


                <div class="input-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="Phone number"
                    >

                </div>


                <div class="input-group">

                    <label>
                        Department
                    </label>

                    <input
                        type="text"
                        name="department"
                        placeholder="Example: Mathematics"
                    >

                </div>


                <div class="input-group">

                    <label>
                        Teacher Username *
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Login username"
                        required
                    >

                </div>


                <div class="input-group">

                    <label>
                        Teacher Password *
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Login password"
                        required
                    >

                </div>


            </div>


            <button
                class="add-btn"
                type="submit"
                name="add_teacher"
            >
                + Add Teacher
            </button>

        </form>

    </div>


    <!-- TEACHER LIST -->

    <div class="table-card">

        <h2 style="margin-bottom:20px;">
            Teacher List
        </h2>

        <table>

            <thead>

                <tr>

                    <th>Employee ID</th>

                    <th>Name</th>

                    <th>Username</th>

                    <th>Email</th>

                    <th>Phone</th>

                    <th>Department</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($teacher = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($teacher["employee_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($teacher["full_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($teacher["username"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($teacher["email"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($teacher["phone"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($teacher["department"]); ?>
                        </td>

                        <td>

                            <?php if ($teacher["status"] == 1): ?>

                                <span class="active">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="disabled">
                                    Disabled
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($teacher["status"] == 1): ?>

                                <a
                                    class="disable-btn"
                                    href="teachers.php?disable=<?php echo $teacher["id"]; ?>"
                                    onclick="return confirm('Disable this teacher account?');"
                                >
                                    Disable
                                </a>

                            <?php else: ?>

                                <span class="disabled">
                                    Disabled
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="8" style="text-align:center;">
                        No teachers added yet.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


</div>

</body>

</html>