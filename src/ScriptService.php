<?php

namespace Mrzkit\WpPluginSnippetCodeManager;

class ScriptService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ScriptRepository();
    }

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

        if ( !empty($ids)) {
            $snippets = $this->repository->selectIncludeIds($ids);

            if ( !empty($snippets)) {
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
        }
    }

    public function importSnippets()
    {
    }
}
