<?php

use Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager;

//if ( !isset($entryFile)) {
//    $entryFile = __FILE__;
//}

// Register the script
//wp_register_script('hfcm_showboxes', plugins_url('assets/js/nnr-hfcm-showboxes.js', dirname($entryFile)), array('jquery'));

// prepare ID (for AJAX)
if ( !isset($id)) {
    $id = -1;
}

// Localize the script with new data
$translation_array = array(
    'header'         => __('Header', 'header-footer-code-manager'),
    'before_content' => __('Before Content', 'header-footer-code-manager'),
    'after_content'  => __('After Content', 'header-footer-code-manager'),
    'footer'         => __('Footer', 'header-footer-code-manager'),
    'id'             => absint($id),
    'security'       => wp_create_nonce('hfcm-get-posts'),
);

wp_localize_script('hfcm_showboxes', 'hfcm_localize', $translation_array);

// Enqueued script with localized data.
wp_enqueue_script('hfcm_showboxes');

$translations = [

];

if ( !isset($createdon)) {
    $createdon = '';
}

if ($update) {
    $fileEditDismissAction = admin_url('admin.php?page=hfcm-update&hfcm-file-edit-notice-dismissed=1&id=' . absint($id));
} else {
    $fileEditDismissAction = admin_url('admin.php?page=hfcm-create&hfcm-file-edit-notice-dismissed=1');
}

$deleteNonce = wp_create_nonce('hfcm_delete_snippet');

$data = [
    'title'                 => $update ? esc_html__('Edit Snippet', 'header-footer-code-manager') : esc_html__('Add New Snippet', 'header-footer-code-manager'),
    'createUrl'             => admin_url('admin.php?page=hfcm-create'),
    'listUrl'               => admin_url('admin.php?page=hfcm-list'),
    'formAction'            => $update ? admin_url('admin.php?page=hfcm-request-handler&id=' . absint($id)) : admin_url('admin.php?page=hfcm-request-handler'),
    'createOrUpdate'        => $update ? wp_nonce_field('update-snippet_' . absint($id), '_wpnonce', true, false) : wp_nonce_field('create-snippet', '_wpnonce', true, false),
    'imagesAjaxLoaderPath'  => plugins_url('assets/images/ajax-loader.gif', dirname(__FILE__)),
    'deleteNonce'           => $deleteNonce,
    'deleteNonceUrl'        => esc_url(admin_url('admin.php?page=hfcm-list&action=delete&_wpnonce=' . $deleteNonce . '&snippet=' . absint($id))),
    'fileEditDismissAction' => $fileEditDismissAction,

    'addNewSnippet'           => esc_html__('Add New Snippet', 'header-footer-code-manager'),
    'scriptUpdated'           => esc_html__('Script updated', 'header-footer-code-manager'),
    'backToList'              => esc_html__('Back to list', 'header-footer-code-manager'),
    'scriptAddedSuccessfully' => esc_html__('Script Added Successfully', 'header-footer-code-manager'),
    'snippetName'             => esc_html__('Snippet Name', 'header-footer-code-manager'),
    'snippetType'             => esc_html__('Snippet Type', 'header-footer-code-manager'),
    'siteDisplay'             => esc_html__('Site Display', 'header-footer-code-manager'),
    'excludePages'            => esc_html__('Exclude Pages', 'header-footer-code-manager'),
    'excludePosts'            => esc_html__('Exclude Posts', 'header-footer-code-manager'),
    'pageList'                => esc_html__('Page List', 'header-footer-code-manager'),
    'postList'                => esc_html__('Post List', 'header-footer-code-manager'),
    'categoryList'            => esc_html__('Category List', 'header-footer-code-manager'),
    'tagList'                 => esc_html__('Tags List', 'header-footer-code-manager'),
    'postType'                => esc_html__('Post Types', 'header-footer-code-manager'),
    'postCount'               => esc_html__('Post Count', 'header-footer-code-manager'),
    'location'                => esc_html__('Location', 'header-footer-code-manager'),
    'deviceDisplay'           => esc_html__('Device Display', 'header-footer-code-manager'),
    'status'                  => esc_html__('Status', 'header-footer-code-manager'),
    'snippet'                 => esc_html__('Snippet', 'header-footer-code-manager'),
    'code'                    => esc_html__('Code', 'header-footer-code-manager'),
    'shortcode'               => esc_html__('Shortcode', 'header-footer-code-manager'),
    'update'                  => esc_html__('Update', 'header-footer-code-manager'),
    'save'                    => esc_html__('Save', 'header-footer-code-manager'),
    'delete'                  => esc_html__('Delete', 'header-footer-code-manager'),
    'copy'                    => esc_html__('Copy', 'header-footer-code-manager'),
    'changelog'               => esc_html__('Changelog', 'header-footer-code-manager'),
    'snippetCreatedBy'        => esc_html__('Snippet Created By', 'header-footer-code-manager'),
    'lastEditedBy'            => esc_html__('Last edited by', 'header-footer-code-manager'),
];

