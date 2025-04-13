<?php
/**
 * Plugin Functions
 * 
 * Simple implementation of WordPress-like plugin functions
 */

// Store actions
global $wp_actions;
$wp_actions = [];

/**
 * Add an action hook
 * 
 * @param string $hook The name of the hook
 * @param callable $callback The function to call
 * @param int $priority The priority (not used in this simple implementation)
 */
function add_action($hook, $callback, $priority = 10) {
    global $wp_actions;
    
    if (!isset($wp_actions[$hook])) {
        $wp_actions[$hook] = [];
    }
    
    $wp_actions[$hook][] = $callback;
}

/**
 * Execute actions for a hook
 * 
 * @param string $hook The name of the hook
 * @param mixed $args Arguments to pass to the callbacks
 */
function do_action($hook, $args = null) {
    global $wp_actions;
    
    if (!isset($wp_actions[$hook])) {
        return;
    }
    
    foreach ($wp_actions[$hook] as $callback) {
        if (is_callable($callback)) {
            call_user_func($callback, $args);
        }
    }
}

/**
 * Add a filter hook
 * 
 * @param string $hook The name of the hook
 * @param callable $callback The function to call
 * @param int $priority The priority (not used in this simple implementation)
 */
function add_filter($hook, $callback, $priority = 10) {
    // For simplicity, we'll use the same implementation as add_action
    add_action($hook, $callback, $priority);
}

/**
 * Apply filters to a value
 * 
 * @param string $hook The name of the hook
 * @param mixed $value The value to filter
 * @return mixed The filtered value
 */
function apply_filters($hook, $value) {
    global $wp_actions;
    
    if (!isset($wp_actions[$hook])) {
        return $value;
    }
    
    foreach ($wp_actions[$hook] as $callback) {
        if (is_callable($callback)) {
            $value = call_user_func($callback, $value);
        }
    }
    
    return $value;
}
