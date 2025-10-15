<?php
// Quick CLI test for AMO_API_Handler::generate_for_topic
// Loads WordPress and invokes the centralized generator for a short sanity check.

require 'c:\\xampp\\htdocs\\wp\\wp-load.php';

// Use a simple topic for testing
$topic = 'Deneme konu CLI';

try {
    $api_handler = AMO_API_Handler::get_instance();
    $res = $api_handler->generate_for_topic($topic);

    if (is_wp_error($res)) {
        echo "WP_ERROR: " . $res->get_error_message() . PHP_EOL;
        $data = $res->get_error_data();
        if (!empty($data)) {
            echo "Error data: " . print_r($data, true) . PHP_EOL;
        }
        exit(1);
    }

    echo "OK: ";
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(2);
}
