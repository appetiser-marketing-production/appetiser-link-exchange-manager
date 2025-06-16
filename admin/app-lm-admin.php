<?php

require_once WP_PLUGIN_DIR . '/appetiser-common-assets/inc/class-googlesheetreader-wrapper.php';

class Appetiser_Link_Mapper_Admin {
 
    public function __construct() {
        add_action( 'admin_menu',  array( $this, 'add_plugin_menu' ) );
        add_action( 'admin_enqueue_scripts', array(  $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array(  $this, 'enqueue_scripts' ) );

        add_action('admin_init', [$this, 'maybe_import_list']);

        add_action('wp_ajax_app_lm_check_url', [$this, 'handle_ajax_check_url']);

        add_action('admin_post_app_lm_export_csv', [$this, 'handle_csv_export']);
    }

    public function enqueue_styles( $hook ) {
        //if ($hook !== 'tools_page_appetiser-link-mapper') return;
        if (!isset($_GET['page']) || $_GET['page'] !== 'appetiser-link-mapper') {
            return;
        }
        
        wp_enqueue_style('dashicons');

        wp_enqueue_style( 'appetiser-dashboard-style', plugins_url() . '/appetiser-common-assets/admin/css/appetiser-dashboard.css', array(), '1.0.0', 'all' );
        wp_enqueue_style( 'appetiser-link-exchange-style', plugin_dir_url( __FILE__ ) . 'css/app-link-exchange-admin.css', array(), '1.0.0', 'all' );
    }

    public function enqueue_scripts( $hook ) {
        //if ($hook !== 'tools_page_appetiser-link-mapper') return;

        if (!isset($_GET['page']) || $_GET['page'] !== 'appetiser-link-mapper') {
            return;
        }

        wp_enqueue_script( 'appetiser-dashboard-script', plugins_url() . '/appetiser-common-assets/admin/js/appetiser-dashboard.js', array( 'jquery' ), '1.0.0', false );
        wp_enqueue_script( 'appetiser-link-exchange-script', plugin_dir_url( __FILE__ ) . 'js/app-link-exchange-admin.js', array( 'jquery' ), '1.0.0', true );

        wp_localize_script('appetiser-link-exchange-script', 'AppLmAjax', [ 
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('app_lm_check_url'),
        ]);
        
    }
    
    public function handle_ajax_check_url() {
        check_ajax_referer('app_lm_check_url', 'nonce');
    
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    
        if (!$url) {
            wp_send_json_error('Invalid URL.');
        }
    
        $post_id = url_to_postid($url);
    
        if (!$post_id) {
            wp_send_json_error('URL does not map to any post.');
        }
    
        $post = get_post($post_id);
    
        if (!$post || $post->post_type !== 'post') {
            wp_send_json_error('URL must link to a blog post (not a page or custom post type).');
        }
    
        wp_send_json_success('Valid blog post URL.');
    }
    
    public function add_plugin_menu() {
        
        add_submenu_page(
            'appetiser-tools',           //parent-slug
            'Link Exchange',     
            'Link Exchange',     
            'manage_options',            
            'appetiser-link-mapper',     //menu-slug
            [$this, 'render_admin_page'] 
        );
         
    }
    
    private function save_link_maps() {
        $sanitized = [];
        $has_invalid = false;
    
        if (!empty($_POST['link_mapper']) && is_array($_POST['link_mapper'])) {
            foreach ($_POST['link_mapper'] as $group) {
                $url      = isset($group['url']) ? esc_url_raw($group['url']) : '';
                $keyword  = isset($group['keyword']) ? sanitize_text_field($group['keyword']) : '';
                $outbound = isset($group['outbound']) ? esc_url_raw($group['outbound']) : '';
                $enabled  = isset($group['enabled']) ? true : false;
    
                $post_id = url_to_postid($url);
                $post    = $post_id ? get_post($post_id) : null;
    
                if (!$url || !$keyword || !$outbound || !$post || $post->post_type !== 'post') {
                    echo "has invalid";
                    $has_invalid = true;
                    break; 
                }
    
                $sanitized[] = [
                    'url'      => $url,
                    'keyword'  => $keyword,
                    'outbound' => $outbound,
                    'enabled'  => $enabled,
                ];
            }
        }
    
        if ($has_invalid) {
            add_settings_error(
                'app_lm_messages',
                'app_lm_error',
                'One or more Blog Post URLs are invalid. Please check that all URLs are valid blog posts.',
                'error'
            );
            return; 
        }
        
        update_option('app_lm_link_mappings', $sanitized);
    
        add_settings_error(
            'app_lm_messages',
            'app_lm_message',
            'Link exchange mapping saved successfully.',
            'updated'
        );
    }

    public function maybe_import_list() {
        if (isset($_POST['app_lm_import_sheet'])) {
            $this->import_list();
        }
    }

    private function import_list() {

        if ( ! class_exists( 'GoogleSheetReader' ) ) {
            return;
        }

        $jsonPath = WP_PLUGIN_DIR . '/appetiser-common-assets/inc/appetiser-website-backend-1af36aead0f7.json';
        $spreadsheetId = '10V1lxO-q8wPFqHy1x1xyj__fqa0SHCcH-tRNUpuFzYw';
        $reader = new GoogleSheetReader( $jsonPath, $spreadsheetId );
        $range = "'Link Exchange Tracker'!G2:N";
        $values = $reader->readRange($range);

        // Load existing mappings
        $existing = get_option('app_lm_link_mappings', []);
        $existing_urls = array_column($existing, 'url');
        $sanitized = $existing;

        $has_invalid = false;

        if (!empty($values)) {
            foreach ($values as $row) {
                $status   = $row[0] ?? ''; // G
                $url      = $row[5] ?? ''; // L
                $keyword  = $row[6] ?? ''; // M
                $outbound = $row[7] ?? ''; // N

                if (
                    strtoupper(trim($status)) !== 'LIVE' ||
                    trim($url) === '' ||
                    trim($keyword) === '' ||
                    trim($outbound) === ''
                ) {
                    continue;
                }

                // BEGIN: for local testing only
                $url = str_replace('https://appetiser.com.au', 'http://appetiser.local', $url);
                // END

                $url      = esc_url_raw($url);
                $keyword  = sanitize_text_field($keyword);
                $outbound = esc_url_raw($outbound);

                $post_id = url_to_postid($url);
                $post    = $post_id ? get_post($post_id) : null;

                if (!$post || $post->post_type !== 'post') {
                    $has_invalid = true;
                    continue;
                }

                // Skip duplicate URLs
                if (in_array($url, $existing_urls, true)) {
                    continue;
                }

                $sanitized[] = [
                    'url'      => $url,
                    'keyword'  => $keyword,
                    'outbound' => $outbound,
                    'enabled'  => false,
                ];
            }
        }

        // Save updated list if anything new was added
        if (count($sanitized) > count($existing)) {
            update_option('app_lm_link_mappings', $sanitized);

            add_settings_error(
                'app_lm_messages',
                'app_lm_imported',
                'Links imported successfully from Google Sheet.',
                'updated'
            );
        } elseif ($has_invalid) {
            add_settings_error(
                'app_lm_messages',
                'app_lm_import_error',
                'Some entries could not be imported. Please check that all blog URLs point to published posts.',
                'error'
            );
        } else {
            add_settings_error(
                'app_lm_messages',
                'app_lm_import_empty',
                'No new valid entries found in Google Sheet.',
                'error'
            );
        }
    }

    public function handle_csv_export() {
        if (
            !current_user_can('manage_options') ||
            !isset($_POST['app_lm_export_csv_nonce_field']) ||
            !wp_verify_nonce($_POST['app_lm_export_csv_nonce_field'], 'app_lm_export_csv_nonce')
        ) {
            wp_die('Unauthorized export request');
        }
    
        $mappings = get_option('app_lm_link_mappings', []);
    
        if (empty($mappings)) {
            wp_die('No mappings to export.');
        }
    
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=link-mappings-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
    
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Blog Post URL', 'Keyword', 'Outbound Link', 'Enabled']);
    
        foreach ($mappings as $map) {
            fputcsv($output, [
                $map['url'],
                $map['keyword'],
                $map['outbound'],
                !empty($map['enabled']) ? 'Yes' : 'No'
            ]);
        }
    
        fclose($output);
        exit;
    }
    
    
    private function get_existing_link_maps() {
        $existing_mappings = get_option('app_lm_link_mappings', []);
        ?>
        <script>
            const appLmSavedMappings = <?php echo json_encode($existing_mappings); ?>;
        </script>
        <?php
    }

    public function render_admin_page() {

        ?>
        <div class="wrap">
            <h1>Link Exchange Dashboard</h1>
            <div class="tab">
                <button class="tablinks" onclick="openTab(event, 'outbound')" id="outboundtablink">Outbound Links to Partners</button>
                <button class="tablinks" onclick="openTab(event, 'inbound')" id="backlinkstablink">Backlinks from Partners</button>
            </div>
        
            <div id="outbound" class="tabcontent">
                <?php   
                    if (isset($_POST['app_lm_form_submitted']) && current_user_can('manage_options')) {
                        $this->save_link_maps();
                    }      
                    $this->get_existing_link_maps();
                ?>
                <form method="post" action="">
                <h2>Outbound</h2>
                    <div id="link-mapper-groups">
                        <!-- JS will populate initial field group here -->
                    </div>
                <p>
                    <button type="button" id="add-mapper-group" class="button add-mapper-button" title="Add Group">
                        <span class="dashicons dashicons-plus-alt2"></span> Add New Link Item
                    </button>
                </p>

                <input type="hidden" name="app_lm_form_submitted" value="1" />
                <?php submit_button('Save Mappings'); ?>
                </form>

                <form method="post">
                    <?php submit_button('Import/Sync from Google Sheet', 'secondary', 'app_lm_import_sheet'); ?>
                </form>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="app_lm_export_csv">
                    <?php wp_nonce_field('app_lm_export_csv_nonce', 'app_lm_export_csv_nonce_field'); ?>
                    <input type="submit" class="button button-primary" value="Export to CSV">
                </form>
            </div>

            <div id="inbound" class="tabcontent">
                <h2>Backlinks Checker</h2>
                <div id="backlinks-check-wrapper">
                    Comming soon.
                </div>
            </div>
            <div class="bottomtab">
                documentation
            </div>
            
        </div>
        <?php
    }
}