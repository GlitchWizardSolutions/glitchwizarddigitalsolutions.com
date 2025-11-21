<?php
@session_start();
if(file_exists('../../../private/blog_config2025.php')){
include ('../../../private/blog_config2025.php');
}else{
    error_log('../../../private/blog_config2025.php does not exist.');
}
include '../../client-dashboard/blog/config_settings.php';
include admin_includes_path . 'main.php';
include admin_includes_path . 'admin_page_setup.php';
include process_path . 'email-process.php';?>