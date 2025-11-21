<?php
require 'assets/includes/admin_config.php';
$filename = '?';
$application= 'Resource System - Medications';
$inputs     = '';
$outputs    = '';
$noted      = 'public_html/admin/resource_system/meds.php';
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

try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}

// Get search parameter
$search = isset($_GET['search']) ? $_GET['search'] : '';

$meds = $onthego_db->query('SELECT * FROM meds WHERE patient = "Barbara Moore" and status = "Active" ORDER BY name DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Medications', 'resources', 'meds')?>
<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="med.php" class="btn">+ Create New Medication Record</a>
        <a href="meds-barbara-pdf.php" class="btn" style="background:#dc3545;">Download PDF</a>
        <button type="button" class="btn" style="background:#28a745;" data-bs-toggle="modal" data-bs-target="#emailModal">Email PDF</button>
    </div>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search records..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>Barbara Moore's Medications</h2>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Medication</td> 
                    <td class="responsive-hidden">Type</td>
                    <td>Dosage</td>
                    <td>Frequency</td>
                    <td class="responsive-hidden">Notes</td>
                 
                    <td class="align-center">Action</td>            
                </tr>
            </thead>
            <tbody>
                <?php if (!$meds): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no records</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($meds as $meds): ?>
                <tr>
                     <td><?=htmlspecialchars($meds['name'], ENT_QUOTES)?></td>
                      <td class="responsive-hidden"><?=htmlspecialchars($meds['type'], ENT_QUOTES)?></td>
                     <td><?=htmlspecialchars($meds['dosage'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($meds['frequency'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=$meds['notes']?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="med.php?id=<?=$meds['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="meds.php?delete=<?=$meds['id']?>" onclick="return confirm('Are you sure you want to delete this med?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
 </div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Email Medication List PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="meds-barbara-email.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipient_name" class="form-label">Recipient Name</label>
                        <input type="text" class="form-control" id="recipient_name" name="recipient_name" value="Barbara Moore" required>
                    </div>
                    <div class="mb-3">
                        <label for="recipient_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="recipient_email" name="recipient_email" placeholder="barbara@example.com" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>