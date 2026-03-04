<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('faculty');

$success = $error = '';

$user_id = (int)($_SESSION['user_id'] ?? 0);

/* ===== Faculty record ===== */
$stmt = mysqli_prepare($conn, "SELECT id FROM faculty WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$faculty) { header("Location: ../login.php"); exit(); }
$faculty_pk = (int)$faculty['id'];

/* ===== Helper: validate YYYY-MM-DD ===== */
function valid_date_ymd(string $d): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y,$m,$day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}

/* ===== Courses dropdown (only my courses) ===== */
$stmt = mysqli_prepare($conn, "SELECT id, course_code, course_name FROM courses WHERE faculty_id=? ORDER BY course_code");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
mysqli_stmt_execute($stmt);
$crs = mysqli_stmt_get_result($stmt);

$courses_arr = [];
while ($c = mysqli_fetch_assoc($crs)) $courses_arr[] = $c;

/* ===== Selected course/date ===== */
$selected_course = (int)($_GET['course_id'] ?? 0);
$selected_date   = (string)($_GET['date'] ?? date('Y-m-d'));
if (!valid_date_ymd($selected_date)) $selected_date = date('Y-m-d');

/* ===== Mark attendance ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $date      = (string)($_POST['date'] ?? '');
        $statuses  = $_POST['status'] ?? [];
        $student_ids = $_POST['student_id'] ?? [];

        if ($course_id <= 0) {
            $error = "Please select a course.";
        } elseif (!valid_date_ymd($date)) {
            $error = "Invalid date.";
        } else {
            // Ensure course belongs to this faculty
            $stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE id=? AND faculty_id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ii", $course_id, $faculty_pk);
            mysqli_stmt_execute($stmt);
            $ok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$ok) {
                $error = "You cannot mark attendance for this course.";
            } else {
                try {
                    $marked_by = (int)($_SESSION['user_id'] ?? 0);

                    // Prepare once
                    $stmtIns = mysqli_prepare($conn, "
                        INSERT INTO attendance (student_id, course_id, date, status, marked_by)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)
                    ");

                    $count = 0;
                    foreach ($student_ids as $sid) {
                        $sid = (int)$sid;
                        $status = (string)($statuses[$sid] ?? 'Absent');
                        if (!in_array($status, ['Present','Absent','Late'], true)) $status = 'Absent';

                        mysqli_stmt_bind_param($stmtIns, "iissi", $sid, $course_id, $date, $status, $marked_by);
                        mysqli_stmt_execute($stmtIns);
                        $count++;
                    }

                    $success = "Attendance saved for $count students on $date.";
                    $selected_course = $course_id;
                    $selected_date = $date;
                } catch (Throwable $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

/* ===== Students for selected course ===== */
$students_list = [];

if ($selected_course > 0) {
    // Ensure selected course belongs to this faculty
    $stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE id=? AND faculty_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $selected_course, $faculty_pk);
    mysqli_stmt_execute($stmt);
    $ok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($ok) {
        // Students via enrollments
        $stmt = mysqli_prepare($conn, "
            SELECT s.id, s.student_id, s.name,
                   COALESCE(a.status, 'Not Marked') AS att_status
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            LEFT JOIN attendance a
                ON a.student_id = s.id AND a.course_id = ? AND a.date = ?
            WHERE e.course_id = ? AND s.status='Active'
            ORDER BY s.name
        ");
        mysqli_stmt_bind_param($stmt, "isi", $selected_course, $selected_date, $selected_course);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) $students_list[] = $r;
    } else {
        $selected_course = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
  <title>Mark Attendance – CollegeMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="logo-icon">🎓</div><div><h3>CollegeMS</h3><span>Faculty Portal</span></div></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="my_courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link active"><span class="nav-icon">✅</span> Mark Attendance</a>
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
        <div class="topbar-title"><h1>Attendance</h1><p>Mark attendance for your courses</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <!-- FILTER -->
      <div class="card" style="margin-bottom:25px;">
        <div class="card-header"><h3>🔍 Select Course & Date</h3></div>
        <div class="card-body">
          <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="margin:0; flex:1; min-width:220px;">
              <label>Course</label>
              <select name="course_id" onchange="this.form.submit()">
                <option value="">Select Course</option>
                <?php foreach ($courses_arr as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $selected_course === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['course_code'].' - '.$c['course_name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:160px;">
              <label>Date</label>
              <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
            </div>
          </form>
        </div>
      </div>

      <!-- MARK -->
      <?php if ($selected_course && $students_list): ?>
      <div class="card">
        <div class="card-header">
          <h3>✅ Mark Attendance – <?= date('d M Y', strtotime($selected_date)) ?></h3>
          <div style="display:flex; gap:8px;">
            <button type="button" onclick="setAll('Present')" class="btn btn-success btn-sm">All Present</button>
            <button type="button" onclick="setAll('Absent')" class="btn btn-danger btn-sm">All Absent</button>
          </div>
        </div>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="course_id" value="<?= (int)$selected_course ?>">
          <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">

          <div class="table-responsive">
            <table>
              <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Status</th></tr></thead>
              <tbody>
              <?php $i=1; foreach ($students_list as $s): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><span class="chip chip-purple"><?= htmlspecialchars($s['student_id']) ?></span></td>
                  <td><?= htmlspecialchars($s['name']) ?></td>
                  <td>
                    <input type="hidden" name="student_id[]" value="<?= (int)$s['id'] ?>">
                    <select name="status[<?= (int)$s['id'] ?>]" class="att-select"
                      style="background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:8px; padding:7px 12px; color:var(--text); font-size:13px;">
                      <option value="Present" <?= $s['att_status']==='Present' ? 'selected':'' ?>>✅ Present</option>
                      <option value="Absent"  <?= $s['att_status']==='Absent' ? 'selected':'' ?>>❌ Absent</option>
                      <option value="Late"    <?= $s['att_status']==='Late' ? 'selected':'' ?>>⏰ Late</option>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div style="padding:20px 25px; border-top:1px solid var(--border);">
            <button type="submit" name="mark_attendance" class="btn btn-primary" style="width:auto; padding:13px 30px;">
              ✓ Save Attendance
            </button>
          </div>
        </form>
      </div>
      <?php elseif ($selected_course): ?>
        <div class="card"><div class="card-body" style="color:var(--text-muted);">No enrolled students found for this course.</div></div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="../js/main.js"></script>
<script>
function setAll(val){ document.querySelectorAll('.att-select').forEach(s => s.value = val); }
</script>
</body>
</html>