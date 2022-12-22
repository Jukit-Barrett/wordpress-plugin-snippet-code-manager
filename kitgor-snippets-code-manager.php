<?php
/**
 * Plugin Name: Kitgor Snippets Code Manager
 * Plugin URI: https://draftpress.com/products
 * Description: Header Footer Code Manager by 99 Robots is a quick and simple way for you to add tracking code
 * snippets, conversion pixels, or other scripts required by third party services for analytics, tracking, marketing,
 * or chat functions. For detailed documentation, please visit the plugin's <a href="https://draftpress.com/"> official
 * page</a>. Version: 1.0 Requires at least: 4.9 Requires PHP: 7.4 Author: Kitgor Author URI: http://wwww.kit.com
 * Disclaimer: Use at your own risk. No warranty expressed or implied is provided. Text Domain: kit-head-foot-code
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

$snippetCodeManager = new \Mrzkit\WpPluginSnippetCodeManager\SnippetCodeManager(
    $config['entryFile'] ?? __FILE__, $config
);

$snippetCodeManager->launch();
