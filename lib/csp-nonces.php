<?php
/**
 * CSP Nonce System
 *
 * Generates and manages cryptographically secure nonces for Content Security Policy.
 * Nonces allow specific inline scripts and styles while maintaining security.
 */

/**
 * Generate a cryptographically secure nonce
 */
function generate_csp_nonce() {
    return bin2hex(random_bytes(16));
}

/**
 * Get or create a CSP nonce for the current request
 */
function get_csp_nonce() {
    if (!isset($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = generate_csp_nonce();
    }
    return $_SESSION['csp_nonce'];
}

/**
 * Output CSP nonce field for forms
 */
function csp_nonce_field() {
    $nonce = get_csp_nonce();
    echo '<input type="hidden" name="csp_nonce" value="' . htmlspecialchars($nonce) . '">';
}

/**
 * Validate CSP nonce from request
 */
function validate_csp_nonce() {
    if (!isset($_POST['csp_nonce']) || !isset($_SESSION['csp_nonce'])) {
        return false;
    }
    return hash_equals($_SESSION['csp_nonce'], $_POST['csp_nonce']);
}

/**
 * Output script tag with CSP nonce
 */
function script_with_nonce($script, $attributes = []) {
    $nonce = get_csp_nonce();
    $attrString = '';

    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }

    echo '<script nonce="' . htmlspecialchars($nonce) . '"' . $attrString . '>' . $script . '</script>';
}

/**
 * Output style tag with CSP nonce
 */
function style_with_nonce($css, $attributes = []) {
    $nonce = get_csp_nonce();
    $attrString = '';

    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }

    echo '<style nonce="' . htmlspecialchars($nonce) . '"' . $attrString . '>' . $css . '</style>';
}

/**
 * Get CSP header with nonce support
 */
function get_csp_header_with_nonce() {
    $nonce = get_csp_nonce();
    $reportUri = BASE_URL . 'csp-report.php';

    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        // Development: Allow unsafe-eval for debugging, use nonce for inline content
        return "Content-Security-Policy: default-src 'self' 'unsafe-eval'; " .
               "script-src 'self' 'unsafe-eval' 'nonce-$nonce' https://www.googletagmanager.com https://static.cloudflareinsights.com https://www.paypal.com; " .
               "style-src 'self' 'unsafe-inline' 'nonce-$nonce' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https: http:; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "connect-src 'self' https://www.google-analytics.com https://www.paypal.com; " .
               "frame-src 'self' https:; " .
               "report-uri $reportUri;";
    } else {
        // Production: Strict CSP with nonce for inline content
        return "Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'nonce-$nonce' https://www.googletagmanager.com https://static.cloudflareinsights.com https://www.paypal.com; " .
               "style-src 'self' 'nonce-$nonce' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "connect-src 'self' https://www.google-analytics.com https://www.paypal.com; " .
               "report-uri $reportUri;";
    }
}

/**
 * Update security headers to use CSP with nonces
 */
function set_security_headers_with_nonces() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy with nonces
    header(get_csp_header_with_nonce());

    // HSTS (HTTP Strict Transport Security) - only if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Example usage functions for common inline scripts
 */
function get_common_inline_scripts() {
    return [
        'ajax_setup' => '
            // AJAX setup for CSRF tokens
            $.ajaxSetup({
                headers: {
                    "X-CSRF-Token": "' . get_csp_nonce() . '"
                }
            });
        ',
        'form_validation' => '
            // Basic form validation
            function validateForm(form) {
                const inputs = form.querySelectorAll("input[required]");
                for (let input of inputs) {
                    if (!input.value.trim()) {
                        alert("Please fill in all required fields");
                        input.focus();
                        return false;
                    }
                }
                return true;
            }
        '
    ];
}

/**
 * Output common inline scripts with nonces
 */
function output_common_scripts() {
    $scripts = get_common_inline_scripts();

    foreach ($scripts as $name => $script) {
        script_with_nonce($script, ['id' => 'script-' . $name]);
    }
}