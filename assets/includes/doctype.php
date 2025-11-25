<?php
echo '<!--
 5. DOCTYPE: client-dashboard/assets/includes/doctype.php-->';
// Single cache-busting key for all assets on this page load
$asset_version = time();
?>
<!DOCTYPE html>
<html lang='en' class='no-js'>
	<head>
	    <meta name="Content-Type" content="text/html">
	    <meta name="charset" content="utf-8">
        <meta name='viewport' content='width=device-width, initial-scale=1.0,user-scalable=no' >
        <meta name='author'                       content='Barbara Moore' >
        <meta name='copyright'                    content='GlitchWizard Solutions, Tallahassee, Florida'>
        <meta name='language'                     content='ES'>
        <meta name='robots'                       content='noindex,nofollow'>
        <meta name='revised'                      content='Sunday, September 8th, 2024, 1:45 pm'>
        <meta name='designer'                     content='Barbara Moore, GlitchWizard Solutions'>
        <meta http-equiv='Cache-Control'          content='no-cache'>
        <meta http-equiv='imagetoolbar'           content='no'>
        <meta http-equiv='x-dns-prefetch-control' content='off'>
        <meta name='target'                       content='all'>
        <meta name='HandheldFriendly'             content='True'>
        <meta name='MobileOptimized'              content='320'>
        <meta name='rating'                       content='General'>
        <meta name='date'                         content='February 20, 2024'>
		<title>Portal | GlitchWizard Digital Solutions | <?php echo "$pageName";?></title>
		<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bad+Script&display=swap" rel="stylesheet">
     <!--limit load of ticket.css-->
        <?php if ($pageName =='communication.php' || $pageName =='review-responses.php'|| $pageName =='view.php' || $pageName =='ticket-view.php'): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/ticket.css">
        <?php endif; ?>
        
	    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Fira+Sans:300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i"><!-- Google fonts -->
	    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i"><!-- Google fonts -->
	    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" Content-Type="font/woff2">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/reset.css?v=<?php echo $asset_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/nav.css?v=<?php echo $asset_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/directory.css?v=<?php echo $asset_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/login.css?v=<?php echo $asset_version; ?>">
	  	<link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/brand.css?v=<?php echo $asset_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/form.css?v=<?php echo $asset_version; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/newsletter.css">
        <link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/accessible.css?v=<?php echo $asset_version; ?>">
      	<link rel="stylesheet" type="text/css" href="<?php echo(site_menu_base) ?>assets/css/css_handler/responsive.css?v=<?php echo $asset_version; ?>"> 

        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/quill/quill.snow.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/vendor/simple-datatables/style.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/dist/css/navik-all.min.css" rel="stylesheet">
        <link href="<?php echo(site_menu_base) ?>client-dashboard/assets/css/style.css?v=<?php echo $asset_version; ?>" rel='stylesheet'>        <script src="<?php echo(site_menu_base) ?>client-dashboard/assets/js/jquery-3.7.1.min.js"></script>
        <?php $debug=0;?>