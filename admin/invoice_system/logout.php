<?php
// production  9/13/24
// refreshed   6/14/25
// re-deployed 6/15/25 VERIFIED
session_start();
// Destroy session data
session_destroy();
// Redirect to login page
header('Location: "https://glitchwizarddigitalsolutions.com"');
?>