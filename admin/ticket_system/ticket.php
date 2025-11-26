<?php
/* QA: Create, Import & Export Approved 3/8/24 (Not tested for accessibility) 
*/
require 'assets/includes/admin_config.php';
// Default ticket values
$page = 'Edit';
$ticket = [
    'title' => '',
    'msg' => '',
    'full_name' => '',
    'email' => '',
    'created' => date('Y-m-d H:i:s'),
    'ticket_status' => 'open',
    'priority' => 'low',
    'category_id' => 1,
    'private' => 1,
    'acc_id' => 0,
    'approved' => 1
];
// Retrieve accounts from the database
$accounts = $pdo->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);
// Create account data JSON for JavaScript auto-population
$accounts_json = json_encode(array_column($accounts, null, 'id'));
// Retrieve categories from the database
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the ticket ID is specified
if (isset($_GET['id'])) {
    // Retrieve the ticket from the database
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing ticket
   
    if (isset($_POST['submit'])) {
        // Update the ticket
        $stmt = $pdo->prepare('UPDATE tickets SET title = ?, msg = ?, full_name = ?, email = ?, created = ?, ticket_status = ?, priority = ?, category_id = ?, private = ?, acc_id = ?, approved = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['msg'], $_POST['full_name'], $_POST['email'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_POST['ticket_status'], $_POST['priority'], $_POST['category_id'], $_POST['private'], $_POST['acc_id'], $_POST['approved'], $_GET['id'] ]);
        header('Location: tickets.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the ticket
        header('Location: tickets.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new ticket
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO tickets (title, msg, full_name, email, created, ticket_status, priority, category_id, private, acc_id, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['title'], $_POST['msg'], $_POST['full_name'], $_POST['email'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_POST['ticket_status'], $_POST['priority'], $_POST['category_id'], $_POST['private'], $_POST['acc_id'], $_POST['approved'] ]);
        header('Location: tickets.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Ticket', 'ticketing', 'client')?>

<div class="content-title mb-3">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Client Ticket</h2>
            <p>Manage client support tickets</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        
        <!-- Ticket Information Section -->
        <div class="form-section">
            <h3 class="section-title">Ticket Information</h3>
            
            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input id="title" type="text" name="title" placeholder="Enter ticket title" value="<?=htmlspecialchars($ticket['title'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="msg">Message <span class="required">*</span></label>
                <textarea id="msg" name="msg" placeholder="Describe the issue or request..." rows="6" required><?=htmlspecialchars($ticket['msg'], ENT_QUOTES)?></textarea>
            </div>
        </div>

        <!-- Client Information Section -->
        <div class="form-section">
            <h3 class="section-title">Client Information</h3>
            
            <div class="form-group">
                <label for="acc_id">Account</label>
                <select id="acc_id" name="acc_id" required>
                    <option value="0"<?=$ticket['acc_id']==0?' selected':''?>>Guest / Unregistered User</option>
                    <?php foreach ($accounts as $account): ?>
                    <option value="<?=$account['id']?>"<?=$ticket['acc_id']==$account['id']?' selected':''?>><?=$account['id']?> - <?=$account['email']?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input id="full_name" type="text" name="full_name" placeholder="Enter full name" value="<?=htmlspecialchars($ticket['full_name'], ENT_QUOTES)?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input id="email" type="email" name="email" placeholder="Enter email address" value="<?=htmlspecialchars($ticket['email'], ENT_QUOTES)?>" required>
                </div>
            </div>
        </div>

        <!-- Ticket Settings Section -->
        <div class="form-section">
            <h3 class="section-title">Ticket Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ticket_status">Status <span class="required">*</span></label>
                    <select id="ticket_status" name="ticket_status" required>
                        <option value="open"<?=$ticket['ticket_status']=='open'?' selected':''?>>Open</option>
                        <option value="resolved"<?=$ticket['ticket_status']=='resolved'?' selected':''?>>Resolved</option>
                        <option value="closed"<?=$ticket['ticket_status']=='closed'?' selected':''?>>Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority <span class="required">*</span></label>
                    <select id="priority" name="priority" required>
                        <option value="low"<?=$ticket['priority']=='low'?' selected':''?>>Low</option>
                        <option value="medium"<?=$ticket['priority']=='medium'?' selected':''?>>Medium</option>
                        <option value="high"<?=$ticket['priority']=='high'?' selected':''?>>High</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category <span class="required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?=$category['id']?>"<?=$ticket['category_id']==$category['id']?' selected':''?>><?=$category['title']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="created">Created Date <span class="required">*</span></label>
                    <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($ticket['created']))?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="private">Private <span class="required">*</span></label>
                    <select id="private" name="private" required>
                        <option value="0"<?=$ticket['private']==0?' selected':''?>>No</option>
                        <option value="1"<?=$ticket['private']==1?' selected':''?>>Yes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="approved">Approved <span class="required">*</span></label>
                    <select id="approved" name="approved" required>
                        <option value="0"<?=$ticket['approved']==0?' selected':''?>>No</option>
                        <option value="1"<?=$ticket['approved']==1?' selected':''?>>Yes</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="tickets.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this ticket?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="<?=$page == 'Edit' ? 'Update' : 'Create'?> Ticket" class="btn btn-success">
        </div>

    </div>
</form>

<script>
// Account data for auto-population
const accountData = <?=$accounts_json?>;

// Auto-populate name and email when account is selected
document.getElementById('acc_id').addEventListener('change', function() {
    const accId = parseInt(this.value);
    const fullNameField = document.getElementById('full_name');
    const emailField = document.getElementById('email');
    
    if (accId === 0) {
        // Guest ticket - enable manual entry
        fullNameField.value = '';
        emailField.value = '';
        fullNameField.removeAttribute('readonly');
        emailField.removeAttribute('readonly');
        fullNameField.style.backgroundColor = '';
        emailField.style.backgroundColor = '';
        fullNameField.placeholder = 'Enter guest full name';
        emailField.placeholder = 'Enter guest email';
    } else if (accountData[accId]) {
        // Registered user - auto-populate from account
        fullNameField.value = accountData[accId].full_name || '';
        emailField.value = accountData[accId].email || '';
        fullNameField.setAttribute('readonly', 'readonly');
        emailField.setAttribute('readonly', 'readonly');
        fullNameField.style.backgroundColor = '#f0f0f0';
        emailField.style.backgroundColor = '#f0f0f0';
        fullNameField.placeholder = '';
        emailField.placeholder = '';
    }
});

// Trigger on page load for edit mode
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('acc_id').dispatchEvent(new Event('change'));
});
</script>

<?=template_admin_footer()?>