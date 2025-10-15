<?php

if (!defined('ABSPATH')) {
    exit;
}

// Gemini API configuration (can be overridden elsewhere if needed)
if (!defined('GEMINI_API_KEY')) {
    // GEMINI_API_KEY should be configured via plugin settings or DB; do not hard-code secrets in source.
    define('GEMINI_API_KEY', '');
}

if (!defined('MODEL_ID')) {
    define('MODEL_ID', 'gemini-2.0-flash');
}

if (!defined('GENERATE_CONTENT_API')) {
    define('GENERATE_CONTENT_API', 'generateContent');
}

class AMO_API_Handler {

    private static $instance = null;
    private $current_key_index = 0;
    
    // Error type constants
    const ERROR_QUOTA_EXCEEDED = 'quota_exceeded';
    const ERROR_TIMEOUT = 'timeout';
    const ERROR_OVERLOADED = 'overloaded';
    const ERROR_INVALID_KEY = 'invalid_key';
    const ERROR_NETWORK = 'network';
    const ERROR_OTHER = 'other';
    
    // Blacklist durations (in seconds)
    const BLACKLIST_QUOTA = 3600;      // 1 hour
    const BLACKLIST_TIMEOUT = 300;      // 5 minutes
    const BLACKLIST_OVERLOADED = 180;   // 3 minutes
    const BLACKLIST_INVALID = 86400;    // 24 hours
    
