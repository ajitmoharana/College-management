<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF).';
    } else {
        $name          = sanitize($conn, $_POST['name'] ?? '');
        $email         = sanitize($conn, $_POST['email'] ?? '');
        $phone         = sanitize($conn, $_POST['phone'] ?? '');
        $dept_id       = (int)($_POST['department_id'] ?? 0);
        $designation   = sanitize($conn, $_POST['designation'] ?? '');
        $qualification = sanitize($conn, $_POST['qualification'] ?? '');
        $experience    = (int)($_POST['experience'] ?? 0);
        $password      = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $error = 'Name, email, and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif ($dept_id <= 0) {
            $error = 'Please select a department.';
        } elseif ($experience < 0) {
            $error = 'Experience cannot be negative.';
        } else {
            $checkDept = mysqli_prepare($conn, 'SELECT id FROM departments WHERE id=? LIMIT 1');
            mysqli_stmt_bind_param($checkDept, 'i', $dept_id);
            mysqli_stmt_execute($checkDept);
            $deptRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkDept));

            if (!$deptRow) {
                $error = 'Selected department does not exist.';
            } else {
                mysqli_begin_transaction($conn);
                try {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "faculty")');
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hashed);
                    mysqli_stmt_execute($stmt);

                    $user_id = mysqli_insert_id($conn);
                    $faculty_id = 'FAC' . str_pad((string)$user_id, 4, '0', STR_PAD_LEFT);

                    $stmt2 = mysqli_prepare($conn, "
                        INSERT INTO faculty
                        (user_id, faculty_id, name, email, phone, department_id, designation, qualification, experience)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    mysqli_stmt_bind_param(
                        $stmt2,
                        'issssissi',
                        $user_id,
                        $faculty_id,
                        $name,
                        $email,
                        $phone,
                        $dept_id,
                        $designation,
                        $qualification,
                        $experience
                    );
                    mysqli_stmt_execute($stmt2);

                    mysqli_commit($conn);
                    $success = "Faculty {$name} added successfully.";
                    $action = 'list';
                } catch (Throwable $e) {
                    mysqli_rollback($conn);
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF).';
    } else {
        $del_id = (int)$_POST['delete_id'];

        $stmt = mysqli_prepare($conn, 'SELECT user_id FROM faculty WHERE id=? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $del_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $uid = (int)($row['user_id'] ?? 0);

        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, 'DELETE FROM faculty WHERE id=?');
            mysqli_stmt_bind_param($stmt, 'i', $del_id);
            mysqli_stmt_execute($stmt);

            if ($uid > 0) {
                $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id=?');
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                mysqli_stmt_execute($stmt);
            }

            mysqli_commit($conn);
            $success = 'Faculty deleted.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = 'Delete failed: ' . $e->getMessage();
        }
    }
}

$departments = mysqli_query($conn, 'SELECT id, name FROM departments ORDER BY name');
$depts_arr = [];
while ($d = mysqli_fetch_assoc($departments)) $depts_arr[] = $d;

$search = trim((string)($_GET['search'] ?? ''));
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn, "
        SELECT f.*, d.name as dept_name
        FROM faculty f
        LEFT JOIN departments d ON f.department_id = d.id
        WHERE f.name LIKE ? OR f.faculty_id LIKE ?
        ORDER BY f.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $faculty_list = mysqli_stmt_get_result($stmt);
} else {
    $faculty_list = mysqli_query($conn, "
        SELECT f.*, d.name as dept_name
        FROM faculty f
        LEFT JOIN departments d ON f.department_id = d.id
        ORDER BY f.created_at DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Faculty – CollegeMS</title>
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
                    <h1>Faculty</h1>
                    <p>Manage faculty members</p>
                </div>
            </div>
            <div class="topbar-right">
                <a href="?action=add" class="btn btn-primary btn-sm">+ Add Faculty</a>
            </div>
        </header>

        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>➕ Add New Faculty</h3>
                    <a href="faculty.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
                <div class="card-body">
                    <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required placeholder="Dr. John Smith">
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" required placeholder="faculty@college.edu">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" placeholder="+1 234 567 8900">
                            </div>
                            <div class="form-group">
                                <label>Department *</label>
                                <select name="department_id" required>
                                    <option value="">Select</option>
                                    <?php foreach ($depts_arr as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" name="designation" placeholder="Professor / Lecturer">
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification" placeholder="PhD / MTech / etc.">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label>Login Password *</label>
                                <input type="password" name="password" required placeholder="Set password">
                            </div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:12px;">
                            <button type="submit" name="add_faculty" class="btn btn-primary" style="width:auto; padding:13px 30px;">✓ Add Faculty</button>
                            <a href="faculty.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3>👨‍🏫 All Faculty</h3>
                    <form method="GET" style="display:flex; gap:8px;">
                        <input type="text" name="search" placeholder="Search..."
                               value="<?= e($search) ?>"
                               style="background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:8px; padding:8px 14px; color:var(--text); font-size:14px; outline:none;">
                        <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th><th>Faculty ID</th><th>Name</th><th>Department</th>
                                <th>Designation</th><th>Qualification</th><th>Exp.</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1; while ($f = mysqli_fetch_assoc($faculty_list)): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><code style="color:var(--info)"><?= e($f['faculty_id']) ?></code></td>
                                <td><strong><?= e($f['name']) ?></strong></td>
                                <td><?= e($f['dept_name'] ?? 'N/A') ?></td>
                                <td><?= e($f['designation']) ?></td>
                                <td><?= e($f['qualification']) ?></td>
                                <td><?= (int)$f['experience'] ?> yrs</td>
                                <td>
                                    <span class="chip <?= $f['status'] === 'Active' ? 'chip-success' : 'chip-warning' ?>">
                                        <?= e($f['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="#" onclick="confirmDelete(<?= (int)$f['id'] ?>, 'faculty.php')" class="btn btn-danger btn-sm">🗑</a>
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