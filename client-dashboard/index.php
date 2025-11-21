<?php 
//PRODUCTION READY 10/9/2024
//Updated 5/24/25 Cleaned up code.
include 'assets/includes/user-config.php';
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
     <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item active">&nbsp;Location: Homepage</li>
        </ol>
      </nav>
    </div>
   <section class="section dashboard">
      <div class="row">
        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">
            <!-- Customers Card -->
            <div>
              <div class="card info-card customers-card">
                <div class="card-body">
                  <h5 class="card-title">Welcome! <span>| Member Portal</span></h5>
                  <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center justify-content-center"></div>
                     <div class="responsive-hidden ps-3">
                      <h6>1</h6><p>New Application!</p>
                      <span class="text-danger small pt-1 fw-bold">10%</span> <span class="text-muted small pt-2 ps-1">increase</span>
                    </div>
        <div class="d-flex align-items-center ps-3"> We have been very productive this quarter!  
       <br>This client portal is being released as a work in progress, so you will definitely see some improvements along the way!  
       <br><br>Please keep your contact information up to date, and thank you for coming along for the journey!</div>             
                  </div>
                </div>
              </div>
            </div><!-- End Customers Card -->
          </div>
        </div><!-- End Left side columns -->

        <!-- Right side columns -->
        <div class="col-lg-4 ms-auto">

          <!-- News & Updates Traffic -->
          <div class="card">

            <div class="card-body pb-0">
              <h5 class="card-title">News &amp; Updates <span></span></h5>

              <div class="news">
                  
                   <div class="post-item clearfix">
                  
                <h4>Partnership</h4>
                   <img src="assets/img/digitalbackups-logo.png"  alt="digitalbackups logo">
                   <p>Since 2020, I've partnered with Digital Backups, which provides us with domain name registrations, ssl, secure server space, and allows us to host your website with 24/7 security, and the highest level of uptime available in the industry.
                </p>
              
                </div>
                
                <div class="post-item clearfix">
                 <a href='<?php echo $base_url; ?>/communication/submit-ticket.php'> <img src="assets/img/ticket.png" alt="ticket page"></a>
                  <h4>Ticketing System</h4>
                  <p>We have a ticketing system in place for all communications regarding your website.  It lets you alert me to specific tasks you need, and allows us to communicate in one spot - to make sure we both know exactly what is necessary.  For more information, see your "Communication" section in your main menu.</p>
                </div>
               <div class="post-item clearfix">
                 <a href='<?php echo $outside_url; ?>client-documents/system-client-data.php'> <img src="assets/img/documents.png" alt="ticket page"></a>
                  <h4>Documents System</h4>
                  <p>We also now have a document system in place for you to keep items related to your website.  
                   This is where you will find your complete website backup zip file, and where you can upload all of the content, images, files, etc. that you'd like
                   to provide for the development of your website. For more information, see the "Documents" section in your main menu.</p>
                </div>
               
              </div><!-- End sidebar recent posts-->

            </div>
          </div><!-- End News & Updates -->

        </div><!-- End Right side columns -->

      </div>
    </section>
  </main><!-- End #main -->
  <?php include includes_path . 'footer-close.php'; ?>