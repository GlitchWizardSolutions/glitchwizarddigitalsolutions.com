<?php
require 'assets/includes/admin_config.php';
// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
// Error message
$error_msg = '';
// Success message
$success_msg = '';
/* 2/17/24 Installed */
set_time_limit(0);
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// Check if POST data exists (user submitted the form)
if (isset($_POST['submit'])) {
    // Validate the CSV file
    if (empty($_FILES['csv']['tmp_name'])) {
        $error_msg = 'Please select a CSV file to import!';
    } else if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) != 'csv') {
        $error_msg = 'File must be a CSV file type!';
    } else {
        // Parse the CSV file
        $csv = array_map('str_getcsv', file($_FILES['csv']['tmp_name']));
        // Adjust the columns accordingly
        $columns = ['id', 'brand', 'name', 'card_type', 'card_number', 'expires', 'code', 'zip_code'];
        // Validate the header
        $header = array_shift($csv);
        if ($header != $columns) {
            $error_msg = 'The CSV header must match the table columns!';
        } else {
            // Import the CSV data
            foreach ($csv as $row) {
                $stmt = $pdo->prepare('INSERT INTO financial_cards (id, brand, name, card_type, card_number, expires, code, zipcode) VALUES (:id, :brand, :name, :card_type, :card_number, :code, :zipcode)');
                foreach ($row as $key => $value) {
                    // check if datetime
                    if (in_array($columns[$key], ['created'])) {
                        $value = date('Y-m-d H:i:s', strtotime($value));
                    }
                    $stmt->bindValue(':' . $columns[$key], $value);
                }
                $stmt->execute();
            }
            // Output message with number of records imported
            $success_msg = 'Imported ' . count($csv) . ' record(s) from CSV file!';
        }
    }
}
?>
<?=template_admin_header('Import records', 'resources', 'financials')?>


<div class="content-title">
    <div class="title">
    <i class="fa-solid fa-file-import fa-lg"></i>
        <div class="txt">
                  <h2 class="responsive-width-100">Import Records</h2>
            <p>Import records from a CSV file, fill in the form below and submit.</p>
        </div>
    </div>
</div>
 
    <form action="" method="post" enctype="multipart/form-data" class="crud-form">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="is_importants.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Import</button>
    </div>
    <div class="content-block">

        <div class="form responsive-width-100">
        <div class="cols">
            <div class="form-control">
                <label for="csv">CSV File</label>
                <input type="file" name="csv" id="csv" accept=".csv" required>
            </div>
        </div>

        <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
        <br>
         </div>

    </div>      
    </form>


<script src="assets/js/not_important_script.js"></script>
<?=template_admin_footer()?>