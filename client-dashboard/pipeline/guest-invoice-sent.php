<?php
/*******************************************************************************
 * GUEST LEVEL - INVOICE SENT CONFIRMATION
 * Shows success message after deposit invoice is generated
 * Created: 2025-12-04
 ******************************************************************************/

if (!session_id()) {
    session_start();
}
require '../../../private/config.php';
require_once public_path . 'lib/email-system.php';

$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);
include includes_path . 'page-setup.php';

// Get invoice details from URL
$invoice_number = $_GET['invoice_number'] ?? '';
$amount = $_GET['amount'] ?? '0.00';

// Get current user's pipeline status
$stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
$stmt->execute([$_SESSION['id']]);
$pipeline = $stmt->fetch(PDO::FETCH_ASSOC);

// Get invoice ID for link
$stmt = $pdo->prepare('SELECT invoice_id FROM invoices WHERE invoice_number = ? AND acc_id = ?');
$stmt->execute([$invoice_number, $_SESSION['id']]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Invoice Sent!</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item">Pipeline</li>
                <li class="breadcrumb-item active">Invoice Confirmation</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Success Message -->
                <div class="card border-success">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 72px;"></i>
                        </div>
                        
                        <h2 class="text-success mb-3">Project Details Received!</h2>
                        
                        <p class="lead mb-4">
                            Your deposit invoice has been generated and sent to your email.
                        </p>

                        <div class="alert alert-info d-inline-block">
                            <div class="mb-2"><strong>Invoice Number:</strong></div>
                            <div class="h4 mb-0"><?= htmlspecialchars($invoice_number) ?></div>
                        </div>

                        <div class="my-4">
                            <div class="text-muted mb-2">Deposit Amount Due</div>
                            <div class="h2 text-primary mb-0">$<?= htmlspecialchars($amount) ?></div>
                            <small class="text-muted">Due within 14 days</small>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-list-check text-primary"></i> What Happens Next?
                        </h5>
                        
                        <div class="timeline">
                            <div class="timeline-item pb-4">
                                <div class="timeline-marker">
                                    <i class="bi bi-1-circle-fill text-success"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Check Your Email</h6>
                                    <p class="text-muted mb-0">
                                        We've sent you a copy of your deposit invoice with payment instructions.
                                    </p>
                                </div>
                            </div>

                            <div class="timeline-item pb-4">
                                <div class="timeline-marker">
                                    <i class="bi bi-2-circle-fill text-primary"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Pay Your Deposit</h6>
                                    <p class="text-muted mb-0">
                                        Click the link below to view and pay your invoice. Payment reserves your spot in our development queue.
                                    </p>
                                </div>
                            </div>

                            <div class="timeline-item pb-4">
                                <div class="timeline-marker">
                                    <i class="bi bi-3-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Complete Onboarding Forms</h6>
                                    <p class="text-muted mb-0">
                                        After payment, you'll receive access to onboarding forms to provide content, images, and design preferences.
                                    </p>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="bi bi-4-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">We Build Your Website</h6>
                                    <p class="text-muted mb-0">
                                        Our team gets to work! You'll receive regular updates and preview access as we develop your site.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h5 class="card-title">Ready to Get Started?</h5>
                        
                        <div class="d-grid gap-3">
                            <?php if ($invoice): ?>
                            <a href="../../invoices/view-invoice.php?id=<?= $invoice['invoice_id'] ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-receipt"></i> View & Pay Invoice
                            </a>
                            <?php endif; ?>
                            
                            <a href="../index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house-door"></i> Return to Dashboard
                            </a>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted mb-2">
                                <i class="bi bi-question-circle"></i> <strong>Questions?</strong>
                            </p>
                            <p class="small text-muted">
                                Contact us at <a href="mailto:support@digitalsolutions.com">support@digitalsolutions.com</a><br>
                                or call (555) 123-4567
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

<style>
.timeline {
    position: relative;
    padding-left: 50px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    font-size: 24px;
}

.timeline-content h6 {
    font-weight: 600;
}
</style>

<?php include includes_path . 'footer-close.php'; ?>
