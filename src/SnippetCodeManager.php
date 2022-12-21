<?php

namespace Mrzkit\WpPluginSnippetCodeManager;

class SnippetCodeManager
{
    private static $entryFile;

    private $service;

    public function __construct($entryFile)
    {
        self::$entryFile = (string) $entryFile;

        $this->service = new ScriptService();
    }

    // 启动
    public function launch()
    {
        register_activation_hook(self::$entryFile, [__CLASS__, 'hfcm_options_install']);
        add_action('plugins_loaded', [__CLASS__, 'hfcm_db_update_check']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'hfcm_enqueue_assets']);
        add_action('plugins_loaded', [__CLASS__, 'hfcm_load_translation_files']);
        add_action('admin_menu', [__CLASS__, 'hfcm_modifymenu']);
        add_filter('plugin_action_links_' . plugin_basename(self::$entryFile), [__CLASS__, 'hfcm_add_plugin_page_settings_link']);
        add_action('admin_init', [__CLASS__, 'hfcm_init']);
        add_shortcode('hfcm', [__CLASS__, 'hfcm_shortcode']);
        add_action('wp_head', [__CLASS__, 'hfcm_header_scripts']);
        add_action('wp_footer', [__CLASS__, 'hfcm_footer_scripts']);
        add_action('the_content', [__CLASS__, 'hfcm_footer_scripts']);
        add_action('wp_ajax_hfcm-request', [__CLASS__, 'hfcm_request_handler']);
    }

    // 激活
    public function active()
    {
    }

    // 停用
    public function deactive()
    {
    }

    // 卸载
    public function uninstall()
    {
        $this->service->uninstall();
    }

    // -----------------

    public static $nnr_hfcm_db_version  = "1.5";
    public static $nnr_hfcm_table       = "hfcm_scripts";
    public static $hfcm_activation_date = "hfcm_activation_date";
    public static $hfcm_db_version      = 'hfcm_db_version';

    /**
     * @desc hfcm init function
     */
    public static function hfcm_init()
    {
        self::hfcm_check_installation_date();
        self::hfcm_plugin_notice_dismissed();
        self::hfcm_import_snippets();
        self::hfcm_export_snippets();
    }

    /**
     * @desc function to create the DB / Options / Defaults
     */
    public static function hfcm_options_install()
    {
        Kitgor_General_Util::wpInc();

        $now = strtotime('now');
        // get_option();
        add_option(self::$hfcm_activation_date, $now);
        update_option(self::$hfcm_activation_date, $now);

        $repository = new ScriptRepository();

        // 初始化数据表
        $repository->createTable();

        add_option(self::$hfcm_db_version, self::$nnr_hfcm_db_version);
    }

    /**
     * @desc function to check if plugin is being updated
     */
    public static function hfcm_db_update_check()
    {
        // Version Diff
        if (get_option(self::$hfcm_db_version) != self::$nnr_hfcm_db_version) {
            $repository = new ScriptRepository();

            // Check for Exclude Pages
            $repository->checkExcludePage();
            // Check for Exclude Posts
            $repository->checkExcludePosts();
            // Check for Snippet Type
            $repository->checkSnippetType();
            // Alter Other Fields
            $repository->alterOtherFields();
            // Reinstall
            self::hfcm_options_install();
            // Update Version
            update_option(self::$hfcm_db_version, self::$nnr_hfcm_db_version);
        }
    }

    /**
     * @desc Enqueue style-file, if it exists.
     * @param $hook
     */
    public static function hfcm_enqueue_assets($hook)
    {
        $allowed_pages = array(
            'toplevel_page_hfcm-list',
            'hfcm_page_hfcm-create',
            'admin_page_hfcm-update',
        );

        wp_register_style('hfcm_general_admin_assets', plugins_url('assets/css/style-general-admin.css', self::$entryFile));
        wp_enqueue_style('hfcm_general_admin_assets');

        if (in_array($hook, $allowed_pages)) {
            // Plugin's CSS
            wp_register_style('hfcm_assets', plugins_url('assets/css/style-admin.css', self::$entryFile));
            wp_enqueue_style('hfcm_assets');
        }

        // Remove hfcm-list from $allowed_pages
        array_shift($allowed_pages);

        if (in_array($hook, $allowed_pages)) {
            // selectize.js plugin CSS and JS files
            wp_register_style('selectize-css', plugins_url('assets/css/selectize.bootstrap3.css', self::$entryFile));
            wp_enqueue_style('selectize-css');

            wp_register_script('selectize-js', plugins_url('assets/js/selectize.min.js', self::$entryFile), array('jquery'));
            wp_enqueue_script('selectize-js');

            wp_enqueue_code_editor(array('type' => 'text/html'));
        }
    }

    /**
     * @desc This function loads plugins translation files
     */
    public static function hfcm_load_translation_files()
    {
        load_plugin_textdomain('header-footer-code-manager', false, dirname(plugin_basename(self::$entryFile)) . 'assets/languages');
    }

    /**
     * @desc function to create menu page, and submenu pages.
     */
    public static function hfcm_modifymenu()
    {
        // This is the main item for the menu
        add_menu_page(
            __('Header Footer Code Manager', 'header-footer-code-manager'),
            __('HFCM', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-list',
            [self::class, 'hfcm_list'],
            'dashicons-hfcm',
        );

        // This is a submenu
        add_submenu_page(
            'hfcm-list',
            __('All Snippets', 'header-footer-code-manager'),
            __('All Snippets', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-list',
            [self::class, 'hfcm_list']
        );

        // This is a submenu
        add_submenu_page(
            'hfcm-list',
            __('Add New Snippet', 'header-footer-code-manager'),
            __('Add New', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-create',
            [self::class, 'hfcm_create'],
        );

        // This is a submenu
        add_submenu_page(
            'hfcm-list',
            __('Tools', 'header-footer-code-manager'),
            __('Tools', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-tools',
            array(self::class, 'hfcm_tools')
        );

        // This submenu is HIDDEN, however, we need to add it anyways
        add_submenu_page(
            null,
            __('Update Script', 'header-footer-code-manager'),
            __('Update', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-update',
            array(self::class, 'hfcm_update')
        );

        // This submenu is HIDDEN, however, we need to add it anyways
        add_submenu_page(
            null,
            __('Request Handler Script', 'header-footer-code-manager'),
            __('Request Handler', 'header-footer-code-manager'),
            'manage_options',
            'hfcm-request-handler',
            array(self::class, 'hfcm_request_handler')
        );
    }

    /**
     * @desc function to add a settings link for the plugin on the Settings Page
     * @param $links
     * @return array
     */
    public static function hfcm_add_plugin_page_settings_link($links)
    {
        $links = array_merge(
            array('<a href="' . admin_url('admin.php?page=hfcm-list') . '">' . __('Settings') . '</a>'),
            $links
        );

        return $links;
    }

    /**
     * @desc function to check the plugins installation date
     */
    public static function hfcm_check_installation_date()
    {
        $install_date = get_option(self::$hfcm_activation_date);
        $past_date    = strtotime('-7 days');
        if ($past_date >= $install_date) {
            add_action('admin_notices', [
                self::class,
                'hfcm_review_push_notice',
            ]);
        }

        add_action('admin_notices', [
            self::class,
            'hfcm_static_notices'
        ]);
    }

    /**
     * @desc function to create the Admin Notice
     */
    public static function hfcm_review_push_notice()
    {
        $allowed_pages_notices = array(
            'toplevel_page_hfcm-list',
            'hfcm_page_hfcm-create',
            'admin_page_hfcm-update',
        );

        $screen = get_current_screen()->id;

        $user_id = get_current_user_id();

        $install_date = get_option(self::$hfcm_activation_date);

        if ( !get_user_meta($user_id, 'hfcm_plugin_notice_dismissed') && in_array($screen, $allowed_pages_notices)) {
            ?>
            <div id="hfcm-message" class="notice notice-success">
                <a class="hfcm-dismiss-alert notice-dismiss" href="?hfcm-admin-notice-dismissed">Dismiss</a>
                <p><?php _e('Hey there! You’ve been using the <strong>Header Footer Code Manager</strong> plugin for a while now. If you like the plugin, please support our awesome development and support team by leaving a <a class="hfcm-review-stars" href="https://wordpress.org/support/plugin/header-footer-code-manager/reviews/"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a> rating. <a href="https://wordpress.org/support/plugin/header-footer-code-manager/reviews/">Rate it!</a> It’ll mean the world to us and keep this plugin free and constantly updated. <a href="https://wordpress.org/support/plugin/header-footer-code-manager/reviews/">Leave A Review</a>', 'header-footer-code-manager'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * @desc function to add the static Admin Notice
     */
    public static function hfcm_static_notices()
    {
        $allowed_pages_notices = array(
            'toplevel_page_hfcm-list',
            'hfcm_page_hfcm-create',
            'admin_page_hfcm-update',
        );

        $screen = get_current_screen()->id;

        if (in_array($screen, $allowed_pages_notices)) {
            ?>
            <div id="hfcm-message" class="notice notice-success">
                <p>
                    🔥 LIFETIME DEAL ALERT: The PRO version of this plugin is released and and available for a
                    limited time as a one-time, exclusive lifetime deal.
                    Want it? <b><i><a
                                    href="http://www.rockethub.com/deal/header-footer-code-manager-pro-wordpress-plugin?utm_source=freehfcm&utm_medium=banner&utm_campaign=rhltd"
                                    target="_blank">Click here</a> to get HFCM Pro for the lowest price ever</i></b>
                </p>
            </div>
            <?php
        }
    }

    /**
     * @desc function to check if current user has already dismissed it
     */
    public static function hfcm_plugin_notice_dismissed()
    {
        $user_id = get_current_user_id();

        // Checking if user clicked on the Dismiss button
        if (isset($_GET['hfcm-admin-notice-dismissed'])) {
            add_user_meta($user_id, 'hfcm_plugin_notice_dismissed', 'true', true);
            // Redirect to original page the user was on
            $current_url = wp_get_referer();
            wp_redirect($current_url);
            exit;
        }

        // Checking if user clicked on the 'I understand' button
        if (isset($_GET['hfcm-file-edit-notice-dismissed'])) {
            add_user_meta($user_id, 'hfcm_file_edit_plugin_notice_dismissed', 'true', true);
        }
    }

    /**
     * @desc unction to render the snippet
     * @param $scriptData
     */
    public static function hfcm_render_snippet($scriptData)
    {
        $output = "<!-- HFCM by 99 Robots - Snippet # " . absint($scriptData->script_id) . ": " . esc_html($scriptData->name) . " -->\n" . html_entity_decode($scriptData->snippet) . "\n<!-- /end HFCM by 99 Robots -->\n";

        return $output;
    }

    /**
     * @desc function to implement shortcode
     * @param $atts
     */
    public static function hfcm_shortcode($atts)
    {
        if ( !empty($atts['id'])) {
            $repository = new ScriptRepository();
            $script     = $repository->selectWithoutDeviceType($atts['id']);
            if ( !empty($script)) {
                return self::hfcm_render_snippet($script);
            }
        }

        return '';
    }

    /**
     * @desc Function to json_decode array and check if empty
     * @param $scriptdata
     * @param $prop_name
     * @return bool
     */
    public static function hfcm_not_empty($scriptdata, $prop_name)
    {
        $data = json_decode($scriptdata->{$prop_name});
        if (empty($data)) {
            return false;
        }

        return true;
    }

    /**
     * @desc function to decide which snippets to show - triggered by hooks
     * @param $location
     * @param $content
     */
    public static function hfcm_add_snippets($location = '', $content = '')
    {
        $beforeContent = '';
        $afterContent  = '';

        $repository = new ScriptRepository();
        $script     = $repository->selectByLocation($location);

        if ( !empty($script)) {
            foreach ($script as $scriptdata) {
                $out = '';
                switch ($scriptdata->display_on) {
                    case 'All':

                        $is_not_empty_ex_pages = self::hfcm_not_empty($scriptdata, 'ex_pages');
                        $is_not_empty_ex_posts = self::hfcm_not_empty($scriptdata, 'ex_posts');
                        if (($is_not_empty_ex_pages && is_page(json_decode($scriptdata->ex_pages))) || ($is_not_empty_ex_posts && is_single(json_decode($scriptdata->ex_posts)))) {
                            $out = '';
                        } else {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 'latest_posts':
                        if (is_single()) {
                            if ( !empty($scriptdata->lp_count)) {
                                $nnr_hfcm_latest_posts = wp_get_recent_posts(
                                    ['numberposts' => absint($scriptdata->lp_count),]
                                );
                            } else {
                                $nnr_hfcm_latest_posts = wp_get_recent_posts(
                                    ['numberposts' => 5]
                                );
                            }

                            foreach ($nnr_hfcm_latest_posts as $key => $lpostdata) {
                                if (get_the_ID() == $lpostdata['ID']) {
                                    $out = self::hfcm_render_snippet($scriptdata);
                                }
                            }
                        }
                        break;
                    case 's_categories':
                        $is_not_empty_s_categories = self::hfcm_not_empty($scriptdata, 's_categories');
                        if ($is_not_empty_s_categories && in_category(json_decode($scriptdata->s_categories))) {
                            if (is_category(json_decode($scriptdata->s_categories))) {
                                $out = self::hfcm_render_snippet($scriptdata);
                            }
                            if ( !is_archive() && !is_home()) {
                                $out = self::hfcm_render_snippet($scriptdata);
                            }
                        }
                        break;
                    case 's_custom_posts':
                        $is_not_empty_s_custom_posts = self::hfcm_not_empty($scriptdata, 's_custom_posts');
                        if ($is_not_empty_s_custom_posts && is_singular(json_decode($scriptdata->s_custom_posts))) {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 's_posts':
                        $is_not_empty_s_posts = self::hfcm_not_empty($scriptdata, 's_posts');
                        if ($is_not_empty_s_posts && is_single(json_decode($scriptdata->s_posts))) {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 's_is_home':
                        if (is_home() || is_front_page()) {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 's_is_archive':
                        if (is_archive()) {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 's_is_search':
                        if (is_search()) {
                            $out = self::hfcm_render_snippet($scriptdata);
                        }
                        break;
                    case 's_pages':
                        $is_not_empty_s_pages = self::hfcm_not_empty($scriptdata, 's_pages');
                        if ($is_not_empty_s_pages) {
                            // Gets the page ID of the blog page
                            $blog_page = get_option('page_for_posts');
                            // Checks if the blog page is present in the array of selected pages
                            if (in_array($blog_page, json_decode($scriptdata->s_pages))) {
                                if (is_page(json_decode($scriptdata->s_pages)) || ( !is_front_page() && is_home())) {
                                    $out = self::hfcm_render_snippet($scriptdata);
                                }
                            } elseif (is_page(json_decode($scriptdata->s_pages))) {
                                $out = self::hfcm_render_snippet($scriptdata);
                            }
                        }
                        break;
                    case 's_tags':
                        $is_not_empty_s_tags = self::hfcm_not_empty($scriptdata, 's_tags');
                        if ($is_not_empty_s_tags && has_tag(json_decode($scriptdata->s_tags))) {
                            if (is_tag(json_decode($scriptdata->s_tags))) {
                                $out = self::hfcm_render_snippet($scriptdata);
                            }
                            if ( !is_archive() && !is_home()) {
                                $out = self::hfcm_render_snippet($scriptdata);
                            }
                        }
                }

                switch ($scriptdata->location) {
                    case 'before_content':
                        $beforecontent .= $out;
                        break;
                    case 'after_content':
                        $aftercontent .= $out;
                        break;
                    default:
                        echo $out;
                }
            }
        }

        // Return results after the loop finishes
        return $beforeContent . $content . $afterContent;
    }

    /**
     * @desc function to add snippets in the header
     */
    public static function hfcm_header_scripts()
    {
        if ( !is_feed()) {
            self::hfcm_add_snippets('header');
        }
    }

    /**
     * @desc function to add snippets in the footer
     */
    public static function hfcm_footer_scripts()
    {
        if ( !is_feed()) {
            self::hfcm_add_snippets('footer');
        }
    }

    /**
     * @desc function to add snippets before/after the content
     * @param $content
     */
    public static function hfcm_content_scripts($content)
    {
        if ( !is_feed() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return self::hfcm_add_snippets(false, $content);
        } else {
            return $content;
        }
    }

    /*
        * load redirection Javascript code
        */
    public static function hfcm_redirect($url = '')
    {
        // Register the script
        wp_register_script('hfcm_redirection', plugins_url('assets/js/location.js', self::$entryFile));

        // Localize the script with new data
        $translation_array = array('url' => $url);
        wp_localize_script('hfcm_redirection', 'hfcm_location', $translation_array);

        // Enqueued script with localized data.
        wp_enqueue_script('hfcm_redirection');
    }

    /**
     * @desc function to sanitize POST data
     * @param $key
     * @param $is_not_snippet
     */
    public static function hfcm_sanitize_text($key, $is_not_snippet = true)
    {
        if ( !empty($_POST['data'][$key])) {
            $post_data = stripslashes_deep($_POST['data'][$key]);
            if ($is_not_snippet) {
                $post_data = sanitize_text_field($post_data);
            } else {
                $post_data = htmlentities($post_data);
            }

            return $post_data;
        }

        return '';
    }

    /**
     * @desc function to sanitize strings within POST data arrays
     * @param $key
     * @param $type
     */
    public static function hfcm_sanitize_array($key, $type = 'integer')
    {
        if ( !empty($_POST['data'][$key])) {
            $arr = $_POST['data'][$key];

            if ( !is_array($arr)) {
                return array();
            }

            if ('integer' === $type) {
                return array_map('absint', $arr);
            } else { // strings
                $new_array = array();
                foreach ($arr as $val) {
                    $new_array[] = sanitize_text_field($val);
                }
            }

            return $new_array;
        }

        return array();
    }

    /*
        * function to handle add/update requests
        */
    public static function hfcm_request_handler()
    {
        // check user capabilities
        self::checkUserCan();

        if (isset($_POST['insert'])) {
            // Check nonce
            check_admin_referer('create-snippet');
        } else {
            if (empty($_REQUEST['id'])) {
                die('Missing ID parameter.');
            }
            $id = absint($_REQUEST['id']);
        }
        if (isset($_POST['update'])) {
            // Check nonce
            check_admin_referer('update-snippet_' . $id);
        }

        $repository = new ScriptRepository();

        // Handle AJAX on/off toggle for snippets
        if (isset($_REQUEST['toggle']) && !empty($_REQUEST['togvalue'])) {
            // Check nonce
            check_ajax_referer('hfcm-toggle-snippet', 'security');

            // Active Or Inactive
            ('on' === $_REQUEST['togvalue']) ? $repository->activateSnippet($id) : $repository->deactivateSnippet($id);
        } elseif (isset($_POST['insert']) || isset($_POST['update'])) {
            // Create / update snippet
            $dataInput = $_POST['data'] ?? [];

            if ('manual' === $dataInput['display_on']) {
                $dataInput['display_on'] = '';
            }

            // Current User
            $currentUser = wp_get_current_user();

            // Create new snippet
            $data = [
                'name'        => $dataInput['name'],
                'snippet'     => $dataInput['snippet'],
                'snippetType' => $dataInput['snippet_type'],
                'deviceType'  => $dataInput['device_type'],
                'location'    => $dataInput['location'],
                'displayOn'   => $dataInput['display_on'],
                'status'      => $dataInput['status'],
                'lpCount'     => max(1, (int) $dataInput['lp_count']),

                'sPages'           => $dataInput['s_pages'] ?? [],
                'exPages'          => $dataInput['ex_pages'] ?? [],
                'sPosts'           => $dataInput['s_posts'] ?? [],
                'exPosts'          => $dataInput['ex_posts'] ?? [],
                'sCustomPosts'     => $dataInput['s_custom_posts'] ?? [],
                'sCategories'      => $dataInput['s_categories'] ?? [],
                'sTags'            => $dataInput['s_tags'] ?? [],
                'created'          => current_time('Y-m-d H:i:s'),
                'createdBy'        => $currentUser->display_name,
                'lastModifiedBy'   => $currentUser->display_name,
                'lastRevisionDate' => current_time('Y-m-d H:i:s'),
            ];

            // Update snippet
            if (isset($id)) {
                $repository->update($id, $data);
                self::hfcm_redirect(admin_url('admin.php?page=hfcm-update&message=1&id=' . $id));
            } else {
                $lastId = $repository->insert($data);
                self::hfcm_redirect(admin_url('admin.php?page=hfcm-update&message=6&id=' . $lastId));
            }
        } elseif (isset($_POST['get_posts'])) {
            // JSON return posts for AJAX

            // Check nonce
            check_ajax_referer('hfcm-get-posts', 'security');

            // Get all selected posts
            if (-1 === $id) {
                $s_posts  = array();
                $ex_posts = array();
            } else {
                // Select value to update
                $script = $repository->getSnippet($id);

                $s_posts  = $script[0]['s_posts'];
                $ex_posts = $script[0]['ex_posts'];

                // Get all posts
                $args        = array(
                    'public'   => true,
                    '_builtin' => false,
                );
                $output      = 'names'; // names or objects, note names is the default
                $operator    = 'and'; // 'and' or 'or'
                $c_posttypes = get_post_types($args, $output, $operator);

                $posttypes = array('post');

                foreach ($c_posttypes as $cpdata) {
                    $posttypes[] = $cpdata;
                }

                $posts = get_posts(
                    array(
                        'post_type'      => $posttypes,
                        'posts_per_page' => -1,
                        'numberposts'    => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    )
                );

                $json_output = array(
                    'selected' => array(),
                    'posts'    => array(),
                    'excluded' => array(),
                );

                if ( !empty($posts)) {
                    foreach ($posts as $pdata) {
                        $nnr_hfcm_post_title = trim($pdata->post_title);

                        if (empty($nnr_hfcm_post_title)) {
                            $nnr_hfcm_post_title = "(no title)";
                        }
                        if ( !empty($ex_posts) && in_array($pdata->ID, $ex_posts)) {
                            $json_output['excluded'][] = $pdata->ID;
                        }

                        if ( !empty($s_posts) && in_array($pdata->ID, $s_posts)) {
                            $json_output['selected'][] = $pdata->ID;
                        }

                        $json_output['posts'][] = array(
                            'text'  => sanitize_text_field($nnr_hfcm_post_title),
                            'value' => $pdata->ID,
                        );
                    }
                }

                echo wp_json_encode($json_output);
                wp_die();
            }
        }
    }

    /**
     * @desc function for submenu "Add snippet" page
     */
    public static function hfcm_create()
    {
        // check user capabilities
        self::checkUserCan();

        // Notify hfcm-add-edit.php to make necesary changes for update
        $update = false;

        $name             = '';
        $snippet          = '';
        $nnr_snippet_type = 'html';
        $device_type      = '';
        $location         = '';
        $display_on       = '';
        $status           = '';
        $lp_count         = 5; // Default value
        $s_pages          = array();
        $ex_pages         = array();
        $s_posts          = array();
        $ex_posts         = array();
        $s_custom_posts   = array();
        $s_categories     = array();
        $s_tags           = array();

        // prepare variables for includes/hfcm-add-edit.php
        wp_register_script('hfcm_showboxes', plugins_url('assets/js/nnr-hfcm-showboxes.js', (self::$entryFile)), array('jquery'));

        include_once plugin_dir_path(self::$entryFile) . 'assets/views/add-edit.php';
    }

    // check user capabilities
    public static function checkUserCan()
    {
        if ( !current_user_can('manage_options')) {
            echo 'Sorry, you do not have access to this page.';
            exit;
        }
    }

    /**
     * @desc function for submenu "Update snippet" page
     */
    public static function hfcm_update()
    {
        add_action('wp_enqueue_scripts', 'hfcm_selectize_enqueue');

        // check user capabilities
        self::checkUserCan();

        if (empty($_GET['id'])) {
            die('Missing ID parameter.');
        }

        $id = absint($_GET['id']);

        $repository = new ScriptRepository();

        $nnr_hfcm_snippets = $repository->getSnippet($id);

        foreach ($nnr_hfcm_snippets as $s) {
            $name             = $s['name'];
            $snippet          = $s['snippet'];
            $nnr_snippet_type = $s['snippet_type'];
            $device_type      = $s['device_type'];
            $location         = $s['location'];
            $display_on       = $s['display_on'];
            $status           = $s['status'];
            $lp_count         = $s['lp_count'];
            $s_pages          = $s['s_pages'];
            $ex_pages         = $s['ex_pages'];
            $ex_posts         = $s['ex_posts'];
            $s_posts          = $s['s_posts'];
            $s_custom_posts   = $s['s_custom_posts'];
            $s_categories     = $s['s_categories'];
            $s_tags           = $s['s_tags'];
            $createdby        = esc_html($s['created_by']);
            $lastmodifiedby   = esc_html($s['last_modified_by']);
            $createdon        = esc_html($s['created']);
            $lastrevisiondate = esc_html($s['last_revision_date']);
        }

        // escape for html output
        $name             = esc_textarea($name ?? "");
        $snippet          = esc_textarea($snippet);
        $nnr_snippet_type = esc_textarea($nnr_snippet_type);
        $device_type      = esc_html($device_type);
        $location         = esc_html($location);
        $display_on       = esc_html($display_on);
        $status           = esc_html($status);
        $lp_count         = esc_html($lp_count);
        // Notify hfcm-add-edit.php to make necesary changes for update
        $update = true;

        wp_register_script('hfcm_showboxes', plugins_url('assets/js/nnr-hfcm-showboxes.js', (self::$entryFile)), array('jquery'));

        // prepare variables for includes/hfcm-add-edit.php
        include_once plugin_dir_path(self::$entryFile) . 'assets/views/add-edit.php';
    }

    /**
     * @desc function to get list of all snippets
     */
    public static function hfcm_list()
    {
        $is_pro_version_active = self::is_hfcm_pro_active();

        if ( !class_exists('WP_List_Table')) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        $snippet_obj = new SnippetListTableExtension();

        if ($is_pro_version_active) {
            ?>
            <div class="notice hfcm-warning-notice notice-warning">
                <?php _e(
                    'Please deactivate the free version of this plugin in order to avoid duplication of the snippets.
                    You can use our tools to import all the snippets from the free version of this plugin.', 'header-footer-code-manager'
                ); ?>
            </div>
            <?php
        }

        if ( !empty($_GET['import'])) {
            if ($_GET['import'] == 2) {
                $message = "Header Footer Code Manager has successfully imported all snippets and set them as INACTIVE. Please review each snippet individually and ACTIVATE those that are needed for this site. Snippet types that are only available in the PRO version are skipped";
            } else {
                $message = "Header Footer Code Manager has successfully imported all snippets and set them as INACTIVE. Please review each snippet individually and ACTIVATE those that are needed for this site.";
            }
            ?>
            <div id="hfcm-message" class="notice notice-success is-dismissible">
                <p>
                    <?php _e($message, 'header-footer-code-manager'); ?>
                </p>
            </div>
            <?php
        }

        $hfcmCreateText = admin_url('admin.php?page=hfcm-create');

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Snippets', 'header-footer-code-manager'); ?>
                <a href="<?= $hfcmCreateText ?>" class="page-title-action">
                    <?php esc_html_e('Add New Snippet', 'header-footer-code-manager'); ?>
                </a>
            </h1>

            <form method="post">
                <?php
                $snippet_obj->prepare_items();
                $snippet_obj->search_box(__('Search Snippets', 'header-footer-code-manager'), 'search_id');
                $snippet_obj->display();
                ?>
            </form>

        </div>
        <?php

        // Register the script
        wp_register_script('hfcm_toggle', plugins_url('assets/js/toggle.js', self::$entryFile));

        // Localize the script with new data
        $translation_array = array(
            'url'      => admin_url('admin.php'),
            'security' => wp_create_nonce('hfcm-toggle-snippet'),
        );

        wp_localize_script('hfcm_toggle', 'hfcm_ajax', $translation_array);

        // Enqueued script with localized data.
        wp_enqueue_script('hfcm_toggle');
    }

    /**
     * @desc function to get load tools page
     */
    public static function hfcm_tools()
    {
        $repository = new ScriptRepository();

        $nnr_hfcm_snippets = $repository->selectAllSnippets();

        // Register the script
        wp_register_script('hfcm_showboxes', plugins_url('js/nnr-hfcm-showboxes.js', dirname(self::$entryFile)), array('jquery'));

        include_once plugin_dir_path(self::$entryFile) . 'assets/views/tools.php';
    }

    /*
       * function to export snippets
       */
    public static function hfcm_export_snippets()
    {
        if ( !empty($_POST['nnr_hfcm_snippets']) && !empty($_POST['action']) && ($_POST['action'] == "download") && check_admin_referer('hfcm-nonce')) {
            $snippetIds = $_POST['nnr_hfcm_snippets'];
            $service    = new ScriptService();
            $service->exportSnippets($snippetIds);
            die;
        }
    }

    /*
     * function to import snippets
     */
    public static function hfcm_import_snippets()
    {
        if (empty($_FILES['nnr_hfcm_import_file']['tmp_name'])) {
            return false;
        }

        if ( !check_admin_referer('hfcm-nonce')) {
            return false;
        }

        $translations = [
            'uploadValidNotice' => __('Please upload a valid import file', 'header-footer-code-manager'),
        ];

        if ( !empty($_FILES['nnr_hfcm_pro_import_file']['type']) && $_FILES['nnr_hfcm_pro_import_file']['type'] != "application/json") {
            echo '<div class="notice hfcm-warning-notice notice-warning">' . $translations['uploadValidNotice'] . '</div>';

            return false;
        }

        try {
            $nnr_hfcm_snippets_json = file_get_contents($_FILES['nnr_hfcm_import_file']['tmp_name']);
            $nnr_hfcm_snippets      = json_decode($nnr_hfcm_snippets_json, true);
        } catch (\Exception $e) {
            echo '<div class="notice hfcm-warning-notice notice-warning">' . $translations['uploadValidNotice'] . '</div>';

            return false;
        }

        if (empty($nnr_hfcm_snippets['title']) || ( !empty($nnr_hfcm_snippets['title']) && $nnr_hfcm_snippets['title'] != "Header Footer Code Manager")) {
            echo '<div class="notice hfcm-warning-notice notice-warning">' . $translations['uploadValidNotice'] . '</div>';

            return false;
        }

        $allowSnippetType = ["html", "css", "js"];
        $allowLocation    = ['header', 'before_content', 'after_content', 'footer'];

        $nnr_non_script_snippets = 1;

        $currentTime = current_time('Y-m-d H:i:s');

        // Current User
        $currentUser = wp_get_current_user();

        $displayName = $currentUser->display_name;

        $repository = new ScriptRepository();

        foreach ((array) $nnr_hfcm_snippets['snippets'] as $item) {
            if ( !empty($item['snippet_type']) && !in_array($item['snippet_type'], $allowSnippetType)) {
                $nnr_non_script_snippets = 2;
                continue;
            }

            if ( !empty($item['location']) && !in_array($item['location'], $allowLocation)) {
                $nnr_non_script_snippets = 2;
                continue;
            }

            // Create new snippet
            $data = [
                'name'        => $item['name'],
                'snippet'     => $item['snippet'],
                'snippetType' => $item['snippet_type'],
                'deviceType'  => $item['device_type'],
                'location'    => $item['location'],
                'displayOn'   => $item['display_on'],
                'status'      => 'status',
                'lpCount'     => max(1, (int) $item['lp_count']),

                'sPages'           => $item['s_pages'] ?? [],
                'exPages'          => $item['ex_pages'] ?? [],
                'sPosts'           => $item['s_posts'] ?? [],
                'exPosts'          => $item['ex_posts'] ?? [],
                'sCustomPosts'     => $item['s_custom_posts'] ?? [],
                'sCategories'      => $item['s_categories'] ?? [],
                'sTags'            => $item['s_tags'] ?? [],
                'created'          => $currentTime,
                'createdBy'        => $displayName,
                'lastModifiedBy'   => $displayName,
                'lastRevisionDate' => $currentTime,
            ];

            $lastId = $repository->insert($data);
        }

        self::hfcm_redirect(admin_url('admin.php?page=hfcm-list&import=' . $nnr_non_script_snippets));

        return true;
    }

    /**
     * @desc Check if HFCM Pro is activated
     * @return bool
     */
    public static function is_hfcm_pro_active()
    {
        if (is_plugin_active('header-footer-code-manager-pro/header-footer-code-manager-pro.php')) {
            return true;
        }

        return false;
    }

    public static function hfcm_get_categories()
    {
        $args = [
            'public'       => true,
            'hierarchical' => true,
        ];

        $output     = 'objects'; // or objects
        $operator   = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);

        $nnr_hfcm_categories = [];

        foreach ($taxonomies as $taxonomy) {
            $nnr_hfcm_taxonomy_categories = get_categories(
                [
                    'taxonomy'   => $taxonomy->name,
                    'hide_empty' => 0
                ]
            );
            $nnr_hfcm_taxonomy_categories = [
                'name'  => $taxonomy->label,
                'terms' => $nnr_hfcm_taxonomy_categories
            ];
            $nnr_hfcm_categories[]        = $nnr_hfcm_taxonomy_categories;
        }

        return $nnr_hfcm_categories;
    }

    public static function hfcm_get_tags()
    {
        $args       = [
            'public'       => true,
            'hierarchical' => false,
        ];
        $output     = 'objects'; // or objects
        $operator   = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);

        $nnr_hfcm_tags = [];

        foreach ($taxonomies as $taxonomy) {
            $nnr_hfcm_taxonomy_tags = get_tags(
                [
                    'taxonomy'   => $taxonomy->name,
                    'hide_empty' => 0
                ]
            );
            $nnr_hfcm_taxonomy_tags = [
                'name'  => $taxonomy->label,
                'terms' => $nnr_hfcm_taxonomy_tags
            ];
            $nnr_hfcm_tags[]        = $nnr_hfcm_taxonomy_tags;
        }

        return $nnr_hfcm_tags;
    }
}
