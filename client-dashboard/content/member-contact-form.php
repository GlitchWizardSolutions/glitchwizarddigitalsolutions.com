<?php
include 'assets/includes/user-config.php'; 
// output message (errors, etc)
$msg = '';
include includes_path . 'page-setup.php';  
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item">Communication</a></li> 
         <li class="breadcrumb-item active">Contact Us</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active contact-us" id="contact-us">
             <h5 class="card-title">Contact Us</h5>
          <div class="row">
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                   <h6 class="card-title">Report a Technical Problem</h6>
                    <p><strong>Report functional issues and changes to your website.</strong><br>
                    Please upload a full screenshot, and explain issue in detail.</p>
                    <!-- Create a Ticket Form -->
                    <form action= "communication.php" id='create-ticket-form' class='form' method="post">
                    <div class="row mb-3">
                      <label for="title" class="col-md-4 col-lg-3 col-form-label"><span class="required">*</span>Title</label>
                    <div class="col-md-8 col-lg-9">
                        <input aria-labelledby="title" type="text" name="title" class="form-control" placeholder="Subject of Ticket" autocomplete="on" id="title" maxlength="<?=max_title_length?>" aria-required="true" required>
                    </div>
                    </div>

                    <div class="row mb-3">
                      <label for="category" class="col-md-4 col-lg-3 col-form-label"><span class="not-required">&nbsp;</span>Category</label>
                    <div class="col-md-8 col-lg-9">
                      <select aria-labelledby="catagory" name="category" id="category">
                        <?php foreach($categories as $category): ?>
                           <option aria-description="<?=$category['title']?>" value="<?=$category['id']?>"><?=$category['title']?></option>
                        <?php endforeach; ?>
                      </select>
                      </div>
                    </div>
                    
                    <div class="row mb-3">
                      <label for="priority" class="col-md-4 col-lg-3 col-form-label"><span class="not-required">&nbsp;</span>Priority</label>
                      <div class="col-md-8 col-lg-9">
                        <select aria-labelledby="priority" name="priority" id="priority" aria-required="true" required>
                       <option aria-description="medium" value="medium">Medium</option>
                       <option aria-description="low" value="low">Low</option>
                       <option aria-description="high" value="high">High</option>
                     </select>
                    </div>
                   </div>
                    
                    <div class="row mb-3">
                        <label for="ticket-message" class="col-md-4 col-lg-3 col-form-label"><span class="required">*</span>Message</label>
                      <div class="col-md-8 col-lg-9">
                       <div class="ticket-message">
                        <textarea aria-description="message" name="ticket-message" placeholder="Enter your message here..." class="form-control" autocomplete="on" id="ticket-message" maxlength="<?=max_msg_length?>" aria-required="true" required></textarea>
                    </div>
                   </div>
                  </div>
                    
             <?php if (attachments): ?>
                   <div class="row mb-3">
                      <label for="attachments" class="col-md-4 col-lg-3 col-form-label"><span class="not-required">&nbsp;</span>Screenshot</label>
                     <div class="col-md-8 col-lg-9">
                       <input type="file" name="attachments[]" id="attachments" accept=".<?=str_replace(',', ',.', attachments_allowed)?>" multiple>
                      </div>
                    </div>
            <?php endif; ?>
                    <div class="text-center">
	        <?php if ($msg): ?>
                 <p class="error-msg"><?=$msg?></p>
            <?php endif; ?>

			<div class="mar-bot-2">
			   <button class="btn btn-success mar-top-1 mar-right-1" type="submit">Create Ticket</button>
			   <a href="communication.php" class="btn alt mar-top-1">Cancel</a>
		    </div>
           </div>

          </form><!-- End Create Ticket Form -->
         </div><!--/body-->
        </div><!--/card-->
       </div><!--/column-6-->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
             <h6 class="card-title">Send a Message</h6>
              <p><strong>Message us questions about your account with us.</strong></p>
              <div class="row">
                               <form id= "contact-form" name="contact-form" class="contact-form" action="" method="post" enctype="multipart/form-data" novalidate>

                <div class="col-12 pad-y-2 pad-r-2">
                    <label for="name"><span class="required">*</span>What is the name of your Business?</label>
                    <div class="form-element">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Complete Business Name" autocomplete="off" pattern="^[a-zA-Z ]+" title="Name must contain only characters!" aria-required="true" required>
                    </div>
                </div>

                <div class="col-12 pad-y-2">
                    <p>If you want me to include a <strong>new custom domain email</strong>, please enter an example email, with anything you'd like before the @
                    and the domain name you would like me to try to get. ie: Service@BusinessName.com.</p>
                    <label for="email"><span class="required">*</span> What Email Would you like your CUSTOMERS to use?</label>
                    
                    <div class="form-element">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your preferred BUSINESS email" autocomplete="email" title="Please enter a valid email address format xxx@xxx.xxx!" aria-required="true" required>
                    </div>
                </div>

                <div class="col-12 pad-y-2">
                    <label for="subject"><span class="required">*</span> What is your PRIMARY Product/Product Type, or Service?</label>
                    <div class="form-element">
                        <input type="text" id="subject" name="subject" placeholder="Enter your subject" autocomplete="on" title="Please enter a subject!" aria-required="true" required>
                    </div>
                </div>

                <div class="col-12 pad-y-2">
                     <p>Please include what your customers benefit from, by choosing your product or service.  Be as descriptive as possible.  You will be able to add more details, later.</p>
                    <label for="message"><span class="required">*</span> What Does Your Company Do?</label>
                    <p>Please include what your customers benefit from, by choosing your product or service.   You will be able to add more details, later.</p>
                    <div class="form-element size-xl">
                        <textarea id="message" name="message" placeholder="Your message ..." title=" Be as descriptive as possible!" autocomplete="on" aria-required="true" required></textarea>
                    </div>
                </div>

                <div class="col-12 pad-y-2">

                    <button type="submit" class="btn">Submit this first form.</button>

                </div>

                <p class="col-12 errors"></p>  

            </form>

        </div>
        
        <script src="<?php echo site_menu_base ?>assets/js/member-contact.js"></script>
       
        <script>
        new ContactForm({
            container: document.querySelector('.contact-form'),
            // The PHP file path that processes the form data
            php_file_url: 'member-contact-process.php'
        });
        </script>

           </div>
          </div><!--/body-->
         </div><!--/card-->
        </div><!--/column-6-->
       </div><!--/row-->
      </div><!--/overview-->

    </div><!-- End Bordered Tabs -->
   </div>
  </div>
 </div>
</div>
</section>
</main><!-- End #main -->
<?php include includes_path . 'footer-close.php'; ?>