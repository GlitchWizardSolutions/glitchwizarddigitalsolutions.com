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
         <li class="breadcrumb-item active">Blank Page</li>
        </ol>
      </nav>
    </div> 

    <section class="section">
      <div class="row">
        <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Example Card</h5>
              <p>This is an examle page with no content. You can use it as a starter for your custom pages.</p>
            </div>
          </div>

        </div>

        <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Example Card</h5>
              <p>This is an examle page with no contrnt. You can use it as a starter for your custom pages.</p>
            </div>
          </div>

        </div>
      </div>
    </section>
  </main><!-- End #main -->
  <?php include includes_path . 'footer-close.php'; ?>