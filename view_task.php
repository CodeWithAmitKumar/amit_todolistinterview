<?php
// =============================================
// view_task.php — View Task Details
// =============================================
require_once 'db.php';

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($conn, 'SELECT * FROM tasks WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task   = mysqli_fetch_assoc($result);

if (!$task) {
    header('Location: index.php');
    exit;
}

$pClass = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high'][$task['priority']] ?? 'badge-medium';
$sClass = $task['status'] === 'Completed' ? 'status-completed' : 'status-pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — View Task</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg glass-nav sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-check2-circle brand-icon"></i>
            <span class="brand-text">Task<span class="brand-accent">Flow</span></span>
        </a>
        <div class="ms-auto d-flex gap-2">
            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-glass-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            <a href="index.php" class="btn btn-glass-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>
</nav>

<div class="container py-5" style="max-width:720px;">

    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-eye me-2"></i>Task Details</h1>
        <p class="page-subtitle">Full information about this task</p>
    </div>

    <div class="glass-card p-4 p-md-5">

        <!-- Title + badges -->
        <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
            <div class="flex-grow-1">
                <h2 class="view-task-title"><?= htmlspecialchars($task['task_title']) ?></h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="priority-badge <?= $pClass ?>"><?= $task['priority'] ?></span>
                <span class="status-badge <?= $sClass ?>">
                    <i class="bi <?= $task['status']==='Completed' ? 'bi-check-circle-fill' : 'bi-clock-fill' ?> me-1"></i>
                    <?= $task['status'] ?>
                </span>
            </div>
        </div>

        <!-- Description -->
        <div class="view-section mb-4">
            <div class="view-section-label"><i class="bi bi-text-paragraph me-2"></i>Description</div>
            <div class="view-section-value">
                <?= $task['description'] ? nl2br(htmlspecialchars($task['description'])) : '<em class="text-muted">No description provided.</em>' ?>
            </div>
        </div>

        <!-- Meta grid -->
        <div class="view-meta-grid mb-4">
            <div class="view-meta-item">
                <div class="view-meta-label"><i class="bi bi-calendar-event me-1"></i>Due Date</div>
                <div class="view-meta-val"><?= $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : '—' ?></div>
            </div>
            <div class="view-meta-item">
                <div class="view-meta-label"><i class="bi bi-clock-history me-1"></i>Created At</div>
                <div class="view-meta-val"><?= date('d M Y, H:i', strtotime($task['created_at'])) ?></div>
            </div>
            <div class="view-meta-item">
                <div class="view-meta-label"><i class="bi bi-hash me-1"></i>Task ID</div>
                <div class="view-meta-val">#<?= $task['id'] ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-3 mt-2">
            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-glass-primary flex-fill py-3">
                <i class="bi bi-pencil me-2"></i>Edit Task
            </a>
            <a href="#" class="btn btn-glass-danger flex-fill py-3"
               onclick="if(confirm('Delete this task?')) window.location='delete_task.php?id=<?= $task['id'] ?>'">
               <i class="bi bi-trash3 me-2"></i>Delete
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
