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
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">&nbsp;Home</a></li>
         <li class="breadcrumb-item">Ideas</li>
         <li class="breadcrumb-item">Pages</li>
         <li class="breadcrumb-item active">Template</li>
        </ol>
      </nav>
    </div> 
    
    <section class="section">
      <div class="row">
        <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Example Card</h5>
              <p>This is an examle page with no content.</p>
            </div>
          </div>

        </div>

        <div class="col-lg-6">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Example Card</h5>
              <p>This is an examle page with no content.</p>
            </div>
          </div>

        </div>
      </div>
    </section>
  </main><!-- End #main -->
  <?php include includes_path . 'footer-close.php'; ?>