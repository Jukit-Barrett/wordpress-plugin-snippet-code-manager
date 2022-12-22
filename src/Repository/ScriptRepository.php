<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Repository;

use Mrzkit\WpPluginSnippetCodeManager\Model\ScriptModel;
use Mrzkit\WpPluginSnippetCodeManager\Util\GeneralUtil;

class ScriptRepository
{
    private $model;

    public function __construct()
    {
        $this->model = new ScriptModel();
    }

    /**
     * @desc Create Table
     * @return array
     */
    public function createTable()
    {
        return $this->model->createTable();
    }

    /**
     * @desc Drop Table
     * @return bool|int|\mysqli_result|resource|null
     */
    public function dropTable()
    {
        return $this->model->dropTable();
    }

    /**
     * @desc Check for Exclude Pages
     */
    public function checkExcludePage()
    {
        $exPages = $this->model->getColumn('ex_pages');

        if (empty($exPages)) {
            $this->model->alterColumnExPages();
        }
    }

    /**
     * @desc Check for Exclude Posts
     */
    public function checkExcludePosts()
    {
        $exPosts = $this->model->getColumn('ex_posts');

        if (empty($exPosts)) {
            $this->model->alterColumnExPosts();
        }
    }

    /**
     * @desc Check for Snippet Type
     */
    public function checkSnippetType()
    {
        $snippetType = $this->model->getColumn('snippet_type');

        if (empty($snippetType)) {
            $this->model->alterColumnSnippetType();
        }
    }

    /**
     * @desc Alter Other Fields
     */
    public function alterOtherFields()
    {
        $this->model->alterSnippet();
        $this->model->alterDisplayOn();
        $this->model->alterSPages();
    }

    /**
     * @desc Delete
     * @param $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function delete($id)
    {
        $id = (int) $id;

        return $this->model->delete($id);
    }

    /**
     * @desc Batch Delete
     * @param $ids
     * @return bool|int|\mysqli_result|resource|null
     */
    public function batchDelete($ids)
    {
        $ids = (array) $ids;

        if (empty($ids)) {
            return false;
        }

        // 返回删除数
        return $this->model->batchDelete($ids);
    }

    /**
     * @desc Get Snippet
     * @param $id
     * @return array
     */
    public function getSnippet($id)
    {
        $snippet = $this->model->getSnippet($id);

        $snippet = $this->renderHandleIterator($snippet);

        return $snippet;
    }

    /**
     * @desc 行数
     * @param $customVar
     * @return string|null
     */
    public function recordCount($customVar = 'all')
    {
        $customVar = GeneralUtil::sanitizeText($customVar);

        return $this->model->recordCount($customVar);
    }

    /**
     * @desc 查询指定设备类型之外的
     * @param int $id
     * @return array|mixed|stdClass
     */
    public function selectWithoutDeviceType($id)
    {
        $id = (int) $id;

        $hideDevice = wp_is_mobile() ? 'desktop' : 'mobile';

        $script = $this->model->selectWithoutDeviceType($id, $hideDevice);

        return $script;
    }

    /**
     * @desc 激活片段
     * @param $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function activateSnippet($id)
    {
        $id = (int) $id;

        return $this->model->activateSnippet($id);
    }

    /**
     * @desc 禁用片段
     * @param $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function deactivateSnippet($id)
    {
        $id = (int) $id;

        return $this->model->deactivateSnippet($id);
    }

    /**
     * @desc 检索列表
     * @param $params
     * @return array|object|\stdClass[]
     */
    public function selectSnippets($params)
    {
        if (isset($params['perPage'])) {
            $params['perPage'] = absint($params['perPage']);
        }
        if (isset($params['pageNumber'])) {
            $params['pageNumber'] = absint($params['pageNumber']);
        }
        if (isset($params['orderBy'])) {
            $params['orderBy'] = sanitize_sql_orderby($params['orderBy']);
        }
        if (isset($params['order'])) {
            $params['order'] = sanitize_sql_orderby($params['order']);
        }
        if (isset($params['status'])) {
            $params['status'] = GeneralUtil::sanitizeText($params['status']);
        }
        if (isset($params['snippetType'])) {
            $params['snippetType'] = GeneralUtil::sanitizeText($params['snippetType']);
        }
        if (isset($params['name'])) {
            $params['name'] = GeneralUtil::sanitizeText($params['name']);
        }

        $result = $this->model->selectSnippets($params);

        return $result;
    }

