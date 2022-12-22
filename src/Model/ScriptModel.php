<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Model;

use InvalidArgumentException;

class ScriptModel
{
    private $db;

    private $tableName = 'kit_scripts';

//    private $tableName = 'hfcm_scripts';

    public function __construct()
    {
        global $wpdb;

        $wpdb->show_errors();

        $this->db = $wpdb;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getPrefix() . $this->tableName;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->db->prefix;
    }

    /**
     * @desc 创建数据表
     * @return array
     */
    public function createTable()
    {
        $charsetCollate = $this->db->get_charset_collate();

        $tableName = $this->getTableName();

        $sql = "CREATE TABLE `{$tableName}` (
                    `script_id` int(10) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) DEFAULT NULL,
                    `snippet` LONGTEXT,
                    `snippet_type` enum('html', 'js', 'css') DEFAULT 'html',
                    `device_type` enum('mobile','desktop', 'both') DEFAULT 'both',
                    `location` varchar(100) NOT NULL,
                    `display_on` enum('All','s_pages', 's_posts','s_categories','s_custom_posts','s_tags', 's_is_home', 's_is_search', 's_is_archive','latest_posts','manual') NOT NULL DEFAULT 'All',
                    `lp_count` int(10) DEFAULT NULL,
                    `s_pages` MEDIUMTEXT DEFAULT NULL,
                    `ex_pages` MEDIUMTEXT DEFAULT NULL,
                    `s_posts` MEDIUMTEXT DEFAULT NULL,
                    `ex_posts` MEDIUMTEXT DEFAULT NULL,
                    `s_custom_posts` varchar(300) DEFAULT NULL,
                    `s_categories` varchar(300) DEFAULT NULL,
                    `s_tags` varchar(300) DEFAULT NULL,
                    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                    `created_by` varchar(300) DEFAULT NULL,
                    `last_modified_by` varchar(300) DEFAULT NULL,
                    `created` datetime DEFAULT NULL,
                    `last_revision_date` datetime DEFAULT NULL,
                    PRIMARY KEY (`script_id`)
                ) {$charsetCollate}";

        // dbDelta() 函数在这个文件中
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        return dbDelta($sql);
    }

    /**
     * @desc 删除数据表
     * @return bool|int|\mysqli_result|resource|null
     */
    public function dropTable()
    {
        $tableName = $this->getTableName();

        return $this->db->query("DROP TABLE IF EXISTS {$tableName}");
    }

    /**
     * @desc 获取字段
     * @param string $columnName
     * @return array|object|\stdClass[]|null
     */
    public function getColumn(string $columnName)
    {
        $tableName   = $this->getTableName();
        $tableSchema = $this->db->dbname;
        $sql         = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ";
        $prepare     = $this->db->prepare($sql, $tableSchema, $tableName, $columnName);

        return $this->db->get_results($prepare);
    }

    /**
     * @desc 更新字段: ex_pages
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterColumnExPages()
    {
        $tableName = $this->getTableName();

        $sql = "ALTER TABLE `{$tableName}` ADD `ex_pages` varchar(300) DEFAULT 0 AFTER `s_pages`";

        return $this->db->query($sql);
    }

    /**
     * @desc 更新字段: ex_posts
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterColumnExPosts()
    {
        $tableName = $this->getTableName();

        $sql = "ALTER TABLE `{$tableName}` ADD `ex_posts` varchar(300) DEFAULT 0 AFTER `s_pages`";

        return $this->db->query($sql);
    }

    /**
     * @desc 更新字段 snippet_type
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterColumnSnippetType()
    {
        $tableName = $this->getTableName();

        $nnr_alter_sql = "ALTER TABLE `{$tableName}` ADD `snippet_type` enum('html', 'js', 'css') DEFAULT 'html' AFTER `snippet`";

        return $this->db->query($nnr_alter_sql);
    }

    /**
     * @desc 更新字段 snippet
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterSnippet()
    {
        $tableName = $this->getTableName();
        $sql       = "ALTER TABLE `{$tableName}` CHANGE `snippet` `snippet` LONGTEXT NULL";

        return $this->db->query($sql);
    }

    /**
     * @desc 更新字段 display_on
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterDisplayOn()
    {
        $tableName = $this->getTableName();

        $sql = "ALTER TABLE `{$tableName}` CHANGE `display_on` `display_on` ENUM('All','s_pages','s_posts','s_categories','s_custom_posts','s_tags','s_is_home','s_is_archive','s_is_search','latest_posts','manual') DEFAULT 'All' NOT NULL";

        return $this->db->query($sql);
    }

    /**
     * @desc 更新字段 s_pages
     * @return bool|int|\mysqli_result|resource|null
     */
    public function alterSPages()
    {
        $tableName = $this->getTableName();

        $sql = "ALTER TABLE `{$tableName}` CHANGE `s_pages` `s_pages` MEDIUMTEXT NULL, CHANGE `ex_pages` `ex_pages` MEDIUMTEXT NULL, CHANGE `s_posts` `s_posts` MEDIUMTEXT NULL, CHANGE `ex_posts` `ex_posts` MEDIUMTEXT NULL";

        return $this->db->query($sql);
    }

    /**
     * @desc 删除
     * @param int $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function delete($id)
    {
        $tableName = $this->getTableName();

        return $this->db->delete($tableName, ['script_id' => $id], ['%d']);
    }

    /**
     * @desc 获取单条代码片断
     * @param $id
     * @param array $fields
     * @return array|object|\stdClass[]|null
     */
    public function getSnippet($id, $fields = [])
    {
        if (empty($fields)) {
            $fieldString = '*';
        } else {
            $fieldString = '`' . join('`,`', $fields) . '`';
        }

        $tableName = $this->getTableName();

        $sql = "SELECT {$fieldString} FROM `{$tableName}` WHERE script_id = %s";

        $sql = $this->db->prepare($sql, $id);

        $result = $this->db->get_results($sql);

        return $result;
    }

    /**
     * @desc 总行数
     * @param $customVar
     * @return string|null
     */
    public function recordCount($customVar = 'all')
    {
        $tableName = $this->getTableName();

        $sql = "SELECT COUNT(*) FROM `{$tableName}`";

        $placeholderArgs = [];

        if (in_array($customVar, ['inactive', 'active'])) {
            $sql               .= " WHERE status = %s";
            $placeholderArgs[] = $customVar;
        }

        if ( !empty($placeholderArgs)) {
            $sql = $this->db->prepare($sql, $placeholderArgs);
        }

        return $this->db->get_var($sql);
    }

    /**
     * @desc 查询桌面设备
     * @param $id
     * @return array|object|\stdClass[]|null
     */
    public function selectDesktopDeviceType($id)
    {
        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE status='active' AND device_type = 'desktop' AND script_id = %d";

        $sql = $this->db->prepare($sql, $id);

        return $this->db->get_results($sql);
    }

    /**
     * @desc 查询移动设备
     * @param $id
     * @return array|object|\stdClass[]|null
     */
    public function selectMobileDeviceType($id)
    {
        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE status='active' AND device_type = 'mobile' AND script_id = %d";

        $sql = $this->db->prepare($sql, $id);

        return $this->db->get_results($sql);
    }

    /**
     * @desc 查询指定设备类型之外的
     * @param int $id
     * @param string $deviceType
     * @return array|object|stdClass[]|null
     */
    public function selectWithoutDeviceType($id, $deviceType)
    {
        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE `status`='active' AND `device_type` != %s AND `script_id` = %d";

        $sql = $this->db->prepare($sql, $deviceType, $id);

        return $this->db->get_results($sql);
    }

    /**
     * @desc 激活片段
     * @param $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function activateSnippet($id)
    {
        $tableName = $this->getTableName();

        $data = [
            'status' => 'active',
        ];

        $where = [
            'script_id' => $id,
        ];

        $format = ['%s'];

        $whereFormat = ['%d'];

        return $this->db->update($tableName, $data, $where, $format, $whereFormat);
    }

    /**
     * @desc 禁用片段
     * @param $id
     * @return bool|int|\mysqli_result|resource|null
     */
    public function deactivateSnippet($id)
    {
        $tableName = $this->getTableName();

        $data = [
            'status' => 'inactive',
        ];

        $where = [
            'script_id' => $id,
        ];

        $format = ['%s'];

        $whereFormat = ['%d'];

        return $this->db->update($tableName, $data, $where, $format, $whereFormat);
    }

    /**
     * @desc 检索
     * @param $params
     * @return array|object|\stdClass[]
     */
    public function selectSnippets($params)
    {
        $inputParams = [
            'perPage'     => (int) ($params['perPage'] ?? 20),
            'pageNumber'  => (int) ($params['pageNumber'] ?? 1),
            'orderBy'     => (string) ($params['orderBy'] ?? ""),
            'order'       => (string) ($params['order'] ?? ""),
            'status'      => (string) ($params['status'] ?? ""),
            'snippetType' => (string) ($params['snippetType'] ?? ""),
            'name'        => (string) ($params['name'] ?? ""),
        ];

        // Init
        $placeholderArgs = [];

        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE script_id > 0";

        // Status
        if ( !empty($inputParams['status']) && in_array($inputParams['status'], ['inactive', 'active'])) {
            $sql               .= " AND `status` = '%s' ";
            $placeholderArgs[] = $inputParams['status'];
        }

        // Snippet Type
        if ( !empty($inputParams['snippetType']) && in_array($inputParams['snippetType'], ['html', 'css', 'js'])) {
            $sql               .= " AND `snippet_type` = '%s' ";
            $placeholderArgs[] = $inputParams['snippetType'];
        }

        // Name
        if ( !empty($inputParams['name'])) {
            $sql               .= " AND `name` LIKE %s ";
            $placeholderArgs[] = '%' . $inputParams['name'] . '%';
        }

        // Order
        $order = 'ASC';
        if ( !empty($inputParams['order']) && in_array($inputParams['order'], ['desc', 'asc'])) {
            $order = $inputParams['order'];
        }

        // OrderBy
        $orderBy = 'script_id';
        if ( !empty($inputParams['orderBy']) && in_array($inputParams['orderBy'], ['script_id', 'name', 'location'])) {
            $orderBy = $inputParams['orderBy'];
        }

        $sql               .= ' ORDER BY %s %s LIMIT %d OFFSET %d';
        $placeholderArgs[] = $orderBy;
        $placeholderArgs[] = $order;
        $placeholderArgs[] = $inputParams['perPage'];
        $placeholderArgs[] = ($inputParams['pageNumber'] - 1) * $inputParams['perPage'];

        $sql = $this->db->prepare($sql, $placeholderArgs);

        $result = $this->db->get_results($sql, 'ARRAY_A');

        return $result ?? [];
    }

    /**
     * @desc 添加片段
     * @param $params
     * @return int
     */
    public function insert($params)
    {
        $data = [
            'name'           => (string) $params['name'],
            'snippet'        => (string) $params['snippet'],
            'snippet_type'   => (string) $params['snippetType'],
            'device_type'    => (string) $params['deviceType'],
            'location'       => (string) $params['location'],
            'display_on'     => (string) $params['displayOn'],
            'status'         => (string) $params['status'],
            'lp_count'       => (int) $params['lpCount'],
            's_pages'        => (string) $params['sPages'],
            'ex_pages'       => (string) $params['exPages'],
            's_posts'        => (string) $params['exPages'],
            'ex_posts'       => (string) $params['exPosts'],
            's_custom_posts' => (string) $params['sCustomPosts'],
            's_categories'   => (string) $params['sCategories'],
            's_tags'         => (string) $params['sTags'],
            'created'        => (string) $params['created'],
            'created_by'     => (string) $params['createdBy'],
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',];

        $tableName = $this->getTableName();

        $this->db->insert($tableName, $data, $format);

        return $this->db->insert_id;
    }

    /**
     * @desc 更新片段
     * @param int $id
     * @param array $params
     * @return bool|int|\mysqli_result|resource|null
     */
    public function update($id, $params)
    {
        $data = [
            'last_revision_date' => [
                'value'  => current_time('Y-m-d H:i:s'),
                'format' => '%s',
            ],
        ];

        if (isset($params['name'])) {
            $data['name'] = [
                'value'  => $params['name'],
                'format' => '%s',
            ];
        }

        if (isset($params['snippet'])) {
            $data['snippet'] = [
                'value'  => $params['snippet'],
                'format' => '%s',
            ];
        }

        if (isset($params['snippetType'])) {
            $data['snippet_type'] = [
                'value'  => $params['snippetType'],
                'format' => '%s',
            ];
        }

        if (isset($params['deviceType'])) {
            $data['device_type'] = [
                'value'  => $params['deviceType'],
                'format' => '%s',
            ];
        }

        if (isset($params['location'])) {
            $data['location'] = [
                'value'  => $params['location'],
                'format' => '%s',
            ];
        }
        if (isset($params['displayOn'])) {
            $data['display_on'] = [
                'value'  => $params['displayOn'],
                'format' => '%s',
            ];
        }

        if (isset($params['status'])) {
            $data['status'] = [
                'value'  => $params['status'],
                'format' => '%s',
            ];
        }

        if (isset($params['lpCount'])) {
            $data['lp_count'] = [
                'value'  => $params['lpCount'],
                'format' => '%s',
            ];
        }

        if (isset($params['sPages'])) {
            $data['s_pages'] = [
                'value'  => $params['sPages'],
                'format' => '%s',
            ];
        }
        if (isset($params['exPages'])) {
            $data['ex_pages'] = [
                'value'  => $params['exPages'],
                'format' => '%s',
            ];
        }

        if (isset($params['exPosts'])) {
            $data['ex_posts'] = [
                'value'  => $params['exPosts'],
                'format' => '%s',
            ];
        }

        if (isset($params['sCustomPosts'])) {
            $data['s_custom_posts'] = [
                'value'  => $params['sCustomPosts'],
                'format' => '%s',
            ];
        }

        if (isset($params['sCategories'])) {
            $data['s_categories'] = [
                'value'  => $params['sCategories'],
                'format' => '%s',
            ];
        }

        if (isset($params['sTags'])) {
            $data['s_tags'] = [
                'value'  => $params['sTags'],
                'format' => '%s',
            ];
        }

        if (isset($params['created'])) {
            $data['created'] = [
                'value'  => $params['created'],
                'format' => '%s',
            ];
        }

        if (isset($params['createdBy'])) {
            $data['created_by'] = [
                'value'  => $params['createdBy'],
                'format' => '%s',
            ];
        }

        if (isset($params['lastModifiedBy'])) {
            $data['last_modified_by'] = [
                'value'  => $params['lastModifiedBy'],
                'format' => '%s',
            ];
        }

        $dataUpdate = [];

        $dataFormat = [];

        foreach ($data as $key => $item) {
            $dataUpdate[$key] = $item['value'];
            $dataFormat[]     = $item['format'];
        }

        $tableName = $this->getTableName();

        $where = ['script_id' => $id];

        $whereFormat = ['%s'];

        return $this->db->update($tableName, $dataUpdate, $where, $dataFormat, $whereFormat);
    }

    /**
     * @desc 通过位置查询
     * @param $hideDevice
     * @param $location
     * @return array|object|\stdClass[]|null
     */
    public function selectByLocation($hideDevice, $location)
    {
        if ( !in_array($hideDevice, ['desktop', 'mobile'])) {
            throw new InvalidArgumentException("参数错误");
        }

        $inputParams = [
            'hideDevice' => (string) $hideDevice,
            'location'   => (string) $location,
        ];

        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE status='active' AND device_type != %s ";

        $placeholderArgs = [
            $inputParams['hideDevice'],
        ];

        if (in_array($inputParams['location'], ['header', 'footer'])) {
            $sql .= " AND `location` = %s";

            $placeholderArgs[] = $inputParams['location'];
        } else {
            $sql .= " AND `location` IN ( 'before_content', 'after_content' )";
        }

        $sql = $this->db->prepare($sql, $placeholderArgs);

        $scripts = $this->db->get_results($sql, ARRAY_A);

        return $scripts;
    }

    /**
     * @desc
     * @param string $device 设备
     * @param string $location 位置
     * @return array|object|\stdClass[]|null
     */
    public function selectDeviceLocation($device, $location)
    {
        $tableName   = $this->getTableName();

        $placeholder = [];

        $sql = "SELECT * FROM `{$tableName}` WHERE `status`='active' ";

        if (in_array($device, ['desktop', 'mobile'])) {
            $sql           .= " AND `device_type` In ( %s, 'both' )";
            $placeholder[] = $device;
        }

        if (in_array($location, ['header', 'footer', 'before_content', 'after_content'])) {
            $sql           .= " AND `location` = %s";
            $placeholder[] = $location;
        } else {
            // $location = false
            $sql           .= " AND `location` IN ('before_content', 'after_content')";
        }

        $sql .= ' LIMIT 20000 ';

        $sql = $this->db->prepare($sql, $placeholder);

        $scripts = $this->db->get_results($sql, ARRAY_A);

        return $scripts;
    }

    // 查询所有代码片断
    public function selectAllSnippets()
    {
        $tableName = $this->getTableName();

        $sql = "SELECT * from `{$tableName}` LIMIT 10000";

        $scripts = $this->db->get_results($sql);

        return $scripts;
    }

    /**
     * @desc 查询指定ID集合
     * @param $ids
     * @return array|object|\stdClass[]|null
     */
    public function selectIncludeIds($ids)
    {
        $ids = (array) $ids;

        $separated = str_repeat('%d,', count($ids));

        if ( !empty($separated)) {
            $separated = substr($separated, 0, strlen($separated) - 1);
        }

        $tableName = $this->getTableName();

        $sql = "SELECT * FROM `{$tableName}` WHERE `script_id` IN ( {$separated} )";

        $sql = $this->db->prepare($sql, $ids);

        $snippets = $this->db->get_results($sql);

        return $snippets;
    }
}
