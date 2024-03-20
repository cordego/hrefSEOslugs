<?php
/**
 * Plugin Name: Href Slugs
 * Description: Manage hreflang slugs for different domains.
 * Version: 1.0
 * Author: Cordego LTD.
 */

require_once plugin_dir_path(__FILE__) . 'hrefslugs-settings.php';

// Activation Hook - Create the hrefslugs table
function hrefslugs_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hrefslugs';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        master_slug varchar(255) NOT NULL,
        slave_slug varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add options for hreflang and slave domain
    add_option('hrefslugs_hreflang', '');
    add_option('hrefslugs_slave_domain', '');
}

register_activation_hook(__FILE__, 'hrefslugs_activate');

// Function to add the settings page to the WordPress admin menu
function hrefslugs_add_admin_menu() {
    add_menu_page(
        'Href Slugs Settings',
        'Href Slugs',
        'manage_options',
        'hrefslugs-settings',
        'hrefslugs_settings_page',
        'dashicons-admin-generic',
        6
    );
}

add_action('admin_menu', 'hrefslugs_add_admin_menu');

function hrefslugs_insert_alternate_link() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hrefslugs';

    $current_path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_path = rtrim($current_path, '/');
    $query = $wpdb->prepare("SELECT * FROM `$table_name` WHERE `master_slug` = %s", $current_path);
    $result = $wpdb->get_row($query);

    // Debugging line: Remove after testing
    error_log('Current Path: ' . $current_path . '; Query Result: ' . print_r($result, true));

    if ($result) {
        $slave_domain = get_option('hrefslugs_slave_domain');
        echo '<link rel="alternate" href="https://' . esc_attr($slave_domain) . esc_attr($result->slave_slug) . '" hreflang="' . esc_attr(get_option('hrefslugs_hreflang')) . '" />';
    }
}

add_action('wp_head', 'hrefslugs_insert_alternate_link');