<?php
/*******************************************************************************
TICKETING SYSTEM - tickets_import.php
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
            $stmt = $pdo->prepare('INSERT IGNORE INTO tickets VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i++;
        }
        header('Location: tickets.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Tickets', 'tickets', 'manage')?>

<div class="content-title">
    <div class="title">
    <i class="fa-solid fa-file-import fa-lg"></i>
        <div class="txt">
            <h2 class="responsive-width-100">Import Tickets</h2>
            <p>Import tickets from CSV file.</p>
        </div>
    </div>
</div>
<form action="" method="post" enctype="multipart/form-data">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="tickets.php" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="submit" value="Import" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="file"><i class="required">*</i> File</label>
            <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>