<?php

namespace Mrzkit\WpPluginSnippetCodeManager\Repository;

class OptionRepository
{
    public function getVersion()
    {
        return get_option('hfcm_db_version');
    }

    public function addVersion($version)
    {
        return add_option('hfcm_db_version', $version);
    }

    public function updateVersion($version)
    {
        return update_option('hfcm_db_version', $version);
    }

    public function deleteVersion()
    {
        return delete_option('hfcm_db_version');
    }

    //
    public function getActivationDate()
    {
        return get_option('hfcm_activation_date');
    }

    public function addActivationDate($date)
    {
        return add_option('hfcm_activation_date', $date);
    }

    public function updateActivationDate($date)
    {
        return update_option('hfcm_activation_date', $date);
    }

    public function deleteActivationDate()
    {
        return delete_option('hfcm_activation_date');
    }

    //
    public function getPageForPosts()
    {
        // Gets the page ID of the blog page
        return get_option('page_for_posts');
    }
}
