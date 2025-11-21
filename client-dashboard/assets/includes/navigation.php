
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item" id='dashboard'>
        <a class="nav-link" href="<?php echo(site_menu_base) ?>client-dashboard/index.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>
        <?php if ($account['role']=='Admin') : ?>
          <li class="nav-item" id='admin'><?php /*admin todo is the number of open client tickets  */ ?>
        <a class="nav-link collapsed" data-bs-target="#admin-nav" data-bs-toggle="collapse" href="#">
                <?php if ($admin_todo_total == 0) : ?> <i class="fa-solid fa-battery-full"></i>
                <?php elseif(in_array($admin_todo_total, range(1,3))) : ?><i class="fa-solid fa-battery-three-quarters"></i>
                <?php elseif(in_array($admin_todo_total, range(4,6))) : ?><i class="fa-solid fa-battery-half"></i>
                <?php elseif(in_array($admin_todo_total, range(7,9))) : ?> <i class="fa-solid fa-battery-quarter"></i>
                <?php else : ?><i class="fa-solid fa-battery-empty"></i>
                <?php endif; ?>
          <span>&nbsp;Administration</span>&nbsp; &nbsp;[<?=$admin_todo_total?>]</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="admin-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
             <li id='admin'>
            <a href="<?php echo(site_menu_base) ?>admin/" style='color:green'>
              <strong><i class="bi bi-circle"></i></strong><span>Admin Area</span>
            </a>
          </li> 
          <li id='submit-project'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-submit-ticket.php" style='color:green'>
              <i class="bi bi-circle"></i><span>Submit Project</span>
            </a>
          </li> 
          
          <li id='admin-projects'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-review-responses.php" style='color:green'>
              <i class="bi bi-circle"></i><span>View Projects</span>
            </a>
          </li> 
          <li id='client-tickets'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/all-client-review.php" style='color:green'>
              <i class="bi bi-circle"></i><span>View Client Tickets</span>
            </a>
          </li> 
           <li id='submit-project'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/barb-resources/warranty-submit-ticket.php" style='color:green'>
              <i class="bi bi-circle"></i><span>Submit Warranty</span>
            </a>
          </li> 
           <li id='gws-legal'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/gws-legal/gws-legal-review-responses.php" style='color:green'>
              <i class="bi bi-circle"></i><span>GSW Legal</span>
            </a>
          </li> 
        </ul>
      </li>
         
        <li class="nav-item" id='blog'>
        <a class="nav-link collapsed" data-bs-target="#blog-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-book"></i><span>Knowledge Base</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="blog-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li id='blog-home'>
            <a href="/public_html/client-dashboard/blog/index.php">
              <i class="bi bi-circle"></i><span>All Articles</span>
            </a>
          </li>
          <li id='blog-search'>
            <a href="/public_html/client-dashboard/blog/search.php">
              <i class="bi bi-circle"></i><span>Search</span>
            </a>
          </li>
        </ul>
      </li><!--/blog-->         
             
      
      
      <!-- /admin-->  
         <?php endif; ?>  

        
     <li class="nav-item" id='communication'>
        <a class="nav-link collapsed" data-bs-target="#communication-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person"></i><span>Communication</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="communication-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
            
          <li id='ticket'>
            <?php if (can_access_communication($account['access_level'])): ?>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/submit-ticket.php">
              <i class="bi bi-circle"></i><span>Submit New Ticket</span>
            </a>
            <?php else: ?>
            <a href="#" class="disabled-link" style="color: #999; cursor: not-allowed;" title="<?php echo get_access_tooltip($account['access_level'], 'communication'); ?>">
              <i class="bi bi-circle"></i><span>Submit New Ticket</span>
            </a>
            <?php endif; ?>
          </li> 
          <li id='mytickets'>
            <?php if (can_access_communication($account['access_level'])): ?>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/communication/review-responses.php">
              <i class="bi bi-circle"></i><span>My Tickets</span>
            </a>
            <?php else: ?>
            <a href="#" class="disabled-link" style="color: #999; cursor: not-allowed;" title="<?php echo get_access_tooltip($account['access_level'], 'communication'); ?>">
              <i class="bi bi-circle"></i><span>My Tickets</span>
            </a>
            <?php endif; ?>
          </li> 
      


        </ul>
      </li><!--/communication-->
      
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#account-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person"></i><span>Documents</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
      <ul id="account-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li id='myfiles'>
            <?php if (can_access_documents($account['access_level'])): ?>
            <a href="<?php echo(site_menu_base) ?>client-documents/system-client-data.php">
              <i class="bi bi-circle"></i><span>My Files</span>
            </a>
            <?php else: ?>
            <a href="#" class="disabled-link" style="color: #999; cursor: not-allowed;" title="<?php echo get_access_tooltip($account['access_level'], 'documents'); ?>">
              <i class="bi bi-circle"></i><span>My Files</span>
            </a>
            <?php endif; ?>
          </li>
      </ul>
     </li><!--/myaccount-->
     
<!--Invoices-->
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#invoices-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-receipt"></i><span>Invoices</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="invoices-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li id='myinvoices'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/client-invoices.php">
              <i class="bi bi-circle"></i><span>My Invoices</span>
            </a>
          </li>
        </ul>
      </li><!--/invoices-->
      
