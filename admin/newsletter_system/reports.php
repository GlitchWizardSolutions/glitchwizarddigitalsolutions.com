<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
template_admin_header('Newsletter', 'reports', 'fa-chart-line');

// Create newsletter_tracking table if it doesn't exist
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS newsletter_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tracking_code VARCHAR(255) NOT NULL,
        action VARCHAR(50) NOT NULL,
        url TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        tracked_at DATETIME NOT NULL,
        INDEX idx_tracking_code (tracking_code),
        INDEX idx_action (action),
        INDEX idx_tracked_at (tracked_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
} catch (PDOException $e) {
    // Table creation failed
    echo '<div style="padding: 20px; background: #ffebee; color: #c62828; border-radius: 4px; margin: 20px;">
        <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

// Get tracking stats
$stmt = $pdo->query('SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN action = "open" THEN 1 ELSE 0 END) as opens,
    SUM(CASE WHEN action = "click" THEN 1 ELSE 0 END) as clicks,
    COUNT(DISTINCT tracking_code) as unique_recipients
FROM newsletter_tracking');
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent tracking events
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$stmt = $pdo->query('SELECT * FROM newsletter_tracking ORDER BY tracked_at DESC LIMIT ' . $limit);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get click-through data grouped by URL
$stmt = $pdo->query('SELECT 
    url,
    COUNT(*) as click_count,
    COUNT(DISTINCT tracking_code) as unique_clicks
FROM newsletter_tracking 
WHERE action = "click" AND url IS NOT NULL
GROUP BY url
ORDER BY click_count DESC
LIMIT 20');
$top_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}
.stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #2196F3;
    margin: 10px 0;
}
.stat-label {
    color: #666;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.tracking-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}
.tracking-table table {
    width: 100%;
    border-collapse: collapse;
}
.tracking-table th {
    background: #f5f5f5;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #ddd;
}
.tracking-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}
.tracking-table tr:hover {
    background: #f9f9f9;
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}
.badge-open {
    background: #4CAF50;
    color: white;
}
.badge-click {
    background: #2196F3;
    color: white;
}
.url-cell {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.section-title {
    font-size: 20px;
    font-weight: 600;
    margin: 30px 0 15px 0;
    color: #333;
}
.filters {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<div class="content-block">
    <div class="content-block-title">Newsletter Tracking Reports</div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Events</div>
            <div class="stat-number"><?=number_format($stats['total_events'])?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Email Opens</div>
            <div class="stat-number"><?=number_format($stats['opens'])?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Link Clicks</div>
            <div class="stat-number"><?=number_format($stats['clicks'])?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Unique Recipients</div>
            <div class="stat-number"><?=number_format($stats['unique_recipients'])?></div>
        </div>
    </div>

    <?php if (count($top_links) > 0): ?>
    <h2 class="section-title">Top Clicked Links</h2>
    <div class="tracking-table">
        <table>
            <thead>
                <tr>
                    <th>URL</th>
                    <th style="text-align: center;">Total Clicks</th>
                    <th style="text-align: center;">Unique Clicks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_links as $link): ?>
                <tr>
                    <td class="url-cell" title="<?=htmlspecialchars($link['url'], ENT_QUOTES)?>">
                        <a href="<?=htmlspecialchars($link['url'], ENT_QUOTES)?>" target="_blank">
                            <?=htmlspecialchars($link['url'], ENT_QUOTES)?>
                        </a>
                    </td>
                    <td style="text-align: center;"><?=number_format($link['click_count'])?></td>
                    <td style="text-align: center;"><?=number_format($link['unique_clicks'])?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="filters">
        <label for="limit">Show recent events: </label>
        <select id="limit" onchange="window.location.href='reports.php?limit='+this.value">
            <option value="50" <?=$limit == 50 ? 'selected' : ''?>>50</option>
            <option value="100" <?=$limit == 100 ? 'selected' : ''?>>100</option>
            <option value="250" <?=$limit == 250 ? 'selected' : ''?>>250</option>
            <option value="500" <?=$limit == 500 ? 'selected' : ''?>>500</option>
        </select>
    </div>

    <h2 class="section-title">Recent Activity</h2>
    <div class="tracking-table">
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>URL</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Date/Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?=$event['action']?>">
                                <?=strtoupper($event['action'])?>
                            </span>
                        </td>
                        <td class="url-cell">
                            <?php if ($event['url']): ?>
                                <a href="<?=htmlspecialchars($event['url'], ENT_QUOTES)?>" target="_blank" title="<?=htmlspecialchars($event['url'], ENT_QUOTES)?>">
                                    <?=htmlspecialchars($event['url'], ENT_QUOTES)?>
                                </a>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?=htmlspecialchars($event['ip_address'], ENT_QUOTES)?></td>
                        <td class="url-cell" title="<?=htmlspecialchars($event['user_agent'], ENT_QUOTES)?>">
                            <?=htmlspecialchars(substr($event['user_agent'], 0, 50), ENT_QUOTES)?><?=strlen($event['user_agent']) > 50 ? '...' : ''?>
                        </td>
                        <td><?=date('M j, Y g:i A', strtotime($event['tracked_at']))?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                            No tracking data yet. Send a newsletter with tracking codes to see data here.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
template_admin_footer();
?>
