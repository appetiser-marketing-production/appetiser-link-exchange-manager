<?php
/**
 * Plugin Name: Appetiser Link Exchange Manager
 * Plugin URI:  https://appetiser.com.au
 * Description: A centralized tool to automatically insert and manage external links in blog content for link exchanges and monitor outbound links provided to partners.
 * Version: 1.0.0
 * Author: Landing page team
 * Author URI: https://appetiser.com.au
 * License: GPL v3
*  License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

 if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_action('admin_init', function() {
    if ( !is_plugin_active('appetiser-common-assets/appetiser-common-assets.php') ) {
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', function() {
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_name = $plugin_data['Name'] ?? 'This plugin';
            echo '<div class="notice notice-error"><p><strong>Error:</strong> <code>' . esc_html($plugin_name) . '</code> requires <code>appetiser-common-assets</code> to be installed and active.</p></div>';
        });
    }
});

// Include admin class
require_once plugin_dir_path(__FILE__) . 'admin/app-lm-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/app-lm-public.php';

// Initialize admin
if (is_admin()) {
    new Appetiser_Link_Mapper_Admin();
} else {
    // Init frontend filter
    new Appetiser_Link_Mapper_Public();
}