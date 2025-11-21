<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked.
include_once 'assets/includes/admin_config.php';

// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_POST['file_type'], $_POST['table'])) {
    // Check if the table is valid
    $table = in_array($_POST['table'], ['invoices', 'invoice_items']) ? $_POST['table'] : 'invoices';
    // Get all invoices
    $result = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY id ASC');
    $invoices = [];
    $columns = [];
    // Fetch all records into an associative array
    if ($result->rowCount() > 0) {
        // Fetch column names
        for ($i = 0; $i < $result->columnCount(); $i++) {
            $columns[] = $result->getColumnMeta($i)['name'];
        }
        // Fetch associative array
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $invoices[] = $row;
        }    
    }
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = $table . '.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($invoices as $invoice) {
            fputcsv($fp, $invoice);
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
        foreach ($invoices as $invoice) {
            $line = '';
            foreach ($invoice as $key => $value) {
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
        fwrite($fp, json_encode($invoices));
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
        foreach ($invoices as $invoice) {
            fwrite($fp, '    <item>' . PHP_EOL);
            foreach ($invoice as $key => $value) {
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
<?=template_admin_header('Export Invoices', 'invoices', 'invoices')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">Export Invoices</h2>
            <p>Batch Program.</p>
        </div>
    </div>
              <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
              
    <a href="invoices.php" class="btn btn-primary">
        View Invoices
    </a>&nbsp;&nbsp;
        <a href="invoices_import.php" class="btn btn-primary">
       Import
    </a>
    <br>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
      
        <a href="invoices.php" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="submit" value="Export" class="btn btn-success">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="table"><span class="required">*</span> Table</label>
            <select id="table" name="table" required>
                <option value="invoices">Invoices</option>
                <option value="invoice_items">Invoice Items</option>
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