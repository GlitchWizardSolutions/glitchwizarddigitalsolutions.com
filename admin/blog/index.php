<?php
require 'assets/includes/admin_config.php';
    // Get the account from the database
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = $blog_pdo->prepare("SELECT * FROM `users` WHERE username = ? AND (role = 'Admin' OR role = 'Editor')");
    $stmt->execute([$uname]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ../../blog/login');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
} else {
    header('Location: ../../blog/login');
    exit;
}
?>