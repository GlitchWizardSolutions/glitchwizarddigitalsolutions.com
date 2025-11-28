<?php
require 'assets/includes/admin_config.php';

header('Content-Type: application/json');

// Check if template ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Template ID not provided']);
    exit;
}

$template_id = (int) $_GET['id'];

// Get template content
$stmt = $blog_pdo->prepare('SELECT content FROM blog_templates WHERE id = ? AND active = "Yes"');
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if ($template) {
    echo json_encode(['content' => $template['content']]);
} else {
    echo json_encode(['error' => 'Template not found or inactive']);
}
?>