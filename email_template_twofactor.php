<!DOCTYPE html>
<html lang='en'>
	<head>
	    <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0' >
        <title><?=htmlspecialchars($subject ?? '', ENT_QUOTES)?></title>
	</head>
	<body style="background-color:#F5F6F8;font-size:16px;box-sizing:border-box;font-family:system-ui,'Segoe UI',Roboto,Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';">
		<div style="box-sizing:border-box;margin:50px auto;background-color:#fff;text-align:center;padding:40px;width:100%;max-width:600px;box-shadow:0 0 7px 0 rgba(0,0,0,.05);">
			<h1 style="box-sizing:border-box;font-size:18px;color:#474a50;padding:0 0 20px 0;margin:0;font-weight:600;">Hello, <?=$username?>!</h1>
			<p style="margin:0;padding:25px 0;">Access Code:<br><?=$link?></p>
		</div>
	</body>
</html>