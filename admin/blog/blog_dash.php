<?php
error_log('ERROR Page: blog_dash ');
require 'assets/includes/admin_config.php';
?>
<?=template_admin_header('Blog Dashboard', 'blog')?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Admin Blog Dashboard</h2>
            <p>Manage your blog from here.</p>
        </div>
    </div>
</div>
	 
	  
            <div class="card">
              <h6 class="card-header">Shortcuts</h6>         
                <div class="card-body">
                  <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="add_post.php" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Write Post</a>
                    <a href="settings.php" class="btn btn-primary btn-sm"><i class="fa fa-cogs"></i> Settings</a>
					<a href="messages.php" class="btn btn-primary btn-sm"><i class="fa fa-envelope"></i> Messages</a>
					<a href="menu_editor.php" class="btn btn-primary btn-sm"><i class="fa fa-bars"></i> Menu Editor</a>
					<a href="add_page.php" class="btn btn-primary btn-sm"><i class="fa fa-file-alt"></i> Add Page</a>
					<a href="add_image.php" class="btn btn-primary btn-sm"><i class="fa fa-camera-retro"></i> Add Image</a>
					<a href="widgets.php" class="btn btn-primary btn-sm"><i class="fa fa-archive"></i> Widgets</a>
					<a href="upload_file.php" class="btn btn-primary btn-sm"><i class="fa fa-upload"></i> Upload File</a>
                    <a href="<?php echo rtrim($settings['site_url'], '/') . '/'; ?>" class="btn btn-primary btn-sm" target="_blank"><i class="fa fa-eye"></i> Visit Site</a>
                  </div>
                </div>
            </div>
	  
	  <div class="row mt-3">
         <div class="col-md-6 column">
             <div class="card">
              <h6 class="card-header">Statistics</h6>         
                  <div class="card-body">
					<ul class="list-group">
<?php
$post_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM posts')->fetchColumn();
?>
                      <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="posts.php">
						    <i class="fa fa-list"></i> Posts
                        </a>
						<span class="badge bg-primary rounded-pill"><?php
echo $post_cnt;
?></span>
                      </li>
<?php
$catagory_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM categories')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="categories.php">
						    <i class="fa fa-list-ol"></i> Categories
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $catagory_cnt;
?></span>
                      </li>
<?php
$comments_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM comments')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="comments.php">
						    <i class="fa fa-comments"></i> Comments
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $comments_cnt;
?></span>
                      </li>
<?php
$gallery_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM gallery')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="gallery.php">
						    <i class="fa fa-image"></i> Images
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $gallery_cnt;
?></span>
                      </li>
<?php
$albums_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM albums')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="albums.php">
						    <i class="fa fa-list-ol"></i> Albums
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $albums_cnt;
?></span>
                      </li>
<?php
$pages_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM pages')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="pages.php">
						    <i class="fa fa-file-alt"></i> Pages
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $pages_cnt;
?></span>
                      </li>
<?php
$widgets_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM widgets')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="widgets.php">
						    <i class="fa fa-archive"></i> Widgets
                        </a>
						<span class="badge bg-primary rounded-pill"><?php
echo $widgets_cnt;
?></span>
                      </li>
<?php
$files_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM files')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="files.php">
						    <i class="fa fa-folder-open"></i> Files
                        </a>
						<span class="badge bg-primary rounded-pill"><?php
echo $files_cnt;
?></span>
                      </li>

<?php
$messages_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM messages')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="messages.php">
						    <i class="fa fa-envelope"></i> Unread Messages
                        </a>
						<span class="badge bg-primary rounded-pill"><?php
echo $messages_cnt;
?></span>
                      </li>
<?php
$users_cnt = $blog_pdo->query('SELECT COUNT(*) AS total FROM users')->fetchColumn();
?>
					  <li class="list-group-item d-flex justify-content-between align-items-start">
                        <a href="users.php">
						    <i class="fa fa-users"></i> Users
                        </a>
                        <span class="badge bg-primary rounded-pill"><?php
echo $users_cnt;
?></span>
                      </li>
                    </ul>
                  </div>
            </div>
        </div>    

		<div class="col-md-6 column">
             <div class="card">
              <h6 class="card-header">Recent Comments</h6>
              <div class="card-container-toggle">
                  <div class="card-body">
                    <div class="row">
<?php
$comments = $blog_pdo->query('SELECT * FROM comments ORDER BY id DESC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
?>
                <?php if (!$comments): ?>
                    <p colspan="20" class="no-results">There are no recent new comments.</p>
                </tr>
                <?php endif; ?>
                <?php 
                        foreach ($comments as $comment){
                            $stmt = $blog_pdo->prepare('SELECT * FROM posts WHERE id = ?');
                            $stmt->execute([ $comment['post_id'] ]);
                            $posts = $stmt->fetch(PDO::FETCH_ASSOC);
                             foreach ($posts as $post) {
                                  $author = $comment['user_id'];
                                        if ($comment['guest'] == 'Yes') {
                                                $avatar = 'assets/img/avatar.png';
                                        }elseif($comment['guest'] == 'No') {
                                                $stmt = $blog_pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
                                                $stmt->execute([$author]);
                                                $users = $stmt->fetch(PDO::FETCH_ASSOC);
                                                if($users){
                                                error_log('username not matching');
                                                   $avatar = $users['avatar'];
                                                }//end if users exist.
                                        }//end if/elseif a poster is a guest.
                            }//end for each post.?>
                      
                        <?php           echo '
				<div class="col-md-10">
					<a href="comments.php?edit-id="' . $comment["id"] . '>
						<span class="blue"><strong>' . $author . ' </strong></a>' . date($settings['date_format'], strtotime($comment['date'])) . '</span>
					<br />
';
            if ($comment['approved'] == "Yes") {
                echo '<strong>Status:</strong> <span class="badge bg-success">Approved</span> ';
            } else {
                echo '<strong>Status:</strong> <span class="badge bg-warning">Pending</span> ';
            }
            if ($comment['guest'] == "Yes") {
                echo '<span class="badge bg-info"> Guest</span> ';
            }
            echo '
                    <p>' . short_text($comment['comment'], 100) . '</p>
				</div>
';
?></tr>
   <?php }//for each comment
                       ?>   
            </tbody>
        </table>
    </div>
</div>
  
            
                   </div>
                  </div>
              </div>
            </div>
         </div>
      </div>

<?php
include "footer.php";
?>