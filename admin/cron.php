<?php
/*
NOTICE: Please read!
You should create a new cron job and schedule this file to execute every 1 or 5 minute(s).
Ensure you change the cron_secret constant in the config file!
*/
set_time_limit(0);
include '../config.php';
include 'functions.php';
// Ensure the cron secret reflects the one in the configuration file
if (isset($_GET['cron_secret']) && $_GET['cron_secret'] == cron_secret) {
    try {
        $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
        // If there is an error with the connection, stop the script and display the error.
        exit('Failed to connect to database!');
    }
    // Declare variables
    $date = date('Y-m-d H:i:s');
    $cron_mails_per_request = cron_mails_per_request;
    // Retrieve all active campaigns from the database
    $stmt = $pdo->prepare('SELECT ci.*, c.id AS campaign_id, s.email, s.status AS subscriber_status, n.title, n.content, n.id AS newsletter_id FROM campaign_items ci JOIN campaigns c ON c.id = ci.campaign_id AND c.status = "Active" AND c.submit_date < :submit_date JOIN newsletters n ON n.id = c.newsletter_id JOIN subscribers s ON s.id = ci.subscriber_id WHERE ci.status = "Queued" ORDER BY ci.id ASC LIMIT :num_items');
    $stmt->bindParam('submit_date', $date);
    $stmt->bindParam('num_items', $cron_mails_per_request, PDO::PARAM_INT);
    $stmt->execute();
    $campaign_items = $stmt->fetchAll(PDO::FETCH_ASSOC);  
    // Iterate the campaign items
    foreach($campaign_items as $n => $item) {
        // Ensure the subscriber is subscribed
        if ($item['subscriber_status'] == 'Subscribed') {
            // Generate the unqiue code
            $code = sha1($item['campaign_id'] . $item['id'] . $item['email']);
            // Replace the template placeholders with the respective code
            $content = str_replace([
                '%open_tracking_code%',
                '%unsubscribe_link%',
                '%click_link%'
            ], [
                '<img src="' . website_url . 'tracking.php?action=open&id=' . $code . '" width="1" height="1" alt="">',
                website_url . 'tracking.php?action=unsubscribe&id=' . $code,
                website_url . 'tracking.php?action=click&id=' . $code . '&url='
            ], $item['content']);
            // Send newsletter to the recipient
            $response = send_newsletter($item['email'], $item['title'], $content);
            // If successfull
            if ($response == 'success') {
                // Mark the item as completed
                $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Completed", ci.fail_text = "" WHERE ci.id = ?');
                $stmt->execute([ $date, $item['id'] ]);
                // Update the last scheduled date for the respective newsletter
                $stmt = $pdo->prepare('UPDATE newsletters SET last_scheduled = ? WHERE id = ?');
                $stmt->execute([ $date, $item['newsletter_id'] ]);
            } else {
                // Failed! Mark the item as failed
                $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Failed", ci.fail_text = ? WHERE ci.id = ?');
                $stmt->execute([ $date, $response, $item['id'] ]);           
            }
        } else {
            // The user previously unsubscribed, so skip the user and mark the item as failed
            $msg = 'The user unsubscribed.';
            $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Failed", ci.fail_text = ? WHERE ci.id = ?');
            $stmt->execute([ $date, $msg, $item['id'] ]);             
        }
        // Sleep until the next iteration
        if (($n+1) < count($campaign_items)) {
            sleep(cron_sleep_per_request);
        }
    }
} else {
    exit('Invalid cron secret!');
}
?>