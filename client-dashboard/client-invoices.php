<?php
/*
Created: 2025-11-20
Purpose: Client-facing invoice listing page showing all invoices for user's business profiles
*/
include 'assets/includes/user-config.php';

// Get account info
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all business profiles for this account
$stmt = $pdo->prepare('SELECT id, business_name FROM invoice_clients WHERE acc_id = ? ORDER BY business_name');
$stmt->execute([ $_SESSION['id'] ]);
$business_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected business filter (if any)
$selected_business = isset($_GET['business_id']) ? $_GET['business_id'] : '';

// Build query to get invoices for all user's business profiles
if ($business_profiles) {
    $business_ids = array_column($business_profiles, 'id');
    $placeholders = str_repeat('?,', count($business_ids) - 1) . '?';
    
    // Base query
    $query = "SELECT i.*, c.business_name, c.first_name, c.last_name, c.email,
              (SELECT SUM(ii.item_quantity * ii.item_price) FROM invoice_items ii WHERE ii.invoice_number = i.invoice_number) AS subtotal
              FROM invoices i 
              LEFT JOIN invoice_clients c ON c.id = i.client_id 
              WHERE i.client_id IN ($placeholders)";
    
    $params = $business_ids;
    
    // Add business filter if selected
    if ($selected_business && in_array($selected_business, $business_ids)) {
        $query = "SELECT i.*, c.business_name, c.first_name, c.last_name, c.email,
                  (SELECT SUM(ii.item_quantity * ii.item_price) FROM invoice_items ii WHERE ii.invoice_number = i.invoice_number) AS subtotal
                  FROM invoices i 
                  LEFT JOIN invoice_clients c ON c.id = i.client_id 
                  WHERE i.client_id = ?";
        $params = [$selected_business];
    }
    
    $query .= " ORDER BY i.created DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $invoices = [];
}

// Calculate totals
$total_unpaid = 0;
$total_paid = 0;
$count_unpaid = 0;
$count_paid = 0;

foreach ($invoices as $invoice) {
    $total = floatval($invoice['payment_amount']) + floatval($invoice['tax_total']);
    if ($invoice['payment_status'] == 'Unpaid') {
        $total_unpaid += $total;
        $count_unpaid++;
    } else if ($invoice['payment_status'] == 'Paid') {
        $total_paid += $total;
        $count_paid++;
    }
}

include includes_path . 'page-setup.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>My Invoices</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">Home</a></li>
                <li class="breadcrumb-item active">My Invoices</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <?php if (empty($business_profiles)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                You don't have any business profiles yet. <a href="client-business-create.php">Create a business profile</a> to receive invoices.
            </div>
        <?php else: ?>
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Unpaid Invoices</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #ffecdf; color: #ff771d; width: 64px; height: 64px;">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 32px;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $count_unpaid ?> Invoice<?= $count_unpaid != 1 ? 's' : '' ?></h6>
                                    <span class="text-danger fw-bold" style="font-size: 24px;">$<?= number_format($total_unpaid, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Paid Invoices</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #e0f8e9; color: #2eca6a; width: 64px; height: 64px;">
                                    <i class="bi bi-check-circle" style="font-size: 32px;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?= $count_paid ?> Invoice<?= $count_paid != 1 ? 's' : '' ?></h6>
                                    <span class="text-success fw-bold" style="font-size: 24px;">$<?= number_format($total_paid, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Invoice List</h5>
                        
                        <?php if (count($business_profiles) > 1): ?>
                        <form method="get" class="d-flex align-items-center">
                            <label class="me-2 mb-0">Filter by Business:</label>
                            <select name="business_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Businesses</option>
                                <?php foreach ($business_profiles as $business): ?>
                                <option value="<?= $business['id'] ?>" <?= $selected_business == $business['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($business['business_name'], ENT_QUOTES) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($invoices)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No invoices found for your business profile(s).
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <?php if (count($business_profiles) > 1 && !$selected_business): ?>
                                        <th>Business</th>
                                        <?php endif; ?>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): 
                                        $total = floatval($invoice['payment_amount']) + floatval($invoice['tax_total']);
                                        $is_overdue = $invoice['payment_status'] == 'Unpaid' && strtotime($invoice['due_date']) < time();
                                        $status_class = '';
                                        $status_icon = '';
                                        
                                        if ($invoice['payment_status'] == 'Paid') {
                                            $status_class = 'success';
                                            $status_icon = 'check-circle';
                                        } elseif ($is_overdue) {
                                            $status_class = 'danger';
                                            $status_icon = 'exclamation-triangle';
                                        } elseif ($invoice['payment_status'] == 'Unpaid') {
                                            $status_class = 'warning';
                                            $status_icon = 'clock';
                                        } elseif ($invoice['payment_status'] == 'Pending') {
                                            $status_class = 'info';
                                            $status_icon = 'hourglass-split';
                                        } else {
                                            $status_class = 'secondary';
                                            $status_icon = 'question-circle';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($invoice['invoice_number'], ENT_QUOTES) ?></strong></td>
                                        <?php if (count($business_profiles) > 1 && !$selected_business): ?>
                                        <td><?= htmlspecialchars($invoice['business_name'], ENT_QUOTES) ?></td>
                                        <?php endif; ?>
                                        <td><?= date('M d, Y', strtotime($invoice['created'])) ?></td>
                                        <td>
                                            <?= date('M d, Y', strtotime($invoice['due_date'])) ?>
                                            <?php if ($is_overdue): ?>
                                                <span class="badge bg-danger ms-1">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>$<?= number_format($total, 2) ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?= $status_class ?>">
                                                <i class="bi bi-<?= $status_icon ?>"></i>
                                                <?= $is_overdue ? 'Overdue' : htmlspecialchars($invoice['payment_status'], ENT_QUOTES) ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="<?php echo(site_menu_base) ?>client-invoices/invoice.php?id=<?= $invoice['invoice_number'] ?>" 
                                               class="btn btn-sm btn-primary" target="_blank" 
                                               style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                               title="View Invoice">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <?php if ($invoice['payment_status'] == 'Unpaid'): ?>
                                            <a href="<?php echo(site_menu_base) ?>client-invoices/pay-invoice.php?id=<?= $invoice['invoice_number'] ?>" 
                                               class="btn btn-sm btn-success" target="_blank"
                                               style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                               title="Pay Invoice">
                                                <i class="bi bi-credit-card"></i>
                                            </a>
                                            <?php elseif ($invoice['payment_status'] == 'Paid'): ?>
                                            <a href="<?php echo(site_menu_base) ?>client-invoices/invoice.php?id=<?= $invoice['invoice_number'] ?>" 
                                               class="btn btn-sm btn-secondary" target="_blank"
                                               style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                               onclick="setTimeout(function() { var w = window.open(this.href); setTimeout(function() { w.print(); }, 500); }.bind(this), 100); return false;"
                                               title="Print Receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include includes_path . 'footer-close.php'; ?>
