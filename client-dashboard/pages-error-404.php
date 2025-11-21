<?php 
/*
2025-06-21 Bug Fix Removed call to users-profile-process.
*/
include 'assets/includes/user-config.php';
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">&nbsp;Home</a></li>
         <li class="breadcrumb-item">Ideas</li>
         <li class="breadcrumb-item active">Pages</li>
         <li class="breadcrumb-item active">Error Page</li>
        </ol>
      </nav>
    </div> 
  <main>
    <div class="container">

      <section class="section error-404 min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <h1>404</h1>
        <h2>The page you are looking for doesn't exist.</h2>
        <a class="btn" href="#">Back to home</a>
        <img src="assets/img/not-found.svg" class="img-fluid py-5" alt="Page Not Found">

      </section>

    </div>
   
  </main><!-- End #main -->
  <?php include includes_path . 'footer-close.php'; ?>