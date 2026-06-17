<?php
// =============================================
// edit_task.php — Edit / Update a Task
// =============================================
require_once 'db.php';

// Fetch task
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($conn, 'SELECT * FROM tasks WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task   = mysqli_fetch_assoc($result);

if (!$task) {
    header('Location: index.php?msg=notfound');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $task_title  = trim($_POST['task_title']  ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = trim($_POST['priority']    ?? '');
    $status      = trim($_POST['status']      ?? '');
    $due_date    = trim($_POST['due_date']    ?? '');

    // Keep values
    $task = array_merge($task, compact('task_title','description','priority','status','due_date'));

    if ($task_title === '')           $errors[] = 'Task title is required.';
    if (strlen($task_title) > 255)   $errors[] = 'Title must be 255 characters or fewer.';
    if (!in_array($priority, ['Low','Medium','High'])) $errors[] = 'Invalid priority.';
    if (!in_array($status,   ['Pending','Completed'])) $errors[] = 'Invalid status.';
    if ($due_date !== '' && !strtotime($due_date))     $errors[] = 'Invalid due date.';

    if (empty($errors)) {
        $sql  = 'UPDATE tasks SET task_title=?, description=?, priority=?, status=?, due_date=? WHERE id=?';
        $stmt = mysqli_prepare($conn, $sql);
        $dd   = $due_date ?: null;
        mysqli_stmt_bind_param($stmt, 'sssssi', $task_title, $description, $priority, $status, $dd, $id);
        mysqli_stmt_execute($stmt);

        header('Location: index.php?msg=updated');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Edit Task</title>
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
        <div class="ms-auto">
            <a href="index.php" class="btn btn-glass-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
        </div>
    </div>
</nav>

<div class="container py-5" style="max-width:700px;">

    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-pencil-square me-2"></i>Edit Task</h1>
        <p class="page-subtitle">Update the task information below</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert glass-alert alert-danger mb-4">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="glass-card p-4 p-md-5">
        <form method="POST" action="edit_task.php?id=<?= $id ?>" novalidate>

            <div class="mb-4">
                <label for="task_title" class="form-label-glass">Task Title <span class="text-danger">*</span></label>
                <input type="text" id="task_title" name="task_title"
                       class="form-control glass-input"
                       value="<?= htmlspecialchars($task['task_title']) ?>"
                       maxlength="255" required>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label-glass">Description</label>
                <textarea id="description" name="description"
                          class="form-control glass-input"
                          rows="4"><?= htmlspecialchars($task['description']) ?></textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="priority" class="form-label-glass">Priority</label>
                    <select id="priority" name="priority" class="form-select glass-input">
                        <option value="Low"    <?= $task['priority']==='Low'    ? 'selected' : '' ?>>🟢 Low</option>
                        <option value="Medium" <?= $task['priority']==='Medium' ? 'selected' : '' ?>>🟡 Medium</option>
                        <option value="High"   <?= $task['priority']==='High'   ? 'selected' : '' ?>>🔴 High</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label-glass">Status</label>
                    <select id="status" name="status" class="form-select glass-input">
                        <option value="Pending"   <?= $task['status']==='Pending'   ? 'selected' : '' ?>>⏳ Pending</option>
                        <option value="Completed" <?= $task['status']==='Completed' ? 'selected' : '' ?>>✅ Completed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="due_date" class="form-label-glass">Due Date</label>
                    <input type="date" id="due_date" name="due_date"
                           class="form-control glass-input"
                           value="<?= htmlspecialchars($task['due_date'] ?? '') ?>">
                </div>
            </div>

            <!-- Task meta info -->
            <div class="task-meta-info mb-4">
                <span><i class="bi bi-hash me-1"></i>ID: <?= $task['id'] ?></span>
                <span><i class="bi bi-calendar-plus me-1"></i>Created: <?= date('d M Y, H:i', strtotime($task['created_at'])) ?></span>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-glass-primary flex-fill py-3">
                    <i class="bi bi-save me-2"></i>Update Task
                </button>
                <a href="index.php" class="btn btn-glass-secondary flex-fill py-3">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
