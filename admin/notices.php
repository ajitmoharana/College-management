<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin('admin');

$success = $error = '';
$action  = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notice'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF).';
    } else {
        $title     = sanitize($conn, $_POST['title'] ?? '');
        $content   = sanitize($conn, $_POST['content'] ?? '');
        $target    = sanitize($conn, $_POST['target_role'] ?? 'all');
        $posted_by = (int)($_SESSION['user_id'] ?? 0);

        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } elseif (!in_array($target, ['all', 'student', 'faculty'], true)) {
            $error = 'Invalid target audience.';
        } else {
            try {
                $stmt = mysqli_prepare($conn, 'INSERT INTO notices (title, content, posted_by, target_role) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'ssis', $title, $content, $posted_by, $target);
                mysqli_stmt_execute($stmt);
                $success = 'Notice posted.';
                $action = 'list';
            } catch (Throwable $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request (CSRF).';
    } else {
        $del_id = (int)$_POST['delete_id'];
        try {
            $stmt = mysqli_prepare($conn, 'DELETE FROM notices WHERE id=?');
            mysqli_stmt_bind_param($stmt, 'i', $del_id);
            mysqli_stmt_execute($stmt);
            $success = 'Notice deleted.';
        } catch (Throwable $e) {
            $error = 'Delete failed: ' . $e->getMessage();
        }
    }
}

$notices = mysqli_query($conn, "
    SELECT n.*, u.name as author
    FROM notices n
    LEFT JOIN users u ON n.posted_by = u.id
    ORDER BY n.created_at DESC, n.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Notices – CollegeMS</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title"><h1>Notices</h1><p>Post and manage announcements</p></div>
            </div>
            <div class="topbar-right">
                <a href="?action=add" class="btn btn-primary btn-sm">📢 Post Notice</a>
            </div>
        </header>

        <div class="page-content">
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>📢 Post New Notice</h3>
                    <a href="notices.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" required placeholder="Notice title">
                        </div>
                        <div class="form-group">
                            <label>Content *</label>
                            <textarea name="content" rows="5" required placeholder="Write notice content..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Target Audience</label>
                            <select name="target_role">
                                <option value="all">Everyone</option>
                                <option value="student">Students Only</option>
                                <option value="faculty">Faculty Only</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:12px;">
                            <button type="submit" name="add_notice" class="btn btn-primary" style="width:auto; padding:13px 30px;">📢 Post Notice</button>
                            <a href="notices.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                <div class="card">
                    <div class="card-body" style="padding:20px 25px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:15px;">
                            <div>
                                <h3 style="font-size:16px; margin-bottom:8px;"><?= e($n['title']) ?></h3>
                                <p style="color:var(--text-muted); font-size:14px; line-height:1.6;"><?= nl2br(e($n['content'])) ?></p>
                                <div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
                                    <span class="chip <?= $n['target_role'] === 'all' ? 'chip-info' : ($n['target_role'] === 'student' ? 'chip-success' : 'chip-warning') ?>">
                                        <?= e(ucfirst($n['target_role'])) ?>
                                    </span>
                                    <span style="font-size:12px; color:var(--text-muted);">
                                        Posted by <?= e($n['author'] ?? '—') ?> · <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                            </div>

                            <a href="#" onclick="confirmDelete(<?= (int)$n['id'] ?>, 'notices.php')" class="btn btn-danger btn-sm" style="flex-shrink:0;">🗑</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../js/main.js"></script>
</body>
</html>