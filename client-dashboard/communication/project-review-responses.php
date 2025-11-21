<?php
/*
2025-05-24 CORRECTION 
This page displays all the projects in tabs.  The status is as follows:
Review - These are Open & Paused - These are currently being worked.
*/
include 'assets/includes/user-config.php';
include process_path . 'project-review-response-process.php';
// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the project-ticket-view.
				header("Location: {$base_url}/communication/project-ticket-view.php");
				exit;
			}
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-submit-ticket.php">Create New Project</a></li> 
       <li class="breadcrumb-item active">WebDev Projects</li>
     </ol>
   </nav>
  </div>
  
<section class="section profile">
 <div id='section-all' class="col-xl-12 col-lg-12 col-sm-12">
  <div id='card-all' class="card">
   <div id='card-body-all' class="card-body pt-3">
    <ul class="nav nav-tabs nav-tabs-bordered">
     <li class="nav-item">
       <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#review-tickets">Review Required</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#open-tickets">Open Projects</button> 
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#paused-tickets">Paused Projects</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#new-tickets">New Projects</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#closed-tickets">Closed Projects</button>
     </li>
   </ul>
   
<div id='tab-content-all' class="tab-content pt-2">
    
<?php /* ****************     REVIEW NEEDED PROJECTS    **************************************************** */ ?>    
<div class="tab-pane fade show active review-tickets" id="review-tickets">
  <h5 class="card-title">Review Required</h5>
     <p>These projects are <strong>due this month!</strong> Please complete by the deadlines.
     There are more next month.</p>
<?php foreach ($action_required_tickets as $review_project_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($review_project_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-warning" style='width:75px' type="submit">Review</button>
              <h6>Review Due <?=date('M d, Y',strtotime($review_project_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($review_project_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
               <h5><span class='fs-6'>
			  <?php if ($review_project_ticket['ticket_status'] == 'open'): ?><i></i>
			    <i title="Open status" alt="open status" class="bi bi-exclamation-circle text-warning" responsive-hidden></i>&nbsp; 
			  <?php elseif ($review_project_ticket['ticket_status'] == 'paused'): ?>
			      <i title="Paused status" alt="paused status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; 
			  <?php endif; ?>
			  </span>
			 <strong><?=htmlspecialchars($review_project_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 </div><!--class="row ticket col-12"-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($review_project_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		     </div>
		   <div class='col-1'></div>
		</div>
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->
<?php if (!$action_required_tickets): ?>
  <p>You have no projects to respond to.</p>
<?php endif; ?>    
</div><!--end review projects-->

<?php /* ****************     OPEN PROJECTS    **************************************************** */ ?>
<div class="tab-pane fade open-tickets pt-3" id="open-tickets">
  <h5 class="card-title">Open Projects</h5>
<?php foreach ($open_tickets as $open_project_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($open_project_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
               <h6>Review Due <?=date('M d, Y',strtotime($open_project_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($open_project_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
             <h5>
			   <span class='fs-6'> <i title="Open status" alt="open status" class="far fa-clock fa-2x responsive-hidden" style="color:#fad087;"></i>&nbsp; </span>
			   <strong><?=htmlspecialchars($open_project_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		  </div><!--row ticket col-12-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($open_project_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		   </div><!--/col-10-->
		   <div class='col-1'></div>
		 </div><!--/class='row col-12'-->
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->


<?php if (!$open_tickets): ?>
<p>You have no open tickets.</p>
<?php endif; ?>
</div>
<!--close open projects-->

<?php /* ****************     PAUSED PROJECTS    **************************************************** */ ?>
<div class="tab-pane fade paused-tickets pt-3" id="paused-tickets">
   <h5 class="card-title">Paused Projects</h5>
<?php foreach ($paused_tickets as $paused_project_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
            <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($paused_project_ticket['id']?? '', ENT_QUOTES)?>">
            <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
             <h6>Review Due <?=date('M d, Y',strtotime($paused_project_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($paused_project_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
               <h5>
			     <span class='fs-6'> <i title="Paused status" alt="paused status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; </span>
			     <strong><?=htmlspecialchars($paused_project_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($paused_project_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		   </div>
		   <div class='col-1'></div>
		 </div><!--/row col-12-->
        </div><!--end row ticket-->
       </div><!--end ticket list-->
      </div><!--end block tickets-->
     </div><!--end card body-->
    </div><!--end card-->
   </div><!--end column-->
  <div class='col-1'></div> 
  </div><!--end row-->
<?php endforeach; ?><!--end for each-->


		<?php if (!$paused_tickets): ?>
		<p>You have no paused projects.</p>
<?php endif; ?>
</div><!--close paused projects-->
<?php /* ****************     NEW PROJECTS    **************************************************** */ ?>
<div class="tab-pane fade new-tickets pt-3" id="new-tickets">
  <h5 class="card-title">New Projects</h5>
<?php foreach ($new_tickets as $new_project_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($new_project_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
               <h6>Review Due <?=date('M d, Y',strtotime($new_project_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($new_project_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
             <h5>
			   <span class='fs-6'> <i title="New status" alt="new status" class="far fa-clock fa-2x responsive-hidden" style="color:#fad087;"></i>&nbsp; </span>
			   <strong><?=htmlspecialchars($new_project_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		  </div><!--row ticket col-12-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($new_project_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		   </div><!--/col-10-->
		   <div class='col-1'></div>
		 </div><!--/class='row col-12'-->
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->


<?php if (!$new_tickets): ?>
<p>You have no open tickets.</p>
<?php endif; ?>
</div>
<!--close open projects-->
<?php /* ****************     CLOSED PROJECTS    **************************************************** */ ?>
<div class="tab-pane closed-tickets fade pt-3" id="closed-tickets">
 <h5 class="card-title">Closed Projects</h5>
  <p>These projects are no longer being worked. <br>
  
 <?php foreach ($closed_tickets as $closed_project_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
            <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($closed_project_ticket['id']?? '', ENT_QUOTES)?>">
            <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
          </form> 
          <div class="row ticket col-12">
           <h5>
             <span class='fs-6'><i title="Closed status" alt="closed status" class="fa-solid fa-x" style="color:#adbce6;"></i>&nbsp;</span>
			 <strong><?=htmlspecialchars($closed_project_ticket['title'] ?? '', ENT_QUOTES)?></strong>
		  </h5>
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($closed_project_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		     </div>
		   <div class='col-1'></div>
		  </div>
        </div><!--end row ticket-->
       </div><!--end ticket list-->
      </div><!--end block tickets-->
     </div><!--end card body-->
    </div><!--end card-->
   </div><!--end column-->
  <div class='col-1'></div> 
  </div><!--end row-->
<?php endforeach; ?><!--end for each-->

<?php if (!$closed_tickets): ?>
<p>You have no closed projects.</p>
<?php endif; ?>
</div><!--close closed tickets-->
</div><!--/tab-content-all-->
 </div><!--/card-body-all-->
   </div><!--/card-all-->
  </div><!--section-all-->
 </section><!-- /section -->
</main><!-- /main -->
<script>
function myTicket() { 
    document.getElementById("ticket-id-form").submit(); 
     } </script>


<?php include includes_path . 'footer-close.php'; ?>