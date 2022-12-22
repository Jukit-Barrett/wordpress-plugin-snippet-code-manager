<?php
/**
 * Plugin Name: WordPress Plugin Snippet Code Manager
 * Plugin URI: https://draftpress.com/products
 * Description: Header Footer Code Manager by 99 Robots is a quick and simple way for you to add tracking code snippets, conversion pixels, or other scripts required by third party services for analytics, tracking, marketing, or chat functions. For detailed documentation, please visit the plugin's <a href="https://draftpress.com/"> official page</a>.
 * Version: 1.1.32
 * Requires at least: 4.9
 * Requires PHP: 5.6.20
 * Author: 99robots
 * Author URI: https://draftpress.com/
 * Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 * Text Domain: header-footer-code-manager
 * Domain Path: /languages
 */
/**
 * If this file is called directly, abort.
 */
if ( !defined('WPINC')) {
    die;
}

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$snippetCodeManager = new \Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager(__FILE__, $config);

$snippetCodeManager->launch();
