// admin/students.php ✅ UPDATED (CSRF + fixed delete + fixed toggle + safe ID generation + transaction)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

/* ===== HANDLE ADD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name     = sanitize($conn, $_POST['name']);
    $email    = sanitize($conn, $_POST['email']);
    $phone    = sanitize($conn, $_POST['phone']);
    $dob      = sanitize($conn, $_POST['dob']);
    $gender   = sanitize($conn, $_POST['gender']);
    $address  = sanitize($conn, $_POST['address']);
    $dept_id  = (int)($_POST['department_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 1);
    $adm_year = (int)($_POST['admission_year'] ?? (int)date('Y'));
    $password = (string)($_POST['password'] ?? '');

    // Generate student_id using MAX() to avoid COUNT() duplicates
    $res  = mysqli_query($conn, "SELECT COALESCE(MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)),0) + 1 AS next_num FROM students");
    $next = (int)(mysqli_fetch_assoc($res)['next_num'] ?? 1);
    $student_id = 'STU' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);

    mysqli_begin_transaction($conn);
    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);
        mysqli_stmt_execute($stmt);

        $user_id = mysqli_insert_id($conn);

        $stmt2 = mysqli_prepare($conn, "
            INSERT INTO students
            (user_id, student_id, name, email, phone, dob, gender, address, department_id, semester, admission_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt2,
            "isssssssiis",
            $user_id,
            $student_id,
            $name,
            $email,
            $phone,
            $dob,
            $gender,
            $address,
            $dept_id,
            $semester,
            $adm_year
        );
        mysqli_stmt_execute($stmt2);

        mysqli_commit($conn);
        $success = "Student <strong>$name</strong> added successfully! (ID: $student_id)";
        $action = 'list';
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = "Error saving student: " . $e->getMessage();
    }
}

/* ===== HANDLE DELETE (POST + CSRF) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $del_id = (int)$_POST['delete_id'];

        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM students WHERE id=$del_id"));
        $uid = (int)($row['user_id'] ?? 0);

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "DELETE FROM students WHERE id=$del_id");
            if ($uid > 0) {
                mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
            }
            mysqli_commit($conn);
            $success = "Student deleted successfully.";
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = "Delete failed: " . $e->getMessage();
        }
    }
}

/* ===== HANDLE STATUS TOGGLE (POST + CSRF) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $tid = (int)$_POST['toggle_id'];
        $curRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM students WHERE id=$tid"));
        $cur = $curRow['status'] ?? 'Active';
        $new = ($cur === 'Active') ? 'Inactive' : 'Active';

        mysqli_query($conn, "UPDATE students SET status='$new' WHERE id=$tid");
        $success = "Status updated.";
    }
}

/* ===== Fetch departments for dropdown ===== */
$departments = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name");
$depts_arr = [];
while ($d = mysqli_fetch_assoc($departments)) $depts_arr[] = $d;

/* ===== Fetch all students ===== */
$search = sanitize($conn, $_GET['search'] ?? '');
$where  = $search ? "WHERE s.name LIKE '%$search%' OR s.student_id LIKE '%$search%' OR s.email LIKE '%$search%'" : '';

$students = mysqli_query($conn, "
    SELECT s.*, d.name as dept_name
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    $where
    ORDER BY s.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Students – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title">
                    <h1>Students</h1>
                    <p>Manage student records</p>
                </div>
            </div>
            <div class="topbar-right">
                <a href="?action=add" class="btn btn-primary btn-sm">+ Add Student</a>
            </div>
        </header>

        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>➕ Add New Student</h3>
                    <a href="students.php" class="btn btn-secondary btn-sm">← Back to List</a>
                </div>
                <div class="card-body">
                    <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required placeholder="student@email.com">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" placeholder="+1 234 567 8900">
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="">Select</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Department *</label>
                                <select name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($depts_arr as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester">
                                    <?php for ($i=1; $i<=8; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Admission Year</label>
                                <input type="number" name="admission_year" value="<?= date('Y') ?>" min="2000" max="2035">
                            </div>
                            <div class="form-group">
                                <label>Login Password *</label>
                                <input type="password" name="password" required placeholder="Set login password">
                            </div>
                            <div class="form-group full-width">
                                <label>Address</label>
                                <textarea name="address" rows="3" placeholder="Enter address..."></textarea>
                            </div>
                        </div>

                        <div style="margin-top:10px; display:flex; gap:12px;">
                            <button type="submit" name="add_student" class="btn btn-primary" style="width:auto; padding:13px 30px;">✓ Add Student</button>
                            <a href="students.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3>👨‍🎓 All Students</h3>
                    <form method="GET" style="display:flex; gap:8px;">
                        <input type="text" name="search" placeholder="Search students..."
                               value="<?= htmlspecialchars($search) ?>"
                               style="background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:8px; padding:8px 14px; color:var(--text); font-size:14px; outline:none;">
                        <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>#</th><th>Student ID</th><th>Name</th><th>Email</th><th>Department</th>
                                <th>Sem</th><th>Adm Year</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1; while ($s = mysqli_fetch_assoc($students)): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><code style="color:var(--accent)"><?= htmlspecialchars($s['student_id']) ?></code></td>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td style="color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['dept_name'] ?? 'N/A') ?></td>
                                <td><?= (int)$s['semester'] ?></td>
                                <td><?= htmlspecialchars($s['admission_year']) ?></td>
                                <td>
                                    <span class="chip <?= $s['status'] === 'Active' ? 'chip-success' : ($s['status'] === 'Graduated' ? 'chip-info' : 'chip-warning') ?>">
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="toggle_id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="btn btn-warning btn-sm" title="Toggle Status">⇄</button>
                                        </form>

                                        <a href="#" onclick="confirmDelete(<?= (int)$s['id'] ?>, 'students.php')" class="btn btn-danger btn-sm" title="Delete">🗑</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../js/main.js"></script>
</body>
</html>