// student/dashboard.php ✅ UPDATED (fix logout link, reduce repeated queries, safer queries)
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

/* ===== Get student record (prepared) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT s.*, d.name as dept_name
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) { header("Location: ../login.php"); exit(); }

$student_pk = (int)$student['id'];

/* ===== Attendance summary (prepared) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(status='Present') as present,
        SUM(status='Absent') as absent
    FROM attendance
    WHERE student_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$att = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['total'=>0,'present'=>0,'absent'=>0];

$att_total = (int)($att['total'] ?? 0);
$att_present = (int)($att['present'] ?? 0);
$att_pct = $att_total > 0 ? round(($att_present / $att_total) * 100) : 0;

/* ===== Counts (prepared) ===== */
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM enrollments WHERE student_id=?");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$enrolled_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM grades WHERE student_id=?");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$grades_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'] ?? 0);

/* ===== Enrolled courses (prepared) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT c.course_code, c.course_name, c.credits, f.name as faculty_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN faculty f ON c.faculty_id = f.id
    WHERE e.student_id = ?
    ORDER BY c.course_code
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$courses = mysqli_stmt_get_result($stmt);

/* ===== Recent grades (prepared) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT g.marks_obtained, g.total_marks, g.grade, g.exam_type, c.course_code, c.course_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, "i", $student_pk);
mysqli_stmt_execute($stmt);
$grades = mysqli_stmt_get_result($stmt);

/* ===== Notices (prepared) ===== */
$stmt = mysqli_prepare($conn, "
    SELECT n.title, n.content, n.created_at, u.name as author
    FROM notices n
    LEFT JOIN users u ON n.posted_by = u.id
    WHERE n.target_role IN ('all','student')
    ORDER BY n.created_at DESC
    LIMIT 5
");
mysqli_stmt_execute($stmt);
$notices = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <!-- STUDENT SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon">🎓</div>
            <div><h3>CollegeMS</h3><span>Student Portal</span></div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"  class="nav-link active"><span class="nav-icon">📊</span> Dashboard</a>
            <a href="courses.php"    class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
            <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Attendance</a>
            <a href="grades.php"     class="nav-link"><span class="nav-icon">📝</span> Grades</a>
            <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
            <a href="profile.php"    class="nav-link"><span class="nav-icon">👤</span> Profile</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($student['student_id']) ?></div>
                </div>
            </div>
            <!-- ✅ FIXED logout -->
            <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;" onclick="sessionStorage.clear()">🚪 Logout</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title">
                    <h1>My Dashboard</h1>
                    <p><?= htmlspecialchars($student['dept_name'] ?? '') ?> · Semester <?= (int)$student['semester'] ?></p>
                </div>
            </div>
            <div class="topbar-right">
                <div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div>
            </div>
        </header>

        <div class="page-content">
            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card" style="--stat-color:#3b82f6">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= $enrolled_count ?></div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>

                <div class="stat-card" style="--stat-color:<?= $att_pct >= 75 ? '#22c55e' : '#ef4444' ?>">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?= $att_pct ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                    <div class="stat-change" style="color:<?= $att_pct >= 75 ? 'var(--success)' : 'var(--danger)' ?>">
                        <?= $att_pct >= 75 ? '✓ Good standing' : '⚠ Below minimum' ?>
                    </div>
                </div>

                <div class="stat-card" style="--stat-color:#f5a623">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?= $grades_count ?></div>
                    <div class="stat-label">Grade Records</div>
                </div>

                <div class="stat-card" style="--stat-color:#a855f7">
                    <div class="stat-icon">🏛️</div>
                    <div class="stat-value"><?= (int)$student['semester'] ?></div>
                    <div class="stat-label">Current Semester</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:25px;">
                <!-- MY COURSES -->
                <div class="card">
                    <div class="card-header"><h3>📚 My Courses</h3></div>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Code</th><th>Course</th><th>Faculty</th><th>Cr</th></tr></thead>
                            <tbody>
                                <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                                <tr>
                                    <td><span class="chip chip-purple"><?= htmlspecialchars($c['course_code']) ?></span></td>
                                    <td><?= htmlspecialchars($c['course_name']) ?></td>
                                    <td style="font-size:12px; color:var(--text-muted)"><?= htmlspecialchars($c['faculty_name'] ?? '—') ?></td>
                                    <td><?= (int)$c['credits'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RECENT GRADES -->
                <div class="card">
                    <div class="card-header"><h3>📝 Recent Grades</h3></div>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>Course</th><th>Type</th><th>Score</th><th>Grade</th></tr></thead>
                            <tbody>
                                <?php while ($g = mysqli_fetch_assoc($grades)):
                                    $pct = ($g['total_marks'] > 0) ? round(($g['marks_obtained']/$g['total_marks'])*100) : 0;
                                    $chip = $pct >= 70 ? 'chip-success' : ($pct >= 50 ? 'chip-warning' : 'chip-danger');
                                ?>
                                <tr>
                                    <td><span class="chip chip-purple"><?= htmlspecialchars($g['course_code']) ?></span></td>
                                    <td style="font-size:12px"><?= htmlspecialchars($g['exam_type']) ?></td>
                                    <td><?= htmlspecialchars($g['marks_obtained']) ?>/<?= htmlspecialchars($g['total_marks']) ?></td>
                                    <td><span class="chip <?= $chip ?>" style="font-weight:800"><?= htmlspecialchars($g['grade']) ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- NOTICES -->
            <div class="card" style="margin-top:25px;">
                <div class="card-header"><h3>📢 Latest Notices</h3></div>
                <div class="card-body" style="padding:15px;">
                    <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                    <div style="padding:15px; border-bottom:1px solid var(--border);">
                        <div style="font-weight:600; margin-bottom:5px;"><?= htmlspecialchars($n['title']) ?></div>
                        <div style="font-size:13px; color:var(--text-muted); margin-bottom:5px; line-height:1.5">
                            <?= htmlspecialchars(substr($n['content'], 0, 200)) ?>...
                        </div>
                        <div style="font-size:11px; color:var(--text-muted)"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>