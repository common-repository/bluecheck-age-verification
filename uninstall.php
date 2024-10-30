<?php

/**
 * This is executed when the plugin in uninstalled.
 * (A different technique is to use register_uninstall_hook(), but using uninstall.php is preferred.)
 */

// If uninstall is not called from WordPress, exit
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

/*
$option_name = 'plugin_option_name';
 
delete_option($option_name);
 
// For site options in Multisite
delete_site_option($option_name);  
*/

/*
// Drop a custom db table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mytable" );
*/
