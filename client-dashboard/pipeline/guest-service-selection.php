<?php
/*******************************************************************************
 * GUEST LEVEL - SERVICE SELECTION
 * First step in the client pipeline journey
 * Allows new clients to select website package and add-ons
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

// Load service catalog functions
require_once public_path . 'lib/service-catalog-functions.php';

// Get current user's pipeline status
$stmt = $pdo->prepare('
    SELECT * FROM client_pipeline_status 
    WHERE acc_id = ?
');
$stmt->execute([$_SESSION['id']]);
$pipeline = $stmt->fetch(PDO::FETCH_ASSOC);

// If no pipeline record exists, create one
if (!$pipeline) {
    $stmt = $pdo->prepare('
        INSERT INTO client_pipeline_status (acc_id, access_level, current_step) 
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$_SESSION['id'], $account['access_level'], 'service_selection']);
    
    $stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
    $stmt->execute([$_SESSION['id']]);
    $pipeline = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all website packages from service catalog
$website_packages = get_website_packages($pdo);

// Separate main packages from add-ons
$main_packages = [];
$add_ons = [];

foreach ($website_packages as $pkg) {
    // Add-ons and standalone services that should NOT be in packages section
    if (in_array($pkg['service_slug'], ['domain-email-setup', 'content-creation-diy', 'google-business-profile'])) {
        $add_ons[] = $pkg;
    } else {
        // Only actual website packages
        $main_packages[] = $pkg;
    }
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Select Your Services</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item">Pipeline</li>
                <li class="breadcrumb-item active">Service Selection</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Let's Get Started!</h5>
                        <p class="mb-4">
                            Select a website package, standalone service, or combine multiple services to fit your needs.
                        </p>

                        <form id="service-selection-form" action="process/save-service-selection.php" method="POST">
                            
                            <!-- Website Package Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Website Packages</label>
                                
                                <!-- No Package Option -->
                                <div class="card mb-3" style="cursor: pointer; background-color: #f8f9fa;">
                                    <div class="card-body py-2">
                                        <div class="form-check">
                                            <input class="form-check-input package-radio" 
                                                   type="radio" 
                                                   name="service_selected" 
                                                   id="no_package" 
                                                   value=""
                                                   data-price="0"
                                                   <?= empty($pipeline['service_selected']) ? 'checked' : '' ?>>
                                            <label class="form-check-label w-100" for="no_package" style="cursor: pointer;">
                                                <strong>No Package - Standalone Services Only</strong>
                                                <small class="text-muted d-block">I only want to purchase standalone services (Domain/Email, Google Business Profile, etc.)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php foreach ($main_packages as $index => $package): 
                                    $features = isset($package['features']) ? $package['features'] : [];
                                    $isSelected = $pipeline['service_selected'] === $package['service_slug'];
                                ?>
                                <div class="card mb-3 package-card <?= $isSelected ? 'border-primary' : '' ?>" style="cursor: pointer; transition: all 0.3s;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input package-radio" 
                                                   type="radio" 
                                                   name="service_selected" 
                                                   id="package_<?= $package['id'] ?>" 
                                                   value="<?= htmlspecialchars($package['service_slug']) ?>"
                                                   data-price="<?= $package['base_price'] ?>"
                                                   data-type="<?= strpos($package['service_slug'], 'mvp') !== false ? 'mvp' : (strpos($package['service_slug'], 'foundational') !== false ? 'foundational' : (strpos($package['service_slug'], 'expanded') !== false ? 'expanded' : 'other')) ?>"
                                                   <?= $isSelected ? 'checked' : '' ?>>
                                            <label class="form-check-label w-100" for="package_<?= $package['id'] ?>" style="cursor: pointer;">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h5 class="mb-1"><?= htmlspecialchars($package['service_name']) ?></h5>
                                                        <p class="text-muted mb-2"><?= htmlspecialchars($package['service_description']) ?></p>
                                                    </div>
                                                    <div class="text-end">
                                                        <h4 class="text-primary mb-0">$<?= number_format($package['base_price'], 2) ?></h4>
                                                        <small class="text-muted">one-time</small>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($features)): ?>
                                                <div class="mt-3">
                                                    <strong class="text-secondary">Includes:</strong>
                                                    <ul class="mb-0 mt-2" style="columns: 2; -webkit-columns: 2; -moz-columns: 2;">
                                                        <?php foreach ($features as $feature): ?>
                                                        <li class="small"><?= htmlspecialchars($feature) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Standalone Services & Add-Ons -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Additional Services</label>
                                <p class="text-muted small">These can be purchased standalone or added to any website package.</p>
                                
                                <?php 
                                // Collect standalone and add-on services
                                $domain_pkg = null;
                                $diy_pkg = null;
                                $google_business_pkg = null;
                                
                                foreach ($add_ons as $addon) {
                                    if ($addon['service_slug'] === 'domain-email-setup') {
                                        $domain_pkg = $addon;
                                    } elseif ($addon['service_slug'] === 'content-creation-diy') {
                                        $diy_pkg = $addon;
                                    } elseif ($addon['service_slug'] === 'google-business-profile') {
                                        $google_business_pkg = $addon;
                                    }
                                }
                                ?>
                                
                                <!-- Domain/Email Checkbox -->
                                <?php if ($domain_pkg): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input service-checkbox" 
                                                   type="checkbox" 
                                                   id="add_domain" 
                                                   name="add_domain_email"
                                                   value="1"
                                                   data-slug="domain-email-setup"
                                                   data-price="<?= $domain_pkg['base_price'] ?>">
                                            <label class="form-check-label w-100" for="add_domain">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($domain_pkg['service_name']) ?></h6>
                                                        <p class="text-muted small mb-0"><?= htmlspecialchars($domain_pkg['service_description']) ?></p>
                                                    </div>
                                                    <div class="text-end ms-3">
                                                        <h5 class="text-primary mb-0">$<?= number_format($domain_pkg['base_price'], 2) ?></h5>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Google Business Profile Checkbox -->
                                <?php if ($google_business_pkg): 
                                    $features = isset($google_business_pkg['features']) ? $google_business_pkg['features'] : [];
                                ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input service-checkbox" 
                                                   type="checkbox" 
                                                   id="add_google" 
                                                   name="add_google_business"
                                                   value="1"
                                                   data-slug="google-business-profile"
                                                   data-price="<?= $google_business_pkg['base_price'] ?>">
                                            <label class="form-check-label w-100" for="add_google">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($google_business_pkg['service_name']) ?></h6>
                                                        <p class="text-muted small mb-0"><?= htmlspecialchars($google_business_pkg['service_description']) ?></p>
                                                    </div>
                                                    <div class="text-end ms-3">
                                                        <h5 class="text-primary mb-0">$<?= number_format($google_business_pkg['base_price'], 2) ?></h5>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($features)): ?>
                                                <div class="mt-3">
                                                    <strong class="text-secondary">Includes:</strong>
                                                    <ul class="mb-0 mt-2" style="columns: 2; -webkit-columns: 2; -moz-columns: 2;">
                                                        <?php foreach ($features as $feature): ?>
                                                        <li class="small"><?= htmlspecialchars($feature) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- DIY Content Creation Checkbox -->
                                <?php if ($diy_pkg): 
                                    $features = isset($diy_pkg['features']) ? $diy_pkg['features'] : [];
                                ?>
                                <div class="card mb-3" style="border-left: 4px solid #ffc107;">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input service-checkbox" 
                                                   type="checkbox" 
                                                   id="add_diy" 
                                                   name="add_diy_content_creation"
                                                   value="1"
                                                   data-slug="content-creation-diy"
                                                   data-price="<?= $diy_pkg['base_price'] ?>"
                                                   <?= !empty($pipeline['add_diy_content_creation']) ? 'checked' : '' ?>>
                                            <label class="form-check-label w-100" for="add_diy">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($diy_pkg['service_name']) ?></h6>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars($diy_pkg['service_description']) ?></p>
                                                        
                                                        <?php if (!empty($features)): ?>
                                                        <div class="mt-2">
                                                            <strong class="text-secondary small">Includes:</strong>
                                                            <ul class="mb-0 mt-1 small">
                                                                <?php foreach ($features as $feature): ?>
                                                                <li class="text-muted"><?= htmlspecialchars($feature) ?></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                                                            <i class="bi bi-info-circle"></i> <strong>Note:</strong> This service requires a website package or Google Business Profile setup to be selected.
                                                        </div>
                                                    </div>
                                                    <div class="text-end ms-3">
                                                        <h5 class="text-warning mb-0">$<?= number_format($diy_pkg['base_price'], 2) ?></h5>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Hosting Preference (Informational Only) -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Hosting Preference (Informational)</label>
                                <p class="text-muted small">
                                    This information helps us prepare your project. You'll make your final hosting decision 
                                    after your website is complete in the Production phase. No payment required now.
                                </p>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="hosting_preference" id="host_yes" value="yes" 
                                           <?= ($pipeline['hosting_preference'] === 'yes' || empty($pipeline['hosting_preference'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="host_yes">
                                        <strong>I want The GlitchWizard to host my website!</strong>
                                        <small class="d-block text-muted">We'll handle hosting, security, hacking remediation, updates & backups for $50 a month. Includes unlimited contact and/or business name changes, plus any spelling, grammar & punctuation corrections. Other modifications are hourly.</small>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="hosting_preference" id="host_self" value="self" 
                                           <?= $pipeline['hosting_preference'] === 'self' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="host_self">
                                        <strong>I'll host the website myself</strong>
                                        <small class="d-block text-muted">You'll receive a ZIP file of your completed website</small>
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="hosting_preference" id="host_undecided" value="undecided" 
                                           <?= $pipeline['hosting_preference'] === 'undecided' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="host_undecided">
                                        <strong>I'm not sure yet</strong>
                                        <small class="d-block text-muted">We'll discuss options later</small>
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-arrow-right-circle"></i> Continue to Project Details
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pricing Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card position-sticky" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title">Your Estimate</h5>
                        
                        <div id="pricing-summary">
                            <div id="selected-services-list" class="mb-3">
                                <p class="text-muted small">Select services to see pricing</p>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total Cost:</strong>
                                <strong class="text-primary" id="total-price">$0.00</strong>
                            </div>
                            
                            <div id="deposit-section" class="alert alert-success" style="display: none;">
                                <strong>50% Deposit Required:</strong>
                                <div class="h4 mb-0" id="deposit-amount">$0.00</div>
                                <small class="text-muted">Remaining 50% due upon design approval</small>
                            </div>
                            
                            <div id="no-selection-alert" class="alert alert-warning">
                                <small><i class="bi bi-exclamation-triangle"></i> Please select at least one service</small>
                            </div>
                        </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Next Steps:</strong>
                            <ol class="mb-0 mt-2 ps-3">
                                <li>Select your package</li>
                                <li>Provide project details</li>
                                <li>Receive deposit invoice</li>
                                <li>Begin your project!</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.package-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}

.package-card.border-primary {
    border-width: 2px !important;
}

.form-check-input:checked ~ .form-check-label {
    color: #0d6efd;
}

/* Make radio buttons and checkboxes more visible */
.form-check-input {
    width: 1.25em;
    height: 1.25em;
    border: 2px solid #495057 !important;
    background-color: #ffffff !important;
}

