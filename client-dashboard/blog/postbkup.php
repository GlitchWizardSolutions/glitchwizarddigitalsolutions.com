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
  <form name="comment_form" action="post?name=<?=$post_slug;?>" method="post">
<?php
    if ($logged == 'No') {//The user doesn't have to be logged in, if guests are allowed to post.
        $guest = 'Yes';
?>
     <label for="name"><i class="fa fa-user"></i> Name:</label>
     <input type="text" name="author" value="" class="form-control" required />
     <br />
<?php
    }
?>
                        <label for="comment"><i class="fa fa-comment"></i> Comment:</label>
                        <textarea name="comment" id="comment" rows="5" class="form-control" maxlength="1000" oninput="countText()" required></textarea>
						<label for="characters"><i>Characters left: </i></label>
						<span id="characters">1000</span><br>
						<br />
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
<?php
    }
?>
<!-- reCAPTCHA v3 token will be added via JS -->
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response"> 
    <input class="g-recaptcha btn btn-primary col-12" type="submit" name="post" data-sitekey="<?=$settings['gcaptcha_sitekey']?>" 
        data-callback='onSubmit' 
        data-action='submit' value="Post" />*/ ?>
   <?php /*       <button name="post" class="g-recaptcha btn btn-primary col-12" type="submit"
        data-sitekey="<?=$settings['gcaptcha_sitekey']?>" 
        data-callback='onSubmit' 
        data-action='submit'>Submit</button>*/ ?>

</form>
<?php
} else {
    echo '<div class="alert alert-info">Please <strong><a href="login"><i class="fas fa-sign-in-alt"></i> Sign In</a></strong> to be able to post a comment.</div>';
}//if a user has to log in to comment, this tells them so.

//  $errors=$errors . ' can comment is ' . $cancomment . ' ';
if ($cancomment == 'Yes') {
     
    if (isset($_POST['post'])) {
      //  $errors=$errors . ' Form was submitted. ';       
        $authname_problem = 'No';
        $date             = date($settings['date_format']);
        $time             = date('H:i');
		$comment          = $_POST['comment'];
		$sitekey = $settings["gcaptcha_sitekey"];
        $secret =  $settings["gcaptcha_secretkey"];  
        $token = $_POST['g-recaptcha-response'];
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        $gcaptcha_projectid = $settings["gcaptcha_projectid"];
	    $score = 0;
	    $bot = 'Yes';//Assuming bot, until passes recaptcha test.
        $captcha = '';
        $errors=$errors . ' Comment submitted ' . $comment . ' ';
 
        if ($logged == 'No') {
            $author = $_POST['author'];//The author is set by the name field the user entered on the form, when they're not logged in.
        } else {// The user is logged in, so the author is the user's logged in name.
            $bot    = 'No';
            $author = $rowu['username'];
        }//end logged in checked.
        
            if (isset($_POST['g-recaptcha-response'])) {
                 $captcha = $_POST['g-recaptcha-response'];
            }//checking the recaptcha response from the form submission.
   
            if ($captcha) {
                $errors=$errors . ' BEGIN CAPTCHA VERIFY WITH GOOGLE... ';
                
            } else{//The Captcha was never posted.
             $errors=$errors . ' No Captcha was Received via POST in the form. ';
            }//end of captcha, score obtained, bot is set for either Yes or No.
                 $errors=$errors . ' End of captcha check';
                //Continue with form validation of user that is not logged in. (Guest)
                    if (strlen($author) < 5) {
                    $authname_problem = 'Yes';
                    echo '<div class="alert alert-warning">Your name is too short, it must be at least 5 letters.</div>';
                    }//end guest author name length validation.
       
        //$errors=$errors . ' End Login Check & Processing.  Continue... ';
        //Continue validation for both guests and users.
         if (strlen($comment) < 5) {
            echo '<div class="alert alert-danger">Your comment is too short, it needs to be at least 5 letters.</div>';
        } else {// if it passes the validation for the comment length, continue to final validation before processing.
        // $errors=$errors . ' Comment Length Check Complete. ';
                if ($authname_problem == 'No' AND $bot == 'No') {
                        $errors=$errors . ' Begin Insert into comments. ';
                        $stmt = $blog_pdo->prepare("INSERT INTO `comments` (`post_id`, `comment`, `user_id`, `date`, `time`, `guest`) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$row['id'], $comment, $author, $date, $time, $guest]);
                        echo '<div class="alert alert-success">Your comment has been successfully posted</div>';
                        echo '<meta http-equiv="refresh" content="0;url=post?name=' . $row['slug'] . '#comments">';
                        $errors=$errors . ' Insert Comment Complete. ';
                }//end the authname and bot validation - the comment was either uploaded or denied at this point.
                // $errors=$errors . ' Authname and Bot Complete. ';
        }//end comment length validation.
       //$errors=$errors . ' Length Validation Complete. ';
    }//end if the user or guest has submitted their comment.
   //$errors=$errors . ' Submission Complete. ';
}//end cancomment 
$errors=$errors . ' Ended. ';
error_log($errors)?? '';
?>
                    </div> <?php /* end div id 5 */ ?>
                </div><?php /* end div id 3. */?> 
            </div><?php /* end div id 2. */?> 
        </div><?php /* end div id 1 */?> 
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
<script>
   function onSubmit(token) {
     document.getElementById("comment_form").submit();
   }
 </script>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>