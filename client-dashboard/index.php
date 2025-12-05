<?php 
//PRODUCTION READY 10/9/2024
//Updated 5/24/25 Cleaned up code.
include 'assets/includes/user-config.php';

// Get account info for password_changed flag
$stmt = $pdo->prepare('SELECT password_changed FROM accounts WHERE id = ?');
$stmt->execute([$_SESSION['id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pipeline status for this user
$stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
$stmt->execute([$_SESSION['id']]);
$pipeline = $stmt->fetch(PDO::FETCH_ASSOC);

// If no pipeline record exists, create one
if (!$pipeline) {
    $stmt = $pdo->prepare('INSERT INTO client_pipeline_status (acc_id, access_level, created_at) VALUES (?, ?, NOW())');
    $stmt->execute([$_SESSION['id'], $_SESSION['access_level']]);
    $stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
    $stmt->execute([$_SESSION['id']]);
    $pipeline = $stmt->fetch(PDO::FETCH_ASSOC);
}

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
            
            <!-- Client Journey Cards -->
            <div class="col-12">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="card info-card">
                    <div class="card-body">
                      <?php if ($_SESSION['access_level'] == 'Guest'): ?>
                      <h5 class="card-title">Getting Started <span>| Welcome!</span></h5>
                      <div class="ps-3">
                        <h6>Welcome to GlitchWizard Digital Solutions</h6>
                        <p>We're excited to help bring your digital vision to life! Here's what you need to know:</p>
                        <ul>
                          <li><strong>Our Process:</strong> We guide you through discovery, design, development, and launch</li>
                          <li><strong>Timeline:</strong> Most projects take 4-12 weeks depending on complexity</li>
                          <li><strong>Investment:</strong> Custom quotes based on your needs (starting at $1,500)</li>
                          <li><strong>Next Steps:</strong> Complete the forms in the "Next Steps" section →</li>
                        </ul>
                        <p class="text-muted"><em>No payment required to explore your options!</em></p>
                      </div>
                      <?php else: ?>
                      <h5 class="card-title">Getting Started <span>| Step 1</span></h5>
                      <div class="d-flex align-items-center">
                        <div class="ps-3">
                          <p>Coming Soon - <?=htmlspecialchars($_SESSION['access_level'], ENT_QUOTES)?></p>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="card info-card">
                    <div class="card-body">
                      <?php if ($_SESSION['access_level'] == 'Guest'): ?>
                      <h5 class="card-title">Next Steps <span>| Required Actions</span></h5>
                      <div class="ps-3">
                        <p><strong>Complete these steps to get started:</strong></p>
                        
                        <style>
                        .pipeline-checklist {
                          margin-top: 15px;
                        }
                        .checklist-item {
                          display: flex;
                          align-items: center;
                          gap: 10px;
                          padding: 10px 0;
                          border-bottom: 1px solid #eee;
                        }
                        .checklist-item:last-child {
                          border-bottom: none;
                        }
                        .checklist-item.completed a {
                          text-decoration: line-through;
                          color: #6c757d;
                        }
                        .checklist-item i {
                          font-size: 20px;
                          min-width: 24px;
                        }
                        .checklist-item a {
                          flex: 1;
                          font-weight: 500;
                        }
                        .checklist-item .badge {
                          font-size: 0.7rem;
                        }
                        </style>
                        
                        <div class="pipeline-checklist">
                          <div class="checklist-item <?=$account['password_changed'] ? 'completed' : ''?>">
                            <i class="bi bi-<?=$account['password_changed'] ? 'check-circle-fill text-success' : 'circle'?>"></i>
                            <a href="users-account-edit.php">Change Your Password</a>
                            <span class="badge bg-<?=$account['password_changed'] ? 'success' : 'danger'?>">
                              <?=$account['password_changed'] ? 'Complete' : 'Required'?>
                            </span>
                          </div>
                          
                          <div class="checklist-item <?=$pipeline['service_selection_date'] ? 'completed' : ''?>">
                            <i class="bi bi-<?=$pipeline['service_selection_date'] ? 'check-circle-fill text-success' : 'circle'?>"></i>
                            <a href="pipeline/guest-service-selection.php">Choose Your Service Package</a>
                            <span class="badge bg-secondary">5 min</span>
                          </div>
                          
                          <div class="checklist-item <?=$pipeline['project_details_date'] ? 'completed' : ''?>">
                            <i class="bi bi-<?=$pipeline['project_details_date'] ? 'check-circle-fill text-success' : 'circle'?>"></i>
                            <a href="pipeline/guest-project-details.php">Project Details & Customization</a>
                            <span class="badge bg-secondary">10 min</span>
                          </div>
                          
                          <div class="checklist-item <?=($pipeline['guest_deposit_status'] == 'paid') ? 'completed' : ''?>">
                            <i class="bi bi-<?=($pipeline['guest_deposit_status'] == 'paid') ? 'check-circle-fill text-success' : 'circle'?>"></i>
                            <?php if ($pipeline['deposit_invoice_number']): ?>
                            <a href="../client-invoices/invoice-view.php?invoice_number=<?=htmlspecialchars($pipeline['deposit_invoice_number'], ENT_QUOTES)?>">View & Pay Your Invoice</a>
                            <?php else: ?>
                            <span style="color: #999;">Invoice (pending)</span>
                            <?php endif; ?>
                            <span class="badge bg-success">Final Step</span>
                          </div>
                        </div>
                        
                        <?php if ($pipeline['service_selection_date'] && $pipeline['project_details_date'] && $pipeline['guest_deposit_status'] != 'paid'): ?>
                        <div class="alert alert-info mt-3">
                          <i class="bi bi-info-circle"></i> <strong>Almost There!</strong> 
                          Please submit your deposit to move forward with your project.
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($pipeline['guest_deposit_status'] == 'paid'): ?>
                        <div class="alert alert-success mt-3">
                          <i class="bi bi-check-circle"></i> <strong>Deposit Received!</strong> 
                          We're reviewing your project and will advance you to Onboarding within 1 business day.
                        </div>
                        <?php endif; ?>
                      </div>
                      <?php else: ?>
                      <h5 class="card-title">Next Steps <span>| Step 2</span></h5>
                      <div class="d-flex align-items-center">
                        <div class="ps-3">
                          <p>Coming Soon - <?=htmlspecialchars($_SESSION['access_level'], ENT_QUOTES)?></p>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Client Journey Cards -->
            
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