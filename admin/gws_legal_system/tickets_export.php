<?php
/*******************************************************************************
PROJECTS SYSTEM - tickets_export.php
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
    $stmt = $pdo->prepare('SELECT * FROM project_tickets ORDER BY id ASC');
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // get column names
    $columns = array_keys($tickets ? $tickets[0] : []);
    // Convert to CSV
    if ($_POST['file_type'] == 'csv') {
        $filename = 'project_tickets.csv';
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
        $filename = 'project_tickets.json';
        $fp = fopen('php://output', 'w');
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        fwrite($fp, json_encode($tickets));
        fclose($fp);
        exit;
    }
    // Convert to XML
    if ($_POST['file_type'] == 'xml') {
        $filename = 'project_tickets.xml';
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
<?=template_admin_header('Legal Filings', 'ticketing', 'export')?>

<div class="content-title mb-3">
    <div class="icon alt">
        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
    </div>
    <div class="txt">
        <h2>Export Legal Filings</h2>
        <p class="subtitle">Download all legal filing records in your preferred format</p>
    </div>
</div>

<form class="form-professional" action="" method="post">

    <div class="form-section">
        <div class="section-title">Export Options</div>
        
        <div class="form-group">
            <label for="file_type"><i class="required">*</i> File Format</label>
            <select id="file_type" name="file_type" required>
                <option value="csv">CSV (Comma-Separated Values)</option>
                <option value="txt">TXT (Plain Text)</option>
                <option value="json">JSON (JavaScript Object Notation)</option>
                <option value="xml">XML (Extensible Markup Language)</option>
            </select>
            <small>Select the format for your exported legal filing data</small>
        </div>

    </div>

    <div class="form-actions">
        <a href="tickets.php" class="btn btn-secondary">Cancel</a>
        <input type="submit" name="submit" value="Export Filings" class="btn btn-primary">
    </div>

</form>

<?=template_admin_footer()?> 