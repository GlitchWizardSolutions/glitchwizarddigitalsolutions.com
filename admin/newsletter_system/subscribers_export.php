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
    $table = in_array($_POST['table'], ['subscribers', 'groups', 'group_subscribers']) ? $_POST['table'] : 'subscribers';
    // Get all subscribers
    $result = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY id ASC');
    $subscribers = [];
    $columns = [];
    // Fetch all records into an associative array
    if ($result->rowCount() > 0) {
        // Fetch column names
        for ($i = 0; $i < $result->columnCount(); $i++) {
            $columns[] = $result->getColumnMeta($i)['name'];
        }
        // Fetch associative array
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $subscribers[] = $row;
        }    
    }
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = $table . '.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($subscribers as $subscriber) {
            fputcsv($fp, $subscriber);
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
        foreach ($subscribers as $subscriber) {
            $line = '';
            foreach ($subscriber as $key => $value) {
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
        fwrite($fp, json_encode($subscribers));
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
        foreach ($subscribers as $subscriber) {
            fwrite($fp, '    <item>' . PHP_EOL);
            foreach ($subscriber as $key => $value) {
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
<?=template_admin_header('Export Subscribers', 'subscribers', 'export')?>

<?=generate_breadcrumbs([
    ['label' => 'Subscribers', 'url' => 'subscribers.php'],
    ['label' => 'Export Subscribers']
])?>

<form method="post" class="form-professional">

    <div class="content-title mb-3">
        <div class="icon alt"><?=svg_icon_download()?></div>
        <div class="txt">
            <h2>Export Subscribers</h2>
            <p class="subtitle">Download subscriber data files</p>
        </div>
        <div class="btns">
            <a href="subscribers.php" class="btn btn-secondary mar-right-1">Cancel</a>
            <input type="submit" name="submit" value="Export" class="btn btn-success">
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Export Options</h3>

            <label for="table"><span class="required">*</span> Table</label>
            <select id="table" name="table" required>
                <option value="subscribers">Subscribers</option>
                <option value="groups">Groups</option>
                <option value="group_subscribers">Group Subscribers</option>
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