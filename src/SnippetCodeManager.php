<?php

namespace Mrzkit\WpPluginSnippetCodeManager;

use Mrzkit\WpPluginSnippetCodeManager\Contract\Plugin;
use Mrzkit\WpPluginSnippetCodeManager\Extension\SnippetListTableExtension;
use Mrzkit\WpPluginSnippetCodeManager\Repository\OptionRepository;
use Mrzkit\WpPluginSnippetCodeManager\Repository\ScriptRepository;
use Mrzkit\WpPluginSnippetCodeManager\Service\ScriptService;

class SnippetCodeManager implements Plugin
{
    protected static $entryFile;

    private $config;

    public function __construct($entryFile, $config = [])
    {
        static::$entryFile = (string) $entryFile;

        $this->config = (array) $config;
    }

    /**
     * @desc 获取入口文件
     * @return string
     */
    public static function getEntryFile()
    {
        return static::$entryFile;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @desc 启动
     */
    public function launch()
    {
        register_activation_hook(static::getEntryFile(), [$this, 'hfcm_options_install']);
        add_action('plugins_loaded', [$this, 'hfcm_db_update_check']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'hfcm_enqueue_assets']);
        add_action('plugins_loaded', [__CLASS__, 'hfcm_load_translation_files']);
        add_action('admin_menu', [__CLASS__, 'hfcm_modifymenu']);
        add_filter('plugin_action_links_' . plugin_basename(static::getEntryFile()), [__CLASS__, 'hfcm_add_plugin_page_settings_link']);
        add_action('admin_init', [$this, 'hfcm_init']);
        add_shortcode('hfcm', [$this, 'hfcm_shortcode']);
        add_action('wp_head', [__CLASS__, 'hfcm_header_scripts']);
        add_action('wp_footer', [__CLASS__, 'hfcm_footer_scripts']);
        add_action('the_content', [__CLASS__, 'hfcm_content_scripts']);
        add_action('wp_ajax_hfcm-request', [__CLASS__, 'hfcm_request_handler']);
    }

    /**
     * @desc 安装
     * @return mixed|void
     */
    public function install()
    {
        // TODO: Implement install() method.
    }

    /**
     * @desc 卸载
     */
    public function uninstall()
    {
        $service = new ScriptService();

        $service->uninstall();
    }

    /**
     * @desc 激活
     * @return mixed|void
     */
    public function active()
    {
        // TODO: Implement active() method.
    }

    /**
     * @desc 禁用
     * @return mixed|void
     */
    public function disable()
    {
        // TODO: Implement disable() method.
    }

    /**
     * @desc 升级
     * @return mixed|void
     */
    public function upgrade()
    {
        // TODO: Implement upgrade() method.
    }


    // -----------------

    /**
     * @desc hfcm init function
     */
    public function hfcm_init()
    {
        static::hfcm_check_installation_date();
        static::hfcm_plugin_notice_dismissed();
        static::hfcm_import_snippets();
        static::hfcm_export_snippets();
    }

    /**
     * @desc function to create the DB / Options / Defaults
     */
    public function hfcm_options_install()
    {
        $now = strtotime('now');

        $optionRepository = new OptionRepository();

        $optionRepository->addActivationDate($now);

        $optionRepository->updateActivationDate($now);

        $repository = new ScriptRepository();

        $config = $this->getConfig();

        // 初始化数据表
        $repository->createTable();

        $optionRepository->addVersion($config['version']);
    }

    /**
     * @desc function to check if plugin is being updated
     */
    public function hfcm_db_update_check()
    {
        $config = $this->getConfig();

        $optionRepository = new OptionRepository();

        $version = $optionRepository->getVersion();

        // Version Diff
        if ($version != $config['version']) {
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
            static::hfcm_options_install();
            // Update Version
            $optionRepository->updateVersion($config['version']);
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

        wp_register_style('hfcm_general_admin_assets', plugins_url('assets/css/style-general-admin.css', static::getEntryFile()));
        wp_enqueue_style('hfcm_general_admin_assets');

        if (in_array($hook, $allowed_pages)) {
            // Plugin's CSS
            wp_register_style('hfcm_assets', plugins_url('assets/css/style-admin.css', static::getEntryFile()));
            wp_enqueue_style('hfcm_assets');
        }

        // Remove hfcm-list from $allowed_pages
        array_shift($allowed_pages);

        if (in_array($hook, $allowed_pages)) {
            // selectize.js plugin CSS and JS files
            wp_register_style('selectize-css', plugins_url('assets/css/selectize.bootstrap3.css', static::getEntryFile()));
            wp_enqueue_style('selectize-css');

            wp_register_script('selectize-js', plugins_url('assets/js/selectize.min.js', static::getEntryFile()), array('jquery'));
            wp_enqueue_script('selectize-js');

            wp_enqueue_code_editor(array('type' => 'text/html'));
        }
    }

    /**
     * @desc This function loads plugins translation files
     */
    public static function hfcm_load_translation_files()
    {
        load_plugin_textdomain('header-footer-code-manager', false, dirname(plugin_basename(static::getEntryFile())) . 'assets/languages');
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
            ['<a href="' . admin_url('admin.php?page=hfcm-list') . '">' . __('Settings') . '</a>'], $links
        );

        return $links;
    }

