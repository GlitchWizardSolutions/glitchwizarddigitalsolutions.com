<?php
require 'assets/includes/admin_config.php';
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_FILES['file'], $_POST['table']) && !empty($_FILES['file']['tmp_name'])) {
    // Check if the table is valid
    $table = in_array($_POST['table'], ['subscribers', 'groups', 'group_subscribers']) ? $_POST['table'] : 'subscribers';
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
            if (isset($row['date_subbed'])) {
                $row['date_subbed'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['date_subbed'])));
            }
            if (isset($row['submit_date'])) {
                $row['submit_date'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['submit_date'])));
            }
            // insert into database
            // tip: if you want to update existing records, use INSERT ... ON DUPLICATE KEY UPDATE instead
            $stmt = $pdo->prepare('INSERT IGNORE INTO ' . $table . ' VALUES (' . $values . ')');
            $stmt->execute(array_values($row));
            $i += $stmt->rowCount();
        }
        header('Location: subscribers.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Import Subscribers', 'subscribers', 'import')?>

<form method="post" enctype="multipart/form-data">

    <div class="content-title">
        <h2>Import Subscribers</h2>
        <div class="btns">
            <a href="subscribers.php" class="btn alt mar-right-1">Cancel</a>
            <input type="submit" name="submit" value="Import" class="btn">
        </div>
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="table"><span class="required">*</span> Table</label>
            <select name="table" id="table" required>
                <option value="subscribers">Subscribers</option>
                <option value="groups">Groups</option>
                <option value="group_subscribers">Group Subscribers</option>
            </select>

            <label for="file"><span class="required">*</span> File</label>
            <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>