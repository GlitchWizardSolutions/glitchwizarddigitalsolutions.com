<?php  
 include process_path  . 'header-process.php';
 include includes_path . 'close-open.php';?>

  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="<?php echo(site_menu_base) ?>client-dashboard/index.php" class="logo d-flex align-items-center">
       <img class="r-logo-public" src="<?php echo(site_menu_base) ?>assets/imgs/purple-on-white-logo.png" alt="Logo" aria-label="logo">
      <span class="ps-3 d-none no-hover d-lg-block r-biz-name">GlitchWizard Solutions</span>
      </a>
      <i class="ps-3 bi bi-list toggle-sidebar-btn" id='toggle-sidebar-btn'></i>
    </div>
    
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
   <?php /*THIS IS CLIENT ONLY VIEW*/ ?>     
   <?php  if ($account['role'] != 'Admin'): ?>
     <?php if (can_view_tickets($account['access_level'])): ?>
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Please respond to these tickets.">
             <span id="boot-icon" class="bi bi-bell-fill" style="font-size: 1rem; color: rgb(120, 13, 227);"></span>
            <?php if($notification_bell == 0) : ?>
            <span class="badge bg-transparent badge-number"><?=$notification_bell ?></span>
           <?php else : ?>

             <span class="badge bg-danger badge-number"><?=$notification_bell ?></span>
            <?php endif; ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Please review ticket responses.
            </li>

        <?php foreach ($actionReq as $ticket): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
               <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
             <div class="row mx-auto">
                  
                     <span style='font-size: .85em'> <?=htmlspecialchars($ticket['title'] ?? '', ENT_QUOTES)?></span> 
                      
                        <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($ticket['id']?? '', ENT_QUOTES)?>">
                </div> 
                 <div class="row mx-auto" style="text-align:center">

			            <button style="width:75%; height:50%" class="btn btn-sm btn-warning mx-auto" type="submit">View Response</button>
                   </div>
                  </form> 
		    </li>
		<?php endforeach; ?>
			
		<?php if (!$actionReq): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>You have no tickets to respond to.</p>
              </div>
        </li>
       <?php endif; ?>
                     <hr class="dropdown-divider">
            </li>
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/review-responses.php">All Tickets</a>
            </li>
           </div>
     </div>  
          </ul><!-- End Notification Dropdown Items -->
        </li><!-- End Notification Nav -->
     <?php else: ?>
        <li class="nav-item">
          <span class="nav-link nav-icon" style="color: #999; cursor: not-allowed;">
             <span id="boot-icon" class="bi bi-bell-slash" style="font-size: 1rem; color: #999;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo get_access_tooltip($account['access_level'], 'tickets'); ?>"></span>
          </span>
        </li>
     <?php endif; ?>
  <?php endif; ?>       
        
   <?php /*THIS IS ADMIN ONLY VIEW*/ ?>         
        
  <?php if ($account['role'] == 'Admin'): ?>
  <!--Client Ticket New Activity Notification Bell-->
  <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Recent Client Ticket Activity.">
         <span id="boot-icon" class="bi bi-person-exclamation" style="font-size: 2rem; color: rgb(120, 13, 227);"></span>
           <br>
            <?php if($admin_notification_bell <= 0) : ?>
            <span class="badge bg-transparent badge-number"><?=$admin_notification_bell?></span>
           <?php else : ?>
             <span class="badge bg-danger badge-number"><?=$admin_notification_bell?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Recent Member Replies.
            </li>
        <?php foreach ($admin_actionReq as $ticket): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
               <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
                <div class="row mx-auto">
                     <span style='font-size:.85em'><?=htmlspecialchars($ticket['title'] ?? '', ENT_QUOTES)?></span> 
                        <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($ticket['id']?? '', ENT_QUOTES)?>">
                </div><br>
                <div class="row mx-auto" style="text-align:center">
			  <button style="width:75%; height:50%" class="btn btn-sm btn-warning mx-auto" type="submit">View Response</button>
                   </div>
                  </form> 
		    </li>
		<?php endforeach; ?>
			
		<?php if (!$admin_actionReq): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>No new client responses.</p>
              </div>
        </li>
       <?php endif; ?>
          <hr class="dropdown-divider">
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/review-responses.php">All Tickets</a>
            </li>
          </ul><!-- End Notification Dropdown Items -->
        </li><!-- End Notification Nav for Client Ticket Responses -->
        
   <!--Recent Clients < 3 days logging in -->
     <li class="nav-item dropdown"> 
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Recent Logins < 3 Days">
         <span id="boot-icon" class="bi bi-alarm" style="font-size: 2rem; color: rgb(120, 13, 227);"></span>
           <br>
           <?php if($active_count <= 0) : ?>
          <span class="badge bg-transparent badge-number"><?=$active_count ?></span>
           <?php else : ?>
            <span class="badge bg-warning badge-number"><?=$active_count ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Recent Member Logins.
            </li>
  
        <?php foreach ($active_recently as $ticket): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
              
                <div class="row mx-auto" style='text-align:center'>
                     <span style='font-size: 1em'><strong><?=htmlspecialchars($ticket['username'] ?? '', ENT_QUOTES)?></strong></span>
                     <span style='font-size: 1em'> <?=htmlspecialchars($ticket['document_path'] ?? '', ENT_QUOTES)?> </span> 
                      <span style='font-size:1em'> [<?=time_elapsed_string($ticket['last_seen']) ?>] </span> 
                </div> 
		    </li>
		<?php endforeach; ?>
		<?php if (!$active_recently): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>No member has logged in recently.</p>
              </div>
        </li>
       <?php endif; ?>
                     <hr class="dropdown-divider">
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>client-documents/system-client-data.php">All Documents</a>
            </li>
          </ul>

        </li><!-- End Notification Dropdown Items for Recent Client Logins -->
        
   <!--BREAK-->
          <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Critical Bugs!">
              <span id="boot-icon" class="bi bi-bug" style="font-size: 2rem; color: rgb(120, 13, 227);"></span>
           <br>
           <?php if($admin_project_critical_count <= 0) : ?>
           <span class="badge bg-transparent badge-number"><?=$admin_project_critical_count ?></span>
           <?php else : ?>
            <span class="badge bg-danger badge-number"><?=$admin_project_critical_count ?></span>
            <?php endif; ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Critical System Bugs.
            </li>
        <?php foreach ($admin_project_critical as $c_ticket): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
               <form action="" id='project-high-id-form' style="width:100%" class='form' method="post"> 
                                <div class="row mx-auto" style="text-align:center">
                     <span style='font-size:.85em'> <?=htmlspecialchars($c_ticket['title'] ?? '', ENT_QUOTES)?></span> 
                     <input name="respond_project_id" type="hidden" id="respond_project_id" value="<?=htmlspecialchars($c_ticket['id']?? '', ENT_QUOTES)?>">
                </div> 
                 <div class="row mx-auto" style="text-align:center">
			            <button style="width:75%; height:50%" class="btn btn-sm btn-danger mx-auto" type="submit">Due <?=date("M d",strtotime($c_ticket['reminder_date'])) ?></button>
                   </div>
                  </form> 
		    </li>
		<?php endforeach; ?>
			
		<?php if (!$admin_project_critical): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>You have no critical bugs.</p>
              </div>
        </li>
       <?php endif; ?>
                     <hr class="dropdown-divider">
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-review-responses.php">All Projects</a>
            </li>
          </ul><!-- End Notification Dropdown Items -->
        </li><!-- End Notification Nav -->
  <!--BREAK-->
           <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Current Projects">
              <span id="boot-icon" class="bi bi-github" style="font-size: 2rem; color: rgb(120, 13, 227);"></span>
           <br>
           <?php if($admin_project_high_count <=5) : ?>
            <span class="badge bg-danger-subtle badge-number"><?=$admin_project_high_count?></span>
          
           <?php else : ?>
            <span class="badge bg-warning badge-number"><?=$admin_project_high_count?></span>
            <?php endif; ?>
           </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Current System Projects 
            </li>
   
        <?php foreach ($admin_project_high as $ticket): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
               <form action="" id='respond_project_med' style="width:100%" class='form' method="post"> 
                <div class="row mx-auto" style="text-align:center">
                  
                     <span style='font-size:.85em'><?=htmlspecialchars($ticket['title'] ?? '', ENT_QUOTES)?></span> 
                      
                        <input name="respond_project_med" type="hidden" id="respond_project_med" value="<?=htmlspecialchars($ticket['id']?? '', ENT_QUOTES)?>">
               </div> 
                <div class="row mx-auto" style="text-align:center">                                                        
                      
			           <button style="width:75%; height:50%" class="btn btn-sm btn-warning mx-auto" type="submit"> Due <?=date("M d",strtotime($ticket['reminder_date'])) ?> </button>
                   </div>
                  
                  </form> 
		    </li>
		<?php endforeach; ?>
			
		<?php if (!$admin_project_high): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>You have no high status projects.</p>
              </div>
        </li>
       <?php endif; ?>
                     <hr class="dropdown-divider">
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-review-responses.php">All Projects</a>
            </li>
          </ul><!-- End Notification Dropdown Items -->

        </li><!-- End Notification Nav -->
               <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Domain Registration Expiring Soon.">
         <span id="boot-icon" class="bi bi-arrow-clockwise" style="font-size: 2rem; color: rgb(120, 13, 227);"></span>
           <br>
            <?php if($domain_due_count < 1) : ?>
            <span class="badge bg-transparent badge-number"><?=$domain_due_count?></span>
           <?php else : ?>
             <span class="badge bg-danger badge-number"><?=$domain_due_count?></span>
            <?php endif; ?>
          </a>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Domain Registrations Expiring Soon
            </li>
        <?php foreach ($domain_registrations_due as $domains_due): ?>
          <li><hr class="dropdown-divider"></li>
            <li class="notification-item">
               <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
                <div class="row mx-auto">
                     <span style='font-size:.85em'><?=date('M Y', strtotime($domains_due['due_date']))?>&nbsp;-&nbsp;<?=htmlspecialchars($domains_due['domain'] ?? '', ENT_QUOTES)?></span> 
                        <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($domains_due['id']?? '', ENT_QUOTES)?>">
                </div>
                  </form> 
		    </li>
		<?php endforeach; ?>
		<?php if (!$domain_registrations_due): ?>
		  <li><hr class="dropdown-divider"></li>
		<li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <p>No Domain Registrations Due.</p>
              </div>
        </li>
       <?php endif; ?>
         <hr class="dropdown-divider">
            <li class="dropdown-footer">
              <a href="<?php echo(site_menu_base) ?>admin/resource_system/domains.php">All Domains</a>
            </li>
          </ul><!-- End Notification Dropdown Items -->
        </li><!-- End Notification Nav -->
   <?php endif; ?>

        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
 
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $account['full_name'];?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $account['full_name'];?></h6>
              <span><?php echo $account['role'];?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="<?php echo(site_menu_base) ?>client-dashboard/users-profile-edit.php">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <?php if ($account['role'] == 'Admin'): ?>
             <li>
              <a class="dropdown-item d-flex align-items-center" href="<?php echo(site_menu_base) ?>admin/">
                <i class="bi bi-person"></i>
                <span>Administration</span>
              </a>
            </li>
            <?php endif; ?>
     <!--       <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="pages-faq.php">
                <i class="bi bi-question-circle"></i>
                <span>Need Help?</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>-->

            <li>
              <a class="dropdown-item d-flex align-items-center" href="<?php echo(site_menu_base) ?>logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->
  <?php include includes_path . 'navigation.php';?>