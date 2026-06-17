<?php
// =============================================
// delete_task.php — Delete a Task
// =============================================
require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = mysqli_prepare($conn, 'DELETE FROM tasks WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header('Location: index.php?msg=deleted');
exit;
?>
