// admin/sidebar.php ✅ UPDATED (adds htmlspecialchars + works with csrf meta from pages)
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-icon">🎓</div>
        <div>
            <h3>CollegeMS</h3>
            <span>Admin Panel</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-section-title">Main</span>
        <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <span class="nav-section-title">Academics</span>
        <a href="students.php" class="nav-link <?= $current_page === 'students.php' ? 'active' : '' ?>">
            <span class="nav-icon">👨‍🎓</span> Students
        </a>
        <a href="faculty.php" class="nav-link <?= $current_page === 'faculty.php' ? 'active' : '' ?>">
            <span class="nav-icon">👨‍🏫</span> Faculty
        </a>
        <a href="courses.php" class="nav-link <?= $current_page === 'courses.php' ? 'active' : '' ?>">
            <span class="nav-icon">📚</span> Courses
        </a>
        <a href="departments.php" class="nav-link <?= $current_page === 'departments.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏛️</span> Departments
        </a>

        <span class="nav-section-title">Records</span>
        <a href="attendance.php" class="nav-link <?= $current_page === 'attendance.php' ? 'active' : '' ?>">
            <span class="nav-icon">✅</span> Attendance
        </a>
        <a href="grades.php" class="nav-link <?= $current_page === 'grades.php' ? 'active' : '' ?>">
            <span class="nav-icon">📝</span> Grades
        </a>
        <a href="notices.php" class="nav-link <?= $current_page === 'notices.php' ? 'active' : '' ?>">
            <span class="nav-icon">📢</span> Notices
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-secondary" style="width:100%; font-size:13px;">
            🚪 Logout
        </a>
    </div>
</aside>