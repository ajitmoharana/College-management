<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('faculty');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($conn, "
    SELECT f.*, d.name as dept_name
    FROM faculty f
    LEFT JOIN departments d ON f.department_id = d.id
    WHERE f.user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$faculty) { header("Location: ../login.php"); exit(); }

$faculty_pk = (int)$faculty['id'];

$stmt = mysqli_prepare($conn, "
    SELECT c.*,
           COUNT(e.id) as enrolled_students
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.faculty_id = ?
    GROUP BY c.id
    ORDER BY c.course_code
");
mysqli_stmt_bind_param($stmt, "i", $faculty_pk);
mysqli_stmt_execute($stmt);
$my_courses = mysqli_stmt_get_result($stmt);

$courses_arr = [];
while ($c = mysqli_fetch_assoc($my_courses)) $courses_arr[] = $c;

$total_students = array_sum(array_map(fn($x) => (int)$x['enrolled_students'], $courses_arr));
$total_courses  = count($courses_arr);

$stmt = mysqli_prepare($conn, "
    SELECT n.title, n.content, n.created_at
    FROM notices n
    WHERE n.target_role IN ('all','faculty')
    ORDER BY n.created_at DESC
    LIMIT 4
");
mysqli_stmt_execute($stmt);
$notices = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard – CollegeMS</title>
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
            <a href="dashboard.php"  class="nav-link active"><span class="nav-icon">📊</span> Dashboard</a>
            <a href="my_courses.php" class="nav-link"><span class="nav-icon">📚</span> My Courses</a>
            <a href="attendance.php" class="nav-link"><span class="nav-icon">✅</span> Mark Attendance</a>
            <a href="grades.php"     class="nav-link"><span class="nav-icon">📝</span> Enter Grades</a>
            <a href="students.php"   class="nav-link"><span class="nav-icon">👨‍🎓</span> My Students</a>
            <a href="notices.php"    class="nav-link"><span class="nav-icon">📢</span> Notices</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'F', 0, 1))) ?></div>
                <div>
                    <div class="user-name"><?= e($_SESSION['user_name'] ?? 'Faculty') ?></div>
                    <div class="user-role"><?= e($faculty['designation'] ?? 'Faculty') ?></div>
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
                    <h1>Faculty Dashboard</h1>
                    <p><?= e($faculty['dept_name'] ?? '') ?> · <?= e($faculty['designation'] ?? '') ?></p>
                </div>
            </div>
            <div class="topbar-right">
                <div style="font-size:13px; color:var(--text-muted)"><?= date('D, d M Y') ?></div>
            </div>
        </header>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card" style="--stat-color:#3b82f6">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= (int)$total_courses ?></div>
                    <div class="stat-label">Courses Teaching</div>
                </div>
                <div class="stat-card" style="--stat-color:#22c55e">
                    <div class="stat-icon">👨‍🎓</div>
                    <div class="stat-value"><?= (int)$total_students ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card" style="--stat-color:#f5a623">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-value"><?= (int)($faculty['experience'] ?? 0) ?></div>
                    <div class="stat-label">Years Experience</div>
                </div>
                <div class="stat-card" style="--stat-color:#a855f7">
                    <div class="stat-icon">🏛️</div>
                    <div class="stat-value"><?= e(substr($faculty['dept_name'] ?? 'N/A', 0, 2)) ?></div>
                    <div class="stat-label">Department</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:25px;">
                <div class="card">
                    <div class="card-header"><h3>📚 My Courses</h3></div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr><th>Code</th><th>Course Name</th><th>Sem</th><th>Credits</th><th>Students</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses_arr as $c): ?>
                                <tr>
                                    <td><span class="chip chip-purple"><?= e($c['course_code']) ?></span></td>
                                    <td><?= e($c['course_name']) ?></td>
                                    <td><?= (int)$c['semester'] ?></td>
                                    <td><span class="chip chip-info"><?= (int)$c['credits'] ?> cr</span></td>
                                    <td><span class="chip chip-success"><?= (int)$c['enrolled_students'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!$courses_arr): ?>
                                <tr><td colspan="5" style="color:var(--text-muted);">No courses assigned yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>📢 Notices</h3></div>
                    <div class="card-body" style="padding:15px;">
                        <?php $hasNotices = false; while ($n = mysqli_fetch_assoc($notices)): $hasNotices = true; ?>
                        <div style="padding:12px; border-bottom:1px solid var(--border);">
                            <div style="font-size:14px; font-weight:600; margin-bottom:4px;"><?= e($n['title']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted)"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
                        </div>
                        <?php endwhile; ?>
                        <?php if (!$hasNotices): ?>
                        <div style="color:var(--text-muted); font-size:13px;">No notices.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:25px;">
                <div class="card-header"><h3>⚡ Quick Actions</h3></div>
                <div class="card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
                    <a href="attendance.php" class="btn btn-success">✅ Mark Attendance</a>
                    <a href="grades.php" class="btn btn-primary">📝 Enter Grades</a>
                    <a href="students.php" class="btn btn-info">👨‍🎓 View Students</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>