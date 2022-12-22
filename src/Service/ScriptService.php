<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Service;

use Mrzkit\WpPluginSnippetCodeManager\Repository\OptionRepository;
use Mrzkit\WpPluginSnippetCodeManager\Repository\ScriptRepository;
use Mrzkit\WpPluginSnippetCodeManager\Util\GeneralUtil;

class ScriptService
{
    private $repository;

    private $optionRepository;

    public function __construct()
    {
        $this->repository = new ScriptRepository();

        $this->optionRepository = new OptionRepository();
    }

    /**
     * @desc 执行卸载
     */
    public function uninstall()
    {
        // Drop a custom db table
        $this->repository->dropTable();

        // Delete Version
        $this->optionRepository->deleteVersion();
        $this->optionRepository->deleteActivationDate();
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

    /**
     * @desc Insert snippet
     * @param $dataInput
     * @return int
     */
    public function insert($dataInput)
    {
        $dataInput = (array) $dataInput;

        if (empty($dataInput)) {
            return -1;
        }

        // Check nonce
        check_admin_referer('create-snippet');

        if ('manual' === $dataInput['display_on']) {
            $dataInput['display_on'] = '';
        }

        // Current User
        $currentUser = wp_get_current_user();

        // Create new snippet
        $data = [
            'name'             => $dataInput['name'],
            'snippet'          => $dataInput['snippet'],
            'snippetType'      => $dataInput['snippet_type'],
            'deviceType'       => $dataInput['device_type'],
            'location'         => $dataInput['location'],
            'displayOn'        => $dataInput['display_on'],
            'status'           => $dataInput['status'],
            'lpCount'          => max(1, (int) $dataInput['lp_count']),
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

        // Insert snippet
        $lastId = $this->repository->insert($data);

        return $lastId;
    }

    /**
     * @desc Update snippet
     * @param $id
     * @param $dataInput
     */
    public function update($id, $dataInput)
    {
        $id        = (int) $id;
        $dataInput = (array) $dataInput;

        // Check nonce
        check_admin_referer('update-snippet_' . $id);

        // Update snippet
        if ('manual' === $dataInput['display_on']) {
            $dataInput['display_on'] = '';
        }

        // Current User
        $currentUser = wp_get_current_user();

        // Create new snippet
        $data = [
            'name'             => $dataInput['name'],
            'snippet'          => $dataInput['snippet'],
            'snippetType'      => $dataInput['snippet_type'],
            'deviceType'       => $dataInput['device_type'],
            'location'         => $dataInput['location'],
            'displayOn'        => $dataInput['display_on'],
            'status'           => $dataInput['status'],
            'lpCount'          => max(1, (int) $dataInput['lp_count']),
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
        $result = $this->repository->update($id, $data);

        return $result;
    }

    /**
     * @desc Handle AJAX on/off toggle for snippets
     * @param $id
     * @param $toggle
     * @param $toggleValue
     * @return bool|int|\mysqli_result|resource|null
     */
    public function toggle($id, $toggle, $toggleValue)
    {
        // Check nonce
        check_ajax_referer('hfcm-toggle-snippet', 'security');

        // Active Or Inactive
        if ('on' === $toggleValue) {
            $result = $this->repository->activateSnippet($id);
        } else {
            $result = $this->repository->deactivateSnippet($id);
        }

        return $result;
    }

    /**
     * @desc Get Posts
     * @param $id
     * @return array|array[]
     */
    public function getPosts($id)
    {
        $id = (int) $id;
        // Check nonce
        check_ajax_referer('hfcm-get-posts', 'security');
        // Get Snippet
        $script = $this->repository->getSnippet($id);

        $sPosts  = $script['s_posts'] ?? [];
        $exPosts = $script['ex_posts'] ?? [];

        // Get all posts type
        $postTypes = $this->repository->getPostType();
        // Default Post Type
        $postTypes[] = 'post';
        // Get all posts
        $posts = $this->repository->getPosts($postTypes);

        // Return Structure
        $jsonOutput = [
            'selected' => [],
            'posts'    => [],
            'excluded' => [],
        ];

        if (empty($posts)) {
            return $jsonOutput;
        }

        foreach ($posts as $pData) {
            $postTitle = trim($pData->post_title);

            if (empty($postTitle)) {
                $postTitle = "(no title)";
            }

            if ( !empty($exPosts) && in_array($pData->ID, $exPosts)) {
                $jsonOutput['excluded'][] = $pData->ID;
            }

            if ( !empty($sPosts) && in_array($pData->ID, $sPosts)) {
                $jsonOutput['selected'][] = $pData->ID;
            }

            $jsonOutput['posts'][] = array(
                'text'  => GeneralUtil::sanitizeText($postTitle),
                'value' => $pData->ID,
            );
        }

        return $jsonOutput;
    }

}
