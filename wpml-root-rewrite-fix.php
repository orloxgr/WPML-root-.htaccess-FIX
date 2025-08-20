<?php
/**
 * Plugin Name: WPML Root Rewrite Fix
 * Description: Forces the WordPress .htaccess block to use RewriteBase / and /index.php, even when WPML adds a language dir (e.g., /el) to home_url(). Safe with “default language in /el”.
 * Author: Byron Iniotakis
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * If WordPress is REALLY installed in a physical subdirectory, set this to that path (e.g. '/site/').
 * For standard WPML-in-directories setups, keep '/'.
 */
if (!defined('WPRRF_BASE')) define('WPRRF_BASE', '/');

function wprrf_fix_rules_text($rules) {
    if (!is_string($rules) || $rules === '') return $rules;

    $fixed = preg_replace_callback('~(# BEGIN WordPress)(.*?)(# END WordPress)~is', function ($m) {
        $block = $m[2];

        // Normalize RewriteBase
        $block = preg_replace('~(?mi)^\s*RewriteBase\s+\S+\s*$~', 'RewriteBase ' . WPRRF_BASE, $block);
        // Normalize catch-all to /index.php (avoid /el/index.php)
        $block = preg_replace('~(?mi)^\s*RewriteRule\s+\.\s+\S*index\.php\s+\[L\]\s*$~', 'RewriteRule . ' . WPRRF_BASE . 'index.php [L]', $block);
        // Canonicalize the ^index\.php$ rule
        $block = preg_replace('~(?mi)^\s*RewriteRule\s+\^index\\.php\$\s+-\s+\[L\]\s*$~', 'RewriteRule ^index\.php$ - [L]', $block);

        return $m[1] . $block . $m[3];
    }, $rules);

    return $fixed ?: $rules;
}

/**
 * 1) Intercept generation: ensure WP produces root-based rules.
 */
add_filter('mod_rewrite_rules', 'wprrf_fix_rules_text', PHP_INT_MAX);

/**
 * 2) Post-write hardening: after WP flushes, rewrite the file on disk just in case.
 */
add_action('flush_rewrite_rules_hard', function () {
    // Delay to ensure core finished writing.
    add_action('shutdown', function () {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (!function_exists('get_home_path')) return;

        $home_path = trailingslashit(get_home_path());
        $file = $home_path . '.htaccess';

        if (!is_readable($file) || !is_writable($file)) return;

        $contents = @file_get_contents($file);
        if (!is_string($contents) || $contents === '') return;

        $fixed = wprrf_fix_rules_text($contents);
        if ($fixed !== $contents) {
            @file_put_contents($file, $fixed, LOCK_EX);
        }
    }, 9999);
}, 1);
