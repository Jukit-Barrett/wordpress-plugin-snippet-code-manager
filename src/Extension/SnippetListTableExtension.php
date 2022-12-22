<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Extension;

use Mrzkit\WpPluginSnippetCodeManager\Repository\ScriptRepository;
use Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager;
use Mrzkit\WpPluginSnippetCodeManager\Util\GeneralUtil;
use WP_List_Table;

class SnippetListTableExtension extends WP_List_Table
{
    private $repository;

    public function __construct()
    {
        $args = [
            'singular' => esc_html__('Snippet', 'header-footer-code-manager'),
            'plural'   => esc_html__('Snippets', 'header-footer-code-manager'),
            'ajax'     => false,
        ];

        parent::__construct($args);

        $this->repository = new ScriptRepository();
    }

    /**
     * Text displayed when no snippet data is available
     */
    public function no_items()
    {
        esc_html_e('No Snippets available.', 'header-footer-code-manager');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                return esc_html($item[$column_name]);

            case 'display_on':
                $nnr_hfcm_display_array = array(
                    'All'            => esc_html__('Site Wide', 'header-footer-code-manager'),
                    's_posts'        => esc_html__('Specific Posts', 'header-footer-code-manager'),
                    's_pages'        => esc_html__('Specific Pages', 'header-footer-code-manager'),
                    's_categories'   => esc_html__('Specific Categories', 'header-footer-code-manager'),
                    's_custom_posts' => esc_html__('Specific Custom Post Types', 'header-footer-code-manager'),
                    's_tags'         => esc_html__('Specific Tags', 'header-footer-code-manager'),
                    's_is_home'      => esc_html__('Home Page', 'header-footer-code-manager'),
                    's_is_search'    => esc_html__('Search Page', 'header-footer-code-manager'),
                    's_is_archive'   => esc_html__('Archive Page', 'header-footer-code-manager'),
                    'latest_posts'   => esc_html__('Latest Posts', 'header-footer-code-manager'),
                    'manual'         => esc_html__('Shortcode Only', 'header-footer-code-manager'),
                );

                if ('s_posts' === $item[$column_name]) {
                    $empty   = 1;
                    $s_posts = json_decode($item['s_posts']);

                    foreach ($s_posts as $id) {
                        $id = absint($id);
                        if ('publish' === get_post_status($id)) {
                            $empty = 0;
                            break;
                        }
                    }
                    if ($empty) {
                        return '<span class="hfcm-red">' . esc_html__('No post selected', 'header-footer-code-manager') . '</span>';
                    }
                }

                return esc_html($nnr_hfcm_display_array[$item[$column_name]]);

            case 'location':

                if ( !$item[$column_name]) {
                    return esc_html__('N/A', 'header-footer-code-manager');
                }

                $nnr_hfcm_locations = array(
                    'header'         => esc_html__('Header', 'header-footer-code-manager'),
                    'before_content' => esc_html__('Before Content', 'header-footer-code-manager'),
                    'after_content'  => esc_html__('After Content', 'header-footer-code-manager'),
                    'footer'         => esc_html__('Footer', 'header-footer-code-manager'),
                );

                return esc_html($nnr_hfcm_locations[$item[$column_name]]);

            case 'device_type':

                if ('both' === $item[$column_name]) {
                    return esc_html__('Show on All Devices', 'header-footer-code-manager');
                } elseif ('mobile' === $item[$column_name]) {
                    return esc_html__('Only Mobile Devices', 'header-footer-code-manager');
                } elseif ('desktop' === $item[$column_name]) {
                    return esc_html__('Only Desktop', 'header-footer-code-manager');
                } else {
                    return esc_html($item[$column_name]);
                }
            case 'snippet_type':
                $snippet_types = array(
                    'html' => esc_html__('HTML', 'header-footer-code-manager'),
                    'css'  => esc_html__('CSS', 'header-footer-code-manager'),
                    'js'   => esc_html__('Javascript', 'header-footer-code-manager')
                );

                return esc_html($snippet_types[$item[$column_name]]);

            case 'status':

                if ('inactive' === $item[$column_name]) {
                    return '<div class="nnr-switch">
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '">OFF</label>
                        <input id="nnr-round-toggle' . esc_attr($item['script_id']) . '" class="round-toggle round-toggle-round-flat" type="checkbox" data-id="' . esc_attr($item['script_id']) . '" />
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '"></label>
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '">ON</label>
                    </div>
                    ';
                } elseif ('active' === $item[$column_name]) {
                    return '<div class="nnr-switch">
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '">OFF</label>
                        <input id="nnr-round-toggle' . esc_attr($item['script_id']) . '" class="round-toggle round-toggle-round-flat" type="checkbox" data-id="' . esc_attr($item['script_id']) . '" checked="checked" />
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '"></label>
                        <label for="nnr-round-toggle' . esc_attr($item['script_id']) . '">ON</label>
                    </div>
                    ';
                } else {
                    return esc_html($item[$column_name]);
                }

            case 'script_id':
                return esc_html($item[$column_name]);

            case 'shortcode':
                return '[hfcm id="' . absint($item['script_id']) . '"]';

            default:
                return print_r($item, true); // Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="snippets[]" value="%s" />', $item['script_id']);
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name($item)
    {
        $delete_nonce = wp_create_nonce('hfcm_delete_snippet');
        $edit_nonce   = wp_create_nonce('hfcm_edit_snippet');

        $title = '<strong>' . esc_html($item['name']) . '</strong>';

        $nnr_current_screen = get_current_screen();

        if ( !empty($nnr_current_screen->parent_base)) {
            $page = $nnr_current_screen->parent_base;
        } else {
            $page = GeneralUtil::sanitizeText($_GET['page']);
        }
        $actions = array(
            'edit'   => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__('Edit', 'header-footer-code-manager') . '</a>', esc_attr('hfcm-update'), 'edit', absint($item['script_id']), $edit_nonce),
            'copy'   => sprintf('<a href="javascript:void(0);" data-shortcode=\'[hfcm id="%s"]\'  class="hfcm_copy_shortcode" id="hfcm_copy_shortcode_%s">' . esc_html__('Copy Shortcode', 'header-footer-code-manager') . '</a>', absint($item['script_id']), absint($item['script_id'])),
            'delete' => sprintf('<a href="?page=%s&action=%s&snippet=%s&_wpnonce=%s">' . esc_html__('Delete', 'header-footer-code-manager') . '</a>', $page, 'delete', absint($item['script_id']), $delete_nonce),
        );

        return $title . $this->row_actions($actions);
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = [
            'cb'           => '<input type="checkbox" />',
            'script_id'    => esc_html__('ID', 'header-footer-code-manager'),
            'status'       => esc_html__('Status', 'header-footer-code-manager'),
            'name'         => esc_html__('Snippet Name', 'header-footer-code-manager'),
            'display_on'   => esc_html__('Display On', 'header-footer-code-manager'),
            'location'     => esc_html__('Location', 'header-footer-code-manager'),
            'snippet_type' => esc_html__('Snippet Type', 'header-footer-code-manager'),
            'device_type'  => esc_html__('Devices', 'header-footer-code-manager'),
            'shortcode'    => esc_html__('Shortcode', 'header-footer-code-manager'),
        ];

        return $columns;
    }

