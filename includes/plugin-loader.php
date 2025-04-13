<?php
/**
 * Plugin Loader
 *
 * Automatically loads all plugins from the plugins directory
 * Plugins starting with an underscore (_) are ignored
 */

// Include plugin functions
require_once __DIR__ . '/plugin-functions.php';

function load_plugins() {
    $plugins_dir = __DIR__ . '/../plugins';

    // Check if plugins directory exists
    if (!is_dir($plugins_dir)) {
        return;
    }

    // Get all PHP files in the plugins directory
    $plugin_files = glob($plugins_dir . '/*.php');

    foreach ($plugin_files as $plugin_file) {
        $plugin_name = basename($plugin_file);

        // Skip plugins that start with an underscore
        if (strpos($plugin_name, '_') === 0) {
            continue;
        }

        // Include the plugin file and invoke it
        $plugin = require_once $plugin_file;

        // If the plugin is a callable (like an anonymous class with __invoke), call it
        if (is_callable($plugin)) {
            $plugin();
        }
    }
}

// Load all plugins
load_plugins();
