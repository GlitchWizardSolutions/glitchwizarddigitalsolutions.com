<?php
/*******************************************************************************
 * ADMIN: Manage Service Catalog
 * Edit pricing, descriptions, and features for all services
 * Location: /admin/invoice_system/manage-services.php
 * Created: 2025-12-04
 ******************************************************************************/

include 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $service_id = (int)$_POST['service_id'];
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description']);
    $base_price = (float)$_POST['base_price'];
    $billing_frequency = $_POST['billing_frequency'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];
    
    // Handle features array
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feature) {
            $feature = trim($feature);
            if (!empty($feature)) {
                $features[] = $feature;
            }
        }
    }
    $features_json = json_encode($features);
    
    try {
        $stmt = $pdo->prepare('
            UPDATE service_catalog 
            SET service_name = ?,
                service_description = ?,
                base_price = ?,
                billing_frequency = ?,
                features_json = ?,
                is_active = ?,
                sort_order = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $service_name,
            $service_description,
            $base_price,
            $billing_frequency,
            $features_json,
            $is_active,
            $sort_order,
            $service_id
        ]);
        
        $success_msg = "Service updated successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error updating service: " . $e->getMessage();
    }
}

// Get all services
$stmt = $pdo->query('
    SELECT * FROM service_catalog 
    ORDER BY service_category, sort_order, service_name
');
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by category
$grouped_services = [];
foreach ($services as $service) {
    $category = $service['service_category'];
    if (!isset($grouped_services[$category])) {
        $grouped_services[$category] = [];
    }
    $grouped_services[$category][] = $service;
}
?>

<?=template_admin_header('Manage Service Catalog', 'invoices', 'manage-services')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoice System', 'url' => 'invoices.php'],
    ['label' => 'Manage Services']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-tags"></i>
        <div class="txt">
            <h2>Manage Service Catalog</h2>
            <p>Edit pricing, descriptions, and features for all services</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=htmlspecialchars($success_msg)?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<?php if (isset($error_msg)): ?>
<div class="msg error">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
    <p><?=htmlspecialchars($error_msg)?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-block">
    <?php foreach ($grouped_services as $category => $category_services): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title text-capitalize"><?= htmlspecialchars($category) ?> Services</h5>
            
            <div class="accordion" id="accordion<?= str_replace(' ', '', $category) ?>">
                <?php foreach ($category_services as $index => $service): 
                    $features = json_decode($service['features_json'], true) ?? [];
                    $accordionId = 'service' . $service['id'];
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordionId ?>">
                                <?= htmlspecialchars($service['service_name']) ?> ($<?= number_format($service['base_price'], 2) ?>)
                                <?php if (!$service['is_active']): ?>
                                <span class="badge bg-secondary ms-2">Inactive</span>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="<?= $accordionId ?>" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Service Name</label>
                                            <input type="text" class="form-control" name="service_name" 
                                                   value="<?= htmlspecialchars($service['service_name']) ?>" required>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" name="base_price" 
                                                       value="<?= $service['base_price'] ?>" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Billing</label>
                                            <select class="form-select" name="billing_frequency">
                                                <option value="one-time" <?= $service['billing_frequency'] === 'one-time' ? 'selected' : '' ?>>One-time</option>
                                                <option value="monthly" <?= $service['billing_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="annual" <?= $service['billing_frequency'] === 'annual' ? 'selected' : '' ?>>Annual</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="service_description" rows="2" required><?= htmlspecialchars($service['service_description']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Features/Includes (one per line)</label>
                                        <div id="features-container-<?= $service['id'] ?>">
                                            <?php foreach ($features as $feature): ?>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" name="features[]" 
                                                       value="<?= htmlspecialchars($feature) ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
                                                    <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: currentColor;"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="addFeatureField(<?= $service['id'] ?>)">
                                            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: currentColor; vertical-align: middle;"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" /></svg>
                                            Add Feature
                                        </button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sort Order</label>
                                            <input type="number" class="form-control" name="sort_order" 
                                                   value="<?= $service['sort_order'] ?>" min="0">
                                            <small class="text-muted">Lower numbers appear first</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active" 
                                                       id="active<?= $service['id'] ?>" <?= $service['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="active<?= $service['id'] ?>">
                                                    Active (visible to clients)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_service" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Update Service
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
</div>

<script>
function addFeatureField(serviceId) {
    const container = document.getElementById('features-container-' + serviceId);
    const newField = document.createElement('div');
    newField.className = 'input-group mb-2';
    newField.innerHTML = `
        <input type="text" class="form-control" name="features[]" placeholder="Enter feature">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.remove()">
            <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: currentColor;"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
        </button>
    `;
    container.appendChild(newField);
}
</script>

<?=template_admin_footer()?>
