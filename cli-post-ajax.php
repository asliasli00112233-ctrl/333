<?php
// CLI POST to admin-ajax.php to simulate browser AJAX for amo_generate_article
require 'c:\\xampp\\htdocs\\wp\\wp-load.php';

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('amo_generate_article');
$topic = 'CLI AJAX test konu';

// Use WordPress HTTP API to post to admin-ajax (this runs in same process so admin-ajax hooks should be accessible)
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'amo_generate_article',
        'nonce' => $nonce,
        'topic' => $topic
    ),
    'timeout' => 120
));

if (is_wp_error($response)) {
    echo "WP_ERROR: " . $response->get_error_message() . PHP_EOL;
    exit(1);
}

$code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "HTTP $code\n";
echo $body . PHP_EOL;
