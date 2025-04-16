<?php
// /admin/tts-analytics.php
// TTS Playback Analytics Page

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'admin-functions.php';
require_once 'analytics-admin-helpers.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Query TTS events (play, pause, end)
$query = "
    SELECT 
        data->>'$.talkId' AS talkId,
        data->>'$.text' AS text,
        MIN(timestamp) AS first_played,
        MAX(timestamp) AS last_played,
        MAX(CAST(data->>'$.percentPlayed' AS DECIMAL(5,2))) AS max_percent_played,
        MAX(CAST(data->>'$.currentTime' AS DECIMAL(10,2))) AS max_time_played,
        COUNT(CASE WHEN event_type = 'tts_play' THEN 1 END) AS play_count,
        COUNT(CASE WHEN event_type = 'tts_end' THEN 1 END) AS end_count,
        COUNT(CASE WHEN event_type = 'tts_pause' THEN 1 END) AS pause_count,
        data->>'$.audioUrl' AS audioUrl,
        data->>'$.voice' AS voice
    FROM events
    WHERE event_type IN ('tts_play', 'tts_pause', 'tts_end')
      AND data->>'$.talkId' IS NOT NULL
    GROUP BY talkId, text, audioUrl, voice
    ORDER BY last_played DESC
    LIMIT 200
";
$result = $conn->query($query);
$ttsBlocks = [];
while ($row = $result->fetch_assoc()) {
    $ttsBlocks[] = $row;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS Analytics - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .tts-table { width:100%; border-collapse:collapse; margin-top:2em; }
        .tts-table th, .tts-table td { border:1px solid #ccc; padding:8px; }
        .tts-table th { background:#f6f6f6; }
        .tts-block-text { max-width:400px; white-space:pre-line; word-break:break-word; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="site-title">TTS Playback Analytics</h1>
                <div class="user-actions">
                    <span class="username">Welcome, <?php echo $_SESSION['username']; ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>
    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="tts-analytics.php" class="nav-link active">TTS Analytics</a></li>
                <li class="nav-item"><a href="sessions.php" class="nav-link">Sessions</a></li>
                <li class="nav-item"><a href="playback.php" class="nav-link">Session Playback</a></li>
                <li class="nav-item"><a href="examples.php" class="nav-link">Manage Examples</a></li>
                <li class="nav-item"><a href="export.php" class="nav-link">Export Data</a></li>
            </ul>
        </div>
    </nav>
    <main class="admin-content">
        <div class="container">
            <h2>Generated Text Blocks (TTS)</h2>
            <table class="tts-table">
                <thead>
                    <tr>
                        <th>Text Block</th>
                        <th>Talk ID</th>
                        <th>Voice</th>
                        <th>First Played</th>
                        <th>Last Played</th>
                        <th>Max % Played</th>
                        <th>Max Time Played (s)</th>
                        <th>Plays</th>
                        <th>Pauses</th>
                        <th>Completions</th>
                        <th>Audio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ttsBlocks as $block): ?>
                        <tr>
                            <td class="tts-block-text"><?php echo htmlspecialchars($block['text']); ?></td>
                            <td><?php echo htmlspecialchars($block['talkId']); ?></td>
                            <td><?php echo htmlspecialchars($block['voice']); ?></td>
                            <td><?php echo htmlspecialchars($block['first_played']); ?></td>
                            <td><?php echo htmlspecialchars($block['last_played']); ?></td>
                            <td><?php echo $block['max_percent_played'] !== null ? htmlspecialchars($block['max_percent_played']) . '%' : '-'; ?></td>
                            <td><?php echo $block['max_time_played'] !== null ? htmlspecialchars($block['max_time_played']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($block['play_count']); ?></td>
                            <td><?php echo htmlspecialchars($block['pause_count']); ?></td>
                            <td><?php echo htmlspecialchars($block['end_count']); ?></td>
                            <td><?php if($block['audioUrl']): ?><audio controls src="<?php echo htmlspecialchars($block['audioUrl']); ?>" style="max-width:120px;"></audio><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
