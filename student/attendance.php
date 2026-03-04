<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$stmt = mysqli_prepare($conn, "
  SELECT n.title, n.content, n.created_at, u.name as author
  FROM notices n
  LEFT JOIN users u ON n.posted_by = u.id
  WHERE n.target_role IN ('all','student')
  ORDER BY n.created_at DESC, n.id DESC
  LIMIT 50
");
mysqli_stmt_execute($stmt);
$notices = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notices – CollegeMS</title>
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
      <a href="notices.php" class="nav-link active"><span class="nav-icon">📢</span> Notices</a>
      <a href="profile.php" class="nav-link"><span class="nav-icon">👤</span> Profile</a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'S', 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?></div>
          <div class="user-role">Student</div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:15px;">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <div class="topbar-title"><h1>Notices</h1><p>Announcements for students</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <div style="display:flex; flex-direction:column; gap:15px;">
        <?php $has=false; while($n=mysqli_fetch_assoc($notices)) { $has=true; ?>
        <div class="card">
          <div class="card-body" style="padding:20px 25px;">
            <h3 style="font-size:16px; margin-bottom:8px;"><?= htmlspecialchars($n['title']) ?></h3>
            <p style="color:var(--text-muted); font-size:14px; line-height:1.6;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
            <div style="margin-top:10px; font-size:12px; color:var(--text-muted);">
              <?= htmlspecialchars($n['author'] ?? '—') ?> · <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <?php if(!$has): ?>
          <div class="card"><div class="card-body" style="color:var(--text-muted);">No notices.</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>