<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked.
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_FILES['file'], $_POST['table']) && !empty($_FILES['file']['tmp_name'])) {
    // Check if the table is valid
    $table = in_array($_POST['table'], ['invoices', 'invoice_items']) ? $_POST['table'] : 'invoices';
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
        $data = json_decode(json_encode($xml), true)['item'];
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
            if (isset($row['created'])) {
                $row['created'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['created'])));
            }
            if (isset($row['due_date'])) {
                $row['due_date'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['due_date'])));
            }
            // insert into database
            // tip: if you want to update existing records, use INSERT ... ON DUPLICATE KEY UPDATE instead
            $stmt = $pdo->prepare('INSERT IGNORE INTO ' . $table . ' VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i += $stmt->rowCount();
        }
        header('Location: invoices.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Import Invoices', 'invoices', 'invoices')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoices', 'url' => 'invoices.php'],
    ['label' => 'Import']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-upload"></i>
        <div class="txt">
            <h2>Import Invoices</h2>
            <p>Batch import invoices from file</p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="invoices.php" class="btn btn-primary">Invoices</a>&nbsp;&nbsp;
    <a href="invoices_export.php" class="btn btn-primary">Export</a>
</div>

<form action="" method="post" enctype="multipart/form-data" class="form-professional">

    <div class="form-actions">
        <a href="invoices.php" class="btn btn-secondary">Cancel</a>
        <input type="submit" name="submit" value="Import" class="btn btn-success">
    </div>

    <div class="content-block">
        <div class="form-section">
            <div class="section-title">Import Details</div>

            <div class="form responsive-width-100">

            <label for="table"><span class="required">*</span> Table</label>
            <select name="table" id="table" required>
                <option value="invoices">Invoices</option>
                <option value="invoice_items">Invoice Items</option>
            </select>

            <label for="file"><span class="required">*</span> File</label>
            <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>