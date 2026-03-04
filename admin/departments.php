// admin/departments.php ✅ UPDATED (CSRF delete + better error handling)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';

/* ===== ADD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dept'])) {
    $name      = sanitize($conn, $_POST['name']);
    $code      = sanitize($conn, strtoupper($_POST['code']));
    $head_name = sanitize($conn, $_POST['head_name']);

    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO departments (name, code, head_name) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $code, $head_name);
        mysqli_stmt_execute($stmt);
        $success = "Department added!";
    } catch (Throwable $e) {
        $error = "Error (maybe duplicate code): " . $e->getMessage();
    }
}

/* ===== DELETE (POST + CSRF) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $del_id = (int)$_POST['delete_id'];
        try {
            mysqli_query($conn, "DELETE FROM departments WHERE id=$del_id");
            $success = "Department deleted.";
        } catch (Throwable $e) {
            // FK constraints can cause failure if you change schema to RESTRICT in future
            $error = "Delete failed: " . $e->getMessage();
        }
    }
}

/* ===== LIST ===== */
$departments = mysqli_query($conn, "
    SELECT d.*,
           COUNT(DISTINCT s.id) as student_count,
           COUNT(DISTINCT f.id) as faculty_count,
           COUNT(DISTINCT c.id) as course_count
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.id
    LEFT JOIN faculty  f ON f.department_id = d.id
    LEFT JOIN courses  c ON c.department_id = d.id
    GROUP BY d.id
    ORDER BY d.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Departments – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title"><h1>Departments</h1><p>Manage academic departments</p></div>
            </div>
        </header>

        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px; align-items:start;">
                <!-- ADD FORM -->
                <div class="card">
                    <div class="card-header"><h3>🏛️ Add Department</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Department Name *</label>
                                <input type="text" name="name" required placeholder="e.g. Computer Science">
                            </div>
                            <div class="form-group">
                                <label>Code *</label>
                                <input type="text" name="code" required placeholder="e.g. CS" maxlength="10">
                            </div>
                            <div class="form-group">
                                <label>HOD Name</label>
                                <input type="text" name="head_name" placeholder="Head of Department">
                            </div>
                            <button type="submit" name="add_dept" class="btn btn-primary">+ Add Department</button>
                        </form>
                    </div>
                </div>

                <!-- LIST -->
                <div class="card">
                    <div class="card-header"><h3>All Departments</h3></div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr><th>Code</th><th>Name</th><th>HOD</th><th>Students</th><th>Faculty</th><th>Courses</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php while ($d = mysqli_fetch_assoc($departments)): ?>
                                <tr>
                                    <td><span class="chip chip-purple"><?= htmlspecialchars($d['code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($d['head_name'] ?? '—') ?></td>
                                    <td><span class="chip chip-info"><?= (int)$d['student_count'] ?></span></td>
                                    <td><span class="chip chip-success"><?= (int)$d['faculty_count'] ?></span></td>
                                    <td><span class="chip chip-warning"><?= (int)$d['course_count'] ?></span></td>
                                    <td>
                                        <a href="#" onclick="confirmDelete(<?= (int)$d['id'] ?>, 'departments.php')" class="btn btn-danger btn-sm">🗑</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>