    /**
     * @desc Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'name'      => ['name', true],
            'location'  => ['location', true],
            'script_id' => ['script_id', false],
        ];
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        return array(
            'bulk-activate'   => esc_html__('Activate', 'header-footer-code-manager'),
            'bulk-deactivate' => esc_html__('Deactivate', 'header-footer-code-manager'),
            'bulk-delete'     => esc_html__('Remove', 'header-footer-code-manager'),
        );
    }

    /**
     * Add filters and extra actions above and below the table
     *
     * @param string $which Are the actions displayed on the table top or bottom
     */
    public function extra_tablenav($which)
    {
        if ('top' === $which) {
            $query        = isset($_POST['snippet_type']) ? GeneralUtil::sanitizeText($_POST['snippet_type']) : '';
            $snippet_type = array(
                'html' => esc_html__('HTML', 'header-footer-code-manager'),
                'css'  => esc_html__('CSS', 'header-footer-code-manager'),
                'js'   => esc_html__('Javascript', 'header-footer-code-manager')
            );

            echo '<div class="alignleft actions">';
            echo '<select name="snippet_type">';
            echo '<option value="">' . esc_html__('All Snippet Types', 'header-footer-code-manager') . '</option>';

            foreach ($snippet_type as $key_type => $type) {
                if ($key_type == $query) {
                    echo '<option value="' . esc_attr($key_type) . '" selected>' . esc_html($type) . '</option>';
                } else {
                    echo '<option value="' . esc_attr($key_type) . '">' . esc_html($type) . '</option>';
                }
            }

            echo '</select>';
            submit_button(__('Filter', 'header-footer-code-manager'), 'button', 'filter_action', false);
            echo '</div>';
        }

        echo '<div class="alignleft actions">';

        echo '</div>';
    }

