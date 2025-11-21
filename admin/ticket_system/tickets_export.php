<?php
/*******************************************************************************
CLIENT TICKETING SYSTEM - tickets_export.php
LOCATION: /public_html/admin/
DESCRIBE: Used for exporting tickets from the database, in bulk.
INPUTREQ: database table 'tickets'
LOGGEDIN: REQUIRED
REQUIRED:
  SYSTEM: DATABASE TABLE tickets (does not export comments)
   ADMIN: /public_html/admin/
   PAGES: None
   FILES: 
   PARMS: 
  OUTPUT: .csv.json,.xml,.txt files
LOG NOTE: PRODUCTION 2024-09-14 
*******************************************************************************/
require 'assets/includes/admin_config.php';
/* 2/17/24 Installed 
Status: PRODUCTION 9/13/4
*/
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_POST['file_type'])) {
    // Get all tickets
    $stmt = $pdo->prepare('SELECT * FROM tickets ORDER BY id ASC');
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // get column names
    $columns = array_keys($tickets ? $tickets[0] : []);
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = 'tickets.csv';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        fputcsv($fp,  $columns);
        foreach ($tickets as $ticket) {
            fputcsv($fp, $ticket);
        }
        fclose($fp);
        exit;
    }
    // Convert to TXT
    if ($_POST['file_type'] == 'txt') {
        $filename = 'tickets.txt';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/txt');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, implode(',', $columns) . PHP_EOL);
        foreach ($tickets as $ticket) {
            $line = '';
            foreach ($ticket as $key => $value) {
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
        $filename = 'tickets.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($tickets));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = 'tickets.xml';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<tickets>' . PHP_EOL);
        foreach ($tickets as $ticket) {
            fwrite($fp, '    <ticket>' . PHP_EOL);
            foreach ($ticket as $key => $value) {
                fwrite($fp, '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL);
            }
            fwrite($fp, '    </ticket>' . PHP_EOL);
        }
        fwrite($fp, '</tickets>' . PHP_EOL);
        fclose($fp);
        exit;
    }
}
?>
<?=template_admin_header('Tickets', 'tickets', 'manage')?>

<div class="content-title">
    <div class="title">
    <i class="fa-solid fa-file-export fa-lg"></i>
        <div class="txt">
                  <h2 class="responsive-width-100">Export Tickets</h2>
            <p>Export tickets to CSV, TXT, JSON, or XML file.</p>
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="tickets.php" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="submit" value="Export" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="file_type"><i class="required">*</i> File Type</label>
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