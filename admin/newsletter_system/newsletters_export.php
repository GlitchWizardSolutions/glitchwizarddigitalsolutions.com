<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_POST['file_type'])) {
    // Get all newsletters
    $result = $pdo->query('SELECT * FROM newsletters ORDER BY id ASC');
    $newsletters = [];
    $columns = [];
    // Fetch all records into an associative array
    if ($result->rowCount() > 0) {
        // Fetch column names
        for ($i = 0; $i < $result->columnCount(); $i++) {
            $columns[] = $result->getColumnMeta($i)['name'];
        }
        // Fetch associative array
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $newsletters[] = $row;
        }    
    }
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = 'newsletters.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($newsletters as $newsletter) {
            fputcsv($fp, $newsletter);
        }
        fclose($fp);
        exit;
    }
    // Convert to TXT
    if ($_POST['file_type'] == 'txt') {
        $filename = 'newsletters.txt';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/txt');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, implode(',', $columns) . PHP_EOL);
        foreach ($newsletters as $newsletter) {
            $line = '';
            foreach ($newsletter as $key => $value) {
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
        $filename = 'newsletters.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($newsletters));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = 'newsletters.xml';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<newsletters>' . PHP_EOL);
        foreach ($newsletters as $newsletter) {
            fwrite($fp, '    <newsletter>' . PHP_EOL);
            foreach ($newsletter as $key => $value) {
                fwrite($fp, '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL);
            }
            fwrite($fp, '    </newsletter>' . PHP_EOL);
        }
        fwrite($fp, '</newsletters>' . PHP_EOL);
        fclose($fp);
        exit;
    }
}
?>
<?=template_admin_header('Export Newsletters', 'newsletters', 'newsletters')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'index.php'],
    ['label' => 'Newsletters', 'url' => 'newsletters.php'],
    ['label' => 'Export Newsletters']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-download"></i>
        <div class="txt">
            <h2>Export Newsletters</h2>
            <p>Download newsletter data in CSV, JSON, XML, or TXT format</p>
        </div>
    </div>
</div>

<form method="post">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Export Details</h3>

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
            <a href="newsletters.php" class="btn btn-secondary">Cancel</a>
            <input type="submit" name="submit" value="Export" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>