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

/* ===== Courses (only my courses) ===== */
$stmt = mysqli_prepare($conn, "SELECT id, course_code, course_name FROM courses WHERE faculty_id=? ORDER BY course_code");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
mysqli_stmt_execute($stmt);
$crs = mysqli_stmt_get_result($stmt);

$courses_arr = [];
while ($c = mysqli_fetch_assoc($crs)) $courses_arr[] = $c;

/* ===== Selected course ===== */
$selected_course = (int)($_GET['course_id'] ?? ($_POST['course_id'] ?? 0));

/* ===== Make sure selected course belongs to faculty ===== */
$course_ok = false;
if ($selected_course > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE id=? AND faculty_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $selected_course, $faculty_pk);
    mysqli_stmt_execute($stmt);
    $course_ok = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$course_ok) $selected_course = 0;
}

/* ===== Enrolled students dropdown (for selected course) ===== */
$students_arr = [];
if ($selected_course > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT s.id, s.student_id, s.name
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        WHERE e.course_id = ?
        ORDER BY s.name
    ");
    mysqli_stmt_bind_param($stmt, "i", $selected_course);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($s = mysqli_fetch_assoc($rs)) $students_arr[] = $s;
}

/* ===== Add grade ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $course_id  = (int)($_POST['course_id'] ?? 0);
        $student_id = (int)($_POST['student_id'] ?? 0);
        $exam_type  = sanitize($conn, $_POST['exam_type'] ?? 'Final');
        $obtained   = (float)($_POST['marks_obtained'] ?? 0);
        $total      = (float)($_POST['total_marks'] ?? 100);
        $remarks    = sanitize($conn, $_POST['remarks'] ?? '');

        // Ensure course belongs to faculty
        $stmt = mysqli_prepare($conn, "SELECT id FROM courses WHERE id=? AND faculty_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $course_id, $faculty_pk);
        mysqli_stmt_execute($stmt);
        $okCourse = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$okCourse) {
            $error = "You cannot add grades for this course.";
        } else {
            // Ensure student is enrolled in this course
            $stmt = mysqli_prepare($conn, "SELECT id FROM enrollments WHERE student_id=? AND course_id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
            mysqli_stmt_execute($stmt);
            $okEnroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$okEnroll) {
                $error = "Student is not enrolled in this course.";
            } else {
                $pct = ($total > 0) ? ($obtained / $total) * 100 : 0;
                $grade = $pct >= 90 ? 'A+' : ($pct >= 80 ? 'A' : ($pct >= 70 ? 'B' : ($pct >= 60 ? 'C' : ($pct >= 50 ? 'D' : 'F'))));

                try {
                    $stmt = mysqli_prepare($conn, "
                        INSERT INTO grades (student_id, course_id, exam_type, marks_obtained, total_marks, grade, remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    mysqli_stmt_bind_param($stmt, "iisddss", $student_id, $course_id, $exam_type, $obtained, $total, $grade, $remarks);
                    mysqli_stmt_execute($stmt);

                    $success = "Grade saved. Student got <strong>$grade</strong>.";
                    $selected_course = $course_id;
                } catch (Throwable $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

/* ===== Recent grades for this faculty (via courses) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT g.*, s.name AS student_name, s.student_id AS stu_id, c.course_code, c.course_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN courses c ON g.course_id = c.id
    WHERE c.faculty_id = ?
    ORDER BY g.created_at DESC, g.id DESC
    LIMIT 30
");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
  <title>Enter Grades – CollegeMS</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="logo-icon">🎓</div><div><h3>CollegeMS</h3><span>Faculty Portal</span></div></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-link"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="my_courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
      <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Mark Attendance</a>
      <a href="grades.php"     class="nav-link active"><span class="nav-icon">📝</span> Enter Grades</a>
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
        <div class="topbar-title"><h1>Grades</h1><p>Enter grades for your courses</p></div>
      </div>
      <div class="topbar-right"><div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div></div>
    </header>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <!-- COURSE FILTER -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h3>🔍 Select Course</h3></div>
        <div class="card-body">
          <form method="GET">
            <div class="form-group" style="max-width:420px; margin:0;">
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
          </form>
        </div>
      </div>

      <!-- ADD GRADE -->
      <div class="card" style="margin-bottom:25px;">
        <div class="card-header"><h3>➕ Add Grade</h3></div>
        <div class="card-body">
          <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="course_id" value="<?= (int)$selected_course ?>">

            <div class="form-grid">
              <div class="form-group">
                <label>Course *</label>
                <select disabled>
                  <option><?= $selected_course ? 'Selected' : 'Select a course above' ?></option>
                </select>
              </div>

              <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required <?= $selected_course ? '' : 'disabled' ?>>
                  <option value="">Select Student</option>
                  <?php foreach ($students_arr as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['student_id'].' - '.$s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Exam Type</label>
                <select name="exam_type" <?= $selected_course ? '' : 'disabled' ?>>
                  <option value="Internal">Internal</option>
                  <option value="Mid-Term">Mid-Term</option>
                  <option value="Final" selected>Final</option>
                  <option value="Assignment">Assignment</option>
                </select>
              </div>

              <div class="form-group">
                <label>Marks Obtained *</label>
                <input type="number" name="marks_obtained" required step="0.01" min="0" placeholder="e.g. 85" <?= $selected_course ? '' : 'disabled' ?>>
              </div>

              <div class="form-group">
                <label>Total Marks</label>
                <input type="number" name="total_marks" value="100" step="0.01" min="1" <?= $selected_course ? '' : 'disabled' ?>>
              </div>

              <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="remarks" placeholder="Optional remarks" <?= $selected_course ? '' : 'disabled' ?>>
              </div>
            </div>

            <div style="margin-top:15px;">
              <button type="submit" name="add_grade" class="btn btn-primary" style="width:auto; padding:13px 30px;" <?= $selected_course ? '' : 'disabled' ?>>
                ✓ Save Grade
              </button>
            </div>
          </form>
          <?php if ($selected_course && empty($students_arr)): ?>
            <div style="margin-top:12px; color:var(--text-muted); font-size:13px;">No enrolled students found for this course.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RECENT GRADES -->
      <div class="card">
        <div class="card-header"><h3>📋 Recent Grades (Your Courses)</h3></div>
        <div class="table-responsive">
          <table>
            <thead><tr><th>Student</th><th>Course</th><th>Exam</th><th>Marks</th><th>%</th><th>Grade</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php $has=false; while($g=mysqli_fetch_assoc($recent)) { $has=true;
              $pct = $g['total_marks']>0 ? round(($g['marks_obtained']/$g['total_marks'])*100) : 0;
              $chip = $pct>=70 ? 'chip-success' : ($pct>=50 ? 'chip-warning' : 'chip-danger');
            ?>
              <tr>
                <td><strong><?= htmlspecialchars($g['student_name']) ?></strong><br><small style="color:var(--text-muted)"><?= htmlspecialchars($g['stu_id']) ?></small></td>
                <td><span class="chip chip-purple"><?= htmlspecialchars($g['course_code']) ?></span><br><small><?= htmlspecialchars($g['course_name']) ?></small></td>
                <td><span class="chip chip-info"><?= htmlspecialchars($g['exam_type']) ?></span></td>
                <td><?= htmlspecialchars($g['marks_obtained']) ?> / <?= htmlspecialchars($g['total_marks']) ?></td>
                <td><?= (int)$pct ?>%</td>
                <td><span class="chip <?= $chip ?>" style="font-weight:800;"><?= htmlspecialchars($g['grade']) ?></span></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($g['remarks'] ?: '—') ?></td>
              </tr>
            <?php } ?>
            <?php if(!$has): ?>
              <tr><td colspan="7" style="color:var(--text-muted);">No grades yet.</td></tr>
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