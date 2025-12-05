<?php
/*******************************************************************************
 * PROCESS: Save Service Selection (Guest Level)
 * Saves the client's service selections (package and/or standalone services)
 * Created: 2025-12-04
 ******************************************************************************/

session_start();
require_once '../../../../private/config.php';

// Load main.php to establish database connection
require_once public_path . 'client-dashboard/assets/includes/main.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header('Location: ../../index.php');
    exit;
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../guest-service-selection.php?error=invalid_request');
    exit;
}

// Debug logging
error_log("Service Selection POST data: " . print_r($_POST, true));

// Get form data
$service_selected = $_POST['service_selected'] ?? null; // Website package (optional)
$add_domain = isset($_POST['add_domain_email']) ? 1 : 0;
$add_google = isset($_POST['add_google_business']) ? 1 : 0;
$add_diy = isset($_POST['add_diy_content_creation']) ? 1 : 0;
$hosting_preference = $_POST['hosting_preference'] ?? 'undecided';

error_log("Parsed values - service_selected: $service_selected, add_domain: $add_domain, add_google: $add_google, add_diy: $add_diy");

// Build array of all selected services for JSON storage
$selected_services = [];
if (!empty($service_selected)) {
    $selected_services[] = $service_selected;
}
if ($add_domain) {
    $selected_services[] = 'domain-email-setup';
}
if ($add_google) {
    $selected_services[] = 'google-business-profile';
}
if ($add_diy) {
    $selected_services[] = 'content-creation-diy';
}

error_log("Selected services array: " . print_r($selected_services, true));

// Validate - must have at least one service
if (empty($selected_services)) {
    error_log("Validation failed - no services selected");
    header('Location: ../guest-service-selection.php?error=no_service');
    exit;
}

// Determine website type from service slug (if package selected)
$website_type = null;
if (!empty($service_selected)) {
    if (strpos($service_selected, 'mvp') !== false) {
        $website_type = 'mvp';
    } elseif (strpos($service_selected, 'foundational') !== false) {
        $website_type = 'foundational';
    } elseif (strpos($service_selected, 'expanded') !== false) {
        $website_type = 'expanded';
    } elseif (strpos($service_selected, 'adhd') !== false) {
        $website_type = 'adhd';
    } else {
        $website_type = 'other';
    }
}

try {
    // Check if pipeline status exists
    $stmt = $pdo->prepare('SELECT id FROM client_pipeline_status WHERE acc_id = ?');
    $stmt->execute([$_SESSION['id']]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare('
            UPDATE client_pipeline_status 
            SET service_selected = ?,
                website_type = ?,
                add_domain_email = ?,
                add_google_business = ?,
                add_diy_content_creation = ?,
                hosting_preference = ?,
                current_step = ?,
                service_selection_date = NOW(),
                updated_at = NOW()
            WHERE acc_id = ?
        ');
        $stmt->execute([
            $service_selected,
            $website_type,
            $add_domain,
            $add_google,
            $add_diy,
            $hosting_preference,
            'project_details',
            $_SESSION['id']
        ]);
    } else {
        // Create new record
        $stmt = $pdo->prepare('
            INSERT INTO client_pipeline_status 
            (acc_id, access_level, service_selected, website_type, add_domain_email, 
             add_google_business, add_diy_content_creation, hosting_preference, 
             current_step, service_selection_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ');
        
        // Get current access level
        $stmt2 = $pdo->prepare('SELECT access_level FROM accounts WHERE id = ?');
        $stmt2->execute([$_SESSION['id']]);
        $account = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        $stmt->execute([
            $_SESSION['id'],
            $account['access_level'] ?? 'Guest',
            $service_selected,
            $website_type,
            $add_domain,
            $add_google,
            $add_diy,
            $hosting_preference,
            'project_details'
        ]);
    }
    
    // Redirect to project details form
    header('Location: ../guest-project-details.php?success=service_saved');
    exit;
    
} catch (PDOException $e) {
    error_log("Service selection save error: " . $e->getMessage());
    header('Location: ../guest-service-selection.php?error=save_failed');
    exit;
}
