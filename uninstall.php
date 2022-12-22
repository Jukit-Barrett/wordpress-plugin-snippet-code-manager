<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$snippetCodeManager = new \Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager(__FILE__, $config);

$snippetCodeManager->uninstall();
