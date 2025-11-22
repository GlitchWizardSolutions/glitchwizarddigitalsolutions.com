<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['unsubscribe'])) {
	$unsubscribe_email = $_GET['unsubscribe'];

    $stmt = $blog_pdo->prepare("SELECT * FROM `newsletter` WHERE email = ? LIMIT 1");
    $stmt->execute([$unsubscribe_email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $blog_pdo->prepare("DELETE FROM `newsletter` WHERE email = ?");
        $stmt->execute([$unsubscribe_email]);
    }
}
?>
<?=template_admin_header('Newsletter', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Newsletter', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Newsletter</h2>
            <p>Manage newsletter subscribers and send messages</p>
        </div>
    </div>
</div>

<div class="form-professional">
        <div class="card">
			<h6 class="card-header">Send mass message</h6>         
			<div class="card-body">
<?php
if (isset($_POST['send_mass_message'])) {
    $title = trim($_POST['title']);
    $content = $_POST['content'];

    $from = $settings['email'];
    $sitename = $settings['sitename'];
	
    $stmt = $blog_pdo->query("SELECT * FROM `newsletter`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		
        $to = $row['email'];
		
        $subject = $title;
        
        $message = '
<html>
<body>
  <b><h1><a href="' . $settings['site_url'] . '/" title="Visit the website">' . $settings['sitename'] . '</a></h1><b/>
  <br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
        
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        $headers .= 'From: ' . $from . '';
        
        @mail($to, $subject, $message, $headers);
    }
    
    echo '<div class="alert alert-success">' . svg_icon_email() . ' Your global message has been sent successfully.</div>';
}
?>
				<form action="" method="post">
					<p>
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</p>
					<p>
						<label>Content</label>
						<textarea class="form-control" id="summernote" name="content" required></textarea>
					</p>
								
					<input type="submit" name="send_mass_message" class="btn btn-primary col-12" value="Send" />
				</form>
			</div>
        </div>
</div><br />
			
			<div class="card">
              <h6 class="card-header">Subscribers</h6>         
                  <div class="card-body">
                    <table class="table table-border table-hover" id="dt-basic" width="100%">
                          <thead>
                              <tr>
                                  <th>E-Mail</th>
								  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM newsletter ORDER BY email ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <tr>
                                <td>' . htmlspecialchars($row['email']) . '</td>
								<td>
									<a href="?unsubscribe=' . urlencode($row['email']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to unsubscribe this email?\')"><i class="fas fa-bell-slash"></i> Unsubscribe</a>
								</td>
                            </tr>
';
    }
?>
                          </tbody>
                     </table>
                  </div>
            </div>

<?=template_admin_footer('
<script>
$(document).ready(function() {

	$("#dt-basic").dataTable( {
		"responsive": true,
		"order": [[ 0, "asc" ]],
		"language": {
			"paginate": {
			  "previous": "<i class=\"fa fa-angle-left\"></i>",
			  "next": "<i class=\"fa fa-angle-right\"></i>"
			}
		}
	} );
	
	$("#summernote").summernote({height: 350});
	
	var noteBar = $(".note-toolbar");
		noteBar.find("[data-toggle]").each(function() {
		$(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
	});
} );
</script>
')?>