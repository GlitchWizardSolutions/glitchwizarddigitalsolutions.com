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
<?=template_admin_header($page . ' Access Resource', 'resources', 'access')?>

<?=generate_breadcrumbs([
    ['label' => 'Access Resources', 'url' => 'access_resources.php'],
    ['label' => $page . ' Resource']
])?>

<div class="content-title mb-3">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Access Resource</h2>
            <p>Manage project access credentials and database information</p>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="form-professional">
        
        <!-- Project Information Section -->
        <div class="form-section">
            <h3 class="section-title">Project Information</h3>
            
            <div class="form-group">
                <label for="project_id">Project <span class="required">*</span></label>
                <select name='project_id' id='project_id' required>      
                    <?php foreach ($project_info as $row): ?>
                    <option value="<?= $row['project_id'] ?>"<?=$record['project_id']==$row['project_id']?' selected':''?>><?= htmlspecialchars($row["project_name"]) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="project_name">Project Name</label>
                <input type="text" name="project_name" id="project_name" placeholder="Project display name" value="<?=htmlspecialchars($record['project_name'], ENT_QUOTES)?>">
            </div>
        </div>

        <!-- Database Credentials Section -->
        <div class="form-section">
            <h3 class="section-title">Database Credentials</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" name="db_host" id="db_host" placeholder="localhost or IP" value="<?=htmlspecialchars($record['db_host'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" name="db_user" id="db_user" placeholder="Database username" value="<?=htmlspecialchars($record['db_user'], ENT_QUOTES)?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="db_pass">Database Password</label>
                <input type="text" name="db_pass" id="db_pass" placeholder="Database password"  value="<?=htmlspecialchars($record['db_pass'], ENT_QUOTES)?>">
            </div>
            
            <div class="form-group">
                <label for="db_name">Primary Database</label>
                <input type="text" name="db_name" id="db_name" placeholder="Main database name" value="<?=htmlspecialchars($record['db_name'], ENT_QUOTES)?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_name2">Database 2</label>
                    <input type="text" name="db_name2" id="db_name2" placeholder="Secondary DB (optional)"  value="<?=htmlspecialchars($record['db_name2'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="db_name3">Database 3</label>
                    <input type="text" name="db_name3" id="db_name3" placeholder="Tertiary DB (optional)"  value="<?=htmlspecialchars($record['db_name3'], ENT_QUOTES)?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="db_name4">Database 4</label>
                    <input type="text" name="db_name4" id="db_name4" placeholder="Optional" value="<?=htmlspecialchars($record['db_name4'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="db_name5">Database 5</label>
                    <input type="text" name="db_name5" id="db_name5" placeholder="Optional"  value="<?=htmlspecialchars($record['db_name5'], ENT_QUOTES)?>">
                </div>
            </div>
        </div>

        <!-- Site Access Section -->
        <div class="form-section">
            <h3 class="section-title">Site Access Credentials</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="site_user_login">User Login</label>
                    <input type="text" name="site_user_login" id="site_user_login" placeholder="User account" value="<?=htmlspecialchars($record['site_user_login'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="site_user_pass">User Password</label>
                    <input type="text" name="site_user_pass" id="site_user_pass" placeholder="User password" value="<?=htmlspecialchars($record['site_user_pass'], ENT_QUOTES)?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="site_admin_login">Admin Login</label>
                    <input type="text" name="site_admin_login" id="site_admin_login" placeholder="Admin account" value="<?=htmlspecialchars($record['site_admin_login'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="site_admin_pass">Admin Password</label>
                    <input type="text" name="site_admin_pass" id="site_admin_pass" placeholder="Admin password" value="<?=htmlspecialchars($record['site_admin_pass'], ENT_QUOTES)?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="site_webmaster_login">Webmaster Login</label>
                    <input type="text" name="site_webmaster_login" id="site_webmaster_login" placeholder="Webmaster account"  value="<?=htmlspecialchars($record['site_webmaster_login'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="site_webmaster_pass">Webmaster Password</label>
                    <input type="text" name="site_webmaster_pass" id="site_webmaster_pass" placeholder="Webmaster password" value="<?=htmlspecialchars($record['site_webmaster_pass'], ENT_QUOTES)?>">
                </div>
            </div>
        </div>

        <!-- Hosting Information Section -->
        <div class="form-section">
            <h3 class="section-title">Hosting Information</h3>
            
            <div class="form-group">
                <label for="hosting_name">Hosting Provider</label>
                <input type="text" name="hosting_name" id="hosting_name" placeholder="e.g., GoDaddy, Hostinger"  value="<?=htmlspecialchars($record['hosting_name'], ENT_QUOTES)?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="hosting_login">Hosting Login</label>
                    <input type="text" name="hosting_login" id="hosting_login" placeholder="Hosting account" value="<?=htmlspecialchars($record['hosting_login'], ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="hosting_pass">Hosting Password</label>
                    <input type="text" name="hosting_pass" id="hosting_pass" placeholder="Hosting password"  value="<?=htmlspecialchars($record['hosting_pass'], ENT_QUOTES)?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="hosting_url">Hosting Control Panel URL</label>
                <input type="text" name="hosting_url" id="hosting_url" placeholder="https://..." value="<?=htmlspecialchars($record['hosting_url'], ENT_QUOTES)?>">
            </div>
        </div>
        
        <div class="form-actions">
            <a href="access_resources.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>

</form>
<script src="assets/js/not_important_script.js"></script>
<?=template_admin_footer()?>