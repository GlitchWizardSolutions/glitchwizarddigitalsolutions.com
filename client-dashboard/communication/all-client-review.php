<?php
/***********************************************************************************************
PROJECT SYSTEM - all-client-review.php
LOCATION:   /public_html/communication
DESCRIBE:   This page is for the web developer (admin) to view all of the client submitted tickets. 
            This can be done from Admin Section, but is not as nice to look at.
INPUTREQ:   Ticket id is passed into a session variable.
LOGGEDIN:   REQUIRED - ADMIN ONLY
   ADMIN:   /public_html/admin/
     OUT:   Notifications are not coded.
      IN:   SESSION variables and sql queries
LOG NOTE:   2025-01-12 PRODUCTION  - Active
            2025-03-21 Bug Fix  - Stray div tag in the closed ticket code.

***********************************************************************************************/
include 'assets/includes/user-config.php';
include process_path . 'all-review-responses-process.php';
 
if ($_SESSION['role'] !="Admin") { 
    //Remove anyone but Admin.
    header("Location: {$outside_url}");
    exit;
}

// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the view-ticket page.
				header("Location: {$base_url}/communication/ticket-view.php");
				exit;
			}
include includes_path . 'page-setup.php';
?>
<div id="top" style='margin:10px'>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base; ?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base; ?>client-dashboard/communication/submit-ticket.php">Submit Client Ticket</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base; ?>client-dashboard/communication/project-submit-ticket.php">Submit WebDev Project</a></li> 
       <li class="breadcrumb-item active">Manage Client Tickets</li>
     </ol>
   </nav>
  </div><? /* pagetitle */ ?>
<?php /* ****************   CLIENT TICKET REVIEW  ADMIN VIEW  **************************************************** */ ?>      
<section class="section profile">
 <div id='section-all' class="col-xl-12 col-lg-12 col-sm-12">
  <div id='card-all' class="card">
   <div id='card-body-all' class="card-body pt-3">
    <ul class="nav nav-tabs nav-tabs-bordered">
     <li class="nav-item">
       <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#review-tickets">Response Required</button>
     </li>
     <li class="nav-item">
       <button class="nav-link" data-bs-toggle="tab" data-bs-target="#waiting-tickets">Awaiting Feedback</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#open-tickets">Open Tickets</button> 
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#resolved-tickets">Resolved Tickets</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#closed-tickets">Closed Tickets</button>
     </li>
   </ul>
   
<div id='tab-content-all' class="tab-content pt-2">
    
<?php /* ****************   ADMIN RESPONSE NEEDED TICKETS    **************************************************** */ ?> 

<div class="tab-pane fade show active review-tickets" id="review-tickets">
  <h5 class="card-title">Admin Response Required</h5>
     <p>These tickets are <strong>awaiting Admin response</strong> to continue, or to close.</p>
