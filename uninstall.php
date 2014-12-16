<?php

// If this file is called during an uninstall, we want to carry on...
if (defined('WP_UNINSTALL_PLUGIN')) {

    $option_name = 'scribble_db_version';

    // Firstly delete the general options...
    delete_option($option_name);

    // For site options in multisite
    delete_site_option($option_name);

    global $wpdb;
    $table_name = $wpdb->prefix . 'scribblemaps';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}