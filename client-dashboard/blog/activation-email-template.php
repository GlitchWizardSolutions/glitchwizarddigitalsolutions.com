<!DOCTYPE html>
<html lang='en'>
	<head>
	    <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0' >
        <title><?=htmlspecialchars($subject ?? '', ENT_QUOTES)?></title>
	</head>
	<body style="background-color:#F5F6F8;font-family:-apple-system, BlinkMacSystemFont, 'segoe ui', roboto, oxygen, ubuntu, cantarell, 'fira sans', 'droid sans', 'helvetica neue', Arial, sans-serif;box-sizing:border-box;font-size:16px;">
		<div style="padding:30px;background-color:#fff;margin:30px;text-align:center;box-sizing:border-box;font-size:16px;">
			<h1 style="box-sizing:border-box;font-size:18px;color:#474a50;padding-bottom:10px;">Account Activation Required</h1>
			<p style="box-sizing:border-box;font-size:16px;">Hello, <?=$username?>! <br> Click <a href="<?=$link?>" style="text-decoration:none;color:#c52424;box-sizing:border-box;font-size:16px;">here</a> to activate your account.</p>
		</div>
	</body>
</html>