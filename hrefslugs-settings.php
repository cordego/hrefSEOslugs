<?php
function hrefslugs_admin_init() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hrefslugs';

    if (isset($_GET['page'], $_GET['delete']) && $_GET['page'] === 'hrefslugs-settings' && current_user_can('manage_options')) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=hrefslugs-settings'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type']) && $_POST['action_type'] == 'edit' && check_admin_referer('hrefslugs-action')) {
        $id = intval($_POST['id']);
        $master_slug = sanitize_text_field($_POST['master_slug']);
        $slave_slug = sanitize_text_field($_POST['slave_slug']);
        $wpdb->update($table_name, ['master_slug' => $master_slug, 'slave_slug' => $slave_slug], ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=hrefslugs-settings'));
        exit;
    }
}

add_action('admin_init', 'hrefslugs_admin_init');

function hrefslugs_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hrefslugs';

    $edit_mode = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_admin_referer('hrefslugs-action') && (!isset($_POST['action_type']) || $_POST['action_type'] !== 'edit')) {
        if (isset($_POST['action_type']) && $_POST['action_type'] == 'add') {
            $master_slug = sanitize_text_field($_POST['master_slug']);
            $slave_slug = sanitize_text_field($_POST['slave_slug']);

            $existing_master = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE master_slug = %s", $master_slug));
            $existing_slave = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE slave_slug = %s", $slave_slug));

            if ($existing_master == 0 && $existing_slave == 0) {
                $wpdb->insert($table_name, [
                    'master_slug' => $master_slug,
                    'slave_slug' => $slave_slug
                ]);
            } else {
                echo '<div class="error"><p>Error: Both Master Slug and Slave Slug must be unique.</p></div>';
            }
        } elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'settings') {
            update_option('hrefslugs_hreflang', sanitize_text_field($_POST['hreflang']));
            update_option('hrefslugs_slave_domain', sanitize_text_field($_POST['slave_domain']));
        }
    }

    $entries = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<table border="1" style="width: 100%;">';
    echo '<tr><th>ID</th><th>Master Slug</th><th>Slave Slug</th><th>Actions</th></tr>';
    foreach ($entries as $entry) {
        echo '<tr>';
        echo '<td>' . esc_html($entry->id) . '</td>';
        echo '<td>' . esc_html($entry->master_slug) . '</td>';
        echo '<td>' . esc_html($entry->slave_slug) . '</td>';
        echo '<td><a href="?page=hrefslugs-settings&edit=' . esc_attr($entry->id) . '">Edit</a> | <a href="?page=hrefslugs-settings&delete=' . esc_attr($entry->id) . '">Delete</a></td>';
        echo '</tr>';
    }
    echo '</table>';

    if ($edit_mode) {
        $entry_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_mode));
    }
    echo '<form method="POST" action="">';
    wp_nonce_field('hrefslugs-action');
    echo '<input type="hidden" name="action_type" value="' . ($edit_mode ? 'edit' : 'add') . '">';
    if ($edit_mode) {
        echo '<input type="hidden" name="id" value="' . esc_attr($edit_mode) . '">';
    }
    echo '<input type="text" name="master_slug" placeholder="Master Slug" value="' . ($edit_mode ? esc_attr($entry_to_edit->master_slug) : '') . '">';
    echo '<input type="text" name="slave_slug" placeholder="Slave Slug" value="' . ($edit_mode ? esc_attr($entry_to_edit->slave_slug) : '') . '">';
    echo '<input type="submit" value="' . ($edit_mode ? 'Update' : 'Add') . '">';
    echo '</form>';

    echo '<h2>Settings</h2>';
    echo '<form method="POST" action="">';
    wp_nonce_field('hrefslugs-action');
    echo '<input type="hidden" name="action_type" value="settings">';
    echo '<label for="hreflang">Hreflang:</label>';
    echo '<input type="text" name="hreflang" value="' . esc_attr(get_option('hrefslugs_hreflang')) . '">';
    echo '<label for="slave_domain">Slave Domain:</label>';
    echo '<input type="text" name="slave_domain" value="' . esc_attr(get_option('hrefslugs_slave_domain')) . '">';
    echo '<input type="submit" value="Save Settings">';
    echo '</form>';
}