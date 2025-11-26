<?php
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
/*

*/
require 'assets/includes/admin_config.php';
try {
	$access_resource = new PDO('mysql:host=' . db_host . ';dbname=' . db_name5 . ';charset=' . db_charset, db_user, db_pass);
	$access_resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the invoice_system database!');
}
$page = 'View';
// Retrieve records from the database
$records = $access_resource->query('SELECT * FROM access_resource')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $access_resource->prepare('SELECT * FROM access_resource WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . 'Access Resource', 'resources', 'access')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Record</h2>
            <p>View Record</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="access_resources.php" class="btn btn-secondary">Return</a>
        <a href="access_resource.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="access_resources.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table"style="width:100%">
        <table>
            
                <tr>
               <td class="title">Proj#: </td><td><?=htmlspecialchars($record['project_id'], ENT_QUOTES)?></td>
                    </tr>
                <tr>
               <td class="title">Project Name: </td><td><?=htmlspecialchars($record['project_name'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">site_admin_login: </td> <td><?=htmlspecialchars($record['site_admin_login'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">site_admin_pass: </td> <td><?=htmlspecialchars($record['site_admin_pass'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">site_webmaster_login: </td> <td><?=htmlspecialchars($record['site_webmaster_login'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">site_webmaster_pass: </td> <td><?=htmlspecialchars($record['site_webmaster_pass'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                <td class="title">site_user_login: </td> <td><?=htmlspecialchars($record['site_user_login'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">site_user_pass: </td> <td><?=htmlspecialchars($record['site_user_pass'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">hosting_name: </td> <td><?=htmlspecialchars($record['hosting_name'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                 <td class="title">hosting_login: </td> <td><?=htmlspecialchars($record['hosting_login'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                <td class="title">hosting_pass: </td> <td><?=htmlspecialchars($record['hosting_pass'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                <td class="title">hosting_url: </td> <td><?=htmlspecialchars($record['hosting_url'], ENT_QUOTES)?></td>
                </tr>
                 <tr>
                <td class="title">db_host: </td><td><?=htmlspecialchars($record['db_host'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">db_user: </td> <td><?=htmlspecialchars($record['db_user'], ENT_QUOTES)?></td>
                </tr>
                  <tr>
               <td class="title">db_pass: </td> <td><?=htmlspecialchars($record['db_pass'], ENT_QUOTES)?></td>
                  </tr>
                <tr>
                 <td class="title">db_name: </td> <td><?=htmlspecialchars($record['db_name'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                 <td class="title">db_name2: </td> <td><?=htmlspecialchars($record['db_name2'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                 <td class="title">db_name3: </td> <td><?=htmlspecialchars($record['db_name3'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                <td class="title">db_name4: </td> <td><?=htmlspecialchars($record['db_name4'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                     <td class="title">db_name5: </td> <td><?=htmlspecialchars($record['db_name5'], ENT_QUOTES)?></td>
                </tr>
          
            <tbody>
                <tr>

                </tr>
            </tbody>
        </table>
    </div>
</div>
<style>
.title  {
font-weight:bold;
width:30%; 
}
</style>
<script src="assets/js/not_important_script.js"></script>
<?=template_admin_footer()?>