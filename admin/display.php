<?php
require "../../private/config.php";
require "../../public_html/lib/database-system.php";

try {
    $onthego_db = get_onthego_db();
    $stmt = $onthego_db->query("SELECT * FROM not_important");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo htmlspecialchars($row['resource'], ENT_QUOTES), '<br>';
    }
} catch (Exception $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES);
}