<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
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
        $data = json_decode(json_encode($xml), true)['account'];
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
            // convert array to question marks for prepared statements
            $values = array_fill(0, count($row), '?');
            $values = implode(',', $values);
            // Convert date to MySQL format, if you have more datetime columns, add them here
            if (isset($row['registered'])) {
                $row['registered'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['registered'])));
            }
            if (isset($row['last_seen'])) {
                $row['last_seen'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['last_seen'])));
            }
            // insert into database
            // tip: if you want to update existing records, use INSERT ... ON DUPLICATE KEY UPDATE instead
            $stmt = $pdo->prepare('INSERT IGNORE INTO accounts VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i += $stmt->rowCount();
        }
        header('Location: accounts.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Import Accounts', 'accounts', 'import')?>

<?=generate_breadcrumbs([
    ['label' => 'Accounts', 'url' => 'accounts.php'],
    ['label' => 'Import Accounts']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_upload()?></div>
    <div class="txt">
        <h2>Import Client Accounts</h2>
        <p class="subtitle">Upload CSV, JSON, XML, or TXT file to import accounts</p>
    </div>
</div>

<form action="" method="post" enctype="multipart/form-data" class="form-professional">

    <div class="form-section">
        <h3 class="section-title">Upload File</h3>
        <label for="file"><span class="required">*</span> File</label>
        <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>
    </div>

    <div class="form-actions">
        <a href="accounts.php" class="btn btn-secondary">Cancel</a>
        <input type="submit" name="submit" value="Import" class="btn btn-success">

</form>

<?=template_admin_footer()?>