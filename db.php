<?php
// =============================================
// db.php — Database Connection
// =============================================

define('DB_HOST',     'localhost');
define('DB_USER',     'root');       // Change if needed
define('DB_PASS',     '');           // Change if needed
define('DB_NAME',     'mytodo_list');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:red;">
            <h2>⚠️ Database Connection Failed</h2>
            <p>' . mysqli_connect_error() . '</p>
            <p>Please check your credentials in <code>db.php</code> and make sure MySQL is running.</p>
         </div>');
}

mysqli_set_charset($conn, 'utf8mb4');
?>
