<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "
  SELECT id, student_id, name
  FROM students
  WHERE user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    header("Location: ../login.php");
    exit();
}

$student_pk = (int)$student['id'];

$stmt = mysqli_prepare($conn, "
  SELECT 
    c.course_code,
    c.course_name,
    SUM(a.status = 'Present') AS present_count,
    SUM(a.status = 'Absent') AS absent_count,
    SUM(a.status = 'Late') AS late_count,
    COUNT(a.id) AS total_count
  FROM enrollments e
  JOIN courses c ON e.course_id = c.id
  LEFT JOIN attendance a 
    ON a.course_id = c.id 
   AND a.student_id = e.student_id
  WHERE e.student_id = ?
  GROUP BY c.id, c.course_code, c.course_name
  ORDER BY c.course_code
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$attendance = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conn, "
  SELECT 
    a.date,
    a.status,
    c.course_code,
    c.course_name
  FROM attendance a
  JOIN courses c ON a.course_id = c.id
  WHERE a.student_id = ?
  ORDER BY a.date DESC, a.id DESC
  LIMIT 30
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance – CollegeMS</title>
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
      <a href="dashboard.php" class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link active"><span class="nav-icon">✅</span> Attendance</a>
      <a href="grades.php" class="nav-link"><span class="nav-icon">📝</span> Grades</a>
      <a href="notices.php" class="nav-link"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php" class="nav-link"><span class="nav-icon">👤</span> Profile</a>
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
        <div class="topbar-title">
          <h1>Attendance</h1>
          <p>Your attendance summary and recent records</p>
        </div>
      </div>
      <div class="topbar-right">
        <div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div>
      </div>
    </header>

    <div class="page-content">
      <div class="card" style="margin-bottom:25px;">
        <div class="card-header"><h3>✅ Course-wise Attendance</h3></div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Course</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Total</th>
                <th>%</th>
              </tr>
            </thead>
            <tbody>
              <?php $has = false; while ($row = mysqli_fetch_assoc($attendance)) { $has = true; 
                $total = (int)$row['total_count'];
                $present = (int)$row['present_count'];
                $absent = (int)$row['absent_count'];
                $late = (int)$row['late_count'];
                $pct = $total > 0 ? round(($present / $total) * 100) : 0;
                $chip = $pct >= 75 ? 'chip-success' : ($pct >= 50 ? 'chip-warning' : 'chip-danger');
              ?>
              <tr>
                <td>
                  <span class="chip chip-purple"><?= e($row['course_code']) ?></span><br>
                  <small><?= e($row['course_name']) ?></small>
                </td>
                <td><span class="chip chip-success"><?= $present ?></span></td>
                <td><span class="chip chip-danger"><?= $absent ?></span></td>
                <td><span class="chip chip-warning"><?= $late ?></span></td>
                <td><?= $total ?></td>
                <td><span class="chip <?= $chip ?>"><?= $pct ?>%</span></td>
              </tr>
              <?php } ?>
              <?php if (!$has): ?>
              <tr>
                <td colspan="6" style="color:var(--text-muted);">No attendance records found yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h3>📋 Recent Attendance Records</h3></div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Course</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php $hasRecent = false; while ($r = mysqli_fetch_assoc($recent)) { $hasRecent = true;
                $statusClass = $r['status'] === 'Present' ? 'chip-success' : ($r['status'] === 'Late' ? 'chip-warning' : 'chip-danger');
              ?>
              <tr>
                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td>
                  <span class="chip chip-purple"><?= e($r['course_code']) ?></span><br>
                  <small><?= e($r['course_name']) ?></small>
                </td>
                <td><span class="chip <?= $statusClass ?>"><?= e($r['status']) ?></span></td>
              </tr>
              <?php } ?>
              <?php if (!$hasRecent): ?>
              <tr>
                <td colspan="3" style="color:var(--text-muted);">No recent attendance records found.</td>
              </tr>
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