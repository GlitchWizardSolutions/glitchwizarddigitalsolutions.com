<?php
require 'assets/includes/admin_config.php';
    // Get the account from the database
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $suser = mysqli_query($connect, "SELECT * FROM `users` WHERE username='$uname' AND (role='Admin' || role='Editor')");
    $count = mysqli_num_rows($suser);
    if ($count <= 0) {
        echo '<meta http-equiv="refresh" content="0; url=../../blog/login" />';
        exit;
    } else {
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php" />';
    }
} else {
     echo '<meta http-equiv="refresh" content="0; url=../../blog/login" />';
    exit;
}
?>