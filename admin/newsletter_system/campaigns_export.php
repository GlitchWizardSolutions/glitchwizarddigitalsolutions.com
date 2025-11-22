<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_POST['file_type'], $_POST['table'])) {
    // Check if the table is valid
    $table = in_array($_POST['table'], ['campaigns', 'campaign_items', 'campaign_clicks', 'campaign_opens', 'campaign_unsubscribes']) ? $_POST['table'] : 'campaigns';
    // Get all campaigns
    $result = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY id ASC');
    $campaigns = [];
    $columns = [];
    // Fetch all records into an associative array
    if ($result->rowCount() > 0) {
        // Fetch column names
        for ($i = 0; $i < $result->columnCount(); $i++) {
            $columns[] = $result->getColumnMeta($i)['name'];
        }
        // Fetch associative array
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $campaigns[] = $row;
        }    
    }
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = $table . '.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($campaigns as $campaign) {
            fputcsv($fp, $campaign);
        }
        fclose($fp);
        exit;
    }
    // Convert to TXT
    if ($_POST['file_type'] == 'txt') {
        $filename = $table . '.txt';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/txt');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, implode(',', $columns) . PHP_EOL);
        foreach ($campaigns as $campaign) {
            $line = '';
            foreach ($campaign as $key => $value) {
                if (is_string($value)) {
                    $value = '"' . str_replace('"', '\"', $value) . '"';
                }
                $line .= $value . ',';
            }
            $line = rtrim($line, ',') . PHP_EOL;
            fwrite($fp, $line);
        }
        fclose($fp);
        exit;
    }
    // Convert to JSON
    if ($_POST['file_type'] == 'json') {
        $filename = $table . '.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($campaigns));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = $table . '.xml';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<' . $table . '>' . PHP_EOL);
        foreach ($campaigns as $campaign) {
            fwrite($fp, '    <item>' . PHP_EOL);
            foreach ($campaign as $key => $value) {
                fwrite($fp, '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL);
            }
            fwrite($fp, '    </item>' . PHP_EOL);
        }
        fwrite($fp, '</' . $table . '>' . PHP_EOL);
        fclose($fp);
        exit;
    }
}
?>
<?=template_admin_header('Export Campaigns', 'campaigns', 'export')?>

<?=generate_breadcrumbs([
    ['label' => 'Campaigns', 'url' => 'campaigns.php'],
    ['label' => 'Export Campaigns']
])?>

<form method="post" class="form-professional">

    <div class="content-title">
        <div class="icon alt"><?=svg_icon_download()?></div>
        <div class="txt">
            <h2>Export Campaigns</h2>
            <p class="subtitle">Download campaign data files</p>
        </div>
        <div class="btns">
            <a href="campaigns.php" class="btn btn-secondary mar-right-1">Cancel</a>
            <input type="submit" name="submit" value="Export" class="btn btn-success">
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Export Options</h3>

            <label for="table"><span class="required">*</span> Table</label>
            <select id="table" name="table" required>
                <option value="campaigns">Campaigns</option>
                <option value="campaign_items">Campaign Items</option>
                <option value="campaign_clicks">Campaign Clicks</option>
                <option value="campaign_opens">Campaign Opens</option>
                <option value="campaign_unsubscribes">Campaign Unsubscribes</option>
            </select>

            <label for="file_type"><span class="required">*</span> File Type</label>
            <select id="file_type" name="file_type" required>
                <option value="csv">CSV</option>
                <option value="txt">TXT</option>
                <option value="json">JSON</option>
                <option value="xml">XML</option>
            </select>

        </div>

    </div>

</form>

<?=template_admin_footer()?>