    // Retry settings
    const MAX_RETRIES = 2;
    const INITIAL_RETRY_DELAY = 2; // seconds

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_amo_generate_article', array($this, 'generate_article'));
        add_action('wp_ajax_nopriv_amo_generate_article', array($this, 'generate_article'));
        add_action('wp_ajax_amo_test_api_key', array($this, 'test_api_key'));
    }

    public function generate_article() {
        check_ajax_referer('amo_generate_article', 'nonce');

        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';

        if (empty($topic)) {
            wp_send_json_error(array('message' => __('Lütfen bir konu girin.', 'otomatik-makale-olusturucu')));
            return;
        }

        $pexels_key = get_option('amo_pexels_api_key', '');

        if (empty($pexels_key)) {
            wp_send_json_error(array('message' => __('Pexels API anahtarı yapılandırılmamış.', 'otomatik-makale-olusturucu')));
            return;
        }

        $image_url = $this->fetch_image_from_pexels($topic, $pexels_key);
        $article_data = $this->try_api_keys_with_rotation($topic, $image_url);

        if (is_wp_error($article_data)) {
            wp_send_json_error(array('message' => $article_data->get_error_message()));
            return;
        }

        wp_send_json_success($article_data);
    }

    private function fetch_image_from_pexels($topic, $api_key) {
        $default_image = 'https://images.pexels.com/photos/3408744/pexels-photo-3408744.jpeg';

        $response = wp_remote_get(
            'https://api.pexels.com/v1/search?query=' . urlencode($topic) . '&per_page=1&orientation=landscape',
            array(
                'headers' => array(
                    'Authorization' => $api_key
                ),
                'timeout' => 15
            )
        );

        if (is_wp_error($response)) {
            return $default_image;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['photos'][0]['src']['large2x'])) {
            return $data['photos'][0]['src']['large2x'];
        }

        return $default_image;
    }

    private function generate_article_content($topic, $image_url, $api_key) {
        $prompt = $this->create_prompt($topic, $image_url);

        // Use configured Gemini model and method
        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1/models/' . MODEL_ID . ':' . GENERATE_CONTENT_API . '?key=' . $api_key,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array('text' => $prompt)
                            )
                        )
                    )
                )),
                'timeout' => 60 // Reduced from 90 to 60
            )
        );

        if (is_wp_error($response)) {
            return new WP_Error('api_error', __('API bağlantı hatası: ', 'otomatik-makale-olusturucu') . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            // Log raw response to help diagnose unexpected response structures
            error_log('AMO: Unexpected API structure in generate_article_content. Response code: ' . wp_remote_retrieve_response_code($response) . '. Raw response (truncated 2000 chars): ' . substr($body, 0, 2000));
            return new WP_Error('api_error', __('API\'den geçersiz yanıt alındı.', 'otomatik-makale-olusturucu'));
        }

        $response_content = $data['candidates'][0]['content']['parts'][0]['text'];
        // Try to clean typical markdown JSON fences first
        $clean_json = preg_replace('/^```json\n?/', '', $response_content);
        $clean_json = preg_replace('/\n?```$/', '', $clean_json);

        // If the cleaned text is not valid JSON, attempt to locate a JSON object inside the text
        $parsed_data = json_decode($clean_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract a JSON substring from the response_content
            $maybe = $this->_extract_json_substring($response_content);
            if ($maybe !== null) {
                $parsed_data = json_decode($maybe, true);
            }
        }

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed_data)) {
            // Log both raw and cleaned responses for debugging with more context
            error_log('AMO: JSON parse error in generate_article_content. json_last_error: ' . json_last_error() . '. HTTP code: ' . wp_remote_retrieve_response_code($response) . '.');
            error_log('AMO: Raw response (truncated 2000 chars): ' . substr($body, 0, 2000));
            error_log('AMO: Cleaned response attempt (truncated 2000 chars): ' . substr($clean_json, 0, 2000));
            if (isset($maybe)) {
                error_log('AMO: Extracted JSON candidate (truncated 2000 chars): ' . substr($maybe, 0, 2000));
            }
            // If parsing failed, but the raw response contains HTML, return it as htmlContent fallback
            if (!empty($response_content) && (stripos($response_content, '<html') !== false || stripos($response_content, '<div') !== false || stripos($response_content, '<p') !== false)) {
                error_log('AMO: Parsed JSON failed but response contains HTML; returning htmlContent fallback.');
                return array('htmlContent' => $response_content, 'chartData' => null);
            }

            return new WP_Error('parse_error', __('API\'den gelen veri islenemedi.', 'otomatik-makale-olusturucu'));
        }

        // If parsed_data is an array but doesn't have expected keys, check for common key variants
        if (is_array($parsed_data) && (isset($parsed_data['htmlContent']) || isset($parsed_data['content']) || isset($parsed_data[0]))) {
            return $parsed_data;
        }

        // As a last-resort, if the original response_content looks like HTML, return it
        if (!empty($response_content) && (stripos($response_content, '<html') !== false || stripos($response_content, '<div') !== false || stripos($response_content, '<p') !== false)) {
            error_log('AMO: Parsed JSON did not include expected structure; returning htmlContent fallback.');
            return array('htmlContent' => $response_content, 'chartData' => null);
        }

        return $parsed_data;
    }

    /**
     * Attempt to extract a JSON object/array substring from arbitrary text.
     * Returns the JSON string if found, or null otherwise.
     */
    private function _extract_json_substring($text) {
        // Find the first { or [ and try to find a balanced JSON block
        $startPos = null;
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            if ($text[$i] === '{' || $text[$i] === '[') {
                $startPos = $i;
                $openChar = $text[$i];
                $closeChar = ($openChar === '{') ? '}' : ']';
                break;
            }
        }
        if ($startPos === null) return null;

        $depth = 0;
        for ($j = $startPos; $j < $len; $j++) {
            $ch = $text[$j];
            if ($ch === $openChar) $depth++;
            elseif ($ch === $closeChar) $depth--;

            if ($depth === 0) {
                $candidate = substr($text, $startPos, $j - $startPos + 1);
                // Quick sanity: must start with { or [ and end with } or ]
                if ((($candidate[0] === '{' && substr($candidate, -1) === '}') || ($candidate[0] === '[' && substr($candidate, -1) === ']'))) {
                    return $candidate;
                }
                break;
            }
        }

        return null;
    }

    /**
     * Test API key functionality
     */
    public function test_api_key() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : 'gemini';
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
        }
        
        $start_time = microtime(true);
        
        if ($provider === 'gemini') {
            $response = wp_remote_post(
                'https://generativelanguage.googleapis.com/v1/models/' . MODEL_ID . ':' . GENERATE_CONTENT_API . '?key=' . $api_key,
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json'
                    ),
                    'body' => json_encode(array(
                        'contents' => array(
                            array(
                                'parts' => array(
                                    array('text' => 'Say "API test successful" in Turkish')
                                )
                            )
                        )
                    )),
                    'timeout' => 30
                )
            );
        } else {
            wp_send_json_error(array('message' => 'Unsupported provider'));
        }
        
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000, 2);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'API connection failed: ' . $response->get_error_message(),
                'response_time' => $response_time . ' ms'
            ));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Check for API errors first
        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? 'Unknown error';
            $error_status = $data['error']['status'] ?? 'UNKNOWN';
            
            wp_send_json_error(array(
                'message' => 'API Error: ' . $error_message,
                'status_code' => $status_code,
                'response_time' => $response_time . ' ms',
                'error' => $error_message,
                'error_status' => $error_status,
                'raw_response' => $body
            ));
            return;
        }
        
        if ($status_code === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            wp_send_json_success(array(
                'message' => 'API key is valid and working!',
                'response_time' => $response_time . ' ms',
                'model' => MODEL_ID,
                'api_response' => $data['candidates'][0]['content']['parts'][0]['text']
            ));
        } else {
            // Better error handling
            $error_msg = 'API key is invalid or quota exceeded';
            
            if ($status_code === 400) {
                $error_msg = 'Bad Request - Invalid API key format';
            } elseif ($status_code === 403) {
                $error_msg = 'Forbidden - API key is invalid';
            } elseif ($status_code === 429) {
                $error_msg = 'Rate limit exceeded - Too many requests';
            } elseif ($status_code === 200 && !isset($data['candidates'])) {
                $error_msg = 'API returned 200 but no candidates found. Possible content filter or quota issue.';
            }
            
            wp_send_json_error(array(
                'message' => $error_msg,
                'status_code' => $status_code,
                'response_time' => $response_time . ' ms',
                'error' => isset($data['error']['message']) ? $data['error']['message'] : 'No candidates in response',
                'raw_response' => substr($body, 0, 500) // First 500 chars for debugging
            ));
        }
    }

    /**
     * Programmatic generation for a given topic and optional image URL.
     * Returns parsed generation result array on success or WP_Error on failure.
     */
    public function generate_for_topic($topic, $image_url = '') {
        return $this->try_api_keys_with_rotation($topic, $image_url);
    }
    
    /**
     * Get next API key with rotation
     */
    private function get_next_api_key() {
        $api_keys = AMO_Database::get_api_keys('gemini', true); // Get only active Gemini keys
        
        if (empty($api_keys)) {
            // Fallback to single option-based Gemini key (legacy)
            $opt_key = get_option('amo_gemini_api_key', '');
            if (!empty($opt_key)) {
                return $opt_key; // return raw key string
            }

            return new WP_Error('no_api_key', 'No active Gemini API keys found');
        }
        
        // Get current index from options (for rotation persistence)
        $this->current_key_index = get_option('amo_current_key_index', 0);
        
        // Get key at current index
        $key = $api_keys[$this->current_key_index % count($api_keys)];
        
        // Increment for next time
        $this->current_key_index = ($this->current_key_index + 1) % count($api_keys);
        update_option('amo_current_key_index', $this->current_key_index);
        
        // Update usage stats
    AMO_Database::increment_key_usage($key->id);
        
        return $key->api_key;
    }
    
    /**
     * Categorize error type from error message
     */
    private function categorize_error($error_message) {
        $error_lower = strtolower($error_message);
        
        if (strpos($error_lower, 'quota') !== false || strpos($error_lower, 'exceeded') !== false) {
            return self::ERROR_QUOTA_EXCEEDED;
        }
        
        if (strpos($error_lower, 'timeout') !== false || strpos($error_lower, 'timed out') !== false) {
            return self::ERROR_TIMEOUT;
        }
        
        if (strpos($error_lower, 'overloaded') !== false) {
            return self::ERROR_OVERLOADED;
        }
        
        if (strpos($error_lower, 'invalid') !== false || strpos($error_lower, 'not valid') !== false) {
            return self::ERROR_INVALID_KEY;
        }
        
        if (strpos($error_lower, 'curl') !== false || strpos($error_lower, 'connection') !== false) {
            return self::ERROR_NETWORK;
        }
        
        return self::ERROR_OTHER;
    }
    
    /**
     * Check if a key is temporarily blacklisted
     */
    private function is_key_blacklisted($key_id) {
        $blacklist = get_transient('amo_key_blacklist');
        
        if (!$blacklist || !is_array($blacklist)) {
            return false;
        }
        
        if (isset($blacklist[$key_id])) {
            $expiry = $blacklist[$key_id]['expiry'];
            
            if (time() < $expiry) {
                return true; // Still blacklisted
            } else {
                // Expired, remove from blacklist
                unset($blacklist[$key_id]);
                set_transient('amo_key_blacklist', $blacklist, 86400);
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Add a key to temporary blacklist
     */
    private function blacklist_key($key_id, $error_type, $error_message) {
        $blacklist = get_transient('amo_key_blacklist');
        
        if (!is_array($blacklist)) {
            $blacklist = array();
        }
        
        // Determine blacklist duration based on error type
        $duration = self::BLACKLIST_TIMEOUT; // Default
        
        switch ($error_type) {
            case self::ERROR_QUOTA_EXCEEDED:
                $duration = self::BLACKLIST_QUOTA;
                break;
            case self::ERROR_TIMEOUT:
                $duration = self::BLACKLIST_TIMEOUT;
                break;
            case self::ERROR_OVERLOADED:
                $duration = self::BLACKLIST_OVERLOADED;
                break;
            case self::ERROR_INVALID_KEY:
                $duration = self::BLACKLIST_INVALID;
                break;
        }
        
        $blacklist[$key_id] = array(
            'error_type' => $error_type,
            'error_message' => $error_message,
            'expiry' => time() + $duration,
            'blacklisted_at' => current_time('mysql')
        );
        
        set_transient('amo_key_blacklist', $blacklist, 86400);
        
        error_log(sprintf(
            'AMO: Key #%d blacklisted for %d seconds due to: %s',
            $key_id,
            $duration,
            $error_type
        ));
    }
    
    /**
     * Try all API keys in rotation until one succeeds (with intelligent error handling)
     */
    private function try_api_keys_with_rotation($topic, $image_url) {
        $api_keys = AMO_Database::get_api_keys('gemini', true);

        $errors = array();
        $timeout_count = 0;

        // If no DB-managed keys, try single-option fallback key
        if (empty($api_keys)) {
            $opt_key = get_option('amo_gemini_api_key', '');
            if (!empty($opt_key)) {
                // Try single key directly (no key_id, no blacklist rotation)
                $result = $this->generate_with_retry($topic, $image_url, $opt_key);
                if (!is_wp_error($result)) {
                    return $result;
                }

                $error_msg = $result->get_error_message();
                $error_type = $this->categorize_error($error_msg);
                $errors[] = array('key_id' => 0, 'error' => $error_msg, 'error_type' => $error_type);
                if ($error_type === self::ERROR_TIMEOUT) {
                    $timeout_count++;
                }
                // No blacklist for option-based key; return summary below
            } else {
                return new WP_Error('no_api_key', 'No active Gemini API keys found');
            }
        } else {
            // Try each key in rotation
            foreach ($api_keys as $index => $key_obj) {
                $api_key = $key_obj->api_key;
                $key_id = $key_obj->id;

                // Check if key is blacklisted
                if ($this->is_key_blacklisted($key_id)) {
                    $blacklist = get_transient('amo_key_blacklist');
                    $remaining = $blacklist[$key_id]['expiry'] - time();

                    error_log(sprintf(
                        'AMO: Key #%d skipped (blacklisted for %d more seconds): %s',
                        $key_id,
                        $remaining,
                        substr($api_key, 0, 10) . '...'
                    ));

                    $errors[] = array(
                        'key_id' => $key_id,
                        'error' => 'Temporarily blacklisted (' . $blacklist[$key_id]['error_type'] . ')',
                        'skipped' => true
                    );

                    continue;
                }

                // Update usage stats
                AMO_Database::increment_key_usage($key_id);

                // Try to generate content with retry logic for specific errors
                $result = $this->generate_with_retry($topic, $image_url, $api_key);

                if (!is_wp_error($result)) {
                    // Success! Clear any previous blacklist and return
                    $this->clear_key_blacklist($key_id);

                    error_log(sprintf(
                        'AMO: Article generated successfully with key #%d: %s',
                        $key_id,
                        substr($api_key, 0, 10) . '...'
                    ));

                    return $result;
                }

                // Error occurred - categorize and handle
                $error_msg = $result->get_error_message();
                $error_type = $this->categorize_error($error_msg);

                $errors[] = array(
                    'key_id' => $key_id,
                    'error' => $error_msg,
                    'error_type' => $error_type
                );

                // Track timeout errors for network issue detection
                if ($error_type === self::ERROR_TIMEOUT) {
                    $timeout_count++;
                }

                // Log the failure
                error_log(sprintf(
                    'AMO: Key #%d failed (type: %s): %s - Error: %s',
                    $key_id,
                    $error_type,
                    substr($api_key, 0, 10) . '...',
                    $error_msg
                ));

                // Blacklist the key temporarily
                $this->blacklist_key($key_id, $error_type, $error_msg);

                // Network issue detection: if 3+ consecutive timeouts, likely a network problem
                if ($timeout_count >= 3) {
                    error_log('AMO: Network issue detected (3+ consecutive timeouts). Stopping key rotation.');
                    return new WP_Error(
                        'network_issue',
                        'Network connectivity issue detected. Please check your internet connection and firewall settings.',
                        array('errors' => $errors)
                    );
                }

                // Continue to next key
                continue;
            }
        }
        
        // All keys failed - provide detailed error report
        $error_summary = $this->generate_error_summary($errors);
        
        error_log('AMO: All API keys failed. Summary: ' . json_encode($error_summary));
        
        return new WP_Error(
            'all_keys_failed',
            $this->format_all_keys_failed_message($error_summary),
            array('errors' => $errors, 'summary' => $error_summary)
        );
    }
    
    /**
     * Clear key from blacklist (on success)
     */
    private function clear_key_blacklist($key_id) {
        $blacklist = get_transient('amo_key_blacklist');
        
        if (is_array($blacklist) && isset($blacklist[$key_id])) {
            unset($blacklist[$key_id]);
            set_transient('amo_key_blacklist', $blacklist, 86400);
        }
    }
    
    /**
     * Generate content with retry logic for specific errors
     */
    private function generate_with_retry($topic, $image_url, $api_key) {
        $retry_count = 0;
        
        while ($retry_count <= self::MAX_RETRIES) {
            $result = $this->generate_article_content($topic, $image_url, $api_key);
            
            if (!is_wp_error($result)) {
                return $result;
            }
            
            // Check if error is retryable (overloaded)
            $error_msg = $result->get_error_message();
            $error_type = $this->categorize_error($error_msg);
            
            if ($error_type === self::ERROR_OVERLOADED && $retry_count < self::MAX_RETRIES) {
                $delay = self::INITIAL_RETRY_DELAY * pow(2, $retry_count); // Exponential backoff
                
                error_log(sprintf(
                    'AMO: Model overloaded, retrying in %d seconds (attempt %d/%d)',
                    $delay,
                    $retry_count + 1,
                    self::MAX_RETRIES
                ));
                
                sleep($delay);
                $retry_count++;
                continue;
            }
            
            // Not retryable or max retries reached
            return $result;
        }
        
        return $result; // Return last error
    }
    
    /**
     * Generate error summary from all failures
     */
    private function generate_error_summary($errors) {
        $summary = array(
            'total' => count($errors),
            'by_type' => array(),
            'skipped' => 0
        );
        
        foreach ($errors as $error) {
            if (isset($error['skipped']) && $error['skipped']) {
                $summary['skipped']++;
                continue;
            }
            
            $type = $error['error_type'] ?? 'unknown';
            
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            
            $summary['by_type'][$type]++;
        }
        
        return $summary;
    }
    
    /**
     * Format user-friendly error message for all keys failed
     */
    private function format_all_keys_failed_message($summary) {
        $message = 'All API keys failed. ';
        
        if (isset($summary['by_type'][self::ERROR_QUOTA_EXCEEDED]) && $summary['by_type'][self::ERROR_QUOTA_EXCEEDED] > 0) {
            $message .= 'Most keys exceeded their quota. ';
        }
        
        if (isset($summary['by_type'][self::ERROR_TIMEOUT]) && $summary['by_type'][self::ERROR_TIMEOUT] >= 3) {
            $message .= 'Network timeout issues detected. Check your internet connection. ';
        }
        
        if (isset($summary['by_type'][self::ERROR_OVERLOADED]) && $summary['by_type'][self::ERROR_OVERLOADED] > 0) {
            $message .= 'Google API is overloaded. Try again in a few minutes. ';
        }
        
        if ($summary['skipped'] > 0) {
            $message .= sprintf('%d key(s) were temporarily blacklisted and skipped. ', $summary['skipped']);
        }
        
        $message .= 'Please add new API keys or wait for existing keys to reset.';
        
        return $message;
    }
    
    private function create_prompt($topic, $image_url) {
        $prompt = <<<'PROMPT'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otomatik Makale Oluşturucu</title>
    <!-- Gerekli kütüphaneler en başta yüklenir ve hiç değiştirilmez -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #007bff;
            --background-color: #f4f7f9;
            --text-color: #333;
            --container-bg: #ffffff;
        }
        html, body {
            height: 100%; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-color); color: var(--text-color);
        }
        .hidden { display: none !important; }

        /* --- Giriş ve Yükleme Ekranı Stilleri --- */
        .initial-state-container { display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; height: 100%; padding: 20px; box-sizing: border-box; transition: opacity 0.5s ease-out; }
        .initial-state-container h1 { font-size: 2.5em; color: var(--primary-color); margin-bottom: 10px; font-weight: 600; }
        .initial-state-container p { font-size: 1.1em; color: #6c757d; margin-bottom: 30px; }
        .form-wrapper { display: flex; width: 100%; max-width: 600px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); border-radius: 10px; overflow: hidden; }
        #topic-input { flex-grow: 1; border: none; padding: 20px; font-size: 1.2em; outline: none; }
        #generate-btn { border: none; background-color: var(--primary-color); color: white; padding: 0 30px; font-size: 1.2em; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
        #generate-btn:hover { background-color: #0056b3; }

        #loading-container h2 { font-size: 2em; color: var(--text-color); margin-bottom: 25px; }
        .progress-bar { width: 80%; max-width: 500px; height: 20px; background-color: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-bar-inner { height: 100%; width: 100%; background-color: var(--primary-color); border-radius: 10px; animation: loading-animation 2s infinite ease-in-out; }
        @keyframes loading-animation { 0% { transform: translateX(-100%); } 50% { transform: translateX(0%); } 100% { transform: translateX(100%); } }

        /* --- Sonuç stilleri dinamik olarak eklenecek --- */
        #result-container { width: 100%; }
        #result-container style { display: none; } /* Stilleri sayfada gösterme */
    </style>
</head>
<body>

    <div id="input-container" class="initial-state-container">
        <h1>Otomatik Makale Oluşturucu</h1>
        <p>Hakkında makale oluşturmak istediğiniz konuyu girin.</p>
        <div class="form-wrapper">
            <input type="text" id="topic-input" placeholder="Örn: Yapay Zeka Etiği">
            <button id="generate-btn">Oluştur</button>
        </div>
    </div>

    <div id="loading-container" class="initial-state-container hidden">
        <h2>Sizin için harika bir makale hazırlanıyor...</h2>
        <div class="progress-bar">
            <div class="progress-bar-inner"></div>
        </div>
    </div>

    <div id="result-container"></div>

    <script>
    // --- NOT: API anahtarları sunucu tarafında saklanmalı; istemciye ve prompt içeriğine gömülmemelidir. ---
    // Sunucu tarafındaki ayarlardan veya AJAX endpoint'ten anahtarlar güvenli şekilde sağlanmalıdır.
    // --------------------------------------------------------------------------

        const inputContainer = document.getElementById('input-container');
        const loadingContainer = document.getElementById('loading-container');
        const resultContainer = document.getElementById('result-container');
        const topicInput = document.getElementById('topic-input');
        const generateBtn = document.getElementById('generate-btn');

        generateBtn.addEventListener('click', generateArticle);
        topicInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') generateArticle();
        });

        async function generateArticle() {
            const topic = topicInput.value.trim();
            if (!topic) return alert('Lütfen bir konu girin.');

            if ((!GEMINI_API_KEY && !OPENAI_API_KEY) || !PEXELS_API_KEY) {
                return alert('Lütfen JavaScript kodunun içindeki API anahtarlarını güncelleyin. Gemini veya OpenAI API anahtarından en az birini girmelisiniz.');
            }

            inputContainer.classList.add('hidden');
            loadingContainer.classList.remove('hidden');
            resultContainer.innerHTML = '';

            try {
                // 1. ADIM: Pexels'ten görsel al
                let imageUrl = 'https://images.pexels.com/photos/3408744/pexels-photo-3408744.jpeg'; 
                try {
                    const pexelsResponse = await fetch(`https://api.pexels.com/v1/search?query=${encodeURIComponent(topic)}&per_page=1&orientation=landscape`, {
                        headers: { 'Authorization': PEXELS_API_KEY }
                    });
                    if (pexelsResponse.ok) {
                        const pexelsData = await pexelsResponse.json();
                        if (pexelsData.photos && pexelsData.photos.length > 0) {
                            imageUrl = pexelsData.photos[0].src.large2x;
                        }
                    }
                } catch (e) {
                    console.error("Pexels API hatası:", e);
                }

                // 2. ADIM: AI ile makaleyi oluştur
                const prompt = createPrompt(topic, imageUrl);
                let response;
                
                if (GEMINI_API_KEY) {
                    // Gemini API kullan
                    response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=${GEMINI_API_KEY}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            contents: [{ parts: [{ text: prompt }] }],
                        })
                    });
                } else if (OPENAI_API_KEY) {
                    // OpenAI API kullan
                    response = await fetch('https://api.openai.com/v1/chat/completions', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${OPENAI_API_KEY}`
                        },
                        body: JSON.stringify({
                            model: 'gpt-4',
                            messages: [{ role: 'user', content: prompt }],
                            max_tokens: 4000
                        })
                    });
                }

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error ? errorData.error.message : `API hatası: ${response.status}`);
                }

                const data = await response.json();
                let responseContent;
                let usingGemini = false;
                
                // Hangi API kullanıldığını belirle
                if (data.candidates && data.candidates[0] && data.candidates[0].content) {
                    // Gemini yanıtı
                    responseContent = data.candidates[0].content.parts[0].text;
                    usingGemini = true;
                } else if (data.choices && data.choices[0] && data.choices[0].message) {
                    // OpenAI yanıtı
                    responseContent = data.choices[0].message.content;
                } else {
                    // Beklenmeyen format
                    console.error("API Yanıtı:", data);
                    throw new Error("API'den beklenmeyen bir yanıt formatı alındı. Konsolu kontrol edin.");
                }
                
                // Yanıtı kontrol et
                if (!responseContent || responseContent.trim() === '') {
                    throw new Error("API'den boş bir yanıt alındı. Lütfen tekrar deneyin.");
                }
                
                console.log("✅ API Başarılı! İçerik uzunluğu:", responseContent.length);
                console.log("📄 İçerik önizleme:", responseContent.substring(0, 200) + "...");
                
                // HTML içeriği doğrudan göster
                loadingContainer.classList.add('hidden');
                resultContainer.innerHTML = responseContent;
                
                console.log("🎉 Makale başarıyla yüklendi!");
                
                // Basit chart data oluştur
                const chartData = {
                    labels: ["2022", "2023", "2024", "2025"],
                    data: [45, 68, 95, 140],
                    label: `${topic} Trend Analizi`
                };
                initializeArticleScripts(chartData);

            } catch (error) {
                console.error('Detaylı Hata:', error);
                
                let errorMessage = error.message;
                if (error.message.includes('User location is not supported')) {
                    errorMessage = 'Coğrafi konum desteklenmiyor. VPN kullanarak farklı bir ülkeden bağlanmayı deneyin veya OpenAI API anahtarı kullanın.';
                } else if (error.message.includes('API key not valid')) {
                    errorMessage = 'API anahtarı geçersiz. Lütfen doğru API anahtarını girin.';
                } else if (error.message.includes('quota')) {
                    errorMessage = 'API kotanız dolmuş. Lütfen API sağlayıcınızın kontrol panelini kontrol edin.';
                }
                
                alert(`Bir hata oluştu: ${errorMessage}`);
                loadingContainer.classList.add('hidden');
                inputContainer.classList.remove('hidden');
            }
        }

        function initializeArticleScripts(chartData) {
            try {
                // FAQ Accordion
                document.querySelectorAll('.faq .question').forEach(q => {
                    q.addEventListener('click', () => {
                        const answer = q.nextElementSibling;
                        const icon = q.querySelector('i');
                        const isOpen = answer.style.display === 'block';
                        
                        document.querySelectorAll('.faq .answer').forEach(ans => ans.style.display = 'none');
                        document.querySelectorAll('.faq .question i').forEach(ico => ico.className = 'fas fa-chevron-down');

                        if (!isOpen) {
                            answer.style.display = 'block';
                            icon.className = 'fas fa-chevron-up';
                        }
                    });
                });

                // Chart.js
                const ctx = document.getElementById('contentChart');
                if (ctx && typeof Chart !== 'undefined' && chartData) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: chartData.label || 'Veri Dağılımı',
                                data: chartData.data,
                                backgroundColor: 'rgba(7, 13, 89, 0.8)',
                                borderColor: 'rgba(7, 13, 89, 1)',
                                borderWidth: 1,
                                borderRadius: 5
                            }]
                        },
                        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
                    });
                }

                // Social Share
                document.querySelectorAll('.share-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const platform = btn.dataset.platform;
                        const url = encodeURIComponent(window.location.href);
                        const text = encodeURIComponent(document.title);
                        let shareUrl = '';

                        if(platform === 'twitter') shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
                        if(platform === 'facebook') shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                        if(platform === 'linkedin') shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${text}`;
                        
                        if(shareUrl) window.open(shareUrl, '_blank', 'width=600,height=400');
                    });
                });

            } catch (e) {
                console.error("Makale script'i çalıştırılırken hata oluştu:", e);
            }
        }

        function createPrompt(topic, imageUrl) {
            return `
EVRENSEL, YÜKSEK PERFORMANSLI HTML İÇERİK ÜRETİM PROMPTU
Rol: Sen, SEO (Arama Motoru Optimizasyonu) alanında derinlemesine uzmanlaşmış bir içerik stratejisti ve aynı zamanda modern, kullanıcı odaklı, estetik tasarımlar yapabilen kıdemli bir front-end geliştiricisisin. Görevin, aşağıda belirtilen [KULLANICININ GİRDİĞİ KONU] üzerine, teknik ve estetik açıdan kusursuz, WordPress'e doğrudan yapıştırılabilecek tek bir HTML dosyası oluşturmaktır.
Ana Hedef: "${topic}" başlığı altında, 1500-2000 kelime aralığında, SEO performansı en üst düzeyde, görsel olarak büyüleyici ve kullanıcı deneyimi odaklı, zengin bir blog içeriği oluştur. Bu içerik, yalnızca satır içi CSS (inline CSS) kullanılarak tasarlanmalı ve hiçbir harici dosya (CSS, JS, CDN) veya <style> bloğu (animasyonlar için gerekli olan @keyframes hariç, ki bu da satır içi style ile tetiklenmeli) içermemelidir.
`;
        }
PROMPT;

        // Normalize placeholders: replace ${topic}, {$topic}, ${image_url}, {$image_url}
        $prompt = str_replace(array('${topic}', '{$topic}', '${image_url}', '{$image_url}'), array($topic, $topic, $image_url, $image_url), $prompt);

        return $prompt;
    }
}

AMO_API_Handler::get_instance();
