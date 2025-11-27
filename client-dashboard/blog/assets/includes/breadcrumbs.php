<?php 
$pageName = basename($_SERVER['PHP_SELF']);
if ($pageName=='template.php'){
    $page='<li class="breadcrumb-item active">Template</li>';
}elseif ($pageName=='index.php'){
    
}
?>

  <main id="main" class="main">

    <div class="pagetitle">

      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo site_menu_base ?>client-dashboard/index.php">Home</a></li>
         <?php if ($pageName=='template.php'): ?>
         <li class="breadcrumb-item active">Template</li>

         <?php elseif ($pageName=='users-profile.php'): ?>
         <li class="breadcrumb-item active">Account Overview</li>
         
          <?php endif; ?>
          
        </ol>
      </nav>
    </div><!-- End Page Title -->