    /**
     * @desc function to check the plugins installation date
     */
    public function hfcm_check_installation_date()
    {
        $optionRepository = new OptionRepository();

        $install_date = $optionRepository->getActivationDate();

        $past_date = strtotime('-7 days');

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
        $allowed_pages_notices = []; // 屏蔽提示

        $screen = get_current_screen()->id;

        if (in_array($screen, $allowed_pages_notices)) {
            ?>
            <div id="hfcm-message" class="notice notice-success">
                <p>
                    🔥 LIFETIME DEAL ALERT: The PRO version of this plugin is released and and available for a
                    limited time as a one-time, exclusive lifetime deal.
                    Want it? <b><i><a href="http://www.rockethub.com/deal/header-footer-code-manager-pro-wordpress-plugin?utm_source=freehfcm&utm_medium=banner&utm_campaign=rhltd"
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
        if (is_array($scriptData)) {
            $scriptId = absint($scriptData['script_id']);
            $name     = esc_html($scriptData['name']);
            $snippet  = html_entity_decode($scriptData['snippet']);
        } else {
            $scriptId = absint($scriptData->script_id);
            $name     = esc_html($scriptData->name);
            $snippet  = html_entity_decode($scriptData->snippet);
        }

        $output = "<!-- HFCM by 99 Robots - Snippet # " . $scriptId . ": " . $name . " -->\n" . $snippet . "\n<!-- /end HFCM by 99 Robots -->\n";

        return $output;
    }

    /**
     * @desc function to implement shortcode
     * @param $atts
     */
    public function hfcm_shortcode($atts)
    {
        if (empty($atts['id'])) {
            return '';
        }

        $repository = new ScriptRepository();

        $script = $repository->selectWithoutDeviceType($atts['id']);

        if (empty($script)) {
            return '';
        }

        return self::hfcm_render_snippet($script);
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
        $script     = $repository->selectDeviceLocation($location);

        $theId = get_the_ID();

        if ( !empty($script)) {
            foreach ($script as $scriptData) {
                $out = '';
                switch ($scriptData['display_on']) {
                    case 'All':
                        $isRender = true;
                        if ( !empty($scriptData['ex_pages']) && is_page($scriptData['ex_pages'])) {
                            $isRender = false;
                        }
                        if ( !empty($scriptData['ex_posts']) && is_single($scriptData['ex_posts'])) {
                            $isRender = false;
                        }
                        if ($isRender) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }

                        break;
                    case 'latest_posts':
                        if (is_single()) {
                            $numberPosts = empty($scriptData['lp_count']) ? 5 : absint($scriptData['lp_count']);

                            $nnr_hfcm_latest_posts = wp_get_recent_posts(
                                ['numberposts' => $numberPosts,]
                            );

                            foreach ($nnr_hfcm_latest_posts as $latestPost) {
                                if ($theId == $latestPost['ID']) {
                                    $out = self::hfcm_render_snippet($scriptData);
                                }
                            }
                        }
                        break;
                    case 's_categories':
                        if ( !empty($scriptData['s_categories']) && in_category($scriptData['s_categories'])) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }

                        if ( !is_archive() && !is_home()) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_custom_posts':
                        if ( !empty($scriptData['s_custom_posts']) && is_singular($scriptData['s_custom_posts'])) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_posts':
                        if ( !empty($scriptData['s_posts']) && is_single($scriptData['s_posts'])) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_is_home':
                        if (is_home() || is_front_page()) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_is_archive':
                        if (is_archive()) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_is_search':
                        if (is_search()) {
                            $out = self::hfcm_render_snippet($scriptData);
                        }
                        break;
                    case 's_pages':
                        if ( !empty($scriptData['s_pages'])) {
                            $optionRepository = new OptionRepository();

                            // Gets the page ID of the blog page
                            $blog_page = $optionRepository->getPageForPosts();
                            // Checks if the blog page is present in the array of selected pages
                            if (in_array($blog_page, $scriptData['s_pages'])) {
                                if (is_page($scriptData['s_pages']) || ( !is_front_page() && is_home())) {
                                    $out = self::hfcm_render_snippet($scriptData);
                                }
                            } elseif (is_page($scriptData['s_pages'])) {
                                $out = self::hfcm_render_snippet($scriptData);
                            }
                        }
                        break;
                    case 's_tags':
                        if ( !empty($scriptData['s_tags']) && has_tag($scriptData['s_tags'])) {
                            if (is_tag($scriptData['s_tags'])) {
                                $out = self::hfcm_render_snippet($scriptData);
                            }
                            if ( !is_archive() && !is_home()) {
                                $out = self::hfcm_render_snippet($scriptData);
                            }
                        }
                }

                switch ($scriptData['location']) {
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

    /**
     * @desc load redirection Javascript code
     * @param $url
     */
    public static function hfcm_redirect($url = '')
    {
        // Register the script
        wp_register_script('hfcm_redirection', plugins_url('assets/js/location.js', static::getEntryFile()));

        // Localize the script with new data
        wp_localize_script('hfcm_redirection', 'hfcm_location', [
            'url' => $url,
        ]);

        // Enqueued script with localized data.
        wp_enqueue_script('hfcm_redirection');
    }

    /*
        * function to handle add/update requests
        */
    public static function hfcm_request_handler()
    {
        // check user capabilities
        self::checkUserCan();

        $service = new ScriptService();

        // Handle AJAX on/off toggle for snippets
        if (isset($_REQUEST['toggle']) && !empty($_REQUEST['togvalue'])) {
            $id = absint($_REQUEST['id']);
            $service->toggle($id, $_REQUEST['toggle'], $_REQUEST['togvalue']);
        } else if (isset($_POST['insert']) && isset($_POST['data'])) {
            // Insert Snippet
            $lastId = $service->insert($_POST['data']);
            self::hfcm_redirect(admin_url('admin.php?page=hfcm-update&message=6&id=' . $lastId));
        } else if (isset($_POST['update']) && isset($_POST['data'])) {
            // Update Snippet
            $id = absint($_REQUEST['id']);
            $service->update($id, $_POST['data']);
            self::hfcm_redirect(admin_url('admin.php?page=hfcm-update&message=1&id=' . $id));
        } elseif (isset($_POST['get_posts'])) {
            // JSON return posts for AJAX
            $id          = absint($_REQUEST['id']);
            $json_output = $service->getPosts($id);
            echo wp_json_encode($json_output);
            wp_die();
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
        wp_register_script('hfcm_showboxes', plugins_url('assets/js/nnr-hfcm-showboxes.js', (static::getEntryFile())), array('jquery'));

        include_once plugin_dir_path(static::getEntryFile()) . 'assets/views/add-edit.php';
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

        $snippet = $repository->getSnippet($id);

        $nnr_hfcm_snippets = [$snippet];

        foreach ($nnr_hfcm_snippets as $item) {
            $name             = $item['name'];
            $snippet          = $item['snippet'];
            $nnr_snippet_type = $item['snippet_type'];
            $device_type      = $item['device_type'];
            $location         = $item['location'];
            $display_on       = $item['display_on'];
            $status           = $item['status'];
            $lp_count         = $item['lp_count'];
            $s_pages          = $item['s_pages'];
            $ex_pages         = $item['ex_pages'];
            $ex_posts         = $item['ex_posts'];
            $s_posts          = $item['s_posts'];
            $s_custom_posts   = $item['s_custom_posts'];
            $s_categories     = $item['s_categories'];
            $s_tags           = $item['s_tags'];
            $createdby        = esc_html($item['created_by']);
            $lastmodifiedby   = esc_html($item['last_modified_by']);
            $createdon        = esc_html($item['created']);
            $lastrevisiondate = esc_html($item['last_revision_date']);
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

        wp_register_script('hfcm_showboxes', plugins_url('assets/js/nnr-hfcm-showboxes.js', (static::getEntryFile())), array('jquery'));

        // prepare variables for includes/hfcm-add-edit.php
        include_once plugin_dir_path(static::getEntryFile()) . 'assets/views/add-edit.php';
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
        wp_register_script('hfcm_toggle', plugins_url('assets/js/toggle.js', static::getEntryFile()));

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
        wp_register_script('hfcm_showboxes', plugins_url('js/nnr-hfcm-showboxes.js', dirname(static::getEntryFile())), array('jquery'));

        include_once plugin_dir_path(static::getEntryFile()) . 'assets/views/tools.php';
    }

    /*
       * function to export snippets
       */
    public function hfcm_export_snippets()
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
    public function hfcm_import_snippets()
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
            $json     = file_get_contents($_FILES['nnr_hfcm_import_file']['tmp_name']);
            $snippets = json_decode($json, true);
            $json     = null;
        } catch (\Exception $e) {
            echo '<div class="notice hfcm-warning-notice notice-warning">' . $translations['uploadValidNotice'] . '</div>';

            return false;
        }

        if (empty($snippets['title']) || ( !empty($snippets['title']) && $snippets['title'] != "Header Footer Code Manager")) {
            echo '<div class="notice hfcm-warning-notice notice-warning">' . $translations['uploadValidNotice'] . '</div>';

            return false;
        }

        $service = new ScriptService();

        $importStatus = $service->importSnippets($snippets);

        self::hfcm_redirect(admin_url('admin.php?page=hfcm-list&import=' . $importStatus));

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

}
