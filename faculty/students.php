<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('faculty');

$user_id = (int)($_SESSION['user_id'] ?? 0);

/* ===== Get faculty record ===== */
$stmt = mysqli_prepare($conn, "SELECT id, name FROM faculty WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$faculty) { header("Location: ../login.php"); exit(); }

$faculty_pk = (int)$faculty['id'];

/* ===== Fetch faculty courses for dropdown ===== */
$stmt = mysqli_prepare($conn, "SELECT id, course_code, course_name FROM courses WHERE faculty_id=? ORDER BY course_code");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
mysqli_stmt_execute($stmt);
$course_rs = mysqli_stmt_get_result($stmt);

$courses_arr = [];
while ($c = mysqli_fetch_assoc($course_rs)) $courses_arr[] = $c;

/* ===== Selected course ===== */
$selected_course = (int)($_GET['course_id'] ?? 0);
$students_rs = null;

if ($selected_course > 0) {
    // Ensure selected course belongs to this faculty
    $stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE id=? AND faculty_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $selected_course, $faculty_pk);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($ok) {
        $stmt = mysqli_prepare($conn, "
            SELECT s.student_id, s.name, s.email, s.phone, s.semester, s.status, d.name AS dept_name
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE e.course_id = ?
            ORDER BY s.name
        ");
        mysqli_stmt_bind_param($stmt, "i", $selected_course);
        mysqli_stmt_execute($stmt);
        $students_rs = mysqli_stmt_get_result($stmt);
    } else {
        $selected_course = 0; // invalid selection
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Students – CollegeMS</title>
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
      <a href="students.php"   class="nav-link active"><span class="nav-icon">👨‍🎓</span> My Students</a>
      <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'F', 0, 1)) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Faculty') ?></div>
          <div class="user-role">Faculty</div>
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
          <h1>My Students</h1>
          <p>View enrolled students by course</p>
        </div>
      </div>
      <div class="topbar-right">
        <div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div>
      </div>
    </header>

    <div class="page-content">

      <!-- FILTER -->
      <div class="card" style="margin-bottom:25px;">
        <div class="card-header"><h3>🔍 Select Course</h3></div>
        <div class="card-body">
          <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="margin:0; flex:1; min-width:240px;">
              <label>Course</label>
              <select name="course_id" onchange="this.form.submit()">
                <option value="">Select Course</option>
                <?php foreach ($courses_arr as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= $selected_course === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
        </div>
      </div>

      <!-- LIST -->
      <div class="card">
        <div class="card-header"><h3>👨‍🎓 Enrolled Students</h3></div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr><th>#</th><th>Student ID</th><th>Name</th><th>Email</th><th>Dept</th><th>Sem</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php
              $i = 1;
              $has = false;
              if ($students_rs) {
                  while ($s = mysqli_fetch_assoc($students_rs)) {
                      $has = true;
                      $chip = ($s['status'] === 'Active') ? 'chip-success' : 'chip-warning';
              ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><span class="chip chip-purple"><?= htmlspecialchars($s['student_id']) ?></span></td>
                  <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                  <td style="color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></td>
                  <td><?= htmlspecialchars($s['dept_name'] ?? '—') ?></td>
                  <td><?= (int)$s['semester'] ?></td>
                  <td><span class="chip <?= $chip ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                </tr>
              <?php }} ?>

              <?php if (!$selected_course): ?>
                <tr><td colspan="7" style="color:var(--text-muted);">Select a course to view students.</td></tr>
              <?php elseif (!$has): ?>
                <tr><td colspan="7" style="color:var(--text-muted);">No students enrolled in this course.</td></tr>
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