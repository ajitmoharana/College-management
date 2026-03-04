<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('faculty');

$user_id = (int)($_SESSION['user_id'] ?? 0);

// faculty record
$stmt = mysqli_prepare($conn, "SELECT id, name FROM faculty WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$faculty) { header("Location: ../login.php"); exit(); }
$faculty_pk = (int)$faculty['id'];

$stmt = mysqli_prepare($conn, "
  SELECT c.*, COUNT(e.id) AS enrolled_students
  FROM courses c
  LEFT JOIN enrollments e ON e.course_id = c.id
  WHERE c.faculty_id = ?
  GROUP BY c.id
  ORDER BY c.course_code
");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
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
    <div class="sidebar-header"><div class="logo-icon">🎓</div><div><h3>CollegeMS</h3><span>Faculty Portal</span></div></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="my_courses.php" class="nav-link active"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Mark Attendance</a>
      <a href="grades.php"     class="nav-link"><span class="nav-icon">📝</span> Enter Grades</a>
      <a href="students.php"   class="nav-link"><span class="nav-icon">👨‍🎓</span> My Students</a>
      <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'F', 0, 1)) ?></div>
        <div><div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Faculty') ?></div><div class="user-role">Faculty</div></div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title"><h1>My Courses</h1><p>Courses assigned to you</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><h3>📚 Courses</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Code</th><th>Course</th><th>Sem</th><th>Credits</th><th>Students</th></tr></thead>
            <tbody>
              <?php $has=false; while($c=mysqli_fetch_assoc($courses)) { $has=true; ?>
              <tr>
                <td><span class="chip chip-purple"><?= htmlspecialchars($c['course_code']) ?></span></td>
                <td><?= htmlspecialchars($c['course_name']) ?></td>
                <td><?= (int)$c['semester'] ?></td>
                <td><span class="chip chip-info"><?= (int)$c['credits'] ?> cr</span></td>
                <td><span class="chip chip-success"><?= (int)$c['enrolled_students'] ?></span></td>
              </tr>
              <?php } ?>
              <?php if(!$has): ?><tr><td colspan="5" style="color:var(--text-muted);">No courses assigned yet.</td></tr><?php endif; ?>
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