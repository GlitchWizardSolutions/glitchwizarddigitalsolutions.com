<?php
echo '<!--
 5. GLOBAL PUBLIC MAIN:assets/includes/main.php-->';
/*
This menu is only displayed when a user is not logged in yet.  It is exclusively for public pages.
11/23/2024 //NOT USED FOR LOGIN PAGE.
*/
?>
<body class="template-dark z_index_highest" >
	<!-- Header -->
	<div class="navik-header header-shadow header-dark z_index_highest">
		<div class="container z_index_highest">

			<!-- Navik header -->
			<div class="navik-header-container z_index_highest"> 
			
				<!--Logo-->
                <div class="logo" data-mobile-logo="https://glitchwizarddigitalsolutions.com/assets/imgs/purple-logo-sm.png" data-sticky-logo="https://glitchwizarddigitalsolutions.com/assets/imgs/purple-logo-sm.png">
                	<a href="https://glitchwizarddigitalsolutions.com"><img src="https://glitchwizarddigitalsolutions.com/assets/imgs/purple-logo-sm.png" width="35px" height="35px" class="d-inline-block align-text-top" alt="logo"/></a>
				</div>
				
				<!-- Burger menu -->
				<div class="burger-menu">
					<div class="line-menu line-half first-line"></div>
					<div class="line-menu"></div>
					<div class="line-menu line-half last-line"></div>
				</div>
			</div>
		</div>
	</div>
<style>
    .z_index_highest{
    z-index: 2147483647;
}
</style>