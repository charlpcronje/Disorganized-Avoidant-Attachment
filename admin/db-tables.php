<?php
// /admin/db-tables.php
// Admin page to view all database tables in tabs
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get all table names from the database
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Handle delete request
if (isset($_POST['delete_all']) && isset($_POST['table'])) {
    $table = $conn->real_escape_string($_POST['table']);
    $conn->query("DELETE FROM `$table`");
    $deleted = true;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Tables</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tab-btn { margin-right: 10px; padding: 5px 10px; cursor: pointer; }
        .tab-btn.active { background: #444; color: #fff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .delete-btn { color: red; font-weight: bold; margin-left: 20px; }
    </style>
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(tb => tb.classList.remove('active'));
            document.getElementById('btn-' + tabId).classList.add('active');
        }
        function confirmDelete(table) {
            if (confirm('Are you sure you want to delete ALL data from ' + table + '? This cannot be undone!')) {
                document.getElementById('delete-form-' + table).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Database Tables</h1>
    <?php if (!empty($deleted)) echo '<p style="color:red;">Table cleared.</p>'; ?>
    <div>
        <?php foreach ($tables as $idx => $table): ?>
            <button class="tab-btn<?php if ($idx === 0) echo ' active'; ?>" id="btn-tab-<?php echo $table; ?>" onclick="showTab('tab-<?php echo $table; ?>')">
                <?php echo htmlspecialchars($table); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div>
        <?php foreach ($tables as $idx => $table): ?>
            <div class="tab-content<?php if ($idx === 0) echo ' active'; ?>" id="tab-<?php echo $table; ?>">
                <h2><?php echo htmlspecialchars($table); ?></h2>
                <form method="post" id="delete-form-<?php echo $table; ?>">
                    <input type="hidden" name="delete_all" value="1">
                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                    <button type="button" class="delete-btn" onclick="confirmDelete('<?php echo htmlspecialchars($table); ?>')">Delete All</button>
                </form>
                <table border="1" cellpadding="5" cellspacing="0">
                    <tr>
                        <?php
                        // Get columns
                        $cols = [];
                        $colRes = $conn->query("SHOW COLUMNS FROM `$table`");
                        while ($col = $colRes->fetch_assoc()) {
                            $cols[] = $col['Field'];
                            echo '<th>' . htmlspecialchars($col['Field']) . '</th>';
                        }
                        ?>
                    </tr>
                    <?php
                    // Get all rows
                    $dataRes = $conn->query("SELECT * FROM `$table` LIMIT 1000");
                    while ($row = $dataRes->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($cols as $col): ?>
                                <td><?php echo htmlspecialchars($row[$col]); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        // Activate first tab on load
        document.addEventListener('DOMContentLoaded', function() {
            showTab(document.querySelector('.tab-content').id);
        });
    </script>
</body>
</html>
