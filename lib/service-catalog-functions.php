<?php
/*******************************************************************************
 * SERVICE CATALOG FUNCTIONS
 * Helper functions for querying service_catalog table
 * Created: 2025-12-04
 * Purpose: Support pipeline invoice generation with standardized pricing
 ******************************************************************************/

// Prevent direct access
if (!defined('db_name')) {
    die('Configuration required');
}

/**
 * Get service price by slug
 * @param PDO $pdo Database connection
 * @param string $service_slug Service identifier (e.g., 'mvp-branded-website')
 * @return float|null Service price or null if not found
 */
function get_service_price($pdo, $service_slug) {
    try {
        $stmt = $pdo->prepare('
            SELECT base_price 
            FROM service_catalog 
            WHERE service_slug = ? 
            AND is_active = 1
        ');
        $stmt->execute([$service_slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? floatval($result['base_price']) : null;
    } catch (PDOException $e) {
        error_log("get_service_price error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get full service details by slug
 * @param PDO $pdo Database connection
 * @param string $service_slug Service identifier
 * @return array|null Service details or null if not found
 */
function get_service_details($pdo, $service_slug) {
    try {
        $stmt = $pdo->prepare('
            SELECT * 
            FROM service_catalog 
            WHERE service_slug = ? 
            AND is_active = 1
        ');
        $stmt->execute([$service_slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['features_json']) {
            $result['features'] = json_decode($result['features_json'], true);
        }
        
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("get_service_details error: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculate hosting price based on tier, website type, and billing cycle
 * @param PDO $pdo Database connection
 * @param string $tier Hosting tier (tier1, tier2, tier3, tier4)
 * @param string $website_type Website complexity (mvp, foundational, expanded)
 * @param string $billing_cycle Billing frequency (monthly, annual)
 * @param bool $add_termageddon Add Termageddon subscription (Tier 1 only)
 * @return float|null Hosting price or null if not found
 */
function calculate_hosting_price($pdo, $tier, $website_type, $billing_cycle = 'annual', $add_termageddon = false) {
    try {
        // Build service slug
        $service_slug = "hosting-{$tier}-{$website_type}";
        if ($tier !== 'tier1') {
            $service_slug .= "-{$billing_cycle}";
        }
        
        // Get base hosting price
        $price = get_service_price($pdo, $service_slug);
        
        if ($price === null) {
            return null;
        }
        
        // Add Termageddon if requested (Tier 1 only, as it's included in Tier 2-4)
        if ($tier === 'tier1' && $add_termageddon) {
            $termageddon_price = get_service_price($pdo, 'termageddon-subscription');
            if ($termageddon_price !== null) {
                $price += $termageddon_price;
            }
        }
        
        return $price;
    } catch (Exception $e) {
        error_log("calculate_hosting_price error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all services by category
 * @param PDO $pdo Database connection
 * @param string $category Service category (website, hosting, addon)
 * @return array Array of service records
 */
function get_services_by_category($pdo, $category) {
    try {
        $stmt = $pdo->prepare('
            SELECT * 
            FROM service_catalog 
            WHERE service_category = ? 
            AND is_active = 1
            ORDER BY sort_order ASC, base_price ASC
        ');
        $stmt->execute([$category]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode features JSON for each service
        foreach ($results as &$service) {
            if ($service['features_json']) {
                $service['features'] = json_decode($service['features_json'], true);
            }
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("get_services_by_category error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get website packages for service selection
 * @param PDO $pdo Database connection
 * @return array Array of website package services
 */
function get_website_packages($pdo) {
    return get_services_by_category($pdo, 'website');
}

/**
 * Get hosting options by tier
 * @param PDO $pdo Database connection
 * @param string $tier Hosting tier filter (optional)
 * @return array Array of hosting services
 */
function get_hosting_options($pdo, $tier = null) {
    try {
        $sql = '
            SELECT * 
            FROM service_catalog 
            WHERE service_category = "hosting" 
            AND is_active = 1
        ';
        
        if ($tier) {
            $sql .= ' AND service_slug LIKE ?';
            $stmt = $pdo->prepare($sql . ' ORDER BY sort_order ASC, base_price ASC');
            $stmt->execute(["hosting-{$tier}-%"]);
        } else {
            $stmt = $pdo->prepare($sql . ' ORDER BY sort_order ASC, base_price ASC');
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode features JSON for each service
        foreach ($results as &$service) {
            if ($service['features_json']) {
                $service['features'] = json_decode($service['features_json'], true);
            }
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("get_hosting_options error: " . $e->getMessage());
        return [];
    }
}

/**
 * Create invoice items from service slugs
 * @param PDO $pdo Database connection
 * @param array $service_slugs Array of service slug strings
 * @param array $quantities Optional array of quantities (defaults to 1)
 * @return array Array of invoice items with name, description, price, quantity
 */
function create_invoice_items_from_services($pdo, $service_slugs, $quantities = []) {
    $items = [];
    
    foreach ($service_slugs as $index => $slug) {
        $service = get_service_details($pdo, $slug);
        
        if ($service) {
            $quantity = isset($quantities[$index]) ? intval($quantities[$index]) : 1;
            
            // Build description from features
            $description = '';
            if (isset($service['features']) && is_array($service['features'])) {
                $description = implode(', ', array_slice($service['features'], 0, 3));
                if (count($service['features']) > 3) {
                    $description .= '...';
                }
            }
            
            $items[] = [
                'item_name' => $service['service_name'],
                'item_description' => $description,
                'item_price' => $service['base_price'],
                'item_quantity' => $quantity,
                'service_slug' => $service['service_slug']
            ];
        }
    }
    
    return $items;
}

/**
 * Calculate total price from service slugs
 * @param PDO $pdo Database connection
 * @param array $service_slugs Array of service slug strings
 * @param array $quantities Optional array of quantities (defaults to 1)
 * @return float Total price
 */
function calculate_total_from_services($pdo, $service_slugs, $quantities = []) {
    $total = 0.00;
    
    foreach ($service_slugs as $index => $slug) {
        $price = get_service_price($pdo, $slug);
        if ($price !== null) {
            $quantity = isset($quantities[$index]) ? intval($quantities[$index]) : 1;
            $total += ($price * $quantity);
        }
    }
    
    return $total;
}

/**
 * Get service name by slug (for display purposes)
 * @param PDO $pdo Database connection
 * @param string $service_slug Service identifier
 * @return string Service name or slug if not found
 */
function get_service_name($pdo, $service_slug) {
    $service = get_service_details($pdo, $service_slug);
    return $service ? $service['service_name'] : $service_slug;
}
