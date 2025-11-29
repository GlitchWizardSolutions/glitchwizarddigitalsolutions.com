<?php
require 'assets/includes/admin_config.php';

// Check if user is logged in via main SSO
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../index.php');
    exit;
}

// Get the account from the database
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has admin or appropriate access level for blog management
if (!$account || !in_array($account['access_level'], ['Admin', 'Master', 'Services', 'Production', 'Development'])) {
    header('Location: ../../index.php?error=access_denied');
    exit;
}

// User is authorized, redirect to dashboard
header('Location: dashboard.php');
exit;
?>