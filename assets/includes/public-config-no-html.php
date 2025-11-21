<?php
/**
 * Public Config - No HTML Output
 * Use this for pages that need to send headers/emails before HTML output
 */
session_start();
require_once '../private/config.php';
include includes_path . 'main.php';
// NOTE: Does NOT include public-page-setup.php to avoid HTML output
?>
