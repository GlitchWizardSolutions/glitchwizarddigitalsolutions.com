<?php
/*
Created: 2025-11-25
Purpose: Dashboard wrapper for invoice view - keeps user in dashboard while viewing invoice
*/
include 'assets/includes/user-config.php';

// Check if invoice ID is provided
if (!isset($_GET['id'])) {
    header('Location: client-invoices.php');
    exit;
}

$invoice_id = $_GET['id'];

// Verify this invoice belongs to one of the user's business profiles
$stmt = $pdo->prepare('SELECT id FROM invoice_clients WHERE acc_id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$user_business_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($user_business_ids)) {
    header('Location: client-invoices.php');
    exit;
}

// Check if invoice belongs to user
$placeholders = str_repeat('?,', count($user_business_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE invoice_number = ? AND client_id IN ($placeholders)");
$stmt->execute(array_merge([$invoice_id], $user_business_ids));
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: client-invoices.php');
    exit;
}

// Mark notification as read if coming from notification
if (isset($_GET['notification_id'])) {
    $stmt = $pdo->prepare('UPDATE client_notifications SET is_read = 1 WHERE id = ? AND client_id IN (' . $placeholders . ')');
    $stmt->execute(array_merge([$_GET['notification_id']], $user_business_ids));
}

include includes_path . 'page-setup.php';
include includes_path . 'navigation.php';
?>
<style>
  .invoice-iframe-container {
    width: 100%;
    min-height: calc(100vh - 200px);
    border: none;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  .invoice-iframe {
    width: 100%;
    min-height: 1200px;
    height: auto;
    border: none;
  }
  .back-button {
    margin-bottom: 20px;
  }
</style>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Invoice #<?= htmlspecialchars($invoice_id) ?></h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="client-invoices.php">Invoices</a></li>
          <li class="breadcrumb-item active">Invoice #<?= htmlspecialchars($invoice_id) ?></li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body" style="padding: 0;">
              <div class="back-button" style="padding: 20px 20px 0 20px;">
                <a href="client-invoices.php" class="btn btn-secondary btn-sm">
                  <i class="bi bi-arrow-left"></i> Back to Invoices
                </a>
              </div>
              <div class="invoice-iframe-container">
                <iframe 
                  src="<?php echo site_menu_base ?>client-invoices/invoice.php?id=<?= htmlspecialchars($invoice_id) ?><?= isset($_GET['notification_id']) ? '&notification_id=' . htmlspecialchars($_GET['notification_id']) : '' ?>" 
                  class="invoice-iframe"
                  title="Invoice #<?= htmlspecialchars($invoice_id) ?>"
                  scrolling="yes"
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main><!-- End #main -->

  <script>
    // Auto-resize iframe based on content
    document.addEventListener('DOMContentLoaded', function() {
      const iframe = document.querySelector('.invoice-iframe');
      if (iframe) {
        iframe.addEventListener('load', function() {
          try {
            // Try to get the height of the iframe content
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const height = iframeDoc.documentElement.scrollHeight;
            iframe.style.height = (height + 50) + 'px'; // Add 50px buffer
          } catch(e) {
            // If we can't access iframe content (CORS), use large fixed height
            iframe.style.height = '1500px';
          }
        });
      }
    });
  </script>

  <?php include includes_path . 'footer-close.php'; ?>
