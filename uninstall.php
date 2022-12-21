<?php

use Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager;

if (defined('WP_UNINSTALL_PLUGIN')) {
    $snippetCodeManager = new SnippetCodeManager(__FILE__);
    $snippetCodeManager->uninstall();
}
