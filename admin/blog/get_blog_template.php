<?php
require 'assets/includes/admin_config.php';

header('Content-Type: application/json');

// Check if listing templates or getting specific template
if (isset($_GET['list'])) {
    // List all active templates
    $stmt = $blog_pdo->query('SELECT id, title FROM blog_templates WHERE active = "Yes" ORDER BY title ASC');
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($templates);
} elseif (isset($_GET['id'])) {
    // Get specific template content
    $template_id = (int) $_GET['id'];

    $stmt = $blog_pdo->prepare('SELECT content FROM blog_templates WHERE id = ? AND active = "Yes"');
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($template) {
        echo json_encode(['content' => $template['content']]);
    } else {
        echo json_encode(['error' => 'Template not found or inactive']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>