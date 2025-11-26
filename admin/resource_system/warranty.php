<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}

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
// Default record values
$record = [
    'title' => '',
    'msg' => '',
    'warranty_type_id' => '',
    'ticket_status' => 'new',
    'owner' => '',
    'reminder_date' => date('Y-m-d', strtotime('+11 months')),
    'purchase_date' => date('Y-m-d'),
    'warranty_expiration_date' => date('Y-m-d', strtotime('+1 year'))
];
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM warranty_tickets')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE warranty_tickets SET title = ?, msg = ?, warranty_type_id = ?, ticket_status = ?, owner = ?, reminder_date = ?, purchase_date = ?, warranty_expiration_date = ? WHERE id = ?');
                $stmt->execute([ $_POST['title'],$_POST['msg'],$_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'],$_POST['reminder_date'],$_POST['purchase_date'],$_POST['warranty_expiration_date'], $_GET['id'] ]);
                
                // Handle file uploads for edit
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                        if (!empty($_FILES['attachments']['tmp_name'][$i])) {
                            $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                            $original_name = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_FILENAME);
                            
                            // Check if file extension is allowed
                            if (in_array($ext, explode(',', attachments_allowed))) {
                                // Create unique filename while preserving original name
                                $filename = $original_name . '.' . $ext;
                                $upload_path = warranty_resource_uploads_path . $filename;
                                $counter = 1;
                                
                                // If file exists, add (1), (2), etc.
                                while (file_exists($upload_path)) {
                                    $filename = $original_name . ' (' . $counter . ').' . $ext;
                                    $upload_path = warranty_resource_uploads_path . $filename;
                                    $counter++;
                                }
                                
                                // Move uploaded file
                                if ($_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
                                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path)) {
                                        // Insert into warranty_tickets_uploads table
                                        $stmt = $onthego_db->prepare('INSERT INTO warranty_tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
                                        $stmt->execute([ $_GET['id'], $filename ]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                header('Location: warranties.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Get all uploaded files for this warranty
        $stmt = $onthego_db->prepare('SELECT filepath FROM warranty_tickets_uploads WHERE ticket_id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete physical files
        foreach ($uploads as $upload) {
            // Handle old database paths that include 'warranty-ticket-uploads/' prefix
            $clean_filepath = str_replace('warranty-ticket-uploads/', '', $upload['filepath']);
            $file_path = warranty_resource_uploads_path . $clean_filepath;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete upload records from database
        $stmt = $onthego_db->prepare('DELETE FROM warranty_tickets_uploads WHERE ticket_id = ?');
        $stmt->execute([ $_GET['id'] ]);
        
        // Redirect and delete the warranty record
        header('Location: warranties.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO warranty_tickets (title, msg, warranty_type_id, ticket_status, owner, reminder_date, purchase_date, warranty_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['title'], $_POST['msg'],  $_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'], $_POST['reminder_date'], $_POST['purchase_date'], $_POST['warranty_expiration_date']]);
                
                // Get the newly created warranty ID
                $warranty_id = $onthego_db->lastInsertId();
                
                // Handle file uploads
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                        if (!empty($_FILES['attachments']['tmp_name'][$i])) {
                            $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                            $original_name = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_FILENAME);
                            
                            // Check if file extension is allowed
                            if (in_array($ext, explode(',', attachments_allowed))) {
                                // Create unique filename while preserving original name
                                $filename = $original_name . '.' . $ext;
                                $upload_path = warranty_resource_uploads_path . $filename;
                                $counter = 1;
                                
                                // If file exists, add (1), (2), etc.
                                while (file_exists($upload_path)) {
                                    $filename = $original_name . ' (' . $counter . ').' . $ext;
                                    $upload_path = warranty_resource_uploads_path . $filename;
                                    $counter++;
                                }
                                
                                // Move uploaded file
                                if ($_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
                                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path)) {
                                        // Insert into warranty_tickets_uploads table
                                        $stmt = $onthego_db->prepare('INSERT INTO warranty_tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
                                        $stmt->execute([ $warranty_id, $filename ]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                header('Location: warranties.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Warranty', 'resources', 'warranties')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Warranties', 'url' => 'warranties.php'],
    ['label' => $page . ' Warranty']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-shield-halved"></i>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Warranty <?=$page == 'Edit' ? '- ' . htmlspecialchars($record['title'] ?? '', ENT_QUOTES) : ''?></h2>
            <p><?=$page == 'Edit' ? 'Update' : 'Create new'?> warranty record</p>
        </div>
    </div>
</div>

<form action="" method="post" enctype="multipart/form-data">
    <div class="form-professional">
        <div class="form-section">
            <h3 class="section-title">Warranty Information</h3>

            <!-- Row 1: Title + Warranty Type -->
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" name="title" id="title" placeholder="Product Name" value="<?=htmlspecialchars($record['title']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="warranty_type_id">Warranty Type</label>
                    <select name="warranty_type_id" id="warranty_type_id">
                        <option value="">Select...</option>
                        <?php
                        $warranty_types = $onthego_db->query('SELECT * FROM warranty_types ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($warranty_types as $type) {
                            $selected = (isset($record['warranty_type_id']) && $record['warranty_type_id'] == $type['id']) ? 'selected' : '';
                            echo '<option value="' . $type['id'] . '" ' . $selected . '>' . htmlspecialchars($type['name'], ENT_QUOTES) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Message/Description (full width) -->
            <div class="form-group">
                <label for="msg">Message/Description <span class="required">*</span></label>
                <textarea name="msg" id="msg" placeholder="Warranty details, terms, conditions..." rows="4" required><?=htmlspecialchars($record['msg']??'', ENT_QUOTES)?></textarea>
            </div>

            <!-- Row 3: Owner + Status + Attachments -->
            <div class="form-row-3">
                <div class="form-group">
                    <label for="owner">Owner</label>
                    <input type="text" name="owner" id="owner" placeholder="Owner Name" value="<?=htmlspecialchars($record['owner']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="ticket_status">Status <span class="required">*</span></label>
                    <select name="ticket_status" id="ticket_status" required>
                        <option value="new" <?=($record['ticket_status']??'new') == 'new' ? 'selected' : ''?>>New</option>
                        <option value="active" <?=($record['ticket_status']??'') == 'active' ? 'selected' : ''?>>Active</option>
                        <option value="service" <?=($record['ticket_status']??'') == 'service' ? 'selected' : ''?>>Service</option>
                        <option value="closed" <?=($record['ticket_status']??'') == 'closed' ? 'selected' : ''?>>Closed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="attachments" class="tooltip-container">
                        Attachments
                        <span class="tooltip-icon">?</span>
                        <span class="tooltip-text">Allowed: <?=str_replace(',', ', ', attachments_allowed)?> (Max <?=round(max_allowed_upload_file_size/1048576, 1)?>MB per file)</span>
                    </label>
                    <input type="file" name="attachments[]" id="attachments" accept=".<?=str_replace(',', ',.', attachments_allowed)?>" multiple>
                </div>
            </div>

            <!-- Row 4: Purchase Date + Warranty Expiration + Reminder Date -->
            <div class="form-row-3">
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>
                    <input type="date" name="purchase_date" id="purchase_date" value="<?=htmlspecialchars($record['purchase_date']??date('Y-m-d'), ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="warranty_expiration_date">Warranty Expiration</label>
                    <input type="date" name="warranty_expiration_date" id="warranty_expiration_date" value="<?=htmlspecialchars($record['warranty_expiration_date']??date('Y-m-d', strtotime('+1 year')), ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="reminder_date">Reminder Date</label>
                    <input type="date" name="reminder_date" id="reminder_date" value="<?=htmlspecialchars($record['reminder_date']??date('Y-m-d', strtotime('+11 months')), ENT_QUOTES)?>">
                </div>
            </div>

            <?php if ($page == 'Edit'): ?>
            <?php
            // Get existing uploads for this warranty
            $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets_uploads WHERE ticket_id = ?');
            $stmt->execute([ $_GET['id'] ]);
            $existing_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($existing_uploads)):
            ?>
            <div class="attachment-preview">
                <strong>Current Attachments:</strong>
                <div class="attachment-grid">
                    <?php foreach($existing_uploads as $upload): ?>
                    <?php
                    // Handle old database paths that include 'warranty-ticket-uploads/' prefix
                    $clean_filepath = str_replace('warranty-ticket-uploads/', '', $upload['filepath']);
                    $file_path = warranty_resource_uploads_path . $clean_filepath;
                    $file_url = warranty_resource_uploads_url . $clean_filepath;
                    $is_image = @getimagesize($file_path) !== false;
                    ?>
                    <div class="attachment-item">
                        <?php if ($is_image): ?>
                        <a href="<?=$file_url?>" target="_blank">
                            <img src="<?=$file_url?>" width="100" height="100" alt="<?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?>">
                        </a>
                        <?php else: ?>
                        <a href="<?=$file_url?>" download class="file-link">
                            <i class="fas fa-file fa-3x" style="color: #6b46c1;"></i>
                        </a>
                        <?php endif; ?>
                        <p class="file-name"><?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?></p>
                        <a href="warranty-delete-upload.php?id=<?=$upload['id']?>&warranty_id=<?=$_GET['id']?>" onclick="return confirm('Delete this file?')" class="delete-link">Delete</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
        <div class="form-actions">
            <a href="warranties.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>
    </div>
</form> 
<?=template_admin_footer()?>