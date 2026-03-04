// admin/faculty.php ✅ UPDATED (CSRF delete + safe ID generation + transaction)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

/* ===== HANDLE ADD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    $name          = sanitize($conn, $_POST['name']);
    $email         = sanitize($conn, $_POST['email']);
    $phone         = sanitize($conn, $_POST['phone']);
    $dept_id       = (int)($_POST['department_id'] ?? 0);
    $designation   = sanitize($conn, $_POST['designation']);
    $qualification = sanitize($conn, $_POST['qualification']);
    $experience    = (int)($_POST['experience'] ?? 0);
    $password      = (string)($_POST['password'] ?? '');

    // Generate faculty_id using MAX() to avoid COUNT() duplicates
    $res  = mysqli_query($conn, "SELECT COALESCE(MAX(CAST(SUBSTRING(faculty_id, 4) AS UNSIGNED)),0) + 1 AS next_num FROM faculty");
    $next = (int)(mysqli_fetch_assoc($res)['next_num'] ?? 1);
    $faculty_id = 'FAC' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);

    mysqli_begin_transaction($conn);
    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'faculty')");
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);
        mysqli_stmt_execute($stmt);

        $user_id = mysqli_insert_id($conn);

        $stmt2 = mysqli_prepare($conn, "
            INSERT INTO faculty
            (user_id, faculty_id, name, email, phone, department_id, designation, qualification, experience)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt2,
            "issssissi",
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
        $success = "Faculty <strong>$name</strong> added successfully!";
        $action = 'list';
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $error = "Error: " . $e->getMessage();
    }
}

/* ===== HANDLE DELETE (POST + CSRF) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $del_id = (int)$_POST['delete_id'];

        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM faculty WHERE id=$del_id"));
        $uid = (int)($row['user_id'] ?? 0);

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "DELETE FROM faculty WHERE id=$del_id");
            if ($uid > 0) {
                mysqli_query($conn, "DELETE FROM users WHERE id=$uid");
            }
            mysqli_commit($conn);
            $success = "Faculty deleted.";
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = "Delete failed: " . $e->getMessage();
        }
    }
}

/* ===== Dropdowns ===== */
$departments = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name");
$depts_arr = [];
while ($d = mysqli_fetch_assoc($departments)) $depts_arr[] = $d;

/* ===== List ===== */
$search = sanitize($conn, $_GET['search'] ?? '');
$where  = $search ? "WHERE f.name LIKE '%$search%' OR f.faculty_id LIKE '%$search%'" : '';

$faculty_list = mysqli_query($conn, "
    SELECT f.*, d.name as dept_name
    FROM faculty f
    LEFT JOIN departments d ON f.department_id = d.id
    $where
    ORDER BY f.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
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
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>➕ Add New Faculty</h3>
                    <a href="faculty.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
                <div class="card-body">
                    <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
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
                                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
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
                               value="<?= htmlspecialchars($search) ?>"
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
                                <td><code style="color:var(--info)"><?= htmlspecialchars($f['faculty_id']) ?></code></td>
                                <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
                                <td><?= htmlspecialchars($f['dept_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($f['designation']) ?></td>
                                <td><?= htmlspecialchars($f['qualification']) ?></td>
                                <td><?= (int)$f['experience'] ?> yrs</td>
                                <td>
                                    <span class="chip <?= $f['status'] === 'Active' ? 'chip-success' : 'chip-warning' ?>">
                                        <?= htmlspecialchars($f['status']) ?>
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