<?php
// /admin/api-calls.php
require_once '../includes/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Fetch logs
$sql = "SELECT * FROM api_calls ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

// Count total
$totalSql = "SELECT COUNT(*) as total FROM api_calls";
$totalResult = $conn->query($totalSql);
$totalCount = $totalResult->fetch_assoc()['total'];

// Count completed (has response)
$successSql = "SELECT COUNT(*) as success FROM api_calls WHERE response IS NOT NULL AND response != ''";
$successResult = $conn->query($successSql);
$successCount = $successResult->fetch_assoc()['success'];

// Count uncompleted
$uncompletedSql = "SELECT COUNT(*) as uncompleted FROM api_calls WHERE response IS NULL OR response = ''";
$uncompletedResult = $conn->query($uncompletedSql);
$uncompletedCount = $uncompletedResult->fetch_assoc()['uncompleted'];

function short_json($json, $max = 80) {
    $str = json_encode(json_decode($json), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    return strlen($str) > $max ? substr($str, 0, $max) . '...' : $str;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Call Logs</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; }
        th { background: #eee; }
        .details { font-size: 0.9em; color: #444; }
        .success { color: green; }
        .fail { color: red; }
        .pending { color: orange; }
        .pagination { margin: 20px 0; }
        .pagination a { margin: 0 4px; text-decoration: none; }
        .count-box { display: inline-block; margin-right: 30px; font-size: 1.1em; }
    </style>
</head>
<body>
    <h1>API Call Logs</h1>
    <div>
        <span class="count-box">Total Calls: <b><?= $totalCount ?></b></span>
        <span class="count-box success">Completed: <b><?= $successCount ?></b></span>
        <span class="count-box pending">Uncompleted: <b><?= $uncompletedCount ?></b></span>
    </div>
    <table>
        <tr>
            <th>ID</th>
            <th>Endpoint</th>
            <th>Method</th>
            <th>IP</th>
            <th>Created</th>
            <th>Status</th>
            <th>Details</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= $log['id'] ?></td>
            <td><?= htmlspecialchars($log['endpoint']) ?></td>
            <td><?= $log['method'] ?></td>
            <td><?= $log['ip_address'] ?></td>
            <td><?= $log['created_at'] ?></td>
            <td>
                <?php if ($log['response']): ?>
                    <span class="success">Completed</span>
                <?php else: ?>
                    <span class="pending">Pending</span>
                <?php endif; ?>
            </td>
            <td>
                <button onclick="toggleDetails('details-<?= $log['id'] ?>')">Show</button>
                <div id="details-<?= $log['id'] ?>" class="details" style="display:none;">
                    <b>Headers:</b> <pre><?= htmlspecialchars(short_json($log['headers'], 300)) ?></pre>
                    <b>Body:</b> <pre><?= htmlspecialchars(short_json($log['body'], 300)) ?></pre>
                    <b>Query Params:</b> <pre><?= htmlspecialchars(short_json($log['query_params'], 300)) ?></pre>
                    <b>Response:</b> <pre><?= htmlspecialchars(short_json($log['response'], 300)) ?></pre>
                    <b>Finished At:</b> <?= $log['finished_at'] ?: '—' ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="pagination">
        <?php for ($i = 1; $i <= ceil($totalCount/$perPage); $i++): ?>
            <a href="?page=<?= $i ?>"<?= $i==$page?' style="font-weight:bold;"':'' ?>><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <script>
        function toggleDetails(id) {
            var el = document.getElementById(id);
            if (el.style.display === 'none') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }
    </script>
</body>
</html>
