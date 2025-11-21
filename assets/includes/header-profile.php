<?php $debug=0;?>
<div class="navbar file-manager-header">
    <nav class="navbar-nav navbar-expand-sm charcoal-bg">
    <div class="container-fluid">
      
    <button id="navbar-toggle-bottom" alt="" name="navbar-toggle-bottom" title="navbar-toggle" class="navbar-toggler brand-text" type="button" data-bs-toggle="collapse" data-bs-target="#profile_nav">
     <span class="navbar-toggler-icon brand-text"></span><span class="brand-text r-size"> &nbsp; Account Management Menu</span>
   </button>
   
       <div class="collapse navbar-collapse charcoal-bg" id="profile_nav">
        <ul class="navbar-nav">
            <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">&nbsp;&nbsp;Profile</a>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                    <li><a class="dropdown-item" aria-current="page" href="profile.php">&nbsp;View</a></li>
		            <li><a class="dropdown-item" href="profile.php?action=edit">&nbsp;Add/Update</a></li>
	            </ul>
            </li>
            <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">&nbsp;&nbsp;Add-Ons</a>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                    <li><a class="dropdown-item" href="profile.php?action=orders">Custom Web Apps</a></li>
                    <li><a class="dropdown-item" href="profile.php?action=downloads">Digital Downloads</a></li>
                </ul>
            </li>
            <li class="nav-item dropdown"><a class="nav-link  dropdown-toggle" href="#" data-bs-toggle="dropdown">&nbsp;&nbsp;Projects</a>
		        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                    <li><a class="dropdown-item" href="profile.php?action=status">Current Project Status</a></li>
                    <li><a class="dropdown-item" href="profile.php?action=projects">Maintainance Schedule</a></li>
                    <li><hr class="dropdown-divider"></li>
		            <li><a class="dropdown-item" href="profile.php?action=faqs">Project Options</a></li>
	  </ul>
     </li>
    </ul>
   </div> <!-- navbar-collapse.// -->
  </div> <!-- container-fluid.// -->
 </nav>
</div>