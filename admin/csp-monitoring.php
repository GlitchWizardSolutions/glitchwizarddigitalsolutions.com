<?php
/**
 * CSP Violation Monitoring Dashboard
 *
 * Admin page to view and monitor Content Security Policy violations.
 * Provides insights into security issues and helps maintain CSP effectiveness.
 */

include '../assets/includes/admin_config.php';

// Check admin access
if (!isset($_SESSION['loggedin']) || $_SESSION['access_level'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Include CSP reporting functions
require_once '../lib/csp-reporting.php';

// Get CSP statistics
$stats = get_csp_stats(30); // Last 30 days

// Handle cleanup action
if (isset($_POST['cleanup_old']) && $_POST['cleanup_old'] === '1') {
    $deleted = cleanup_csp_violations();
    $cleanupMessage = "Cleaned up $deleted old CSP violation records.";
}

// Get filter parameters
$filterDirective = $_GET['directive'] ?? '';
$filterUri = $_GET['uri'] ?? '';
$days = (int)($_GET['days'] ?? 30);

// Apply filters to recent violations
$filteredViolations = array_filter($stats['recent_violations'], function($violation) use ($filterDirective, $filterUri) {
    if (!empty($filterDirective) && stripos($violation['violated_directive'], $filterDirective) === false) {
        return false;
    }
    if (!empty($filterUri) && stripos($violation['document_uri'], $filterUri) === false) {
        return false;
    }
    return true;
});

$pageTitle = 'CSP Violation Monitoring';
include '../assets/includes/main.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">CSP Violation Monitoring</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">CSP Monitoring</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo number_format($stats['total_violations']); ?></h3>
                            <p>Total Violations (<?php echo $stats['period_days']; ?> days)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo count($stats['by_directive']); ?></h3>
                            <p>Directive Types</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo count(array_filter($filteredViolations, function($v) {
                                return strtotime($v['created_at']) > strtotime('-24 hours');
                            })); ?></h3>
                            <p>Violations (Last 24h)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo count(array_unique(array_column($stats['recent_violations'], 'ip_address'))); ?></h3>
                            <p>Unique IPs</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cleanup Message -->
            <?php if (isset($cleanupMessage)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php echo htmlspecialchars($cleanupMessage); ?>
            </div>
            <?php endif; ?>

            <!-- Controls -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Controls & Filters</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group mr-3">
                            <label class="mr-2">Days:</label>
                            <select name="days" class="form-control">
                                <option value="7" <?php echo $days === 7 ? 'selected' : ''; ?>>7 days</option>
                                <option value="30" <?php echo $days === 30 ? 'selected' : ''; ?>>30 days</option>
                                <option value="90" <?php echo $days === 90 ? 'selected' : ''; ?>>90 days</option>
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label class="mr-2">Directive:</label>
                            <input type="text" name="directive" value="<?php echo htmlspecialchars($filterDirective); ?>" class="form-control" placeholder="e.g., script-src">
                        </div>
                        <div class="form-group mr-3">
                            <label class="mr-2">URI:</label>
                            <input type="text" name="uri" value="<?php echo htmlspecialchars($filterUri); ?>" class="form-control" placeholder="Filter by document URI">
                        </div>
                        <button type="submit" class="btn btn-primary mr-3">Filter</button>
                        <a href="?days=<?php echo $days; ?>" class="btn btn-secondary">Clear Filters</a>
                    </form>

                    <div class="mt-3">
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="cleanup_old" value="1">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Delete CSP violation records older than 90 days?')">
                                <i class="fas fa-trash"></i> Cleanup Old Records
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Violations by Directive -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Violations by Directive</h3>
                </div>
                <div class="card-body">
                    <canvas id="directiveChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Violations Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Violations (<?php echo count($filteredViolations); ?> shown)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Directive</th>
                                <th>Document URI</th>
                                <th>Blocked URI</th>
                                <th>Source</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredViolations as $violation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($violation['created_at']))); ?></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($violation['violated_directive']); ?></span></td>
                                <td><small><?php echo htmlspecialchars(substr($violation['document_uri'], 0, 50)); ?>...</small></td>
                                <td><small><?php echo htmlspecialchars(substr($violation['blocked_uri'], 0, 50)); ?>...</small></td>
                                <td>
                                    <?php if ($violation['source_file']): ?>
                                        <small><?php echo htmlspecialchars($violation['source_file']); ?>:<?php echo $violation['line_number']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($violation['ip_address']); ?></small></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="showViolationDetails(<?php echo $violation['id']; ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Violation Details Modal -->
<div class="modal fade" id="violationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">CSP Violation Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="violationDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showViolationDetails(violationId) {
    // In a real implementation, you'd fetch details via AJAX
    // For now, just show a placeholder
    $('#violationDetails').html('<p>Detailed violation information would be loaded here.</p>');
    $('#violationModal').modal('show');
}

// Chart.js for directive statistics
<?php if (!empty($stats['by_directive'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('directiveChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($stats['by_directive'], 'violated_directive')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats['by_directive'], 'count')); ?>,
                backgroundColor: [
                    '#ff6384', '#36a2eb', '#cc65fe', '#ffce56', '#ff9f40',
                    '#4bc0c0', '#9966ff', '#ff6384', '#c9cbcf', '#ff6384'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php include '../assets/includes/footer.php'; ?>