<!--Settings-->     
       <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gear"></i><span>Account & Settings</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
      <ul id="settings-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/users-profile-edit.php">
              <i class="bi bi-circle"></i><span>Member Profile</span>
            </a>
          </li>
          <li id='mybusiness'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/client-businesses.php">
              <i class="bi bi-circle"></i><span>Business Profiles</span>
            </a>
          </li> 
         <li id='acct'>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/users-account-edit.php">
              <i class="bi bi-circle"></i><span>Settings</span>
            </a>
          </li><!--/acct-->
      </ul>
     </li>  
         <li class="nav-item" id='logoff'>
        <a class="nav-link collapsed" href="<?php echo(site_menu_base) ?>logout.php">
           <i class="bi bi-box-arrow-right"></i><span>Sign Out</span> 
        </a><!--/logoff-->
        </li>
           <li class="nav-item" id='scroll'>
        <a class="nav-link" href="#scroll">
            
          <span>Design Ideas</span>
        </a>
      </li>

       <li class="nav-heading">Ideas</li>
      <li class="nav-item" id='components'>
        <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-menu-button-wide"></i><span>Components</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="components-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-alerts.php">
              <i class="bi bi-circle"></i><span>Alerts</span>
            </a>
          </li><!--/alerts-->
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-accordion.php">
              <i class="bi bi-circle"></i><span>Accordion</span>
            </a>
          </li>
          <li>
            <a href="components-badges.php">
              <i class="bi bi-circle"></i><span>Badges</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-breadcrumbs.php">
              <i class="bi bi-circle"></i><span>Breadcrumbs</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-buttons.php">
              <i class="bi bi-circle"></i><span>Buttons</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-cards.php">
              <i class="bi bi-circle"></i><span>Cards</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-carousel.php">
              <i class="bi bi-circle"></i><span>Carousel</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-list-group.php">
              <i class="bi bi-circle"></i><span>List group</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-modal.php">
              <i class="bi bi-circle"></i><span>Modal</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-tabs.php">
              <i class="bi bi-circle"></i><span>Tabs</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-pagination.php">
              <i class="bi bi-circle"></i><span>Pagination</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-progress.php">
              <i class="bi bi-circle"></i><span>Progress</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-spinners.php">
              <i class="bi bi-circle"></i><span>Spinners</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/components-tooltips.php">
              <i class="bi bi-circle"></i><span>Tooltips</span>
            </a>
          </li>
        </ul>
      </li><!-- End Components Nav -->

      <li class="nav-item" id-'forms'>
        <a class="nav-link collapsed" data-bs-target="#forms-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-journal-text"></i><span>Forms</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="forms-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/forms-elements.php">
              <i class="bi bi-circle"></i><span>Form Elements</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/forms-layouts.php">
              <i class="bi bi-circle"></i><span>Form Layouts</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/forms-editors.php">
              <i class="bi bi-circle"></i><span>Form Editors</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/forms-validation.php">
              <i class="bi bi-circle"></i><span>Form Validation</span>
            </a>
          </li>
        </ul>
      </li><!-- End Forms Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#tables-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-layout-text-window-reverse"></i><span>Tables</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="tables-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/tables-general.php">
              <i class="bi bi-circle"></i><span>General Tables</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/tables-data.php">
              <i class="bi bi-circle"></i><span>Data Tables</span>
            </a>
          </li>
        </ul>
      </li><!-- End Tables Nav -->

      <li class="nav-item" id='charts'>
        <a class="nav-link collapsed" data-bs-target="#charts-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Charts</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="charts-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/charts-chartjs.php">
              <i class="bi bi-circle"></i><span>Chart.js</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/charts-apexcharts.php">
              <i class="bi bi-circle"></i><span>ApexCharts</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/charts-echarts.php">
              <i class="bi bi-circle"></i><span>ECharts</span>
            </a>
          </li>
        </ul>
      </li><!-- End Charts Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-gem"></i><span>Icons</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="icons-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/icons-bootstrap.php">
              <i class="bi bi-circle"></i><span>Bootstrap Icons</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/icons-remix.php">
              <i class="bi bi-circle"></i><span>Remix Icons</span>
            </a>
          </li>
          <li>
            <a href="<?php echo(site_menu_base) ?>client-dashboard/icons-boxicons.php">
              <i class="bi bi-circle"></i><span>Boxicons</span>
            </a>
          </li>
        </ul>
      </li><!-- End Icons Nav -->

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="<?php echo(site_menu_base) ?>client-dashboard/pages-faq.php">
          <i class="bi bi-question-circle"></i>
          <span>F.A.Q</span>
        </a>
      </li><!-- End F.A.Q Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="<?php echo(site_menu_base) ?>client-dashboard/pages-contact.php">
          <i class="bi bi-envelope"></i>
          <span>Contact</span>
        </a>
      </li><!-- End Contact Page Nav -->

   

      <li class="nav-item">
        <a class="nav-link collapsed" href="<?php echo(site_menu_base) ?>client-dashboard/pages-error-404.php">
          <i class="bi bi-dash-circle"></i>
          <span>Error 404</span>
        </a>
      </li><!-- End Error 404 Page Nav -->


    </ul>

  </aside>