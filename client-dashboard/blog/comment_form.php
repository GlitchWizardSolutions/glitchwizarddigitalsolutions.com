<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
include_once 'assets/includes/blog-config.php';
$errors ='DEBUG post.php : ';
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}//end sidebar position setting check.
?>
<div  id='1' class="col-md-8 mb-3">
<?php
$slug = $_GET['name'];

if (empty($slug)) {
    $errors=$errors . ' slug get name is empty ';
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}//end checking for the get name slug.

$stmt = $blog_pdo->prepare("SELECT * FROM `posts` WHERE active='Yes' AND slug=?");
$stmt->execute([$slug]);
if ($stmt->rowCount() == 0) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}//end selecting the correct post to display.

$stmt_update = $blog_pdo->prepare("UPDATE `posts` SET views = views + 1 WHERE active='Yes' AND slug=?");
$stmt_update->execute([$slug]);
$row         = $stmt->fetch(PDO::FETCH_ASSOC);
$post_id     = $row['id'];
$post_slug   = $row['slug'];
?>
<div id='2' class="card shadow-sm bg-light">
     <div id='3' class="col-md-12">
<?php         
if ($row['image'] != '') {
    echo '<img src="' . $row['image'] . '" width="100%" height="auto" alt="' . $row['title'] . '"/>';
}//end row img check
?>
<div id='4' class="card-body">
    	<div id='5' class="mb-1">
    	    <i class="fas fa-chevron-right"></i> 
            <a href="category?name=<?=post_categoryslug($row['category_id'])?>"><?=post_category($row['category_id'])?></a> 
		</div><?php /* end div id 5 */ ?>
				<h5 class="card-title fw-bold"><?=$row['title']?></h5>
				<div id='6' class="d-flex justify-content-between align-items-center">
					<small>
						Posted by <b><i><i class="fas fa-user"></i><?=post_author($row['author_id']) ?></i></b> 
						on <b><i><i class="far fa-calendar-alt"></i><?=date($settings['date_format'], strtotime($row['date']))?>, <?=$row['time']?></i></b>
					</small>
					<small> 	
						<i class="fa fa-eye"></i><?=$row['views']?>
					</small>
					<small class="float-end">
						<i class="fa fa-comments"></i> <a href="#comments"><b><?=post_commentscount($row['id'])?></b></a>
					</small>
				</div><?php /* end div id 6 */ ?>
				<hr />
				
               <?=html_entity_decode($row['content'])?>
				<hr />
				
				<h5><i class="fas fa-share-alt-square"></i> Share</h5>
				<div id="share" style="font-size: 14px;"></div><?php /* end div id share */ ?>
				<hr />

				<h5 class="mt-2" id="comments">
					<i class="fa fa-comments"></i> Comments (<?=post_commentscount($row['id'])?>)
				</h5> 
 
<?php
$stmt_comments = $blog_pdo->prepare("SELECT * FROM comments WHERE post_id=? AND approved='Yes' ORDER BY id DESC");
$stmt_comments->execute([$row['id']]);
$count = $stmt_comments->rowCount();
if ($count <= 0) {
    echo '<div class="alert alert-info">There are no comments yet.</div>';
} else {
    while ($comment = $stmt_comments->fetch(PDO::FETCH_ASSOC)) {
        $aauthor = $comment['user_id'];
        
        if ($comment['guest'] == 'Yes') {
            $aavatar = 'assets/img/avatar.png';
            $arole   = '<span class="badge bg-secondary">Guest</span>';
        } else {
            
            $stmt_user = $blog_pdo->prepare("SELECT * FROM `users` WHERE id=? LIMIT 1");
            $stmt_user->execute([$aauthor]);
            if ($stmt_user->rowCount() > 0) {
                $rowch = $stmt_user->fetch(PDO::FETCH_ASSOC);
                
                $aavatar = $rowch['avatar'];
                $aauthor = $rowch['username'];
                if ($rowch['role'] == 'Admin') {
                    $arole = '<span class="badge bg-danger">Administrator</span>';
                } elseif ($rowch['role'] == 'Editor') {
                    $arole = '<span class="badge bg-warning">Editor</span>';
                } else {
                    $arole = '<span class="badge bg-info">User</span>';
                }
            }
        }
        
        echo '
		<div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
			<div class="mb-2 d-flex flex-start align-items-center">
				<img class="rounded-circle shadow-1-strong mt-1 me-3"
					src="' . $aavatar . '" alt="' . $aauthor . '" 
					width="50" height="50" />
				<div class="mt-1 mb-1">
					<h6 class="fw-bold mt-1 mb-1">
						<i class="fa fa-user"></i> ' . $aauthor . ' ' . $arole . '
					</h6>
					<p class="small mb-0">
						<i><i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($comment['date'])) . ', ' . $comment['time'] . '</i>
					</p>
				</div>
			</div>
			<hr class="my-0" />
			<p class="mt-1 mb-1 pb-1">
				' . emoticons($comment['comment']) . '
			</p>
		</div>
	';
    }
}
?>
                   <h5 class="mt-4">Leave A Comment</h5>
