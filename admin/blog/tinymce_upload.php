<?php
require 'assets/includes/admin_config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
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

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Return the absolute URL for TinyMCE
    $base_url = 'https://glitchwizarddigitalsolutions.com';
    $location = $base_url . '/client-dashboard/blog/uploads/images/' . $filename;

    // Make sure we only output JSON
    if (ob_get_length()) ob_clean();
    echo json_encode(['location' => $location]);
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['error' => 'Failed to save file']);
}
?>