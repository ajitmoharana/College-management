<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT id, student_id FROM students WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$student) { header("Location: ../login.php"); exit(); }
$student_pk = (int)$student['id'];

$stmt = mysqli_prepare($conn, "
  SELECT g.*, c.course_code, c.course_name
  FROM grades g
  JOIN courses c ON g.course_id = c.id
  WHERE g.student_id = ?
  ORDER BY g.created_at DESC, g.id DESC
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$grades = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Grades – CollegeMS</title>
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
      <a href="grades.php" class="nav-link active"><span class="nav-icon">📝</span> Grades</a>
      <a href="notices.php" class="nav-link"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php" class="nav-link"><span class="nav-icon">👤</span> Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?></div>
          <div class="user-role"><?= htmlspecialchars($student['student_id'] ?? '') ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title"><h1>Grades</h1><p>Your grade records</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><h3>📝 Grade Records</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Course</th><th>Exam</th><th>Marks</th><th>%</th><th>Grade</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php $has=false; while($g=mysqli_fetch_assoc($grades)) { $has=true;
              $pct = $g['total_marks']>0 ? round(($g['marks_obtained']/$g['total_marks'])*100) : 0;
              $chip = $pct>=70 ? 'chip-success' : ($pct>=50 ? 'chip-warning' : 'chip-danger');
            ?>
              <tr>
                <td><span class="chip chip-purple"><?= htmlspecialchars($g['course_code']) ?></span><br><small><?= htmlspecialchars($g['course_name']) ?></small></td>
                <td><span class="chip chip-info"><?= htmlspecialchars($g['exam_type']) ?></span></td>
                <td><?= htmlspecialchars($g['marks_obtained']) ?> / <?= htmlspecialchars($g['total_marks']) ?></td>
                <td><?= (int)$pct ?>%</td>
                <td><span class="chip <?= $chip ?>" style="font-weight:800;"><?= htmlspecialchars($g['grade']) ?></span></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($g['remarks'] ?: '—') ?></td>
              </tr>
            <?php } ?>
            <?php if(!$has): ?>
              <tr><td colspan="6" style="color:var(--text-muted);">No grades found yet.</td></tr>
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