// admin/attendance.php ✅ UPDATED (date validation + prepared selects + prepared insert once + CSRF)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';

/* ===== Fetch courses for filter ===== */
$courses = mysqli_query($conn, "SELECT id, course_name, course_code FROM courses ORDER BY course_name");
$courses_arr = [];
while ($c = mysqli_fetch_assoc($courses)) $courses_arr[] = $c;

/* ===== Helpers ===== */
function valid_date_ymd(string $d): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y,$m,$day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}

/* ===== HANDLE MARK ATTENDANCE ===== */
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
            $marked_by = (int)($_SESSION['user_id'] ?? 0);

            try {
                // Prepare once
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO attendance (student_id, course_id, date, status, marked_by)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)
                ");

                $count = 0;
                foreach ($student_ids as $sid) {
                    $sid = (int)$sid;
                    $status = (string)($statuses[$sid] ?? 'Absent');
                    if (!in_array($status, ['Present','Absent','Late'], true)) $status = 'Absent';

                    mysqli_stmt_bind_param($stmt, "iissi", $sid, $course_id, $date, $status, $marked_by);
                    mysqli_stmt_execute($stmt);
                    $count++;
                }

                $success = "Attendance saved for $count students on $date.";
            } catch (Throwable $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

/* ===== Fetch students for selected course ===== */
$selected_course = (int)($_GET['course_id'] ?? 0);
$selected_date   = (string)($_GET['date'] ?? date('Y-m-d'));
if (!valid_date_ymd($selected_date)) $selected_date = date('Y-m-d');

$students_list = [];

if ($selected_course > 0) {
    // prepared select
    $stmt = mysqli_prepare($conn, "
        SELECT s.id, s.student_id, s.name,
               COALESCE(a.status, 'Not Marked') as att_status
        FROM students s
        LEFT JOIN attendance a
            ON a.student_id = s.id AND a.course_id = ? AND a.date = ?
        WHERE s.status='Active'
        ORDER BY s.name
    ");
    mysqli_stmt_bind_param($stmt, "is", $selected_course, $selected_date);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($r = mysqli_fetch_assoc($res)) $students_list[] = $r;
}

/* ===== Attendance report (last 10) ===== */
$report = mysqli_query($conn, "
    SELECT a.date, c.course_name, c.course_code,
           SUM(a.status='Present') as present,
           SUM(a.status='Absent') as absent,
           SUM(a.status='Late') as late,
           COUNT(*) as total
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    GROUP BY a.date, a.course_id
    ORDER BY a.date DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Attendance – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title"><h1>Attendance</h1><p>Mark and view attendance</p></div>
            </div>
        </header>

        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- FILTER -->
            <div class="card" style="margin-bottom:25px;">
                <div class="card-header"><h3>🔍 Select Course & Date</h3></div>
                <div class="card-body">
                    <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
                        <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                            <label>Course</label>
                            <select name="course_id" onchange="this.form.submit()">
                                <option value="">Select Course</option>
                                <?php foreach ($courses_arr as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $selected_course == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                            <label>Date</label>
                            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>

            <!-- MARK ATTENDANCE -->
            <?php if ($selected_course && $students_list): ?>
            <div class="card" style="margin-bottom:25px;">
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
                            <thead>
                                <tr><th>#</th><th>Student ID</th><th>Name</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($students_list as $s): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><code style="color:var(--accent)"><?= htmlspecialchars($s['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td>
                                        <input type="hidden" name="student_id[]" value="<?= (int)$s['id'] ?>">
                                        <select name="status[<?= (int)$s['id'] ?>]" class="att-select"
                                                style="background:rgba(255,255,255,0.06); border:1px solid var(--border); border-radius:8px; padding:7px 12px; color:var(--text); font-size:13px;">
                                            <option value="Present" <?= $s['att_status'] === 'Present' ? 'selected' : '' ?>>✅ Present</option>
                                            <option value="Absent"  <?= $s['att_status'] === 'Absent'  ? 'selected' : '' ?>>❌ Absent</option>
                                            <option value="Late"    <?= $s['att_status'] === 'Late'    ? 'selected' : '' ?>>⏰ Late</option>
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
            <?php endif; ?>

            <!-- ATTENDANCE LOG -->
            <div class="card">
                <div class="card-header"><h3>📋 Recent Attendance Log</h3></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Course</th><th>Present</th><th>Absent</th><th>Late</th><th>Total</th><th>%</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($r = mysqli_fetch_assoc($report)): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                <td><span class="chip chip-purple"><?= htmlspecialchars($r['course_code']) ?></span> <?= htmlspecialchars($r['course_name']) ?></td>
                                <td><span class="chip chip-success"><?= (int)$r['present'] ?></span></td>
                                <td><span class="chip chip-danger"><?= (int)$r['absent'] ?></span></td>
                                <td><span class="chip chip-warning"><?= (int)$r['late'] ?></span></td>
                                <td><?= (int)$r['total'] ?></td>
                                <td><?= $r['total'] > 0 ? round(((int)$r['present']/(int)$r['total'])*100) : 0 ?>%</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
function setAll(val) {
    document.querySelectorAll('.att-select').forEach(s => s.value = val);
}
</script>
</body>
</html>