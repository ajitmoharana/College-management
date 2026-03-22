<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

// Fetch stats (prepared style not needed for static queries, but handle safely)
$total_students  = (int)(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM students WHERE status='Active'"))[0] ?? 0);
$total_faculty   = (int)(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM faculty WHERE status='Active'"))[0] ?? 0);
$total_courses   = (int)(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM courses"))[0] ?? 0);
$total_depts     = (int)(mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM departments"))[0] ?? 0);

// Recent students
$recent_students = mysqli_query($conn, "
    SELECT s.student_id, s.name, s.email, d.name as dept, s.semester, s.status
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    ORDER BY s.created_at DESC
    LIMIT 5
");

// Recent notices
$notices = mysqli_query($conn, "
    SELECT n.title, n.created_at, u.name as author
    FROM notices n
    LEFT JOIN users u ON n.posted_by = u.id
    ORDER BY n.created_at DESC
    LIMIT 4
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>!</p>
                </div>
            </div>
            <div class="topbar-right">
                <div class="notification-btn">🔔<span class="badge"></span></div>
                <div style="font-size:13px; color:var(--text-muted);">
                    <?= date('D, d M Y') ?>
                </div>
            </div>
        </header>

        <div class="page-content">

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card" style="--stat-color:#e94560">
                    <div class="stat-icon">👨‍🎓</div>
                    <div class="stat-value"><?= $total_students ?></div>
                    <div class="stat-label">Active Students</div>
                    <div class="stat-change">↑ Enrolled this semester</div>
                </div>
                <div class="stat-card" style="--stat-color:#3b82f6">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-value"><?= $total_faculty ?></div>
                    <div class="stat-label">Faculty Members</div>
                    <div class="stat-change">↑ Active this term</div>
                </div>
                <div class="stat-card" style="--stat-color:#22c55e">
                    <div class="stat-icon">📚</div>
                    <div class="stat-value"><?= $total_courses ?></div>
                    <div class="stat-label">Total Courses</div>
                    <div class="stat-change">↑ Across all semesters</div>
                </div>
                <div class="stat-card" style="--stat-color:#f5a623">
                    <div class="stat-icon">🏛️</div>
                    <div class="stat-value"><?= $total_depts ?></div>
                    <div class="stat-label">Departments</div>
                    <div class="stat-change">↑ Academic departments</div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card" style="margin-bottom:25px;">
                <div class="card-header"><h3>⚡ Quick Actions</h3></div>
                <div class="card-body" style="display:flex; flex-wrap:wrap; gap:12px;">
                    <a href="students.php?action=add" class="btn btn-primary">+ Add Student</a>
                    <a href="faculty.php?action=add"  class="btn btn-info">+ Add Faculty</a>
                    <a href="courses.php?action=add"  class="btn btn-success">+ Add Course</a>
                    <a href="notices.php?action=add"  class="btn btn-warning">📢 Post Notice</a>
                    <a href="attendance.php"          class="btn btn-secondary">✅ Mark Attendance</a>
                    <a href="grades.php?action=add"   class="btn btn-secondary">📝 Add Grades</a>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:25px; flex-wrap:wrap;">

                <!-- RECENT STUDENTS -->
                <div class="card">
                    <div class="card-header">
                        <h3>👨‍🎓 Recent Students</h3>
                        <a href="students.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr><th>ID</th><th>Name</th><th>Department</th><th>Sem</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            <?php while ($s = mysqli_fetch_assoc($recent_students)): ?>
                                <tr>
                                    <td><code style="color:var(--accent)"><?= htmlspecialchars($s['student_id']) ?></code></td>
                                    <td><?= htmlspecialchars($s['name']) ?></td>
                                    <td><?= htmlspecialchars($s['dept'] ?? 'N/A') ?></td>
                                    <td><?= (int)$s['semester'] ?></td>
                                    <td>
                                        <span class="chip <?= $s['status'] === 'Active' ? 'chip-success' : 'chip-warning' ?>">
                                            <?= htmlspecialchars($s['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RECENT NOTICES -->
                <div class="card">
                    <div class="card-header">
                        <h3>📢 Notices</h3>
                        <a href="notices.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <div class="card-body" style="padding:15px;">
                        <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                        <div style="padding:12px; border-bottom:1px solid var(--border);">
                            <div style="font-size:14px; font-weight:600; margin-bottom:4px;">
                                <?= htmlspecialchars($n['title']) ?>
                            </div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                By <?= htmlspecialchars($n['author'] ?? '—') ?> · <?= date('d M Y', strtotime($n['created_at'])) ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../js/main.js"></script>
</body>
</html>