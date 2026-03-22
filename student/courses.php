<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT id, student_id, semester, department_id FROM students WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$student) { header("Location: ../login.php"); exit(); }

$student_pk = (int)$student['id'];

$stmt = mysqli_prepare($conn, "
    SELECT c.course_code, c.course_name, c.credits, c.semester, f.name AS faculty_name, d.name AS dept_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN faculty f ON c.faculty_id = f.id
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE e.student_id = ?
    ORDER BY c.course_code
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$courses = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Courses – CollegeMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-icon">🎓</div>
      <div><h3>CollegeMS</h3><span>Student Portal</span></div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="courses.php"    class="nav-link active"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Attendance</a>
      <a href="grades.php"     class="nav-link"><span class="nav-icon">📝</span> Grades</a>
      <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php"    class="nav-link"><span class="nav-icon">👤</span> Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1))) ?></div>
        <div>
          <div class="user-name"><?= e($_SESSION['user_name'] ?? 'Student') ?></div>
          <div class="user-role"><?= e($student['student_id'] ?? '') ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title"><h1>My Courses</h1><p>All enrolled courses</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><h3>📚 Enrolled Courses</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Code</th><th>Course</th><th>Dept</th><th>Sem</th><th>Credits</th><th>Faculty</th></tr></thead>
            <tbody>
              <?php $has=false; while($c=mysqli_fetch_assoc($courses)) { $has=true; ?>
              <tr>
                <td><span class="chip chip-purple"><?= e($c['course_code']) ?></span></td>
                <td><?= e($c['course_name']) ?></td>
                <td><?= e($c['dept_name'] ?? '—') ?></td>
                <td><?= (int)$c['semester'] ?></td>
                <td><?= (int)$c['credits'] ?></td>
                <td><?= e($c['faculty_name'] ?? '—') ?></td>
              </tr>
              <?php } ?>
              <?php if(!$has): ?>
                <tr><td colspan="6" style="color:var(--text-muted);">No courses enrolled yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>