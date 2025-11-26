<?php
require 'assets/includes/admin_config.php';
try {
	$login_db  = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_db ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the invoice_system database!');
}
// If form submitted
if (isset($_POST['file_type'])) {
    // Get all records
    $stmt = $login_db->prepare('SELECT * FROM domains ORDER BY domain ASC');
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // get column names
    $columns = array_keys($records ? $records[0] : []);
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = 'domains.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($records as $record) {
            fputcsv($fp, $record);
        }
        fclose($fp);
        exit;
    }
    // Convert to TXT
    if ($_POST['file_type'] == 'txt') {
        $filename = 'domains.txt';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/txt');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, implode(',', $columns) . PHP_EOL);
        foreach ($records as $record) {
            $line = '';
            foreach ($record as $key => $value) {
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
        $filename = 'records.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($records));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = 'domains.xml';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<records>' . PHP_EOL);
        foreach ($records as $record) {
            fwrite($fp, '    <record>' . PHP_EOL);
            foreach ($record as $key => $value) {
                fwrite($fp, '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL);
            }
            fwrite($fp, '    </record>' . PHP_EOL);
        }
        fwrite($fp, '</records>' . PHP_EOL);
        fclose($fp);
        exit;
    }
}
?>
<?=template_admin_header('Export records', 'resources', 'domains')?>

<div class="content-title mb-3">
    <div class="title">
    <i class="fa-solid fa-file-export fa-lg"></i>
        <div class="txt">
                  <h2 class="responsive-width-100">Export Domains</h2>
            <p>Export domain records to CSV, TXT, JSON, or XML file</p>
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Export Options</h3>
            
            <div class="form-group">
                <label for="file_type">File Type <span class="required">*</span></label>
                <select id="file_type" name="file_type" required>
                    <option value="csv">CSV</option>
                    <option value="txt">TXT</option>
                    <option value="json">JSON</option>
                    <option value="xml">XML</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="domains.php" class="btn btn-secondary">Cancel</a>
            <input type="submit" name="submit" value="Export" class="btn btn-success">
        </div>

    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>