<?php foreach ($client_waiting_tickets as $cw_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($cw_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-warning" style='width:75px' type="submit">Respond</button>  
          </form> 
           <div class="row ticket col-12">
               <h5><span class='fs-6'>
			  <?php if ($cw_ticket['ticket_status'] == 'open'): ?><i ></i>
			    <i title="Open status" alt="open status" class="bi bi-exclamation-circle text-warning" responsive-hidden></i>&nbsp; 
			  <?php elseif ($cw_ticket['ticket_status'] == 'resolved'): ?>
			      <i title="Resolved status" alt="resolved status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; 
			  <?php endif; ?>
			  </span>
			 <strong><?=htmlspecialchars($cw_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 </div><? /* end row ticket col-12   */ ?>
		   <div class='row col-12'> 
		   <div class='col-1'></div><? /* end col-1 */ ?>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($cw_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		     </div><? /* end col-10   */ ?>
		   <div class='col-1'></div><? /* end col-1 */ ?>
		</div><? /* end row col-12   */ ?>
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->
<?php if (!$client_waiting_tickets): ?>
  <p>You have no tickets to respond to.</p>
<?php endif; ?>    
</div><!--end review tickets-->
  
<?php /* ****************     CLIENT RESPONSE NEEDED TICKETS    **************************************************** */ ?>    
<div class="tab-pane fade waiting-tickets pt-3" id="waiting-tickets">
  <h5 class="card-title">Awaiting Client Response</h5>
     <p>These tickets are <strong>awaiting client to respond</strong> to continue, or to close.</p>
<?php foreach ($admin_waiting_tickets as $aw_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($aw_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-warning" style='width:75px' type="submit">Respond</button>  
          </form> 
          <div class="row ticket col-12">
               <h5><span class='fs-6'>
			  <?php if ($aw_ticket['ticket_status'] == 'open'): ?><i ></i>
			    <i title="Open status" alt="open status" class="bi bi-exclamation-circle text-warning" responsive-hidden></i>&nbsp; 
			  <?php elseif ($aw_ticket['ticket_status'] == 'resolved'): ?>
			      <i title="Resolved status" alt="resolved status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; 
			  <?php endif; ?>
			  </span>
			 <strong><?=htmlspecialchars($aw_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 </div><!--class="row ticket col-12"-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($aw_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
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
<?php if (!$admin_waiting_tickets): ?>
  <p>You have no tickets waiting for a client response to.</p>
<?php endif; ?>    
</div><!--end waiting tickets-->

<?php /* ****************     OPEN TICKETS    **************************************************** */ ?>
<div class="tab-pane fade open-tickets pt-3" id="open-tickets">
  <h5 class="card-title">Open Tickets</h5>
<?php foreach ($open_tickets as $o_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($o_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
          </form> 
          <div class="row ticket col-12">
             <h5>
			   <span class='fs-6'> <i title="Open status" alt="open status" class="far fa-clock fa-2x responsive-hidden" style="color:#fad087;"></i>&nbsp; </span>
			   <strong><?=htmlspecialchars($o_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		  </div><!--row ticket col-12-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($o_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
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
</div><!--close open tickets-->

<?php /* ****************     RESOLVED TICKETS    **************************************************** */ ?>
<div class="tab-pane fade resolved-tickets pt-3" id="resolved-tickets">
   <h5 class="card-title">Resolved Tickets</h5>
<?php foreach ($resolved_tickets as $r_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
            <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($r_ticket['id']?? '', ENT_QUOTES)?>">
            <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
          </form> 
          <div class="row ticket col-12">
               <h5>
			     <span class='fs-6'> <i title="Resolved status" alt="resolved status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; </span>
			     <strong><?=htmlspecialchars($r_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($r_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
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
<?php if (!$resolved_tickets): ?>
		<p>You have no resolved tickets.</p>
<?php endif; ?>
</div><!--close resolved tickets-->

<?php /* ****************    CLOSED TICKETS    **************************************************** */ ?>
<div class="tab-pane closed-tickets fade pt-3" id="closed-tickets">
 <h5 class="card-title">Closed Tickets</h5>
  <p>These tickets are no longer being worked. <br>
  
 <?php foreach ($closed_tickets as $c_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
            <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($c_ticket['id']?? '', ENT_QUOTES)?>">
            <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
          </form> 
          <div class="row ticket col-12">
           <h5>
             <span class='fs-6'><i title="Closed status" alt="closed status" class="fa-solid fa-x" style="color:#adbce6;"></i>&nbsp;</span>
			 <strong><?=htmlspecialchars($c_ticket['title'] ?? '', ENT_QUOTES)?></strong>
		  </h5>
		  </div>
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($c_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
		     </div>
		   <div class='col-1'></div>
		   
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
<p>You have no closed tickets.</p>
<?php endif; ?>
</div><!--close closed tickets-->
</div><!--/tab-content-all-->
 </div><!--/card-body-all-->
   </div><!--/card-all-->
  </div><!--section-all-->
 </section> <? /* / end viewing section */ ?>
</main><? /* end main */ ?> 
<? /* / end top */ ?>
<script>
function myTicket() { 
    document.getElementById("ticket-id-form").submit(); 
     } 
</script>

<?php include includes_path . 'footer-close.php'; ?>