<?php
$guest = 'No';
if ($logged == 'No' AND $settings['comments'] == 'guests') {//If you're not logged on, settings = guests for commenting.
    $cancomment = 'Yes';
} else {
    $cancomment = 'No';//This value is for commenting being no, and comments does not allow guests.
}//checking status of whether user is logged in and/or is allowed by settings to comment.
if ($logged == 'Yes') { //If you're logged, comment all you want to.
    $cancomment = 'Yes';
}
if ($cancomment == 'Yes') {// Display the form, if comments are allowd for the user.
?>
<?php
if ($logged == 'No'){
    $logged_in = false;
}else{
    $logged_in = true;
}
//This prefills the form with the logged in username, or the guest leaves it blank so you have to type it in.
$author_name = $logged_in ? $author = $rowu['id'] : "";  
?> 
 

<h2>Leave a Comment</h2>

<?php
// Optional error display if redirected back
if (isset($_GET['error'])) {
  echo "<p style='color:red'>" . htmlspecialchars($_GET['error'], ENT_QUOTES) . "</p>";
}
?>

<form id="comment-form" method="POST" action="recaptcha_process.php">
  <label for="author">Author:</label><br>
  <?php if (!$logged_in): ?>
    <input type="text" name="author" id="author" minlength="5" required><br>
  <?php else: ?>
    <input type="text" name="author" id="author" value="<?php echo htmlspecialchars($author_name); ?>" readonly><br>
  <?php endif; ?>

  <label for="comment">Comment:</label><br>
  <textarea name="comment" id="comment" minlength="5" required></textarea><br>

  <!-- Hidden reCAPTCHA token field -->
  <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

  <!-- Submit "link" styled as a button -->
  <a href="#" id="submit-btn" class="btn btn-standard">Submit Comment</a>
</form>

<!-- JavaScript to handle reCAPTCHA execution and form submit -->
<script>
  document.getElementById('submit-btn').addEventListener('click', function (e) {
    e.preventDefault(); // Prevent default link behavior

    // Optional: Basic frontend validation
    const author = document.getElementById('author');
    const comment = document.getElementById('comment');
    if (author && author.value.length < 5) {
      alert("Author must be at least 5 characters.");
      return;
    }
    if (comment.value.length < 5) {
      alert("Comment must be at least 5 characters.");
      return;
    }

    grecaptcha.ready(function () {
      grecaptcha.execute('6LdmAmgrAAAAAIdsJeCLDjkPhYeVZIH6wSGqkxIH', { action: 'submit' }).then(function (token) {
        document.getElementById('g-recaptcha-response').value = token;
        document.getElementById('comment-form').submit(); // Submit after token
      });
    });
  });
</script>
<script>
$("#share").jsSocials({
    showCount: false,
    showLabel: true,
    shares: [
        { share: "facebook", logo: "fab fa-facebook-square", label: "Share" },
        { share: "twitter", logo: "fab fa-twitter-square", label: "Tweet" },
        { share: "linkedin", logo: "fab fa-linkedin", label: "Share" },
		{ share: "email", logo: "fas fa-envelope", label: "E-Mail" }
    ]
});

function countText() {
	let text = document.comment_form.comment.value;
	
	document.getElementById('characters').innerText = 1000 - text.length;
	//document.getElementById('words').innerText = text.length == 0 ? 0 : text.split(/\s+/).length;
	//document.getElementById('rows').innerText = text.length == 0 ? 0 : text.split(/\n/).length;
}
</script>
<?php
} // Close the if ($cancomment == 'Yes') block from line 136
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>