$nnr_hfcm_snippet_type_array = array(
    'html' => esc_html__('HTML', 'header-footer-code-manager'),
    'css'  => esc_html__('CSS', 'header-footer-code-manager'),
    'js'   => esc_html__('Javascript', 'header-footer-code-manager')
);

$nnr_hfcm_display_array = array(
    'All'            => esc_html__('Site Wide', 'header-footer-code-manager'),
    's_posts'        => esc_html__('Specific Posts', 'header-footer-code-manager'),
    's_pages'        => esc_html__('Specific Pages', 'header-footer-code-manager'),
    's_categories'   => esc_html__('Specific Categories (Archive & Posts)', 'header-footer-code-manager'),
    's_custom_posts' => esc_html__('Specific Post Types (Archive & Posts)', 'header-footer-code-manager'),
    's_tags'         => esc_html__('Specific Tags (Archive & Posts)', 'header-footer-code-manager'),
    's_is_home'      => esc_html__('Home Page', 'header-footer-code-manager'),
    's_is_search'    => esc_html__('Search Page', 'header-footer-code-manager'),
    's_is_archive'   => esc_html__('Archive Page', 'header-footer-code-manager'),
    'latest_posts'   => esc_html__('Latest Posts', 'header-footer-code-manager'),
    'manual'         => esc_html__('Shortcode Only', 'header-footer-code-manager'),
);

$nnr_hfcm_device_type_array = array(
    'both'    => __('Show on All Devices', 'header-footer-code-manager'),
    'desktop' => __('Only Desktop', 'header-footer-code-manager'),
    'mobile'  => __('Only Mobile Devices', 'header-footer-code-manager')
);

$nnr_hfcm_status_array = array(
    'active'   => __('Active', 'header-footer-code-manager'),
    'inactive' => __('Inactive', 'header-footer-code-manager')
);

$nnr_hfcm_categories         = SnippetCodeManager::hfcm_get_categories();
$nnr_hfcm_tags               = SnippetCodeManager::hfcm_get_tags();
$nnr_hfcm_categories_style   = 's_categories' === $display_on ? '' : 'display:none;';
$nnr_hfcm_tags_style         = 's_tags' === $display_on ? '' : 'display:none;';
$nnr_hfcm_custom_posts_style = 's_custom_posts' === $display_on ? '' : 'display:none;';
$nnr_hfcm_lpcount_style      = 'latest_posts' === $display_on ? '' : 'display:none;';
$nnr_hfcm_location_style     = 'manual' === $display_on ? 'display:none;' : '';

// Get all names of Post Types
$args                       = ['public' => true,];
$output                     = 'names';
$operator                   = 'and';
$nnr_hfcm_custom_post_types = get_post_types($args, $output, $operator);

?>

