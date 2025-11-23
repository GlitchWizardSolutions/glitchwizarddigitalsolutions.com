<?php
/**
 * Reusable UI Components for Admin System
 * Created: November 21, 2025
 */

/**
 * Generate breadcrumb navigation
 * @param array $crumbs Array of ['label' => 'Text', 'url' => 'link.php'] or ['title' => 'Text', 'url' => 'link.php']
 * @return string HTML breadcrumb
 */
function generate_breadcrumbs($crumbs) {
    $html = '<div class="breadcrumbs">';
    $html .= '<a href="../../index.php">Dashboard</a>';
    
    foreach ($crumbs as $index => $crumb) {
        $html .= '<span class="separator">›</span>';
        // Support both 'label' and 'title' keys
        $text = isset($crumb['label']) ? $crumb['label'] : (isset($crumb['title']) ? $crumb['title'] : '');
        if (isset($crumb['url']) && $index < count($crumbs) - 1) {
            $html .= '<a href="' . htmlspecialchars($crumb['url']) . '">' 
                  . htmlspecialchars($text) . '</a>';
        } else {
            $html .= '<span class="current">' . htmlspecialchars($text) . '</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Professional page header with icon
 * @param string $title Page title
 * @param string $subtitle Page description
 * @param string $icon_svg SVG icon HTML
 * @return string HTML header
 */
function professional_page_header($title, $subtitle, $icon_svg) {
    return '
    <div class="content-title">
        <div class="icon alt">' . $icon_svg . '</div>
        <div class="txt">
            <h2>' . htmlspecialchars($title) . '</h2>
            <p class="subtitle">' . htmlspecialchars($subtitle) . '</p>
        </div>
    </div>';
}

/**
 * Status badge generator
 * @param string $status Status text
 * @param string $type Badge color type (optional override)
 * @return string HTML badge
 */
function status_badge($status, $type = 'default') {
    $colors = [
        'open' => 'green',
        'closed' => 'grey',
        'resolved' => 'blue',
        'paid' => 'green',
        'unpaid' => 'red',
        'pending' => 'orange',
        'active' => 'green',
        'inactive' => 'grey',
        'draft' => 'grey',
        'published' => 'green',
        'archived' => 'grey',
        'overdue' => 'red',
        'sent' => 'blue',
        'partial' => 'orange'
    ];
    
    $color = $colors[strtolower($status)] ?? $colors[$type] ?? 'grey';
    
    return '<span class="badge badge-' . $color . '">' . htmlspecialchars(ucwords($status)) . '</span>';
}

/**
 * Common SVG Icons
 */
function svg_icon_invoice() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" />
    </svg>';
}

function svg_icon_user() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M12 4a4 4 0 0 1 4 4 4 4 0 0 1-4 4 4 4 0 0 1-4-4 4 4 0 0 1 4-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4z"/>
    </svg>';
}

function svg_icon_email() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" />
    </svg>';
}

function svg_icon_settings() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" />
    </svg>';
}

function svg_icon_upload() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="17 8 12 3 7 8"></polyline>
        <line x1="12" y1="3" x2="12" y2="15"></line>
    </svg>';
}

function svg_icon_download() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>';
}

function svg_icon_newsletter() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M20 2H4a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM6 4h12v2H6V4zm0 4h12v2H6V8zm0 4h8v2H6v-2zm0 4h10v2H6v-2z"/>
    </svg>';
}

function svg_icon_budget() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
    </svg>';
}

function svg_icon_blog() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M3 3v18h18V3H3zm16 16H5V5h14v14zm-2-2H7V7h10v10zm-2-8H9v2h6V9zm0 4H9v2h6v-2z"/>
    </svg>';
}

function svg_icon_content() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11zM8 15h8v2H8v-2zm0-4h8v2H8v-2zm0-4h5v2H8V7z"/>
    </svg>';
}

function svg_icon_dashboard() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24">
        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
    </svg>';
}
