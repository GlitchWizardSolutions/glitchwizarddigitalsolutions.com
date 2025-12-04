<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
$rows_imported = 0;
$validation_errors = [];

// Get last transactions for reference
$stmt = $budget_pdo->prepare('SELECT * FROM hancock ORDER BY date DESC LIMIT 6');
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process CSV upload
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    try {
        // Validate file upload
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $_FILES['csv_file']['error']);
        }
        
        // Validate file extension
        $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            throw new Exception('Only CSV files are allowed. Uploaded file type: ' . $file_ext);
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['csv_file']['size'] > $max_size) {
            throw new Exception('File is too large. Maximum size is 5MB.');
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['csv_file']['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception('Invalid file type. Please upload a CSV file.');
        }
        
        // Open the CSV file
        $file_handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($file_handle === false) {
            throw new Exception('Unable to open CSV file.');
        }
        
        // Read and skip header row (first row from bank)
        $header = fgetcsv($file_handle);
        
        // Clear the csv_upload table
        $budget_pdo->exec('TRUNCATE TABLE csv_upload');
        
        // Prepare insert statement
        $stmt = $budget_pdo->prepare('
            INSERT INTO csv_upload 
            (date, check_number, transaction_type, description, debits, credits)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $row_number = 1; // Start at 1 (after header)
        
        // Process each row
        while (($row = fgetcsv($file_handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Ensure we have at least 6 columns
            if (count($row) < 6) {
                $validation_errors[] = "Row $row_number: Not enough columns (expected 6, got " . count($row) . ")";
                continue;
            }
            
            // Map and clean the data
            $date = trim($row[0]);
            $check_number = trim($row[1] ?? '');
            $transaction_type = trim($row[2] ?? '');
            $description = trim($row[3] ?? '');
            $debits = trim($row[4] ?? '');
            $credits = trim($row[5] ?? '');
            
            // Convert date to YYYY-MM-DD format if needed
            $date_obj = null;
            $date_formats = ['Y-m-d', 'm/d/Y', 'm-d-Y', 'Y/m/d', 'd/m/Y'];
            
            foreach ($date_formats as $format) {
                $date_obj = DateTime::createFromFormat($format, $date);
                if ($date_obj !== false) {
                    $date = $date_obj->format('Y-m-d');
                    break;
                }
            }
            
            if ($date_obj === false) {
                $validation_errors[] = "Row $row_number: Invalid date format '$date'";
                continue;
            }
            
            // Convert empty/blank values to 0 for debits and credits
            // Bank exports debits in accounting notation with parentheses: (100.00)
            // Remove parentheses and convert to NEGATIVE number (debits are money OUT)
            $debits = trim($debits);
            if ($debits === '') {
                $debits = 0;
            } else {
                // Remove parentheses and commas, then convert to negative float
                $debits = str_replace(['(', ')', ','], '', $debits);
                $debits = is_numeric($debits) ? -abs(floatval($debits)) : 0;
            }
            
            $credits = trim($credits);
            if ($credits === '') {
                $credits = 0;
            } else {
                // Remove commas and convert to float
                $credits = str_replace(',', '', $credits);
                $credits = is_numeric($credits) ? floatval($credits) : 0;
            }
            
            // Note: We allow both to be zero for informational rows, or both to have values
            // The validation happens later in the processing workflow
            
            // Insert into database
            try {
                $stmt->execute([$date, $check_number, $transaction_type, $description, $debits, $credits]);
                $rows_imported++;
            } catch (PDOException $e) {
                $validation_errors[] = "Row $row_number: Database error - " . $e->getMessage();
            }
        }
        
        fclose($file_handle);
        
        if ($rows_imported > 0) {
            $success_msg = "Successfully imported $rows_imported transaction(s) into csv_upload table.";
            if (count($validation_errors) > 0) {
                $success_msg .= " However, " . count($validation_errors) . " row(s) had errors and were skipped.";
            }
        } else {
            throw new Exception('No valid transactions were imported. Please check your CSV file format.');
        }
        
    } catch (Exception $e) {
        $error_msg = 'Upload failed: ' . $e->getMessage();
    }
}
?>
<?=template_admin_header('Budget System', 'budget', 'process')?>

<div class="content read">
    <div class="page-title">
        <i class="fa-solid fa-file-csv fa-lg"></i>
        <div class="wrap">
            <h2>CSV Upload (Automated)</h2>
            <p>Upload bank CSV directly - no manual editing needed!</p>
        </div>
    </div>

    <?php if ($error_msg): ?>
    <div class="msg-error" style="padding: 15px; margin: 20px 0; background: #fee; border-left: 4px solid #c33;">
        <strong>Error:</strong> <?=htmlspecialchars($error_msg)?>
    </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
    <div class="msg-success" style="padding: 15px; margin: 20px 0; background: #efe; border-left: 4px solid #3c3;">
        <strong>Success!</strong> <?=htmlspecialchars($success_msg)?>
        <br><br>
        <a href="instructions-p2.php" class="btn" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
            <i class="fa-solid fa-arrow-right"></i> Continue to Step 2: Process Transactions
        </a>
    </div>
    <?php endif; ?>

    <?php if (!empty($validation_errors)): ?>
    <div class="msg-warning" style="padding: 15px; margin: 20px 0; background: #fff3cd; border-left: 4px solid #ff9800;">
        <strong>Validation Errors:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <?php foreach ($validation_errors as $error): ?>
            <li><?=htmlspecialchars($error)?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3><strong>Step 1: Review Last Transactions</strong></h3>
        <p>Check the dates below to determine what date range you need from the bank:</p>
        
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <td style="text-align: center;">Date</td>
                        <td>Description</td>    
                        <td>Comment</td>
                        <td style="text-align: center;">Debits</td>
                        <td style="text-align: center;">Credits</td>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No transactions found.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($recent_transactions as $txn): ?>
                    <tr>
                        <td class="date"><?=date("m/d/Y", strtotime($txn['date']))?></td>
                        <td><?=htmlspecialchars($txn['description'] ?? '', ENT_QUOTES)?></td>
                        <td class="comment left"><?=htmlspecialchars($txn['comment'] ?? '', ENT_QUOTES)?></td>
                        <td class="debits right"><?=number_format($txn['debits'] ?? 0, 2)?></td>
                        <td class="credits right"><?=number_format($txn['credits'] ?? 0, 2)?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3><strong>Step 2: Download CSV from Bank</strong></h3>
        <p>Login to Hancock Bank and download your transactions as a CSV file.</p>
        <a href="https://www.hancockwhitney.com/" target="_blank" class="btn" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0;">
            <i class="fa-solid fa-external-link"></i> Open Hancock Bank
        </a>
        <ul style="margin: 10px 0 10px 20px;">
            <li><strong>Login:</strong> Sys#1/B!H</li>
            <li><strong>Download:</strong> Name format YYYY-MON-dd-dd.csv</li>
            <li><strong>Save to:</strong> Downloads folder</li>
        </ul>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3><strong>Step 3: Upload CSV File</strong></h3>
        <p><strong>No manual editing required!</strong> The system will automatically:</p>
        <ul style="margin: 10px 0 10px 20px;">
            <li>Skip the header row</li>
            <li>Convert dates to YYYY-MM-DD format</li>
            <li>Convert blank values to 0</li>
            <li>Validate all data</li>
        </ul>

        <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
            <div style="margin: 20px 0;">
                <label for="csv_file" style="display: block; margin-bottom: 10px; font-weight: bold;">
                    <i class="fa-solid fa-upload"></i> Select CSV File:
                </label>
                <input 
                    type="file" 
                    name="csv_file" 
                    id="csv_file" 
                    accept=".csv" 
                    required
                    style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; width: 100%; max-width: 500px;"
                >
                <small style="display: block; margin-top: 5px; color: #666;">
                    Accepted format: CSV file (max 5MB)
                </small>
            </div>

            <div style="margin: 20px 0;">
                <button 
                    type="submit" 
                    name="upload_csv" 
                    class="btn" 
                    style="background: #4CAF50; color: white; padding: 12px 30px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer;"
                >
                    <i class="fa-solid fa-cloud-upload"></i> Upload and Process CSV
                </button>
            </div>
        </form>

        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-top: 20px;">
            <h4 style="margin-top: 0;"><i class="fa-solid fa-info-circle"></i> Expected CSV Format:</h4>
            <p style="margin: 5px 0;">The CSV should have columns in this order:</p>
            <code style="display: block; background: white; padding: 10px; border-radius: 4px; margin: 10px 0;">
                Date, Check Number, Transaction Type, Description, Debits, Credits
            </code>
            <p style="margin: 5px 0; font-size: 14px; color: #666;">
                <strong>Note:</strong> The first row (header) will be automatically skipped.
            </p>
        </div>
    </div>

    <div style="margin: 30px 0;">
        <a href="instructions-p1.php" class="btn" style="background: #666; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Manual Instructions
        </a>
    </div>
</div>

<style>
    .right { text-align: right; }
    .left { text-align: left; }
    .date { text-align: center; }
    
    .btn:hover {
        opacity: 0.9;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    input[type="file"]:hover {
        border-color: #4CAF50;
    }
    
    button[type="submit"]:hover {
        background: #45a049 !important;
    }
</style>

<?=template_admin_footer()?>
