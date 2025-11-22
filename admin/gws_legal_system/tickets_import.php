<?php
/*******************************************************************************
PROJECTS SYSTEM - tickets_import.php
LOCATION: /public_html/admin/
DESCRIBE: Used for importing tickets into a new database, in bulk.
INPUTREQ: .csv,.json,.xml or .txt file
LOGGEDIN: NOT REQUIRED
REQUIRED:
  SYSTEM: DATABASE TABLE tickets (does not update comments)
   ADMIN: /public_html/admin/
   PAGES: None
   FILES: 
   PARMS: 
     OUT: 
LOG NOTE: PRODUCTION 2024-09-14 
*******************************************************************************/
require 'assets/includes/admin_config.php';
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
if (isset($_FILES['file']) && !empty($_FILES['file']['tmp_name'])) {
    // check type
    $type = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $data = [];
    if ($type == 'csv') {
        $file = fopen($_FILES['file']['tmp_name'], 'r');
        $header = fgetcsv($file);
        while ($row = fgetcsv($file)) {
            $data[] = array_combine($header, $row);
        }
        fclose($file);
    } elseif ($type == 'json') {
        $data = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
    } elseif ($type == 'xml') {
        $xml = simplexml_load_file($_FILES['file']['tmp_name']);
        $data = json_decode(json_encode($xml), true)['ticket'];
    } elseif ($type == 'txt') {
        $file = fopen($_FILES['file']['tmp_name'], 'r');
        while ($row = fgetcsv($file)) {
            $data[] = $row;
        }
        fclose($file);
    }
    // insert into database
    if (isset($data) && !empty($data)) {    
        $i = 0;   
        foreach ($data as $k => $row) {
            // skip first row
            if ($k == 0) {
                continue;
            }
            // convert array to question marks for prepared statements
            $values = array_fill(0, count($row), '?');
            $values = implode(',', $values);
            // insert into database
            $stmt = $pdo->prepare('INSERT IGNORE INTO project_tickets VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i++;
        }
        header('Location: tickets.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Legal Filings', 'ticketing', 'import')?>

<div class="content-title">
    <div class="icon alt">
        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
    </div>
    <div class="txt">
        <h2>Import Legal Filings</h2>
        <p class="subtitle">Upload and import legal filing records from CSV, JSON, XML, or TXT files</p>
    </div>
</div>

<form class="form-professional" action="" method="post" enctype="multipart/form-data">

    <div class="form-section">
        <div class="section-title">File Upload</div>
        
        <div class="form-group">
            <label for="file"><i class="required">*</i> Select File</label>
            <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>
            <small>Supported formats: CSV, JSON, XML, TXT</small>
        </div>

    </div>

    <div class="form-actions">
        <a href="tickets.php" class="btn btn-secondary">Cancel</a>
        <input type="submit" name="submit" value="Import Filings" class="btn btn-primary">
    </div>

</form>

<?=template_admin_footer()?>