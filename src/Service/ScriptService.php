<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Service;

use Mrzkit\WpPluginSnippetCodeManager\Repository\ScriptRepository;

class ScriptService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ScriptRepository();
    }

    /**
     * @desc 执行卸载
     */
    public function uninstall()
    {
        // Drop a custom db table
        $this->repository->dropTable();

        delete_option('hfcm_db_version');
    }

    /**
     * @desc 导出指定代码片断
     * @param $snippetIds
     */
    public function exportSnippets($snippetIds)
    {
        $snippetIds = (array) $snippetIds;

        $len = strlen('snippet_');

        $ids = [];

        foreach ($snippetIds as $nnr_hfcm_snippet) {
            $id = absint(substr($nnr_hfcm_snippet, $len));

            if ( !empty($id)) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return false;
        }

        $repository = new ScriptRepository();

        $snippets = $repository->selectIncludeIds($ids);

        if (empty($snippets)) {
            return false;
        }

        $listSnippets = [
            "title"    => "Header Footer Code Manager",
            'snippets' => [],
        ];

        foreach ($snippets as $item) {
            unset($item->script_id);
            $listSnippets['snippets'][] = $item;
        }

        $file_name = 'hfcm-export-' . date('Y-m-d') . '.json';
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file_name}");
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($listSnippets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @desc 导入指定代码片断
     * @param $snippets
     * @return int
     */
    public function importSnippets($snippets)
    {
        $snippets = (array) $snippets;

        $allowSnippetType = ["html", "css", "js"];

        $allowLocation = ['header', 'before_content', 'after_content', 'footer'];

        $returnStatus = 1;

        $currentTime = current_time('Y-m-d H:i:s');

        // Current User
        $currentUser = wp_get_current_user();

        $displayName = $currentUser->display_name;

        $repository = new ScriptRepository();

        foreach ((array) $snippets['snippets'] as $item) {
            if ( !empty($item['snippet_type']) && !in_array($item['snippet_type'], $allowSnippetType)) {
                $returnStatus = 2;
                continue;
            }

            if ( !empty($item['location']) && !in_array($item['location'], $allowLocation)) {
                $returnStatus = 2;
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
                'status'      => $item['status'],
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

            $repository->insert($data);
        }

        return $returnStatus;
    }
}
