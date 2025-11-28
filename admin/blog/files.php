<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id     = (int) $_GET["delete-id"];
    $query2 = $blog_pdo->prepare("SELECT * FROM `files` WHERE id=?");
    $query2->execute([$id]);
    $row2   = $query2->fetch(PDO::FETCH_ASSOC);
    $path   = $row2['path'];
	
    // Convert web path to server path for deletion
    if (strpos($path, '/') === 0) {
        // Web path format - convert to server path using constant
        $server_path = str_replace(blog_files_url, blog_files_path, $path);
    } else {
        // Old relative path format
        $server_path = $path;
    }
    
    if (file_exists($server_path)) {
        unlink($server_path);
    }
    
	$query = $blog_pdo->prepare("DELETE FROM `files` WHERE id=?");
    $query->execute([$id]);
    
    header('Location: files.php');
    exit;
}
?>
<?=template_admin_header('Files', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Files', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-file"></i>
        <div class="txt">
            <h2>Files</h2>
            <p>Manage uploaded files</p>
        </div>
    </div>
</div>

            <div class="card">
              <h6 class="card-header">Files</h6>         
					<div class="card-body">
						<a href="upload_file.php" class="btn btn-primary col-12">
							<i class="fas fa-upload"></i> Upload File
						</a><br /><br />

            <table class="table table-border table-hover" width="100%">
                <thead>
				<tr>
                    <th>File Name</th>
					<th>Type</th>
					<th>Size</th>
					<th>Uploaded</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$query = $blog_pdo->query("SELECT * FROM files ORDER BY id DESC");
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // Convert old relative paths to web paths if needed
        $file_path = $row['path'];
        
        // Handle various old path formats
        if (strpos($file_path, '../') === 0 || strpos($file_path, '../../') === 0) {
            // Old relative path format - extract just the filename and use the constant
            $filename = basename($file_path);
            $file_path = blog_files_url . $filename;
        } elseif (strpos($file_path, '/') !== 0 && strpos($file_path, 'http') !== 0) {
            // Relative path without ../ - assume it's in the files directory
            $file_path = blog_files_url . basename($file_path);
        }
        
        // Check if file exists for server-side path using constant
        $server_path = str_replace(blog_files_url, blog_files_path, $file_path);
        $file_exists = file_exists($server_path);
        $file_type = $file_exists ? strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)) : 'Unknown';
        $file_size = $file_exists ? byte_convert(filesize($server_path)) : 'N/A';
        
        echo '
                <tr>
	                <td>' . htmlspecialchars($row['filename']) . '</td>
					<td>' . $file_type . '</td>
					<td>' . $file_size . '</td>
					<td data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . ', ' . htmlspecialchars($row['time']) . '</td>
					<td>
					    <a href="' . htmlspecialchars($file_path) . '" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-eye"></i> View</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
    }
?>
            </table>
				</div>
            </div>

<?=template_admin_footer()?>