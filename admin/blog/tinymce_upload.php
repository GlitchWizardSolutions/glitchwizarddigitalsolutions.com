<?php
require 'assets/includes/admin_config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get list of uploaded images for the image browser
if (isset($_GET['list_images'])) {
    $upload_dir = '../../client-dashboard/blog/uploads/images/';
    $images = [];
    
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        // Use the configured blog uploads URL
        $base_url = rtrim(BASE_URL, '/') . blog_uploads_url . 'images/';
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filepath = $upload_dir . $file;
                if (is_file($filepath) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $file)) {
                    $images[] = [
                        'title' => $file,
                        'value' => $base_url . $file,
                        'modified' => filemtime($filepath)
                    ];
                }
            }
        }
        
        // Sort by modification time (newest first)
        usort($images, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    
    echo json_encode($images);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) && !isset($_FILES['newsletter_image'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = isset($_FILES['file']) ? $_FILES['file'] : $_FILES['newsletter_image'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error: ' . $file['error']]);
    exit;
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, WebP, and SVG are allowed.']);
    exit;
}

// Check file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = '../../client-dashboard/blog/uploads/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate filename preserving original name with numbering for duplicates
$original_name = pathinfo($file['name'], PATHINFO_FILENAME);
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = $original_name . '.' . $extension;

// Check if file already exists and add number if needed
$counter = 1;
while (file_exists($upload_dir . $filename)) {
    $filename = $original_name . ' (' . $counter . ').' . $extension;
    $counter++;
}

$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Return the absolute URL for TinyMCE using configured URLs
    $base_url = rtrim(BASE_URL, '/') . blog_uploads_url . 'images/';
    $location = $base_url . $filename;

    // Make sure we only output JSON
    if (ob_get_length()) ob_clean();
    echo json_encode(['location' => $location]);
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['error' => 'Failed to save file']);
}
?>