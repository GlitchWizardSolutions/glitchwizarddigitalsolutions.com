<?php
session_start();
require_once '../private/config.php';

// Load unified email system
require_once public_path . 'lib/email-system.php';

include includes_path . 'main.php';
include includes_path . 'public-page-setup.php';
?>