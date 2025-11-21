<?php defined('mail_enabled') or exit; ?>
<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
        <title><?=htmlspecialchars($subject, ENT_QUOTES)?></title>
	</head>
	<body style="background-color:#F5F6F8;font-size:16px;box-sizing:border-box;font-family:system-ui,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';">
		<div style="box-sizing:border-box;margin:50px auto;background-color:#fff;padding:40px;width:100%;max-width:600px;box-shadow:0 0 7px 0 rgba(0,0,0,.05);">
			<h1 style="box-sizing:border-box;font-size:18px;color:#474a50;padding:0 0 20px 0;margin:0;font-weight:700;border-bottom:1px solid #eee;">Ticket #<?=$id?></h1>
			<p style="margin:0;padding:25px 0;">Ticket Details:</p>
           
			<?php if ($type == 'comment'): ?>
			 <div style="display:flex;flex-wrap:wrap;">
            <div style="width:100%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Comment</div>
                <div style="padding:5px;font-size:16px;"><?=nl2br(htmlspecialchars($msg, ENT_QUOTES))?></div>
            </div>
            </div>
			<?php else: ?>
            <?php if ($name): ?>
            <div style="display:flex;flex-wrap:wrap;">
            <div style="width:100%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Name</div>
                <div style="padding:5px;font-size:16px;"><?=htmlspecialchars($name, ENT_QUOTES)?></div>
            </div>
            </div>
            <?php endif; ?>
            <?php if ($user_email): ?>
            <div style="display:flex;flex-wrap:wrap;">
            <div style="width:100%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Email</div>
                <div style="padding:5px;font-size:16px;"><?=htmlspecialchars($user_email, ENT_QUOTES)?></div>
            </div>
            </div>
            <?php endif; ?>
 
            <div style="display:flex;flex-wrap:wrap;">
            <div style="width:50%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Category</div>
                <div style="padding:5px;font-size:16px;"><?=$category?></div>
            </div>
             
            <div style="width:50%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Priority</div>
                <div style="padding:5px;font-size:16px;"><?=ucfirst($priority) ?></div>
            </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;">
            <div style="width:50%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Private</div>
                <div style="padding:5px;font-size:16px;">Yes</div>
            </div>  
            <div style="width:50%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Status</div>
                <div style="padding:5px;font-size:16px;"><?=ucfirst($status) ?></div>
            </div>
            </div>
            <div style="width:100%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Title</div>
                <div style="padding:5px;font-size:16px;"><?=htmlspecialchars($title, ENT_QUOTES)?></div>
            </div>
            <div style="width:100%;padding-bottom:10px;">
                <div style="padding:5px;font-weight:700;font-size:16px;">Message</div>
                <div style="padding:5px;font-size:16px;"><?=nl2br(htmlspecialchars($msg), ENT_QUOTES)?></div>
            </div>
			<?php endif; ?>
            </div>
             <div style="text-align:center;padding:30px 0;">
			<p style="margin-bottom:20px;">Click the button below to view and respond to your ticket:</p>
			<a href="<?=$link?>" style="display:inline-block;background-color:#012970;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:600;">View Ticket #<?=$id?></a>
			<p style="margin-top:20px;font-size:14px;color:#666;">Or copy this link: <a href="<?=$link?>" style="color:#c52424;word-break:break-all;"><?=$link?></a></p>
		</div>
	</body>
</html>