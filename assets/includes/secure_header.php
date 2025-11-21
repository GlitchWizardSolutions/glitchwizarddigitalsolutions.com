<!-- Header -->
<div class="navik-header">
  <div class="container">

    <!-- Navik header -->
    <div class="navik-header-container">
					
      <!-- Logo -->
     
      
      
      
      <div class="logo" data-mobile-logo="<?php echo(site_menu_base) ?>assets/imgs/black_logo.png" data-sticky-logo="<?php echo(site_menu_base) ?>assets/imgs/black_logo.png">
        <a href="#"><img src="<?php echo(site_menu_base) ?>assets/imgs/black_logo.png" alt="logo"/></a>
        <a class="navbar-brand r-biz-name" href="<?php echo(site_menu_base) ?>home.php">GlitchWizard Solutions</a>
      </div>
						
      <!-- Burger menu -->
      <div class="burger-menu">
        <div class="line-menu line-half first-line"></div>
        <div class="line-menu"></div>
        <div class="line-menu line-half last-line"></div>
      </div>

      <!-- Navigation menu -->
      <nav class="navik-menu brand-bg">
        <ul>
          <li><a href="#">Menu</a>
            <ul>
              <li><a href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-location-dot"></i> Orientation</a></li>">
              <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>start-here/communication.php"><i class="fa-solid fa-ticket"></i> Communication</a></li>
              <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>start-here/communication.php"><i class="fa-solid fa-ticket"></i> Communication</a></li>
            </ul>
          </li>
          <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>start-here/communication.php"><i class="fa-solid fa-ticket"></i> Communication</a></li>
          <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>start-here/communication.php"><i class="fa-solid fa-ticket"></i> Communication</a></li>
        </ul>
        <ul>
<?php /* ACCESS LEVEL: Guest */ ?>
<?php if (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Guest')):  ?>
    <li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Start Here!</a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-location-dot"></i> Orientation</a></li>
		    <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>start-here/communication.php"><i class="fa-solid fa-ticket"></i> Communication</a></li>
		    <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-asterisk"></i> Terms & Conditions</a></li>
	    </ul>
   </li>
   <li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Steps to Complete</a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-video"></i> Overview of Stage 1</a></li>
		    <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>profile.php?action=edit"><i class="fa-solid fa-1"></i> Contact Information</a></li>
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-2"></i> Statement of Work</a></li>
	        <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>#"><i class="fa-brands fa-paypal"></i> Submit Payment</a></li>
        </ul>
    </li>
<?php /*Onboarding */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Onboarding')):  ?>
   <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Steps to Complete</a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
           <li><a class="dropdown-item "  href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-video"></i> Overview of Onboarding Stage</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-1"></i> Copyright Options</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-2"></i> Image Options</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-3"></i> Submit Website Content</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-4"></i> Statement of Work</a></li>
	        <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-brands fa-paypal"></i> Submit Payment</a></li>
        </ul>
    </li>
<?php /*Branding */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Branding')):  ?>
    <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Steps to Complete</a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
            <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-video"></i> Overview of Branding Stage</a></li>
            <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-1"></i> Branding Options</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-2"></i> Domain Name Options</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-3"></i> Custom Email Options</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-4"></i> Submit Branding Files</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-5"></i> Statement of Work</a></li>
	        <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-brands fa-paypal"></i> Submit Payment</a></li>
        </ul>
	</li> 
<?php /* Legal */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Legal')):  ?>
    <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Steps to Complete</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
		    <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-video"></i> Overview of Legal Stage</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-circle-info"></i> Legal Compliance Info</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-1"></i> Accessibility Policy</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-2"></i> Privacy Policy</a></li>
            <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-3"></i> Terms of Service</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-4"></i> Statement of Work</a></li>
	        <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-brands fa-paypal"></i> Submit Payment</a></li>
        </ul>
	</li>
<?php /* Development */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Development')):  ?>
    <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Steps to Complete</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
		    <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-video"></i> Overview of Development Stage</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-code"></i> Development in Progress</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-1"></i> Proof-Read Copy</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-2"></i> Proof-View Imgs</a></li>
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-solid fa-3"></i> Statement of Work</a></li>
	        <li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>#"><i class="fa-brands fa-paypal"></i> Submit Payment</a></li>
        </ul>
	</li>
<?php /* Production */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Production')):  ?> 
	<li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Assistance</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			<li><a class="dropdown-item " href="<?php echo(site_menu_base) ?>ticketing-system.php"><i class="fas fa-ticket"></i> Tickets</a></li>
		</ul>
	</li> 
<?php /* Hosting */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Hosting')):  ?>
	<li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Assistance</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>ticketing-system.php"><i class="fas fa-ticket"></i> Tickets</a></li>
		</ul>
	</li> 
<?php /* Services */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Services')):  ?>
	<li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Assistance</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>ticketing-system.php"><i class="fas fa-ticket"></i> Tickets</a></li>
		</ul>
	</li> 
<?php /* Master */ ?>
<?php elseif (isset($_SESSION['access_level']) and ($_SESSION['access_level'] == 'Master')):  ?>
	<li class="nav-item dropdown "><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Assistance</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>ticketing-system.php"><i class="fas fa-ticket"></i> Tickets</a></li>
		</ul>
	</li> 
<?php endif; ?>
<?php /* ADMINISTRATION */ ?>
    <?php if ($_SESSION['role'] == 'Admin'):  ?>
	    <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Admin</a>
		    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			  <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>system-client-data.php"><i class="fas fa-paperclip"></i> Client Files</a></li>
              <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>terms-agreement-form.php"><i class="fas fa-home"></i> Form</a></li>
			  <li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>admin/index.php" target="_blank"><i class="fas fa-user-cog"></i> Admin</a></li>
		    </ul>
	    </li>
    <?php  endif; ?>
          <li class="nav-item dropdown "><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">Your Data</a>
		<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>profile.php"><i class="fas fa-user-circle"></i>  Account Profile</a></li>
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>system-client-data.php"><i class="fas fa-paperclip"></i> My Documents</a></li>
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>p-content/collections.php"><i class="fas fa-images"></i> Upload Content</a></li>
			<li><a class="dropdown-item" href="<?php echo(site_menu_base) ?>my-tickets.php"><i class="fas fa-ticket"></i> My Tickets</a></li>
		</ul>
		  <li><a class="nav-link right" href="<?php echo(site_menu_base) ?>logout.php">Log Out <i class="fas fa-right-to-bracket"></i></a></li>
	</li>

   </ul>
        
        
      </nav>

    </div>

  </div>
</div>
<script src="dist/js/navik.menu.js"></script>