    /**
     * @desc 添加代码片断
     * @param $params
     * @return int
     */
    public function insert($params)
    {
        $params['sPages']       = GeneralUtil::sanitizeArray($params['sPages']);
        $params['exPages']      = GeneralUtil::sanitizeArray($params['sPages']);
        $params['sPosts']       = GeneralUtil::sanitizeArray($params['sPosts']);
        $params['exPosts']      = GeneralUtil::sanitizeArray($params['exPosts']);
        $params['sCustomPosts'] = GeneralUtil::sanitizeArray($params['sCustomPosts']);
        $params['sCategories']  = GeneralUtil::sanitizeArray($params['sCategories']);
        $params['sTags']        = GeneralUtil::sanitizeArray($params['sTags']);
        $params['snippet']      = htmlspecialchars_decode(stripslashes_deep($params['snippet']));

        $data = [
            'name'         => GeneralUtil::sanitizeText($params['name']),
            'snippet'      => htmlentities($params['snippet']),
            'snippetType'  => GeneralUtil::sanitizeText($params['snippetType']),
            'deviceType'   => GeneralUtil::sanitizeText($params['deviceType']),
            'location'     => GeneralUtil::sanitizeText($params['location']),
            'displayOn'    => GeneralUtil::sanitizeText($params['displayOn']),
            'status'       => GeneralUtil::sanitizeText($params['status']),
            'lpCount'      => GeneralUtil::sanitizeText($params['lpCount']),
            'sPages'       => wp_json_encode($params['sPages']),
            'exPages'      => wp_json_encode($params['exPages']),
            'sPosts'       => wp_json_encode($params['sPosts']),
            'exPosts'      => wp_json_encode($params['exPosts']),
            'sCustomPosts' => wp_json_encode($params['sCustomPosts']),
            'sCategories'  => wp_json_encode($params['sCategories']),
            'sTags'        => wp_json_encode($params['sTags']),
            'created'      => current_time('Y-m-d H:i:s'),
            'createdBy'    => GeneralUtil::sanitizeText($params['createdBy']),
        ];

        return $this->model->insert($data);
    }

    /**
     * @desc 更新代码片断
     * @param $id
     * @param $params
     * @return bool|int|\mysqli_result|resource|null
     */
    public function update($id, $params)
    {
        if (isset($params['name'])) {
            $data['name'] = GeneralUtil::sanitizeText($params['name']);
        }

        if (isset($params['snippet'])) {
            $data['snippet'] = htmlentities($params['snippet']);
        }

        if (isset($params['snippetType'])) {
            $data['snippetType'] = GeneralUtil::sanitizeText($params['snippetType']);
        }

        if (isset($params['deviceType'])) {
            $data['deviceType'] = GeneralUtil::sanitizeText($params['deviceType']);
        }

        if (isset($params['location'])) {
            $data['location'] = GeneralUtil::sanitizeText($params['location']);
        }
        if (isset($params['displayOn'])) {
            $data['displayOn'] = GeneralUtil::sanitizeText($params['displayOn']);
        }

        if (isset($params['status'])) {
            $data['status'] = GeneralUtil::sanitizeText($params['status']);
        }

        if (isset($params['lpCount'])) {
            $data['lp_count'] = GeneralUtil::sanitizeText($params['lpCount']);
        }

        if (isset($params['sPages'])) {
            $params['sPages'] = GeneralUtil::sanitizeArray($params['sPages']);
            $data['sPages']   = wp_json_encode($params['sPages']);
        }
        if (isset($params['exPages'])) {
            $params['exPages'] = GeneralUtil::sanitizeArray($params['sPages']);
            $data['ex_pages']  = wp_json_encode($params['exPages']);
        }

        if (isset($params['exPosts'])) {
            $params['exPosts'] = GeneralUtil::sanitizeArray($params['exPosts']);
            $params['exPosts'] = GeneralUtil::sanitizeArray($params['exPosts']);
            $data['exPosts']   = wp_json_encode($params['exPosts']);
        }

        if (isset($params['sCustomPosts'])) {
            $params['sCustomPosts'] = GeneralUtil::sanitizeArray($params['sCustomPosts']);
            $data['sCustomPosts']   = wp_json_encode($params['sCustomPosts']);
        }

        if (isset($params['sCategories'])) {
            $params['sCategories'] = GeneralUtil::sanitizeArray($params['sCategories']);
            $data['sCategories']   = wp_json_encode($params['sCategories']);
        }

        if (isset($params['sTags'])) {
            $params['sTags'] = GeneralUtil::sanitizeArray($params['sTags']);
            $data['sTags']   = wp_json_encode($params['sTags']);
        }

        if (isset($params['createdBy'])) {
            $data['createdBy'] = GeneralUtil::sanitizeText($params['createdBy']);
        }

        if (isset($params['lastModifiedBy'])) {
            $data['last_modified_by'] = GeneralUtil::sanitizeText($params['lastModifiedBy']);
        }

        $result = $this->model->update($id, $data);

        return $result;
    }