    /**
     * @desc Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        // Retrieve $customvar for use in query to get items.

        $customvar = 'all';
        if ( !empty($_GET['customvar'])) {
            $customvar = GeneralUtil::sanitizeText($_GET['customvar']);
            if (empty($customvar) || !in_array($customvar, ['inactive', 'active', 'all'])) {
                $customvar = 'all';
            }
        }
        $this->_column_headers = [$columns, $hidden, $sortable];

        /**
         * Process bulk action
         */
        $this->process_bulk_action();
        $this->views();
        $per_page     = $this->get_items_per_page('sippets_per_page', 20);
        $current_page = $this->get_pagenum();

        $total_items = $this->repository->recordCount();

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ]
        );

        $params = [
            'perPage'     => $per_page,
            'pageNumber'  => $current_page,
            'orderBy'     => (string) ($_GET['order'] ?? ""),
            'order'       => (string) ($_GET['orderby'] ?? ""),
            'status'      => $customvar,
            'snippetType' => (string) ($_POST['snippet_type'] ?? ""),
            'name'        => (string) ($_POST['s'] ?? ""),
        ];

        $this->items = $this->repository->selectSnippets($params);
    }

    public function get_views()
    {
        $views   = array();
        $current = 'all';
        if ( !empty($_GET['customvar'])) {
            $current = GeneralUtil::sanitizeText($_GET['customvar']);
        }

        //All link
        $class        = 'all' === $current ? 'current' : '';
        $all_url      = remove_query_arg('customvar');
        $views['all'] = '<a href="' . esc_html($all_url) . '" class="' . esc_html($class) . '">' . esc_html__('All', 'header-footer-code-manager') . ' (' . esc_html__($this->repository->recordCount()) . ')</a>';

        //Foo link
        $foo_url         = add_query_arg('customvar', 'active');
        $class           = ('active' === $current ? 'current' : '');
        $views['active'] = '<a href="' . esc_html($foo_url) . '" class="' . esc_html($class) . '">' . esc_html__('Active', 'header-footer-code-manager') . ' (' . esc_html__($this->repository->recordCount('active')) . ')</a>';

        //Bar link
        $bar_url           = add_query_arg('customvar', 'inactive');
        $class             = ('inactive' === $current ? 'current' : '');
        $views['inactive'] = '<a href="' . esc_html($bar_url) . '" class="' . esc_html($class) . '">' . esc_html__('Inactive', 'header-footer-code-manager') . ' (' . esc_html__($this->repository->recordCount('inactive')) . ')</a>';

        return $views;
    }

    // 批量操作
    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
            $nonce = GeneralUtil::sanitizeText($_REQUEST['_wpnonce']);

            if ( !wp_verify_nonce($nonce, 'hfcm_delete_snippet')) {
                die('Go get a life script kiddies');
            }

            if ( !empty($_GET['snippet'])) {
                $snippet_id = absint($_GET['snippet']);
                if ( !empty($snippet_id)) {
                    $this->repository->delete($snippet_id);
                }
            }

            SnippetCodeManager::hfcm_redirect(admin_url('admin.php?page=hfcm-list'));

            return;
        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && 'bulk-delete' === $_POST['action'])
            || (isset($_POST['action2']) && 'bulk-delete' === $_POST['action2'])
        ) {
            $deleteIds = $_POST['snippets'];

            $repository = new ScriptRepository();

            // loop over the array of record IDs and delete them
            $repository->batchDelete($deleteIds);

            SnippetCodeManager::hfcm_redirect(admin_url('admin.php?page=hfcm-list'));

            return;

        } elseif ((isset($_POST['action']) && 'bulk-activate' === $_POST['action'])
                  || (isset($_POST['action2']) && 'bulk-activate' === $_POST['action2'])
        ) {
            $activate_ids = $_POST['snippets'];

            // loop over the array of record IDs and activate them
            foreach ($activate_ids as $id) {
                $id = absint($id);
                if ( !empty($id) && is_int($id)) {
                    $this->repository->activateSnippet($id);
                }
            }

            SnippetCodeManager::hfcm_redirect(admin_url('admin.php?page=hfcm-list'));

            return;
        } elseif ((isset($_POST['action']) && 'bulk-deactivate' === $_POST['action'])
                  || (isset($_POST['action2']) && 'bulk-deactivate' === $_POST['action2'])
        ) {
            $delete_ids = $_POST['snippets'];

            // loop over the array of record IDs and deactivate them
            foreach ($delete_ids as $id) {
                $id = absint($id);
                if ( !empty($id) && is_int($id)) {
                    $this->repository->deactivateSnippet($id);
                }
            }

            SnippetCodeManager::hfcm_redirect(admin_url('admin.php?page=hfcm-list'));

            return;
        }
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     * @since 3.1.0
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }
        $input_id = $input_id . '-search-input';
        ?>
        <p class="search-box">
            <label class="screen-reader-text"
                   for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php esc_attr(_admin_search_query()); ?>"/>
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
        <?php
    }
}
