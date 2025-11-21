<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/users ');
require 'assets/includes/admin_config.php';
if (isset($_GET['delete-id'])) {
    $id     = (int) $_GET["delete-id"];
    // Delete the user
    $stmt = $blog_pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
        // Delete the comments
    $stmt = $blog_pdo->prepare('DELETE FROM comments WHERE user_id = ? AND guest="No"');
    $stmt->execute([$id]);
}
?>

<?=template_admin_header('Blog Users', 'blog', 'blog')?>
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Blog Users</h2>
            <p>Manage your users from here.</p>
        </div>
    </div>
        <div class="btns">
           <a href="https://glitchwizarddigitalsolutions.com/blog/" class="btn btn-primary" style='background:green'><i class="fa fa-eye"></i>&nbsp;  Go to Blog</a>
    </div>
</div>

<div class="content-header responsive-flex-column pad-top-5">
               <div class="card">
              <h6 class="card-header">Shortcuts</h6>         
                <div class="card-body">
     <center>
                    <a href="blog_dash.php" class="btn btn-sm btn-primary mt-2">Blog Dashboard</a>
					<a href="add_user.php" class="btn btn-sm btn-primary mt-2">+ User</a>
				 
                  </center>
</div>
      </div>
            </div>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	 
	</div>
	
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_users = $blog_pdo->query('SELECT COUNT(*) AS total FROM users')->fetchColumn();

    if (empty($id) ||  $total_users == 0) {
        echo '<meta http-equiv="refresh" content="0; url=users.php">';
       	exit;
    }
    
   	if (isset($_POST['edit'])) {
		$role = $_POST['role'];
        $stmt =$blog_pdo->prepare('UPDATE users SET role= ?, WHERE id= ?');
        $stmt->execute([$role, $id]);

        echo '<meta http-equiv="refresh" content="0;url=users.php">';
    }   
        
?>

            <div class="card mb-3">
              <h6 class="card-header">Edit User</h6>         
                  <div class="card-body">
                    <form action="" method="post">
						<div class="form-group">
							<label class="control-label">Username: </label>
							<input type="text" name="username" class="form-control" value="<?php
    echo $row['username'];
?>" readonly disabled>
						</div><br />
						<div class="form-group">
							<label class="control-label">E-Mail Address: </label>
								<input type="email" name="email" class="form-control" value="<?php
    echo $row['email'];
?>" readonly disabled>
						</div><br />
						<div class="form-group">
							<label class="control-label">Role: </label><br />
							<select name="role" class="form-select" required>
								<option value="User" <?php
    if ($row['role'] == "User") {
        echo 'selected';
    }
?>>User</option>
                                <option value="Editor" <?php
    if ($row['role'] == "Editor") {
        echo 'selected';
    }
?>>Editor</option>
								<option value="Admin" <?php
    if ($row['role'] == "Admin") {
        echo 'selected';
    }
?>>Administrator</option>
                            </select><br />
						</div>
						<div class="form-actions">
                            <input type="submit" name="edit" class="btn btn-primary col-12" value="Save" />
                        </div>
					</form>
                  </div>
            </div>
<?php
}
?>

			<div class="card">
              <h6 class="card-header">Users</h6>         
                  <div class="card-body">
                    <table id="dt-basic" class="table table-border table-hover bootstrap-datatable" width="100%">
                          <thead>
                              <tr>
								  <th>Username</th>
								  <th>E-Mail</th>
								  <th>Role</th>
								  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
<?php
$stmt =$blog_pdo->prepare("SELECT * FROM users ORDER BY id ASC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
 foreach($users as $row){ 
       $badge = '';
    if ($row['role'] == 'Admin') {
        $badge = '<h6><span class="badge bg-danger">Admin</span></h6>';
    }
	if ($row['role'] == 'Editor') {
        $badge = '<h6><span class="badge bg-success">Editor</span></h6>';
    }
	if ($row['role'] == 'User') {
        $badge = '<h6><span class="badge bg-primary">User</span></h6>';
    }
    echo '
                            <tr>
                                <td><img src="../../blog/' . $row['avatar'] . '" width="40px" height="40px" /> ' . $row['username'] . '</td>
								<td>' . $row['email'] . '</td>
								<td>' . $badge . '</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="?edit-id=' . $row['id'] . '">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?delete-id=' . $row['id'] . '">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
';
}
?>                              
                          </tbody>
                     </table>
                  </div>
            </div>
			
<script>
$(document).ready(function() {
	$('#dt-basic').dataTable( {
		"responsive": true,
		"language": {
			"paginate": {
			  "previous": '<i class="fas fa-angle-left"></i>',
			  "next": '<i class="fas fa-angle-right"></i>'
			}
		}
	} );
} );
</script>
<?php
include "footer.php";
?>