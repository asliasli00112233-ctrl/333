<?php

if (!defined('ABSPATH')) {
    exit;
}

class AMO_Scheduler {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('amo_auto_generate_article', array($this, 'auto_generate_article'));
        add_action('wp_ajax_amo_start_auto_publish', array($this, 'start_auto_publish'));
        add_action('wp_ajax_amo_stop_auto_publish', array($this, 'stop_auto_publish'));
        add_action('wp_ajax_amo_generate_now', array($this, 'generate_now'));
    }

    public function auto_generate_article() {
        $auto_publish_enabled = get_option('amo_auto_publish_enabled', 0);
        
        if (!$auto_publish_enabled) {
            return;
        }

        $articles_per_hour = get_option('amo_articles_per_hour', 1);
        $last_generation_time = get_option('amo_last_generation_time', 0);
        $current_time = time();
        
        // Check if enough time has passed based on articles per hour setting
        $time_between_articles = 3600 / $articles_per_hour; // seconds between articles
        
        if (($current_time - $last_generation_time) < $time_between_articles) {
            return; // Not enough time has passed
        }

        // Get next unused keyword
        $keyword = AMO_Database::get_unused_keyword();
        
        if (!$keyword) {
            error_log('AMO: No unused keywords available for auto generation');
            return;
        }

        // Generate article using centralized API handler (ensures same rotation, blacklist, settings)
        $api_handler = AMO_API_Handler::get_instance();
        $result = $api_handler->generate_for_topic($keyword->keyword);

        // If generator returned WP_Error or failed, log and mark as failed
        if (is_wp_error($result)) {
            error_log('AMO: Auto-generate failed: ' . $result->get_error_message());
            AMO_Database::update_article_status_by_keyword($keyword->keyword, 'failed', null, $result->get_error_message());
            return;
        }

        // On success, create post and record
        if (is_array($result) && isset($result['htmlContent'])) {
            try {
                $post_id = $this->create_wordpress_post($keyword->keyword, $result['htmlContent']);
                AMO_Database::update_article_status_by_keyword($keyword->keyword, 'published', $post_id);
            } catch (Exception $e) {
                error_log('AMO: Failed to create post for auto-generate: ' . $e->getMessage());
                AMO_Database::update_article_status_by_keyword($keyword->keyword, 'failed', null, $e->getMessage());
            }
        } else {
            error_log('AMO: Auto-generate returned unexpected result format.');
            AMO_Database::update_article_status_by_keyword($keyword->keyword, 'failed', null, 'Unexpected result format');
        }
        
        // Update last generation time
        update_option('amo_last_generation_time', $current_time);
    }

    public function start_auto_publish() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        update_option('amo_auto_publish_enabled', 1);
        
        wp_send_json_success(array(
            'message' => 'Otomatik yayınlama başlatıldı',
            'status' => 'active'
        ));
    }

    public function stop_auto_publish() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        update_option('amo_auto_publish_enabled', 0);
        
        wp_send_json_success(array(
            'message' => 'Otomatik yayınlama durduruldu',
            'status' => 'inactive'
        ));
    }

    public function generate_now() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Get next unused keyword
        $keyword = AMO_Database::get_unused_keyword();
        
        if (!$keyword) {
            wp_send_json_error(array(
                'message' => 'Kullanılmamış kelime bulunamadı. Lütfen kelime listesine yeni kelimeler ekleyin.'
            ));
        }

        // Mark keyword as used and add a generated_articles row
        AMO_Database::mark_keyword_used($keyword->id);
        $article_row_id = AMO_Database::add_generated_article($keyword->keyword, null, 'generating');

        // Use centralized API handler for generation
        $api_handler = AMO_API_Handler::get_instance();
        $result = $api_handler->generate_for_topic($keyword->keyword);

        if (is_wp_error($result)) {
            $err = $result->get_error_message();
            AMO_Database::update_article_status($article_row_id, 'failed', null, $err);
            wp_send_json_error(array('message' => 'Makale oluşturulurken hata oluştu: ' . $err));
            return;
        }

        // Expecting array with htmlContent or direct content string
        try {
            $content = '';
            if (is_array($result) && isset($result['htmlContent'])) {
                $content = $result['htmlContent'];
            } elseif (is_string($result)) {
                $content = $result;
            } elseif (is_array($result) && isset($result['content'])) {
                $content = $result['content'];
            }

            if (empty($content)) {
                throw new Exception('Sunucudan geçerli içerik alınamadı.');
            }

            $post_id = $this->create_wordpress_post($keyword->keyword, $content);
            AMO_Database::update_article_status($article_row_id, 'published', $post_id);

            wp_send_json_success(array(
                'message' => 'Makale başarıyla oluşturuldu ve yayınlandı',
                'post_id' => $post_id,
                'keyword' => $keyword->keyword
            ));
        } catch (Exception $e) {
            AMO_Database::update_article_status($article_row_id, 'failed', null, $e->getMessage());
            wp_send_json_error(array('message' => 'Makale oluşturulurken hata oluştu: ' . $e->getMessage()));
        }
    }

    private function generate_article($keyword, $keyword_id) {
        // Increase max execution time for this operation
        @set_time_limit(300); // 5 minutes max
        
        $start_time = time();
        
        // Mark keyword as used
        AMO_Database::mark_keyword_used($keyword_id);
        
        // Add to generated articles table
        $article_id = AMO_Database::add_generated_article($keyword, null, 'generating');
        
        try {
            // Get API keys
            $api_keys = AMO_Database::get_api_keys();
            
            if (empty($api_keys)) {
                throw new Exception('API anahtarı bulunamadı. Lütfen en az bir API anahtarı ekleyin.');
            }

            $article_generated = false;
            $last_error = '';
            
            // Try each API key until one works
            foreach ($api_keys as $api_key_data) {
                try {
                    // Get image from Pexels
                    $image_url = $this->get_pexels_image($keyword);
                    
                    // Generate article content using API
                    $content = $this->generate_article_content($keyword, $image_url, $api_key_data->api_key);
                    
                    if ($content) {
                        // Create WordPress post
                        $post_id = $this->create_wordpress_post($keyword, $content);
                        
                        if ($post_id) {
                            // Update API key usage
                            AMO_Database::update_api_key_usage($api_key_data->api_key);
                            
                            // Update article status
                            $generation_time = time() - $start_time;
                            AMO_Database::update_article_status($article_id, 'published', $post_id);
                            
                            $article_generated = true;
                            break;
                        }
                    }
                } catch (Exception $e) {
                    $last_error = $e->getMessage();
                    error_log('AMO: Key failed, trying next: ' . substr($api_key_data->api_key, 0, 10) . '... - Error: ' . $e->getMessage());
                    
                    // Just continue to next key without deactivating
                    // Keys might recover after rate limits reset or temporary issues resolve
                    continue;
                }
            }
            
            if (!$article_generated) {
                throw new Exception($last_error ?: 'Tüm API anahtarları denendi ancak makale oluşturulamadı.');
            }
            
            return array(
                'success' => true,
                'post_id' => $post_id,
                'generation_time' => time() - $start_time
            );
            
        } catch (Exception $e) {
            // Update article status as failed
            AMO_Database::update_article_status($article_id, 'failed', null, $e->getMessage());
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    private function get_pexels_image($keyword) {
        $default_image = 'https://images.pexels.com/photos/3408744/pexels-photo-3408744.jpeg';
        
        try {
            $pexels_key = get_option('amo_pexels_api_key', '');
            $response = wp_remote_get("https://api.pexels.com/v1/search?query=" . urlencode($keyword) . "&per_page=1&orientation=landscape", array(
                'headers' => array(
                    'Authorization' => $pexels_key
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return $default_image;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['photos']) && !empty($data['photos'])) {
                return $data['photos'][0]['src']['large2x'];
            }
            
            return $default_image;
        } catch (Exception $e) {
            return $default_image;
        }
    }

    private function generate_article_content($keyword, $image_url, $api_key) {
        $prompt = $this->create_prompt($keyword, $image_url);
        
        // Try Gemini API
        $response = wp_remote_post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=" . $api_key, array(
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
            'timeout' => 60 // Reduced from 120 to 60 seconds
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API isteği başarısız: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_body = wp_remote_retrieve_body($response);
            $error_data = json_decode($error_body, true);
            
            if (isset($error_data['error']['message'])) {
                throw new Exception('API Hatası: ' . $error_data['error']['message']);
            } else {
                throw new Exception('API Hatası: HTTP ' . $response_code);
            }
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        throw new Exception('API\'den geçerli içerik alınamadı');
    }

    private function create_prompt($keyword, $image_url) {
        // Use the handler's prompt logic for full consistency
        if (!class_exists('AMO_API_Handler')) {
            require_once dirname(__FILE__) . '/class-amo-api-handler.php';
        }
        $handler = AMO_API_Handler::get_instance();
        return $handler->create_prompt($keyword, $image_url);
    }

    private function create_wordpress_post($keyword, $content) {
        $post_data = array(
            'post_title' => $keyword,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
            'meta_input' => array(
                '_amo_generated' => 1,
                '_amo_generation_time' => current_time('mysql')
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception('WordPress post oluşturulamadı: ' . $post_id->get_error_message());
        }
        
        return $post_id;
    }
}

AMO_Scheduler::get_instance();
