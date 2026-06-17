<?php
// =============================================
// import_excel.php — Import Tasks from Excel
// Uses PhpSpreadsheet (installed via Composer)
// =============================================
require_once 'db.php';

// Check if Composer autoloader exists
$autoloader = __DIR__ . '/vendor/autoload.php';
$phpSpreadsheetAvailable = file_exists($autoloader);
if ($phpSpreadsheetAvailable) {
    require_once $autoloader;
}

$errors   = [];
$success  = [];
$imported = 0;
$skipped  = 0;

// Valid values
$validPriorities = ['Low', 'Medium', 'High'];
$validStatuses   = ['Pending', 'Completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {

    if (!$phpSpreadsheetAvailable) {
        $errors[] = 'PhpSpreadsheet is not installed. Please run <code>composer require phpoffice/phpspreadsheet</code> in the project root.';
    } else {

        $file     = $_FILES['excel_file'];
        $fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['xlsx', 'xls', 'csv'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error. Please try again.';
        } elseif (!in_array($fileExt, $allowed)) {
            $errors[] = 'Invalid file type. Only .xlsx, .xls, and .csv files are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File size must not exceed 5 MB.';
        } else {

            try {
                // Load appropriate reader
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheet       = $spreadsheet->getActiveSheet();
                $rows        = $sheet->toArray(null, true, true, true);

                // Skip header row (row 1)
                $isFirst = true;
                foreach ($rows as $row) {
                    if ($isFirst) { $isFirst = false; continue; }

                    // Map columns A-E
                    $task_title  = trim((string)($row['A'] ?? ''));
                    $description = trim((string)($row['B'] ?? ''));
                    $priority    = ucfirst(strtolower(trim((string)($row['C'] ?? 'Medium'))));
                    $status      = ucfirst(strtolower(trim((string)($row['D'] ?? 'Pending'))));
                    $due_date    = trim((string)($row['E'] ?? ''));

                    // Skip empty rows
                    if ($task_title === '') { $skipped++; continue; }

                    // Sanitize enum values
                    if (!in_array($priority, $validPriorities)) $priority = 'Medium';
                    if (!in_array($status,   $validStatuses))   $status   = 'Pending';

                    // Parse date (handle Excel numeric dates and string dates)
                    $dd = null;
                    if ($due_date !== '') {
                        if (is_numeric($due_date)) {
                            // Excel serial date
                            $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float)$due_date);
                            $dd = date('Y-m-d', $timestamp);
                        } else {
                            $ts = strtotime($due_date);
                            if ($ts) $dd = date('Y-m-d', $ts);
                        }
                    }

                    // Insert with prepared statement
                    $sql  = 'INSERT INTO tasks (task_title, description, priority, status, due_date) VALUES (?, ?, ?, ?, ?)';
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, 'sssss', $task_title, $description, $priority, $status, $dd);
                    if (mysqli_stmt_execute($stmt)) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }

                if ($imported > 0) {
                    header("Location: index.php?msg=imported");
                    exit;
                } else {
                    $errors[] = 'No tasks were imported. Make sure your file has valid data rows below the header.';
                }

            } catch (\Exception $e) {
                $errors[] = 'Failed to read the file: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Import Excel</title>
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

<div class="container py-5" style="max-width:760px;">

    <div class="page-header mb-4">
        <h1 class="page-title"><i class="bi bi-file-earmark-excel me-2"></i>Import from Excel</h1>
        <p class="page-subtitle">Upload an .xlsx, .xls or .csv file to bulk-import tasks</p>
    </div>

    <?php if (!$phpSpreadsheetAvailable): ?>
    <div class="alert glass-alert alert-warning mb-4">
        <h5><i class="bi bi-exclamation-triangle me-1"></i> PhpSpreadsheet Not Installed</h5>
        <p class="mb-2">To enable Excel import, install PhpSpreadsheet via Composer. Open a terminal in your project folder and run:</p>
        <code class="d-block p-3 rounded" style="background:rgba(0,0,0,.3);">composer require phpoffice/phpspreadsheet</code>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert glass-alert alert-danger mb-4">
            <strong><i class="bi bi-x-circle me-1"></i>Import Failed</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Upload card -->
    <div class="glass-card p-4 p-md-5 mb-4">
        <form method="POST" action="import_excel.php" enctype="multipart/form-data" id="importForm">
            <div class="upload-zone mb-4" id="uploadZone">
                <i class="bi bi-cloud-upload upload-icon"></i>
                <p class="upload-text">Drag & drop your file here, or <span class="upload-browse">browse</span></p>
                <p class="upload-hint">Supports .xlsx, .xls, .csv — Max 5 MB</p>
                <input type="file" name="excel_file" id="excelFileInput" class="upload-input" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="selected-file mb-4" id="selectedFile" style="display:none;">
                <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>
                <span id="selectedFileName"></span>
                <a href="#" id="clearFile" class="ms-2 text-danger" onclick="clearSelection(event)"><i class="bi bi-x-circle"></i></a>
            </div>
            <button type="submit" class="btn btn-glass-primary w-100 py-3" <?= !$phpSpreadsheetAvailable ? 'disabled' : '' ?>>
                <i class="bi bi-upload me-2"></i>Import Tasks
            </button>
        </form>
    </div>

    <!-- Format guide -->
    <div class="glass-card p-4">
        <h5 class="guide-title mb-3"><i class="bi bi-info-circle me-2"></i>Required File Format</h5>
        <p class="text-muted small mb-3">Your file must have a <strong>header row</strong> as shown below, followed by data rows:</p>
        <div class="table-responsive">
            <table class="table glass-table mb-3">
                <thead>
                    <tr>
                        <th>A — task_title</th>
                        <th>B — description</th>
                        <th>C — priority</th>
                        <th>D — status</th>
                        <th>E — due_date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Design Homepage</td>
                        <td>Create wireframes</td>
                        <td>High</td>
                        <td>Pending</td>
                        <td>2026-07-01</td>
                    </tr>
                    <tr>
                        <td>Write Tests</td>
                        <td>Unit test all functions</td>
                        <td>Medium</td>
                        <td>Completed</td>
                        <td>2026-07-10</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="text-muted small mb-0 ps-3">
            <li><strong>priority</strong>: Low, Medium, or High (defaults to Medium if invalid)</li>
            <li><strong>status</strong>: Pending or Completed (defaults to Pending if invalid)</li>
            <li><strong>due_date</strong>: YYYY-MM-DD format recommended; row 1 is treated as the header and skipped</li>
        </ul>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const zone     = document.getElementById('uploadZone');
const fileInput = document.getElementById('excelFileInput');
const selectedFile = document.getElementById('selectedFile');
const fileName  = document.getElementById('selectedFileName');

fileInput.addEventListener('change', () => showFile(fileInput.files[0]));

zone.addEventListener('click', () => fileInput.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', ()  => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFile(e.dataTransfer.files[0]);
    }
});

function showFile(file) {
    if (!file) return;
    fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    zone.style.display = 'none';
    selectedFile.style.display = 'flex';
}

function clearSelection(e) {
    e.preventDefault();
    fileInput.value = '';
    zone.style.display = 'flex';
    selectedFile.style.display = 'none';
}
</script>
</body>
</html>
