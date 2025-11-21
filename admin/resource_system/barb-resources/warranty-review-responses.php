<?php
include 'assets/includes/user-config.php';
include process_path . 'warranty-review-response-process.php';
// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the warranty-ticket-view.
				header("Location: {$base_url}/barb-resources/warranty-ticket-view.php");
				exit;
			}
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/barb-resources/warranty-submit-ticket.php">Create Warranty Record</a></li> 
       <li class="breadcrumb-item active">Warranty Rescords</li>
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
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#expiring-tickets">Expiring Warranty</button> 
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#active-tickets">Active Warranty</button> 
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#service-tickets">Service Only</button>
     </li>
     <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#closed-tickets">Closed Warranty</button>
     </li>
   </ul>
   
<div id='tab-content-all' class="tab-content pt-2">
    
<?php /* ****************     REVIEW NEEDED PROJECTS    **************************************************** */ ?>    
<div class="tab-pane fade show active review-tickets" id="review-tickets">
  <h5 class="card-title">Review Required</h5>
     <p>These warranty tickets are <strong>awaiting review</strong> to review for completion.</p>
<?php foreach ($action_required_ticket as $action_required): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($action_required['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-warning" style='width:75px' type="submit">Review</button>
              <h6>Review Due <?=date('M d, Y',strtotime($action_required['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($action_required['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
               <h5><span class='fs-6'>
			  <?php if ($action_required['ticket_status'] == 'active'): ?><i></i>
			    <i title="Active status" alt="active status" class="bi bi-exclamation-circle text-warning" responsive-hidden></i>&nbsp; 
			  <?php elseif ($action_required['ticket_status'] == 'service'): ?>
			      <i title="Service status" alt="service status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; 
			  <?php endif; ?>
			  </span>
			 <strong><?=htmlspecialchars($action_required['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 </div><!--class="row ticket col-12"-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($action_required['msg'] ?? ''), ENT_QUOTES)?></p></span>
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
<?php if (!$action_required_ticket): ?>
  <p>You have no warranty ticket to review.</p>
<?php endif; ?>    
</div><!--end review warranty tickets-->


<?php /* ****************   EXPIRING WARRANTIES < 30 DAYS******************************** */ ?>
<div class="tab-pane fade expiring-tickets pt-3" id="expiring-tickets">
  <h5 class="card-title">Expiring Warranties</h5>
<?php foreach ($expiring as $expiring_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
              <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($expiring_ticket['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
               <h6>Review Due <?=date('M d, Y',strtotime($expiring_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($expiring_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
             <h5>
			   <span class='fs-6'> <i title="Active status" alt="active status" class="far fa-clock fa-2x responsive-hidden" style="color:#fad087;"></i>&nbsp; </span>
			   <strong><?=htmlspecialchars($expiring_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		  </div><!--row ticket col-12-->
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($expiring_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
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


<?php if (!$expiring): ?>
<p>You have no expiring warranty tickets.</p>
<?php endif; ?>
</div><!--close expiring warranty tickets-->

<?php /* ****************    ACTIVE PROJECTS    **************************************************** */ ?>
<div class="tab-pane fade active-tickets pt-3" id="active-tickets">
  <h5 class="card-title">Active Warranty Tickets</h5>
<?php foreach ($active_tickets as $o_ticket): ?>
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
               <h6>Review Due <?=date('M d, Y',strtotime($o_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($o_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
             <h5>
			   <span class='fs-6'> <i title="Active status" alt="active status" class="far fa-clock fa-2x responsive-hidden" style="color:#fad087;"></i>&nbsp; </span>
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


<?php if (!$active_tickets): ?>
<p>You have no active warranty tickets.</p>
<?php endif; ?>
</div><!--close active warranty tickets-->

<?php /* ****************     SERVICE ONLY Records    **************************************************** */ ?>
<div class="tab-pane fade service-tickets pt-3" id="service-tickets">
   <h5 class="card-title">Service Only</h5>
<?php foreach ($service_tickets as $p_ticket): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='ticket-id-form' style="width:100%" class='form' method="post"> 
            <input name="respond_ticket_id" type="hidden" id="respond_ticket_id" value="<?=htmlspecialchars($p_ticket['id']?? '', ENT_QUOTES)?>">
            <button class="btn btn-sm btn-success" style='width:55px' type="submit">View</button>  
             <h6>Review Due <?=date('M d, Y',strtotime($p_ticket['reminder_date'])) ?> &nbsp; [<span style='color:purple'><?=time2str($p_ticket['reminder_date']) ?></span>]</h6> 
          </form> 
          <div class="row ticket col-12">
               <h5>
			     <span class='fs-6'> <i title="Service status" alt="service status" class="fa-solid fa-arrow-right" style="color:#adbce6;"></i>&nbsp; </span>
			     <strong><?=htmlspecialchars($p_ticket['title'] ?? '', ENT_QUOTES)?></strong>
			  </h5>
		 <div class='row col-12'> 
		   <div class='col-1'></div>
		   <div class='col-10'>
		     <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($p_ticket['msg'] ?? ''), ENT_QUOTES)?></p></span>
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


		<?php if (!$service_tickets): ?>
		<p>You have no service warranty tickets.</p>
<?php endif; ?>
</div><!--close service warranty tickets-->
<div class="tab-pane closed-tickets fade pt-3" id="closed-tickets">
 <h5 class="card-title">Closed Warranty Tickets</h5>
  <p>These warranty tickets are no longer being worked. <br>
  
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
<p>You have no closed warranty tickets.</p>
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

?>
<?php include includes_path . 'footer-close.php'; ?>