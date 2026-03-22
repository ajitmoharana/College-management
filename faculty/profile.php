<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('faculty');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "
  SELECT f.*, d.name AS dept_name
  FROM faculty f
  LEFT JOIN departments d ON f.department_id = d.id
  WHERE f.user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$faculty) { header("Location: ../login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile – CollegeMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-icon">🎓</div>
      <div><h3>CollegeMS</h3><span>Faculty Portal</span></div>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="my_courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Mark Attendance</a>
      <a href="grades.php"     class="nav-link"><span class="nav-icon">📝</span> Enter Grades</a>
      <a href="students.php"   class="nav-link"><span class="nav-icon">👨‍🎓</span> My Students</a>
      <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php"    class="nav-link active"><span class="nav-icon">👤</span> Profile</a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'F', 0, 1))) ?></div>
        <div>
          <div class="user-name"><?= e($_SESSION['user_name'] ?? 'Faculty') ?></div>
          <div class="user-role"><?= e($faculty['designation'] ?? 'Faculty') ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title">
          <h1>Profile</h1>
          <p>Your faculty information</p>
        </div>
      </div>
      <div class="topbar-right">
        <div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div>
      </div>
    </header>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><h3>👤 Faculty Details</h3></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group"><label>Faculty ID</label><input value="<?= e($faculty['faculty_id']) ?>" readonly></div>
            <div class="form-group"><label>Name</label><input value="<?= e($faculty['name']) ?>" readonly></div>
            <div class="form-group"><label>Email</label><input value="<?= e($faculty['email']) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= e($faculty['phone'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= e($faculty['dept_name'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Designation</label><input value="<?= e($faculty['designation'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Qualification</label><input value="<?= e($faculty['qualification'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Experience (Years)</label><input value="<?= (int)($faculty['experience'] ?? 0) ?>" readonly></div>
            <div class="form-group"><label>Status</label><input value="<?= e($faculty['status'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Created At</label><input value="<?= e($faculty['created_at'] ?? '') ?>" readonly></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/main.js"></script>
</body>
</html>