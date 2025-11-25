<?php
//2025-06-24 Production
error_log('Loading Page: /blog/recaptcha_process');
include_once 'assets/includes/blog-config.php';
// === EDIT THIS WITH YOUR SECRET KEY ===
$secret = $settings['gcaptcha_secretkey'];
$date             = date($settings['date_format']);
$time             = date('H:i');
$remoteIp = $_SERVER['REMOTE_ADDR'];
$gcaptcha_projectid = $settings["gcaptcha_projectid"];
// === Get form data ===
if ($logged == 'No'){
    $logged_in = false;
}else{
    $logged_in = true;
}
$author_name = $logged_in ? $author = $rowu['id'] : trim($_POST['author']);  

$author = $author_name;
$comment = trim($_POST['comment']);
$recaptcha_response = $_POST['g-recaptcha-response'];

// === Validate basic fields ===
if (strlen($author)??0 < 5 || strlen($comment)??0 < 5) {
    // Safely encode the query parameters
                 $slug = urlencode($row['slug']);
                 $error = 'Please use at least 5 letters.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: post?name=$slug&error=$error_encoded#comments");
                 exit;
}

// === Verify reCAPTCHA token ===
$errors=$errors . ' BEGIN CAPTCHA VERIFY WITH GOOGLE... ';
$verify_url = 'https://www.google.com/recaptcha/api/siteverify';

$data = [
    'secret' => $secret,
    'response' => $recaptcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($verify_url, false, $context);
$verification = json_decode($result, true);

// === Check score and success ===
if ($verification['success'] && $verification['score'] >= 0.5) {
    // ✅ Good score, accept comment
            if ($cancomment == 'Yes') {
                    // Safely encode the query parameters
                        $slug = urlencode($row['slug']);
                        $success = 'Your comment has been successfully posted.';
                        $success_encoded = urlencode($error);
    
                        $stmt = $blog_pdo->prepare("INSERT INTO comments (post_id, comment, user_id, date, time, guest, ip) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$row['id'], $comment, $author, $date, $time, $guest, $remoteIp]);
                        $errors=$errors . ' Insert Comment Complete. ';
                        header("Location: post?name=$slug&success=$success_encoded#comments");
                        exit;
            }else{
                // Safely encode the query parameters
                 $slug = urlencode($row['slug']);
                 $error = 'You must be logged in to comment.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: post?name=$slug&error=$error_encoded#comments");
                 exit;
            }
} else {
    // ❌ Low score or invalid
                 $slug = urlencode($row['slug']);
                 $error = 'Google cannot verify you are a human.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: post?name=$slug&error=$error_encoded#comments");
                 exit;
}
?>
