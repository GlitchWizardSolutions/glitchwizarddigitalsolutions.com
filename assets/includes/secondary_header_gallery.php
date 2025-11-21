<?php
//This loads the toggle menu to the right of the upload center for client image files other content
?>
<!--loaded secure header gallery menu-->
<div class="navbar file-manager-header">
    <nav class="navbar-nav navbar-expand navbar-dark">
    <div class="container-fluid">
     
    <button id="navbar-toggle-bottom" alt="" name="navbar-toggle-bottom" title="navbar-toggle" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#gallery_nav">
     <span class="navbar-toggler-icon no-hover"></span><p class="no-hover">Gallery Menu</p>
   </button>
   
       <div class="collapse navbar-collapse" id="gallery_nav">
           
        <ul class="navbar-nav" style="margin:auto;">
            
            <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">&nbsp;&nbsp;Manage Content</a>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                    <li><a class="dropdown-item" href="collections.php" id="collections" class="refresh other"><i class="fa-regular fa-eye"></i>&nbsp;View Collections</a></li>
                    <li><a class="dropdown-item" href="manage-collection.php" class="other file"><i alt="" class="fa-solid fa-folder"></i>&nbsp;Create a New Collection</a></li>
                    <li><a class="dropdown-item" href="upload.php" class="upload other"> <i alt="" class="fa-solid fa-image"></i>&nbsp;Upload Images & Media</a></li>
                    <li><a class="dropdown-item" href="#" class="upload last other"> <i alt="" class="fa-regular fa-copyright"></i>&nbsp;Fill Copyright Forms</a></li>
                </ul>
            </li>
    </ul> <!-- navbar-nav-->
   </div> <!-- navbar-collapse.// -->
  </div> <!-- container-fluid.// -->
 </nav>
</div>