.form-check-input:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

.form-check-input:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.form-check-input[type="radio"] {
    border-radius: 50%;
}

.form-check-input[type="checkbox"] {
    border-radius: 0.25em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const packageRadios = document.querySelectorAll('.package-radio');
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    const form = document.getElementById('service-selection-form');
    
    function updatePricing() {
        let total = 0;
        let selectedServices = [];
        let hasPackage = false;
        
        // Check for selected website package
        packageRadios.forEach(radio => {
            if (radio.checked && radio.value !== '') {
                hasPackage = true;
                const price = parseFloat(radio.dataset.price);
                let name = radio.closest('.card-body').querySelector('h5').textContent.trim();
                // Truncate long names
                if (name.length > 22) {
                    name = name.substring(0, 22) + '...';
                }
                selectedServices.push({
                    name: name,
                    price: price
                });
                total += price;
            }
        });
        
        // Check for selected additional services
        serviceCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const price = parseFloat(checkbox.dataset.price);
                const h6Element = checkbox.closest('.card-body').querySelector('h6');
                let name = h6Element ? h6Element.textContent.trim() : checkbox.nextElementSibling.textContent.trim();
                selectedServices.push({
                    name: name,
                    price: price
                });
                total += price;
            }
        });
        
        // Update selected services list
        const servicesList = document.getElementById('selected-services-list');
        if (selectedServices.length === 0) {
            servicesList.innerHTML = '<p class="text-muted small mb-0">Select services to see pricing</p>';
            document.getElementById('no-selection-alert').style.display = 'block';
            document.getElementById('deposit-section').style.display = 'none';
        } else {
            let html = '';
            selectedServices.forEach(service => {
                html += `<div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">${service.name}</span>
                    <span class="small">$${service.price.toFixed(2)}</span>
                </div>`;
            });
            servicesList.innerHTML = html;
            document.getElementById('no-selection-alert').style.display = 'none';
            
            // Show deposit section only if website package is selected
            if (hasPackage) {
                const deposit = total * 0.50;
                document.getElementById('deposit-amount').textContent = '$' + deposit.toFixed(2);
                document.getElementById('deposit-section').style.display = 'block';
            } else {
                document.getElementById('deposit-section').style.display = 'none';
            }
        }
        
        // Update total
        document.getElementById('total-price').textContent = '$' + total.toFixed(2);
    }
    
    // Form validation - require at least one service
    form.addEventListener('submit', function(e) {
        let hasSelection = false;
        let hasPackageOrGoogle = false;
        let hasDIY = false;
        
        // Check if any package is selected (not "No Package")
        packageRadios.forEach(radio => {
            if (radio.checked) {
                hasSelection = true;
                if (radio.value !== '') {
                    hasPackageOrGoogle = true;
                }
            }
        });
        
        // Check if any service checkbox is selected
        serviceCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                hasSelection = true;
                if (checkbox.id === 'add_google') {
                    hasPackageOrGoogle = true;
                }
                if (checkbox.id === 'add_diy') {
                    hasDIY = true;
                }
            }
        });
        
        if (!hasSelection) {
            e.preventDefault();
            alert('Please select at least one service (website package or standalone service) before continuing.');
            return false;
        }
        
        // Validate DIY Content Creation requires a package or Google Business Profile
        if (hasDIY && !hasPackageOrGoogle) {
            e.preventDefault();
            alert('"Do It For Me Content Creation" requires a website package or Google Business Profile setup to be selected.');
            return false;
        }
    });
    
    // Add event listeners
    packageRadios.forEach(radio => {
        radio.addEventListener('change', updatePricing);
        
        // Make card clickable (if it has package-card class)
        const card = radio.closest('.package-card');
        if (card) {
            card.addEventListener('click', function(e) {
                if (e.target.type !== 'radio') {
                    radio.checked = true;
                    updatePricing();
                    
                    // Update card borders
                    document.querySelectorAll('.package-card').forEach(c => c.classList.remove('border-primary'));
                    card.classList.add('border-primary');
                }
            });
        }
    });
    
    serviceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updatePricing);
    });
    
    // Initial update
    updatePricing();
});
</script>

<?php include includes_path . 'footer-close.php'; ?>
