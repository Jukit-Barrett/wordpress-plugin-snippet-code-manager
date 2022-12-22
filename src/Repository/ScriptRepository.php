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

    // Create Table
    public function createTable()
    {
        return $this->model->createTable();
    }

    // Drop Table
    public function dropTable()
    {
        return $this->model->dropTable();
    }

    // Check for Exclude Pages
    public function checkExcludePage()
    {
        $exPages = $this->model->getColumn('ex_pages');

        if (empty($exPages)) {
            $this->model->alterColumnExPages();
        }
    }

    // Check for Exclude Posts
    public function checkExcludePosts()
    {
        $exPosts = $this->model->getColumn('ex_posts');

        if (empty($exPosts)) {
            $this->model->alterColumnExPages();
        }
    }

    // Check for Snippet Type
    public function checkSnippetType()
    {
        $snippetType = $this->model->getColumn('snippet_type');

        if (empty($snippetType)) {
            $this->model->alterColumnSnippetType();
        }
    }

    // Alter Other Fields
    public function alterOtherFields()
    {
        $this->model->alterSnippet();
        $this->model->alterDisplayOn();
        $this->model->alterSPages();
    }

    // Delete
    public function delete($id)
    {
        $id = (int) $id;

        return $this->model->delete($id);
    }

    // Get Snippet
    public function getSnippet($id)
    {
        $snippet = $this->model->getSnippet($id);

        $list = [];

        foreach ($snippet as $item) {
            $o = [
                'script_id'          => $item->script_id,
                'name'               => $item->name,
                'snippet'            => $item->snippet,
                'snippet_type'       => $item->snippet_type,
                'device_type'        => $item->device_type,
                'location'           => $item->location,
                'display_on'         => $item->display_on,
                'lp_count'           => $item->lp_count,
                's_pages'            => $item->s_pages,
                'ex_pages'           => $item->ex_pages,
                's_posts'            => $item->s_posts,
                'ex_posts'           => $item->ex_posts,
                's_custom_posts'     => $item->s_custom_posts,
                's_categories'       => $item->s_categories,
                's_tags'             => $item->s_tags,
                'status'             => $item->status,
                'created_by'         => $item->created_by,
                'last_modified_by'   => $item->last_modified_by,
                'created'            => $item->created,
                'last_revision_date' => $item->last_revision_date,
            ];

            $o['s_pages'] = json_decode($item->s_pages);
            if ( !is_array($o['s_pages'])) {
                $o['s_pages'] = [];
            }

            $o['ex_pages'] = json_decode($item->ex_pages);
            if ( !is_array($o['ex_pages'])) {
                $o['ex_pages'] = [];
            }

            $o['s_posts'] = json_decode($item->s_posts);
            if ( !is_array($o['s_posts'])) {
                $o['s_posts'] = [];
            }

            $o['ex_posts'] = json_decode($item->ex_posts);
            if ( !is_array($o['ex_posts'])) {
                $o['ex_posts'] = [];
            }

            $o['s_custom_posts'] = json_decode($item->s_custom_posts);
            if ( !is_array($o['s_custom_posts'])) {
                $o['s_custom_posts'] = [];
            }

            $o['s_categories'] = json_decode($item->s_categories);
            if ( !is_array($o['s_categories'])) {
                $o['s_categories'] = [];
            }

            $o['s_tags'] = json_decode($item->s_tags);
            if ( !is_array($o['s_tags'])) {
                $o['s_tags'] = [];
            }

            $list[] = $o;
        }

        return $list;
    }

    // Record Count
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
        $id = absint($id);

        $hideDevice = wp_is_mobile() ? 'desktop' : 'mobile';

        $script = $this->model->selectWithoutDeviceType($id, $hideDevice);

        return empty($script) ? [] : $script[0];
    }

    // 激活片段
    public function activateSnippet($id)
    {
        $id = (int) $id;

        return $this->model->activateSnippet($id);
    }

    // 禁用片段
    public function deactivateSnippet($id)
    {
        $id = (int) $id;

        return $this->model->deactivateSnippet($id);
    }

    // 检索列表
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

    // 更新代码片断
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

    // 查询所有代码片断
    public function selectAllSnippets()
    {
        $result = $this->model->selectAllSnippets();

        return $result;
    }

    // 查询指定ID集合
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
}