<div class="wrap">
    <h1>
        <?= $data['title']; ?>
        <?php if ($update) : ?>
            <a href="<?= $data['createUrl']; ?>" class="page-title-action">
                <?= $data['addNewSnippet']; ?>
            </a>
        <?php endif; ?>
    </h1>
    <?php
    if ( !empty($_GET['message'])) :
        if (1 === $_GET['message']) :
            ?>
            <div class="updated">
                <p><?= $data['scriptUpdated']; ?></p>
            </div>
            <a href="<?= $data['listUrl']; ?>">&laquo; <?= $data['backToList']; ?></a>
        <?php elseif (6 === $_GET['message']) : ?>
            <div class="updated">
                <p><?= $data['scriptAddedSuccessfully']; ?></p>
            </div>
            <a href="<?= $data['listUrl']; ?>">&laquo; <?= $data['backToList']; ?></a>
        <?php
        endif;
    endif;

    ?>
    <form method="post" action="<?= $data['formAction'] ?>">

        <?= $data['createOrUpdate']; ?>

        <table class="wp-list-table widefat fixed hfcm-form-width form-table">
            <tr>
                <th class="hfcm-th-width"><?= $data['snippetName']; ?></th>
                <td>
                    <input type="text" name="data[name]" value="<?= $name; ?>" class="hfcm-field-width"/>
                </td>
            </tr>

            <tr id="snippet_type">
                <th class="hfcm-th-width">
                    <?= $data['snippetType']; ?>
                </th>
                <td>
                    <select name="data[snippet_type]">
                        <?php
                        foreach ($nnr_hfcm_snippet_type_array as $nnr_key => $nnr_item) {
                            if ($nnr_key === $nnr_snippet_type) {
                                echo "<option value='" . esc_attr($nnr_key) . "' selected>" . esc_html($nnr_item) . "</option>";
                            } else {
                                echo "<option value='" . esc_attr($nnr_key) . "'>" . esc_html($nnr_item) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th class="hfcm-th-width"><?= $data['siteDisplay']; ?></th>
                <td>
                    <select name="data[display_on]" onchange="hfcm_showotherboxes(this.value);">
                        <?php
                        foreach ($nnr_hfcm_display_array as $dkey => $statusv) {
                            if ($display_on === $dkey) {
                                printf('<option value="%1$s" selected="selected">%2$s</option>', $dkey, $statusv);
                            } else {
                                printf('<option value="%1$s">%2$s</option>', $dkey, $statusv);
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php
            $nnr_hfcm_pages                      = get_pages();
            $nnr_hfcm_exclude_pages_style        = ('s_pages' === $display_on) ? 'display:none;' : '';
            $nnr_hfcm_exclude_posts_style        = ('s_posts' === $display_on) ? 'display:none;' : '';
            $nnr_hfcm_exclude_categories_style   = 's_categories' === $display_on ? 'display:none;' : '';
            $nnr_hfcm_exclude_tags_style         = 's_tags' === $display_on ? 'display:none;' : '';
            $nnr_hfcm_exclude_custom_posts_style = 's_custom_posts' === $display_on ? 'display:none;' : '';
            $nnr_hfcm_exclude_lp_count_style     = 'latest_posts' === $display_on ? 'display:none;' : '';
            $nnr_hfcm_exclude_manual_style       = 'manual' === $display_on ? 'display:none;' : '';
            ?>
            <tr id="ex_pages"
                style="<?= esc_attr($nnr_hfcm_exclude_pages_style . $nnr_hfcm_exclude_posts_style . $nnr_hfcm_exclude_tags_style . $nnr_hfcm_exclude_custom_posts_style . $nnr_hfcm_exclude_categories_style . $nnr_hfcm_exclude_lp_count_style . $nnr_hfcm_exclude_manual_style); ?>">
                <th class="hfcm-th-width"><?= $data['excludePages']; ?></th>
                <td>
                    <select name="data[ex_pages][]" multiple>
                        <?php
                        foreach ($nnr_hfcm_pages as $pdata) {
                            if (in_array($pdata->ID, $ex_pages)) {
                                printf('<option value="%1$s" selected="selected">%2$s</option>', $pdata->ID, $pdata->post_title);
                            } else {
                                printf('<option value="%1$s">%2$s</option>', $pdata->ID, $pdata->post_title);
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id="ex_posts"
                style="<?= esc_attr($nnr_hfcm_exclude_pages_style . $nnr_hfcm_exclude_posts_style . $nnr_hfcm_exclude_tags_style . $nnr_hfcm_exclude_custom_posts_style . $nnr_hfcm_exclude_categories_style . $nnr_hfcm_exclude_lp_count_style . $nnr_hfcm_exclude_manual_style); ?>">
                <th class="hfcm-th-width"><?= $data['excludePosts']; ?></th>
                <td>
                    <select class="nnr-wraptext" name="data[ex_posts][]" multiple>
                        <option disabled></option>
                    </select>
                    <img id="loader"
                         src="<?= $data['imagesAjaxLoaderPath']; ?>">
                </td>
            </tr>
            <?php
            $nnr_hfcm_pages       = get_pages();
            $nnr_hfcm_pages_style = ('s_pages' === $display_on) ? '' : 'display:none;';
            ?>
            <tr id="s_pages" style="<?= esc_attr($nnr_hfcm_pages_style); ?>">
                <th class="hfcm-th-width">
                    <?= $data['pageList']; ?>
                </th>
                <td>
                    <select name="data[s_pages][]" multiple>
                        <?php
                        foreach ($nnr_hfcm_pages as $pdata) {
                            if (in_array($pdata->ID, $s_pages)) {
                                printf('<option value="%1$s" selected="selected">%2$s</option>', esc_attr($pdata->ID), esc_attr($pdata->post_title));
                            } else {
                                printf('<option value="%1$s">%2$s</option>', esc_attr($pdata->ID), esc_attr($pdata->post_title));
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id="s_posts" style="<?= esc_attr('s_posts' === $display_on ? '' : 'display:none;'); ?>">
                <th class="hfcm-th-width">
                    <?= $data['postList']; ?>
                </th>
                <td>
                    <select class="nnr-wraptext" name="data[s_posts][]" multiple>
                        <option disabled>...</option>
                    </select>
                </td>
            </tr>

            <tr id="s_categories" style="<?= esc_attr($nnr_hfcm_categories_style); ?>">
                <th class="hfcm-th-width"><?= $data['categoryList']; ?></th>
                <td>
                    <select name="data[s_categories][]" multiple>
                        <?php
                        foreach ($nnr_hfcm_categories as $nnr_key_cat => $nnr_item_cat) {
                            foreach ($nnr_item_cat['terms'] as $nnr_item_cat_key => $nnr_item_cat_term) {
                                if (in_array($nnr_item_cat_term->term_id, $s_categories)) {
                                    echo "<option value='" . esc_attr($nnr_item_cat_term->term_id) . "' selected>" . esc_html($nnr_item_cat['name']) . " - " . esc_html($nnr_item_cat_term->name) . "</option>";
                                } else {
                                    echo "<option value='" . esc_attr($nnr_item_cat_term->term_id) . "'>" . esc_html($nnr_item_cat['name']) . " - " . esc_html($nnr_item_cat_term->name) . "</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id="s_tags" style="<?= esc_attr($nnr_hfcm_tags_style); ?>">
                <th class="hfcm-th-width"><?= $data['tagList']; ?></th>
                <td>
                    <select name="data[s_tags][]" multiple>
                        <?php
                        foreach ($nnr_hfcm_tags as $nnr_key_cat => $nnr_item_tag) {
                            foreach ($nnr_item_tag['terms'] as $nnr_item_tag_key => $nnr_item_tag_term) {
                                if (in_array($nnr_item_tag_term->term_id, $s_tags)) {
                                    echo "<option value='" . esc_attr($nnr_item_tag_term->term_id) . "' selected>" . esc_html($nnr_item_tag['name']) . " - " . esc_html($nnr_item_tag_term->name) . "</option>";
                                } else {
                                    echo "<option value='" . esc_attr($nnr_item_tag_term->term_id) . "'>" . esc_html($nnr_item_tag['name']) . " - " . esc_html($nnr_item_tag_term->name) . "</option>";
                                }
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id="c_posttype" style="<?= esc_attr($nnr_hfcm_custom_posts_style); ?>">
                <th class="hfcm-th-width"><?= $data['postType']; ?></th>
                <td>
                    <select name="data[s_custom_posts][]" multiple>
                        <?php
                        foreach ($nnr_hfcm_custom_post_types as $cpkey => $cpdata) {
                            if (in_array($cpkey, $s_custom_posts)) {
                                echo "<option value='" . esc_attr($cpkey) . "' selected>" . esc_html($cpdata) . "</option>";
                            } else {
                                echo "<option value='" . esc_attr($cpkey) . "'>" . esc_html($cpdata) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id="lp_count" style="<?= esc_attr($nnr_hfcm_lpcount_style); ?>">
                <th class="hfcm-th-width"><?= $data['postCount']; ?></th>
                <td>
                    <select name="data[lp_count]">
                        <?php
                        for ($i = 1; $i <= 20; $i++) {
                            if ($i == $lp_count) {
                                echo "<option value='" . esc_attr($i) . "' selected>" . esc_html($i) . "</option>";
                            } else {
                                echo "<option value='" . esc_attr($i) . "'>" . esc_html($i) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php
            if (in_array($display_on, array(
                's_posts', 's_pages', 's_custom_posts', 's_tags',
                'latest_posts'
            ))) {
                $nnr_hfcm_locations = array(
                    'header'         => __('Header', 'header-footer-code-manager'),
                    'before_content' => __('Before Content', 'header-footer-code-manager'),
                    'after_content'  => __('After Content', 'header-footer-code-manager'),
                    'footer'         => __('Footer', 'header-footer-code-manager')
                );
            } else {
                $nnr_hfcm_locations = array(
                    'header' => __('Header', 'header-footer-code-manager'),
                    'footer' => __('Footer', 'header-footer-code-manager')
                );
            }
            ?>
            <tr id="locationtr" style="<?= esc_attr($nnr_hfcm_location_style); ?>">
                <th class="hfcm-th-width">
                    <?= $data['location']; ?>
                </th>
                <td>
                    <select name="data[location]" id="data_location">
                        <?php
                        foreach ($nnr_hfcm_locations as $lkey => $statusv) {
                            if ($location === $lkey) {
                                echo "<option value='" . esc_attr($lkey) . "' selected='selected'>" . esc_html($statusv) . '</option>';
                            } else {
                                echo "<option value='" . esc_attr($lkey) . "'>" . esc_html($statusv) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <p>
                        <b><?php _e("Note", 'header-footer-code-manager'); ?></b>: <?php _e("Not all locations (such as before content) exist on all page/post types. The location will only appear as an option if the appropriate hook exists on the page.", 'header-footer-code-manager'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th class="hfcm-th-width"><?= $data['deviceDisplay']; ?></th>
                <td>
                    <select name="data[device_type]">
                        <?php
                        foreach ($nnr_hfcm_device_type_array as $smkey => $typev) {
                            if ($device_type === $smkey) {
                                echo "<option value='" . esc_attr($smkey) . "' selected='selected'>" . esc_html($typev) . '</option>';
                            } else {
                                echo "<option value='" . esc_attr($smkey) . "'>" . esc_html($typev) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th class="hfcm-th-width"><?= $data['status']; ?></th>
                <td>
                    <select name="data[status]">
                        <?php
                        foreach ($nnr_hfcm_status_array as $skey => $statusv) {
                            if ($status === $skey) {
                                echo "<option value='" . esc_attr($skey) . "' selected='selected'>" . esc_html($statusv) . '</option>';
                            } else {
                                echo "<option value='" . esc_attr($skey) . "'>" . esc_html($statusv) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php if ($update) : ?>
                <tr>
                    <th class="hfcm-th-width"><?= $data['shortcode']; ?></th>
                    <td>
                        <p>
                            [hfcm id="<?= esc_html($id); ?>"]
                            <?php if ($update) :
                                ?>
                                <a data-shortcode='[hfcm id="<?= absint($id); ?>"]'
                                   href="javascript:void(0);" class="nnr-btn-click-to-copy nnr-btn-copy-inline"
                                   id="hfcm_copy_shortcode">
                                    <?= $data['copy']; ?>
                                </a>
                            <?php endif; ?>
                        </p>

                    </td>
                </tr>
                <tr>
                    <th class="hfcm-th-width">
                        <?= $data['changelog']; ?>
                    </th>
                    <td>
                        <p>
                            <?= $data['snippetCreatedBy']; ?>
                            <b><?= esc_html($createdby ?? ""); ?></b>
                            <?= _e('on', 'header-footer-code-manager') . ' ' . date_i18n(get_option('date_format'), strtotime($createdon ?? "")) . ' ' . __('at', 'header-footer-code-manager') . ' ' . date_i18n(get_option('time_format'), strtotime($createdon)) ?>
                            <br/>
                            <?php if ( !empty($lastmodifiedby)) : ?>
                                <?= $data['lastEditedBy']; ?>
                                <b><?= esc_html($lastmodifiedby); ?></b> <?= _e('on', 'header-footer-code-manager') . ' ' . date_i18n(get_option('date_format'), strtotime($lastrevisiondate ?? "")) . ' ' . __('at', 'header-footer-code-manager') . ' ' . date_i18n(get_option('time_format'), strtotime($lastrevisiondate)) ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <div class="nnr-mt-20">
            <h1><?= $data['snippet']; ?> / <?= $data['code'] ?></h1>
            <div class="nnr-mt-20 nnr-hfcm-codeeditor-box">
                <textarea name="data[snippet]" aria-describedby="nnr-newcontent-description" id="nnr_newcontent"
                          rows="20"><?= html_entity_decode($snippet); ?></textarea>

                <p class="notice notice-warning nnr-padding10" id="nnr-snippet-warning">
                    <?php _e('Warning: Using improper code or untrusted sources code can break your site or create security risks. <a href="https://draftpress.com/security-risks-of-wp-plugins-that-allow-code-editing-or-insertion" target="_blank">Learn more</a>.', 'header-footer-code-manager'); ?>
                </p>
                <div class="wp-core-ui">
                    <input type="submit"
                           name="<?= $update ? 'update' : 'insert'; ?>"
                           value="<?= ($update ? $data['update'] : $data['save']) ?>"
                           class="button button-primary button-large nnr-btnsave">
                    <?php if ($update) : ?>
                        <a onclick="return nnr_confirm_delete_snippet();"
                           href="<?= $data['deleteNonceUrl']; ?>"
                           class="button button-secondary button-large nnr-btndelete"><?= $data['delete']; ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
if (defined('DISALLOW_FILE_EDIT') && true === DISALLOW_FILE_EDIT && !get_user_meta(get_current_user_id(), 'hfcm_file_edit_plugin_notice_dismissed', true)) : ?>

    <div id="file-editor-warning" class="notification-dialog-wrap file-editor-warning hide-if-no-js">
        <div class="notification-dialog-background"></div>
        <div class="notification-dialog">
            <div class="file-editor-warning-content">
                <div class="file-editor-warning-message">
                    <h1>Heads up!</h1>
                    <p>
                        <?php _e('Your site has <a href="https://draftpress.com/disallow-file-edit-setting-wordpress" target="_blank">disallow_file_edit</a> setting enabled inside the wp-config file to prevent file edits. By using this plugin, you acknowledge that you know what you’re doing and intend on adding code snippets only from trusted sources.', 'header-footer-code-manager'); ?>
                    </p>
                </div>
                <p>
                    <a href="<?= $data['fileEditDismissAction']; ?>"
                       class="file-editor-warning-dismiss button button-primary" id="nnr-dismiss-editor-warning">I
                        understand</a>
                </p>
            </div>
        </div>
    </div>

<?php endif; ?>
