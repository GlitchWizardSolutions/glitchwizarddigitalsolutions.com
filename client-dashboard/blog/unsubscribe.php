<?php
//2025-06-25 READY FOR TESTING.
//UNSUBSCRIBE TO THE BLOG NEWSLETTER
include_once 'assets/includes/blog-config.php';
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-envelope"></i> Unsubscribe</div>
                <div class="card-body">
<?php
if (!isset($_GET['email'])) {
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit;
} else {
  	    $email = $_GET['email'];
        $stmt = $blog_pdo->prepare('SELECT * FROM newsletter WHERE email = ? LIMIT 1');
	    $stmt->execute([$email]);
	    $newletter_email = $stmt->fetch(PDO::FETCH_ASSOC);
     
    if (!$newletter_email) {
        echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
        exit;
        
    } else {
        $stmt = $blog_pdo->prepare('DELETE FROM newsletter WHERE email = ?');
        $stmt->execute([$email]);;
        echo '<div class="alert alert-primary">You were unsubscribed successfully.</div>';
    }
}//end if there is an email or not to unsubscribe.
?>
                </div>
        </div>
    </div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>