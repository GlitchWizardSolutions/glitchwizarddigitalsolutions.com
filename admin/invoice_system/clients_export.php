<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked. VERIFIED.
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_POST['file_type'])) {
    // Get all invoice_clients
    $result = $pdo->query('SELECT * FROM invoice_clients ORDER BY id ASC');
    $clients = [];
    $columns = [];
    // Fetch all records into an associative array
    if ($result->rowCount() > 0) {
        // Fetch column names
        for ($i = 0; $i < $result->columnCount(); $i++) {
            $columns[] = $result->getColumnMeta($i)['name'];
        }
        // Fetch associative array
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $clients[] = $row;
        }    
    }
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = 'clients.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($clients as $client) {
            fputcsv($fp, $client);
        }
        fclose($fp);
        exit;
    }
    // Convert to TXT
    if ($_POST['file_type'] == 'txt') {
        $filename = 'clients.txt';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/txt');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, implode(',', $columns) . PHP_EOL);
        foreach ($clients as $client) {
            $line = '';
            foreach ($client as $key => $value) {
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
        $filename = 'clients.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($clients));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = 'clients.xml';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<clients>' . PHP_EOL);
        foreach ($clients as $client) {
            fwrite($fp, '    <item>' . PHP_EOL);
            foreach ($client as $key => $value) {
                fwrite($fp, '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL);
            }
            fwrite($fp, '    </item>' . PHP_EOL);
        }
        fwrite($fp, '</clients>' . PHP_EOL);
        fclose($fp);
        exit;
    }
}
?>
<?=template_admin_header('Export Invoice Clients', 'invoices', 'clients')?>

<?=generate_breadcrumbs([
    ['label' => 'Clients', 'url' => 'clients.php'],
    ['label' => 'Export']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_download()?></div>
    <div class="txt">
        <h2>Export Clients</h2>
        <p class="subtitle">Batch export invoice clients to file</p>
    </div>
</div>

<form action="" method="post" class="form-professional">

    <div class="form-actions">
        <a href="clients.php" class="btn btn-secondary">Cancel</a>
        <input type="submit" name="submit" value="Export" class="btn btn-success">
    </div>

    <div class="content-block">
        <div class="form-section">
            <div class="section-title">Export Options</div>

            <div class="form responsive-width-100">

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