    /**
     * @desc 通过位置查询代码片断
     * @param $location
     * @return array
     */
    public function selectByLocation($location)
    {
        $hideDevice = wp_is_mobile() ? 'desktop' : 'mobile';

        $result = $this->model->selectByLocation($hideDevice, $location);

        foreach ($result as $item) {
            $list[] = $this->renderHandleIterator($item);
        }

        return $list;
    }

    /**
     * @desc 通过设备和位置查询代码片断
     * @param $location
     * @return array
     */
    public function selectDeviceLocation($location)
    {
        $device = wp_is_mobile() ? 'mobile' : 'desktop';

        $result = $this->model->selectDeviceLocation($device, $location);

        $list = [];

        foreach ($result as $item) {
            $list[] = $this->renderHandleIterator($item);
        }

        return $list;
    }

    /**
     * @desc 查询所有代码片断
     * @return array|object|\stdClass[]|null
     */
    public function selectAllSnippets()
    {
        $result = $this->model->selectAllSnippets();

        return $result;
    }

    /**
     * @desc 查询指定ID集合
     * @param $ids
     * @return array|object|\stdClass[]|null
     */
    public function selectIncludeIds($ids)
    {
        $ids = (array) $ids;

        $result = $this->model->selectIncludeIds($ids);

        return $result;
    }

    /**
     * @desc 对查询的数据进行处理
     * @param $script
     * @return array
     */
    protected function renderHandleIterator($script)
    {
        $script = (array) $script;

        if (empty($script)) {
            return [];
        }

        $data = [
            'script_id'          => (int) $script['script_id'],
            'name'               => $script['name'],
            'snippet'            => $script['snippet'],
            'snippet_type'       => $script['snippet_type'],
            'device_type'        => $script['device_type'],
            'location'           => $script['location'],
            'display_on'         => $script['display_on'],
            'lp_count'           => $script['lp_count'],
            's_pages'            => json_decode($script['s_pages'], true),
            'ex_pages'           => json_decode($script['ex_pages'], true),
            's_posts'            => json_decode($script['s_posts'], true),
            'ex_posts'           => json_decode($script['ex_posts'], true),
            's_custom_posts'     => json_decode($script['s_custom_posts'], true),
            's_categories'       => json_decode($script['s_categories'], true),
            's_tags'             => json_decode($script['s_tags'], true),
            'status'             => $script['status'],
            'created_by'         => $script['created_by'],
            'last_modified_by'   => $script['last_modified_by'],
            'created'            => $script['created'],
            'last_revision_date' => $script['last_revision_date'],
        ];

        return $data;
    }

    /**
     * @desc Get all posts type
     * @return string[]
     */
    public function getPostType()
    {
        // Get all posts type
        $args = [
            'public'   => true,
            '_builtin' => false,
        ];

        $output   = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        $postTypes = get_post_types($args, $output, $operator);

        $postTypes = array_values($postTypes);

        return $postTypes;
    }

    /**
     * @desc Get all posts
     * @param $postTypes
     * @return array
     */
    public function getPosts($postTypes)
    {
        $postTypes = (array) $postTypes;

        $params = [
            'post_type'      => $postTypes,
            'posts_per_page' => -1,
            'numberposts'    => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];

        return get_posts($params);
    }

    /**
     * @desc Get Categories
     * @return array
     */
    public function getCategories()
    {
        $args = [
            'public'       => true,
            'hierarchical' => true,
        ];

        $output     = 'objects'; // or objects
        $operator   = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);

        $categories = [];

        foreach ($taxonomies as $taxonomy) {
            $taxonomyCategories = get_categories(
                [
                    'taxonomy'   => $taxonomy->name,
                    'hide_empty' => 0
                ]
            );

            $taxonomyCategories = [
                'name'  => $taxonomy->label,
                'terms' => $taxonomyCategories
            ];

            $categories[] = $taxonomyCategories;
        }

        return $categories;
    }

    /**
     * @desc Get Tags
     * @return array
     */
    public function getTags()
    {
        $args = [
            'public'       => true,
            'hierarchical' => false,
        ];

        $output     = 'objects'; // or objects
        $operator   = 'and'; // 'and' or 'or'
        $taxonomies = get_taxonomies($args, $output, $operator);

        $tags = [];

        foreach ($taxonomies as $taxonomy) {
            $taxonomyTags = get_tags(
                [
                    'taxonomy'   => $taxonomy->name,
                    'hide_empty' => 0
                ]
            );

            $taxonomyTags = [
                'name'  => $taxonomy->label,
                'terms' => $taxonomyTags
            ];

            $tags[] = $taxonomyTags;
        }

        return $tags;
    }
}
