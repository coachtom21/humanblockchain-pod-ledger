<?php
/**
 * Test Shortcode - Access via: http://humanblockchain.local/wp-content/plugins/humanblockchain-pod-ledger/test-shortcode.php
 * 
 * This file tests if the shortcode is working
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if shortcode exists
if (shortcode_exists('hbc_participate')) {
    echo '<h1>✅ Shortcode [hbc_participate] is registered!</h1>';
    echo '<hr>';
    echo '<h2>Shortcode Output:</h2>';
    echo do_shortcode('[hbc_participate]');
} else {
    echo '<h1>❌ Shortcode [hbc_participate] is NOT registered!</h1>';
    echo '<p>Please check:</p>';
    echo '<ul>';
    echo '<li>Is the plugin activated?</li>';
    echo '<li>Is the plugin file loaded correctly?</li>';
    echo '</ul>';
}
