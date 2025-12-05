<?php
/*******************************************************************************
 * GUEST LEVEL - PROJECT DETAILS
 * Second step in Guest pipeline - gather project information
 * Created: 2025-12-04
 ******************************************************************************/

if (!session_id()) {
    session_start();
}
require '../../../private/config.php';

// Load main.php to get $pdo and auth functions
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';

// Check authentication (before any page output)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../index.php');
    exit;
}

// Load service catalog functions
require_once public_path . 'lib/service-catalog-functions.php';

// Get current user's pipeline status and validate (before page output)
$stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
$stmt->execute([$_SESSION['id']]);
$pipeline = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if service not selected yet
if (!$pipeline || empty($pipeline['service_selected'])) {
    header('Location: guest-service-selection.php?error=select_service_first');
    exit;
}

// Get selected service details
$selected_service = get_service_details($pdo, $pipeline['service_selected']);

// Check if form data exists in client_pipeline_forms
$stmt = $pdo->prepare('
    SELECT form_data FROM client_pipeline_forms 
    WHERE acc_id = ? AND form_name = ? 
    ORDER BY submitted_at DESC LIMIT 1
');
$stmt->execute([$_SESSION['id'], 'project_details']);
$existing_form = $stmt->fetch(PDO::FETCH_ASSOC);
$form_data = $existing_form ? json_decode($existing_form['form_data'], true) : [];

// Now load page output includes
require_once public_path . 'lib/email-system.php';
include includes_path . 'page-setup.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Project Details</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item">Pipeline</li>
                <li class="breadcrumb-item active">Project Details</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            Service selection saved! Please provide your project details below.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tell Us About Your Project</h5>
                        <p class="mb-4">
                            Help us understand your vision so we can create the perfect website for you.
                        </p>

                        <form action="process/save-project-details.php" method="POST">
                            
                            <!-- Business/Project Name -->
                            <div class="mb-3">
                                <label for="business_name" class="form-label">Business or Project Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="business_name" name="business_name" 
                                       value="<?= htmlspecialchars($form_data['business_name'] ?? '', ENT_QUOTES) ?>" required>
                                <small class="text-muted">What should we call your project?</small>
                            </div>

                            <!-- Industry/Type -->
                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry or Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="industry" name="industry" required>
                                    <option value="">-- Select --</option>
                                    <option value="professional_services" <?= ($form_data['industry'] ?? '') === 'professional_services' ? 'selected' : '' ?>>Professional Services</option>
                                    <option value="healthcare" <?= ($form_data['industry'] ?? '') === 'healthcare' ? 'selected' : '' ?>>Healthcare</option>
                                    <option value="retail" <?= ($form_data['industry'] ?? '') === 'retail' ? 'selected' : '' ?>>Retail</option>
                                    <option value="restaurant_food" <?= ($form_data['industry'] ?? '') === 'restaurant_food' ? 'selected' : '' ?>>Restaurant/Food Service</option>
                                    <option value="creative" <?= ($form_data['industry'] ?? '') === 'creative' ? 'selected' : '' ?>>Creative/Arts</option>
                                    <option value="nonprofit" <?= ($form_data['industry'] ?? '') === 'nonprofit' ? 'selected' : '' ?>>Non-Profit</option>
                                    <option value="personal" <?= ($form_data['industry'] ?? '') === 'personal' ? 'selected' : '' ?>>Personal Portfolio/Resume</option>
                                    <option value="other" <?= ($form_data['industry'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- Project Goals -->
                            <div class="mb-3">
                                <label for="project_goals" class="form-label">What are your main goals for this website? <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="project_goals" name="project_goals" rows="4" required><?= htmlspecialchars($form_data['project_goals'] ?? '', ENT_QUOTES) ?></textarea>
                                <small class="text-muted">Examples: Generate leads, showcase portfolio, sell products, provide information, etc.</small>
                            </div>

                            <!-- Target Audience -->
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Who is your target audience?</label>
                                <input type="text" class="form-control" id="target_audience" name="target_audience" 
                                       value="<?= htmlspecialchars($form_data['target_audience'] ?? '', ENT_QUOTES) ?>"
                                       placeholder="e.g., Small business owners, young professionals, local community">
                            </div>

                            <!-- Timeline Expectations -->
                            <div class="mb-3">
                                <label for="timeline_preference" class="form-label">Preferred Timeline</label>
                                <select class="form-select" id="timeline_preference" name="timeline_preference">
                                    <option value="">-- Select --</option>
                                    <option value="asap" <?= ($form_data['timeline_preference'] ?? '') === 'asap' ? 'selected' : '' ?>>As soon as possible</option>
                                    <option value="1_month" <?= ($form_data['timeline_preference'] ?? '') === '1_month' ? 'selected' : '' ?>>Within 1 month</option>
                                    <option value="2_months" <?= ($form_data['timeline_preference'] ?? '') === '2_months' ? 'selected' : '' ?>>1-2 months</option>
                                    <option value="flexible" <?= ($form_data['timeline_preference'] ?? '') === 'flexible' ? 'selected' : '' ?>>Flexible</option>
                                </select>
                                <small class="text-muted">Typical project timeline: 4-8 weeks from deposit to completion</small>
                            </div>

                            <!-- Domain Name Preference -->
                            <div class="mb-3">
                                <label for="domain_preference" class="form-label">Domain Name Preference</label>
                                <input type="text" class="form-control" id="domain_preference" name="domain_preference" 
                                       value="<?= htmlspecialchars($form_data['domain_preference'] ?? '', ENT_QUOTES) ?>"
                                       placeholder="e.g., mybusiness.com (we'll check availability)">
                                <small class="text-muted">We'll help you choose the perfect domain if you're not sure</small>
                            </div>

                            <!-- Color Preferences -->
                            <div class="mb-3">
                                <label for="color_preferences" class="form-label">Color Preferences</label>
                                <input type="text" class="form-control" id="color_preferences" name="color_preferences" 
                                       value="<?= htmlspecialchars($form_data['color_preferences'] ?? '', ENT_QUOTES) ?>"
                                       placeholder="e.g., Blue and white, earthy tones, modern and minimalist">
                            </div>

                            <!-- Additional Notes -->
                            <div class="mb-4">
                                <label for="additional_notes" class="form-label">Anything else we should know?</label>
                                <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3"><?= htmlspecialchars($form_data['additional_notes'] ?? '', ENT_QUOTES) ?></textarea>
                                <small class="text-muted">Features you need, design inspiration, competitors to look at, etc.</small>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="guest-service-selection.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Service Selection
                                </a>
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-check-circle"></i> Complete & Request Deposit Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card position-sticky" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title">Your Selection</h5>
                        
                        <div class="mb-3">
                            <strong class="text-primary"><?= htmlspecialchars($selected_service['service_name']) ?></strong>
                            <div class="text-muted">$<?= number_format($selected_service['base_price'], 2) ?></div>
                        </div>

                        <?php if ($pipeline['add_diy_content_creation']): ?>
                        <div class="mb-3">
                            <strong>"Do It For Me" Content Creation</strong>
                            <div class="text-muted">+$1,000.00</div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <strong>Domain & Email Setup</strong>
                            <div class="text-muted">+$150.00</div>
                        </div>

                        <hr>

                        <?php
                        $domain_price = 150;
                        $diy_price = $pipeline['add_diy_content_creation'] ? 1000 : 0;
                        $total = $selected_service['base_price'] + $domain_price + $diy_price;
                        $deposit = $total * 0.50;
                        ?>

                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong class="text-primary">$<?= number_format($total, 2) ?></strong>
                        </div>

                        <div class="alert alert-success mb-3">
                            <strong>50% Deposit:</strong>
                            <div class="h5 mb-0">$<?= number_format($deposit, 2) ?></div>
                        </div>

                        <div class="alert alert-info">
                            <strong>Next Steps:</strong>
                            <ol class="mb-0 mt-2 ps-3 small">
                                <li>Submit project details</li>
                                <li>Receive deposit invoice via email</li>
                                <li>Pay deposit to begin project</li>
                                <li>Move to Onboarding phase</li>
                            </ol>
                        </div>

                        <?php if ($pipeline['hosting_preference'] !== 'undecided'): ?>
                        <div class="mt-3">
                            <strong>Hosting Preference:</strong>
                            <div class="text-muted">
                                <?php if ($pipeline['hosting_preference'] === 'yes'): ?>
                                    <i class="bi bi-server text-success"></i> GlitchWizard hosting
                                <?php elseif ($pipeline['hosting_preference'] === 'self'): ?>
                                    <i class="bi bi-file-zip text-primary"></i> Self-hosting
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include includes_path . 'footer-close.php'; ?>
