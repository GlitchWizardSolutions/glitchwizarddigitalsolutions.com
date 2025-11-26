<?php
/*
choose selected value in dropdown by what is in the database, this is not occuring nor coded for yet. Was interrupted.

*/
require 'assets/includes/admin_config.php';
try {
	$access_resource = new PDO('mysql:host=' . db_host . ';dbname=' . db_name5 . ';charset=' . db_charset, db_user, db_pass);
	$access_resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the invoice_system database!');
}
// Error message
$error_msg = '';
// Success message
$success_msg = '';
//declaire vars
$proj_name='';
// Default record values
$record = [
    'project_id' => ' ',
    'project_name' => ' ',
    'db_host' => '',
    'db_user'  => '',
    'db_pass' => '',
    'db_name'  => '',
    'db_name2' => '',
    'db_name3' => '',
    'db_name4' => '',
    'db_name5' => '',
    'site_user_login'  => '',
    'site_user_pass' => '',
    'site_admin_login'  => '',
    'site_admin_pass' => '',
    'site_webmaster_login' => 'GlitchWizard',
    'site_webmaster_pass' => '',
    'hosting_name' => '',
    'hosting_login'  => '',
    'hosting_pass' => '',
    'hosting_url'  => ''
];

$project_info = $access_resource->query('SELECT * FROM ip_projects')->fetchAll(PDO::FETCH_ASSOC);

