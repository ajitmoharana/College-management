<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "
  SELECT s.*, d.name as dept_name
  FROM students s
  LEFT JOIN departments d ON s.department_id = d.id
  WHERE s.user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) { header("Location: ../login.php"); exit(); }
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
    <div class="sidebar-header"><div class="logo-icon">🎓</div><div><h3>CollegeMS</h3><span>Student Portal</span></div></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Attendance</a>
      <a href="grades.php" class="nav-link"><span class="nav-icon">📝</span> Grades</a>
      <a href="notices.php" class="nav-link"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php" class="nav-link active"><span class="nav-icon">👤</span> Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1))) ?></div>
        <div>
          <div class="user-name"><?= e($_SESSION['user_name'] ?? 'Student') ?></div>
          <div class="user-role"><?= e($student['student_id']) ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title"><h1>Profile</h1><p>Your student information</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><h3>👤 Student Details</h3></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group"><label>Student ID</label><input value="<?= e($student['student_id']) ?>" readonly></div>
            <div class="form-group"><label>Name</label><input value="<?= e($student['name']) ?>" readonly></div>
            <div class="form-group"><label>Email</label><input value="<?= e($student['email']) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= e($student['phone'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= e($student['dept_name'] ?? '') ?>" readonly></div>
            <div class="form-group"><label>Semester</label><input value="<?= (int)$student['semester'] ?>" readonly></div>
            <div class="form-group"><label>Status</label><input value="<?= e($student['status']) ?>" readonly></div>
            <div class="form-group"><label>Admission Year</label><input value="<?= e($student['admission_year']) ?>" readonly></div>
            <div class="form-group full-width"><label>Address</label><textarea rows="3" readonly><?= e($student['address'] ?? '') ?></textarea></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>