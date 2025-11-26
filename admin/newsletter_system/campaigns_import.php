<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Remove the time limit and file size limit
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// If form submitted
if (isset($_FILES['file'], $_POST['table']) && !empty($_FILES['file']['tmp_name'])) {
    // Check if the table is valid
    $table = in_array($_POST['table'], ['campaigns', 'campaign_items', 'campaign_clicks', 'campaign_opens', 'campaign_unsubscribes']) ? $_POST['table'] : 'campaigns';
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
            if (isset($row['update_date'])) {
                $row['update_date'] = date('Y-m-d H:i', strtotime(str_replace('/','-', $row['update_date'])));
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
        header('Location: campaigns.php?success_msg=4&imported=' . $i);
        exit;
    }
}
?>
<?=template_admin_header('Import Campaigns', 'campaigns', 'import')?>

<?=generate_breadcrumbs([
    ['label' => 'Campaigns', 'url' => 'campaigns.php'],
    ['label' => 'Import Campaigns']
])?>

<div class="content-title mb-3">
    <div class="icon alt"><?=svg_icon_upload()?></div>
    <div class="txt">
        <h2>Import Campaigns</h2>
        <p class="subtitle">Upload campaign data files in CSV, JSON, XML, or TXT format</p>
    </div>
</div>

<form method="post" enctype="multipart/form-data">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Import Details</h3>

            <div class="form-group">
                <label for="table">Table <span class="required">*</span></label>
                <select id="table" name="table" required>
                    <option value="campaigns">Campaigns</option>
                    <option value="campaign_items">Campaign Items</option>
                    <option value="campaign_clicks">Campaign Clicks</option>
                    <option value="campaign_opens">Campaign Opens</option>
                    <option value="campaign_unsubscribes">Campaign Unsubscribes</option>
                </select>
            </div>

            <div class="form-group">
                <label for="file">File <span class="required">*</span></label>
                <input type="file" name="file" id="file" accept=".csv,.json,.xml,.txt" required>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="campaigns.php" class="btn btn-secondary">Cancel</a>
            <input type="submit" name="submit" value="Import" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>