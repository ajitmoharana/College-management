<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF).';
    } else {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $course_id  = (int)($_POST['course_id'] ?? 0);
        $exam_type  = sanitize($conn, $_POST['exam_type'] ?? 'Final');
        $obtained   = (float)($_POST['marks_obtained'] ?? 0);
        $total      = (float)($_POST['total_marks'] ?? 100);
        $remarks    = sanitize($conn, $_POST['remarks'] ?? '');

        if ($student_id <= 0 || $course_id <= 0) {
            $error = 'Please select a student and course.';
        } elseif (!in_array($exam_type, ['Internal', 'Mid-Term', 'Final', 'Assignment'], true)) {
            $error = 'Invalid exam type.';
        } elseif ($total <= 0) {
            $error = 'Total marks must be greater than 0.';
        } elseif ($obtained < 0) {
            $error = 'Marks obtained cannot be negative.';
        } elseif ($obtained > $total) {
            $error = 'Marks obtained cannot exceed total marks.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM students WHERE id=? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $student_id);
            mysqli_stmt_execute($stmt);
            $student_ok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            $stmt = mysqli_prepare($conn, 'SELECT id FROM courses WHERE id=? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $course_id);
            mysqli_stmt_execute($stmt);
            $course_ok = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$student_ok || !$course_ok) {
                $error = 'Selected student or course does not exist.';
            } else {
                $stmt = mysqli_prepare($conn, 'SELECT id FROM grades WHERE student_id=? AND course_id=? AND exam_type=? LIMIT 1');
                mysqli_stmt_bind_param($stmt, 'iis', $student_id, $course_id, $exam_type);
                mysqli_stmt_execute($stmt);
                $duplicate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                if ($duplicate) {
                    $error = 'A grade for this student, course, and exam type already exists.';
                } else {
                    $pct   = ($obtained / $total) * 100;
                    $grade = $pct >= 90 ? 'A+' : ($pct >= 80 ? 'A' : ($pct >= 70 ? 'B' : ($pct >= 60 ? 'C' : ($pct >= 50 ? 'D' : 'F'))));

                    try {
                        $stmt = mysqli_prepare($conn, "
                            INSERT INTO grades (student_id, course_id, exam_type, marks_obtained, total_marks, grade, remarks)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        mysqli_stmt_bind_param($stmt, 'iisddss', $student_id, $course_id, $exam_type, $obtained, $total, $grade, $remarks);
                        mysqli_stmt_execute($stmt);

                        $success = "Grade added. Student got {$grade}.";
                        $action = 'list';
                    } catch (Throwable $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$students = mysqli_query($conn, "SELECT id, student_id, name FROM students WHERE status='Active' ORDER BY name");
$stu_arr = [];
while ($s = mysqli_fetch_assoc($students)) $stu_arr[] = $s;

$courses = mysqli_query($conn, "SELECT id, course_code, course_name FROM courses ORDER BY course_name");
$crs_arr = [];
while ($c = mysqli_fetch_assoc($courses)) $crs_arr[] = $c;

$grades = mysqli_query($conn, "
    SELECT g.*, s.name as student_name, s.student_id as stu_id, c.course_code, c.course_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN courses c ON g.course_id = c.id
    ORDER BY g.created_at DESC, g.id DESC
    LIMIT 30
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Grades – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title"><h1>Grades</h1><p>Manage student grades and results</p></div>
            </div>
            <div class="topbar-right">
                <a href="?action=add" class="btn btn-primary btn-sm">+ Add Grade</a>
            </div>
        </header>
        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>📝 Add Grade</h3>
                    <a href="grades.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
                <div class="card-body">
                    <form method="POST" id="addForm" onsubmit="return validateForm('addForm')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Student *</label>
                                <select name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($stu_arr as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"><?= e($s['student_id'] . ' - ' . $s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Course *</label>
                                <select name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($crs_arr as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Exam Type</label>
                                <select name="exam_type">
                                    <option value="Internal">Internal</option>
                                    <option value="Mid-Term">Mid-Term</option>
                                    <option value="Final" selected>Final</option>
                                    <option value="Assignment">Assignment</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Marks Obtained *</label>
                                <input type="number" name="marks_obtained" required step="0.01" min="0" placeholder="e.g. 85">
                            </div>
                            <div class="form-group">
                                <label>Total Marks</label>
                                <input type="number" name="total_marks" value="100" step="0.01" min="1">
                            </div>
                            <div class="form-group">
                                <label>Remarks</label>
                                <input type="text" name="remarks" placeholder="Optional remarks">
                            </div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:12px;">
                            <button type="submit" name="add_grade" class="btn btn-primary" style="width:auto; padding:13px 30px;">✓ Save Grade</button>
                            <a href="grades.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <div class="card-header"><h3>📋 Grade Records</h3></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Student</th><th>Course</th><th>Exam</th><th>Marks</th><th>%</th><th>Grade</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            <?php $i=1; while ($g = mysqli_fetch_assoc($grades)):
                                $pct = $g['total_marks'] > 0 ? round(($g['marks_obtained'] / $g['total_marks']) * 100) : 0;
                                $chip = $pct >= 70 ? 'chip-success' : ($pct >= 50 ? 'chip-warning' : 'chip-danger');
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td>
                                    <strong><?= e($g['student_name']) ?></strong><br>
                                    <small style="color:var(--text-muted)"><?= e($g['stu_id']) ?></small>
                                </td>
                                <td>
                                    <span class="chip chip-purple"><?= e($g['course_code']) ?></span><br>
                                    <small><?= e($g['course_name']) ?></small>
                                </td>
                                <td><span class="chip chip-info"><?= e($g['exam_type']) ?></span></td>
                                <td><?= e($g['marks_obtained']) ?> / <?= e($g['total_marks']) ?></td>
                                <td><?= (int)$pct ?>%</td>
                                <td><span class="chip <?= $chip ?>" style="font-size:15px; font-weight:800;"><?= e($g['grade']) ?></span></td>
                                <td style="color:var(--text-muted); font-size:13px;"><?= e($g['remarks'] ?: '—') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>