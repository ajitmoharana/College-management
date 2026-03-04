// admin/courses.php ✅ UPDATED (CSRF delete + faculty_id NULL handling + safer ordering)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

/* ===== HANDLE ADD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $code    = sanitize($conn, $_POST['course_code']);
    $name    = sanitize($conn, $_POST['course_name']);
    $dept_id = (int)($_POST['department_id'] ?? 0);

    // faculty is optional -> store NULL if empty
    $fac_raw = trim((string)($_POST['faculty_id'] ?? ''));
    $fac_id  = ($fac_raw === '') ? null : (int)$fac_raw;

    $sem     = (int)($_POST['semester'] ?? 1);
    $credits = (int)($_POST['credits'] ?? 3);

    // Build statement depending on whether faculty_id is NULL
    if ($fac_id === null) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO courses (course_code, course_name, department_id, faculty_id, semester, credits)
            VALUES (?, ?, ?, NULL, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "ssiii", $code, $name, $dept_id, $sem, $credits);
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO courses (course_code, course_name, department_id, faculty_id, semester, credits)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "ssiiii", $code, $name, $dept_id, $fac_id, $sem, $credits);
    }

    try {
        mysqli_stmt_execute($stmt);
        $success = "Course <strong>$name</strong> added!";
        $action = 'list';
    } catch (Throwable $e) {
        $error = "Error: " . $e->getMessage();
    }
}

/* ===== HANDLE DELETE (POST + CSRF) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $del_id = (int)$_POST['delete_id'];
        try {
            mysqli_query($conn, "DELETE FROM courses WHERE id=$del_id");
            $success = "Course deleted.";
        } catch (Throwable $e) {
            $error = "Delete failed: " . $e->getMessage();
        }
    }
}

/* ===== Dropdown data ===== */
$departments = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name");
$depts_arr = [];
while ($d = mysqli_fetch_assoc($departments)) $depts_arr[] = $d;

$faculty_list = mysqli_query($conn, "SELECT id, name FROM faculty WHERE status='Active' ORDER BY name");
$fac_arr = [];
while ($f = mysqli_fetch_assoc($faculty_list)) $fac_arr[] = $f;

/* ===== Courses list ===== */
$courses = mysqli_query($conn, "
    SELECT c.*, d.name as dept_name, f.name as faculty_name
    FROM courses c
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN faculty f ON c.faculty_id = f.id
    ORDER BY c.created_at DESC, c.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Courses – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title"><h1>Courses</h1><p>Manage academic courses</p></div>
            </div>
            <div class="topbar-right">
                <a href="?action=add" class="btn btn-primary btn-sm">+ Add Course</a>
            </div>
        </header>
        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>➕ Add New Course</h3>
                    <a href="courses.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
                <div class="card-body">
                    <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Course Code *</label>
                                <input type="text" name="course_code" required placeholder="e.g. CS101">
                            </div>
                            <div class="form-group">
                                <label>Course Name *</label>
                                <input type="text" name="course_name" required placeholder="e.g. Introduction to Programming">
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
                                <label>Assigned Faculty</label>
                                <select name="faculty_id">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($fac_arr as $f): ?>
                                    <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
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
                                <label>Credits</label>
                                <select name="credits">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3" selected>3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:12px;">
                            <button type="submit" name="add_course" class="btn btn-primary" style="width:auto; padding:13px 30px;">✓ Add Course</button>
                            <a href="courses.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <div class="card-header"><h3>📚 All Courses</h3></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th><th>Code</th><th>Course Name</th><th>Department</th>
                                <th>Faculty</th><th>Sem</th><th>Credits</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1; while ($c = mysqli_fetch_assoc($courses)): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><span class="chip chip-purple"><?= htmlspecialchars($c['course_code']) ?></span></td>
                                <td><strong><?= htmlspecialchars($c['course_name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['dept_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($c['faculty_name'] ?? 'Unassigned') ?></td>
                                <td><?= (int)$c['semester'] ?></td>
                                <td><span class="chip chip-info"><?= (int)$c['credits'] ?> cr</span></td>
                                <td>
                                    <a href="#" onclick="confirmDelete(<?= (int)$c['id'] ?>, 'courses.php')" class="btn btn-danger btn-sm">🗑</a>
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