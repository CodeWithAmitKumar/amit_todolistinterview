<?php
// =============================================
// index.php — Dashboard + Task Listing
// =============================================
require_once 'db.php';

// ---- Search / Filter ----
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$status   = isset($_GET['status'])   ? trim($_GET['status'])   : '';

// ---- Build query ----
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = 'task_title LIKE ?';
    $params[] = '%' . $search . '%';
    $types   .= 's';
}
if ($priority !== '') {
    $where[]  = 'priority = ?';
    $params[] = $priority;
    $types   .= 's';
}
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
    $types   .= 's';
}

$sql = 'SELECT * FROM tasks';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tasks  = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ---- Stats ----
$total_res     = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM tasks');
$pending_res   = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tasks WHERE status='Pending'");
$completed_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tasks WHERE status='Completed'");

$total_count     = mysqli_fetch_assoc($total_res)['c'];
$pending_count   = mysqli_fetch_assoc($pending_res)['c'];
$completed_count = mysqli_fetch_assoc($completed_res)['c'];

// ---- Flash message ----
$flash = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'added'   => ['success', '✅ Task added successfully!'],
        'updated' => ['success', '✏️ Task updated successfully!'],
        'deleted' => ['danger',  '🗑️ Task deleted successfully!'],
        'imported'=> ['success', '📊 Tasks imported from Excel successfully!'],
    ];
    if (isset($msgs[$_GET['msg']])) {
        [$type, $text] = $msgs[$_GET['msg']];
        $flash = "<div class='alert alert-{$type} glass-alert'>{$text}</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Dashboard</title>
    <meta name="description" content="Modern To-Do List Manager built with PHP, MySQL and Bootstrap 5">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg glass-nav sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-check2-circle brand-icon"></i>
            <span class="brand-text">Task<span class="brand-accent">Flow</span></span>
        </a>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <a href="add_task.php"    class="btn btn-glass-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Task</a>
            <a href="import_excel.php" class="btn btn-glass-secondary btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Import Excel</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <!-- Flash -->
    <?= $flash ?>

    <!-- ===== PAGE TITLE ===== -->
    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-layout-text-sidebar-reverse me-2"></i>Dashboard</h1>
        <p class="page-subtitle">Manage your tasks efficiently</p>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-4">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="bi bi-list-task"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_count ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="stat-card stat-pending">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?= $pending_count ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="stat-card stat-completed">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?= $completed_count ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SEARCH / FILTER ===== -->
    <div class="glass-card mb-4 p-4">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label-glass">Search by Title</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-search input-icon"></i>
                    <input type="text" name="search" class="form-control glass-input"
                           placeholder="Search tasks..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label-glass">Priority</label>
                <select name="priority" class="form-select glass-input">
                    <option value="">All Priorities</option>
                    <option value="Low"    <?= $priority==='Low'    ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $priority==='Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High"   <?= $priority==='High'   ? 'selected' : '' ?>>High</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label-glass">Status</label>
                <select name="status" class="form-select glass-input">
                    <option value="">All Statuses</option>
                    <option value="Pending"   <?= $status==='Pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="Completed" <?= $status==='Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-glass-primary flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="index.php" class="btn btn-glass-danger flex-fill" title="Clear"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>

    <!-- ===== TASK TABLE ===== -->
    <div class="glass-card p-0 overflow-hidden">
        <div class="table-header px-4 py-3 d-flex align-items-center justify-content-between">
            <span class="table-title"><i class="bi bi-table me-2"></i>All Tasks
                <span class="badge-count ms-2"><?= count($tasks) ?></span>
            </span>
        </div>
        <?php if (empty($tasks)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-inbox empty-icon"></i>
                <p class="empty-text">No tasks found.</p>
                <a href="add_task.php" class="btn btn-glass-primary mt-2"><i class="bi bi-plus-lg me-1"></i>Add Your First Task</a>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table glass-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $i => $t): ?>
                    <tr class="task-row">
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <div class="task-title-cell">
                                <span class="task-title-text"><?= htmlspecialchars($t['task_title']) ?></span>
                                <?php if ($t['description']): ?>
                                    <small class="task-desc-text"><?= htmlspecialchars(substr($t['description'], 0, 60)) ?><?= strlen($t['description']) > 60 ? '…' : '' ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $pClass = ['Low'=>'badge-low','Medium'=>'badge-medium','High'=>'badge-high'][$t['priority']] ?? 'badge-medium';
                            ?>
                            <span class="priority-badge <?= $pClass ?>"><?= $t['priority'] ?></span>
                        </td>
                        <td>
                            <?php $sClass = $t['status'] === 'Completed' ? 'status-completed' : 'status-pending'; ?>
                            <span class="status-badge <?= $sClass ?>">
                                <i class="bi <?= $t['status']==='Completed' ? 'bi-check-circle-fill' : 'bi-clock-fill' ?> me-1"></i>
                                <?= $t['status'] ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?></td>
                        <td class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="action-btns">
                                <a href="view_task.php?id=<?= $t['id'] ?>" class="action-btn action-view" title="View"><i class="bi bi-eye"></i></a>
                                <a href="edit_task.php?id=<?= $t['id'] ?>" class="action-btn action-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="#" class="action-btn action-delete" title="Delete"
                                   onclick="confirmDelete(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['task_title'])) ?>')">
                                   <i class="bi bi-trash3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /container -->

<!-- ===== DELETE MODAL ===== -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete:</p>
                <p class="fw-bold" id="deleteTaskName" style="color:#fff;"></p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-glass-danger"><i class="bi bi-trash3 me-1"></i>Delete</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, title) {
    document.getElementById('deleteTaskName').textContent = title;
    document.getElementById('deleteConfirmBtn').href = 'delete_task.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>