// Retrieve records from the database
$records = $access_resource->query('SELECT * FROM access_resource')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $access_resource->prepare('SELECT * FROM access_resource WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
 $stmt = $access_resource->prepare('UPDATE access_resource SET project_id = ?, project_name = ?, db_host = ?, db_user = ?, db_pass = ?, db_name = ?, db_name2 = ?, db_name3 = ?, db_name4 = ?, db_name5 = ?, site_user_login = ?, site_user_pass = ?, site_admin_login = ?, site_admin_pass = ?, site_webmaster_login = ?, site_webmaster_pass = ?, hosting_name = ?, hosting_login = ?, hosting_pass = ?, hosting_url = ? WHERE id = ?');
                $stmt->execute([$_POST['project_id'],$_POST['project_name'], $_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name'], $_POST['db_name2'], $_POST['db_name3'],$_POST['db_name4'], $_POST['db_name5'], $_POST['site_user_login'], $_POST['site_user_pass'], $_POST['site_admin_login'], $_POST['site_admin_pass'], $_POST['site_webmaster_login'], $_POST['site_webmaster_pass'], $_POST['hosting_name'], $_POST['hosting_login'], $_POST['hosting_pass'], $_POST['hosting_url'], $_GET['id'] ]);
                header('Location: access_resources.php?success_msg=2');
                exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: access_resources.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
          
                $stmt = $access_resource->prepare('INSERT INTO access_resource (project_id, project_name, db_host, db_user, db_pass, db_name, db_name2, db_name3, db_name4, db_name5, site_user_login, site_user_pass, site_admin_login, site_admin_pass, site_webmaster_login, site_webmaster_pass, hosting_name, hosting_login, hosting_pass, hosting_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([ $_POST['project_id'],$_POST['project_name'], $_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name'], $_POST['db_name2'], $_POST['db_name3'], $_POST['db_name4'], $_POST['db_name5'], $_POST['site_user_login'], $_POST['site_user_pass'], $_POST['site_admin_login'], $_POST['site_admin_pass'], $_POST['site_webmaster_login'], $_POST['site_webmaster_pass'], $_POST['hosting_name'], $_POST['hosting_login'], $_POST['hosting_pass'], $_POST['hosting_url'] ]);
                header('Location: access_resources.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . 'Access Resource', 'resources', 'access')?>
<div class="content-title mb-3">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Record</h2>
            <p>Project Access Data</p>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="access_resources.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
      <label for="project_id">project_id</label>
<select name='project_id' id='project_id' required>      
<?php foreach ($project_info as $row): ?>

<option value="<?= $row['project_id'] ?>"><?= $proj_name=htmlspecialchars( $row["project_name"] ) ?></option>
  
<?php endforeach ?>
</select>   
            <label for="db_host">project_name</label>
            <input type="text" name="project_name" id="project_name" placeholder="project_name" value="<?=htmlspecialchars($record['project_name'], ENT_QUOTES)?>">
            
            <label for="db_host">db_host</label>
            <input type="text" name="db_host" id="db_host" placeholder="db_host" value="<?=htmlspecialchars($record['db_host'], ENT_QUOTES)?>">

            <label for="db_user">db_user</label>
            <input type="text" name="db_user" id="db_user" placeholder="db_user" value="<?=htmlspecialchars($record['db_user'], ENT_QUOTES)?>">

            <label for="db_pass">db_pass</label>
            <input type="text" name="db_pass" id="db_pass" placeholder="db_pass"  value="<?=htmlspecialchars($record['db_pass'], ENT_QUOTES)?>">
    
            <label for="db_name">db_name</label>
            <input type="text" name="db_name" id="db_name" placeholder="db_name" value="<?=htmlspecialchars($record['db_name'], ENT_QUOTES)?>">
            
            <label for="db_name2">db_name2</label>
            <input type="text" name="db_name2" id="db_name2" placeholder="db_name2"  value="<?=htmlspecialchars($record['db_name2'], ENT_QUOTES)?>">
         
            <label for="db_name3">db_name3</label>
            <input type="text" name="db_name3" id="db_name3" placeholder="db_name3"  value="<?=htmlspecialchars($record['db_name3'], ENT_QUOTES)?>">

            <label for="db_name4">db_name4</label>
            <input type="text" name="db_name4" id="db_name4" placeholder="db_name4" value="<?=htmlspecialchars($record['db_name4'], ENT_QUOTES)?>">

            <label for="db_name5">db_name5</label>
            <input type="text" name="db_name5" id="db_name5" placeholder="db_name5"  value="<?=htmlspecialchars($record['db_name5'], ENT_QUOTES)?>">
    
            <label for="site_user_login">site_user_login </label>
            <input type="text" name="site_user_login" id="site_user_login" placeholder="site_user_login" value="<?=htmlspecialchars($record['site_user_login'], ENT_QUOTES)?>">
            
            <label for="site_user_pass">site_user_pass</label>
            <input type="text" name="site_user_pass" id="site_user_pass" placeholder="site_user_pass" value="<?=htmlspecialchars($record['site_user_pass'], ENT_QUOTES)?>">

            <label for="site_admin_login">site_admin_login</label>
            <input type="text" name="site_admin_login" id="site_admin_login" placeholder="site_admin_login" value="<?=htmlspecialchars($record['site_admin_login'], ENT_QUOTES)?>">

             <label for="site_admin_pass">site_admin_pass</label>
            <input type="text" name="site_admin_pass" id="site_admin_pass" placeholder="site_admin_pass" value="<?=htmlspecialchars($record['site_admin_pass'], ENT_QUOTES)?>">
            
            <label for="site_webmaster_login">site_webmaster_login</label>
            <input type="text" name="site_webmaster_login" id="site_webmaster_login" placeholder="site_webmaster_login"  value="<?=htmlspecialchars($record['site_webmaster_login'], ENT_QUOTES)?>">

            <label for="site_webmaster_pass">site_webmaster_pass</label>
            <input type="text" name="site_webmaster_pass" id="site_webmaster_pass" placeholder="site_webmaster_pass" value="<?=htmlspecialchars($record['site_webmaster_pass'], ENT_QUOTES)?>">

            <label for="hosting_name">hosting_name</label>
            <input type="text" name="hosting_name" id="hosting_name" placeholder="hosting_name"  value="<?=htmlspecialchars($record['hosting_name'], ENT_QUOTES)?>">
            
            <label for="hosting_login">hosting_login</label>
            <input type="text" name="hosting_login" id="hosting_login" placeholder="hosting_login" value="<?=htmlspecialchars($record['hosting_login'], ENT_QUOTES)?>">

            <label for="hosting_pass">hosting_pass</label>
            <input type="text" name="hosting_pass" id="hosting_pass" placeholder="hosting_pass"  value="<?=htmlspecialchars($record['hosting_pass'], ENT_QUOTES)?>">

            <label for="hosting_url">hosting_url</label>
            <input type="text" name="hosting_url" id="hosting_url" placeholder="hosting_url" value="<?=htmlspecialchars($record['hosting_url'], ENT_QUOTES)?>">

        </div>

    </div>

</form>
<script src="assets/js/not_important_script.js"></script>
<?=template_admin_footer()?>