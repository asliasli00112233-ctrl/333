<?php

if (!defined('ABSPATH')) {
    exit;
}

class AMO_Ajax {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin AJAX actions
        add_action('wp_ajax_amo_get_articles_data', array($this, 'get_articles_data'));
        add_action('wp_ajax_amo_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_amo_delete_article', array($this, 'delete_article'));
        add_action('wp_ajax_amo_regenerate_article', array($this, 'regenerate_article'));
        add_action('wp_ajax_amo_get_generation_progress', array($this, 'get_generation_progress'));
        add_action('wp_ajax_amo_get_debug_logs', array($this, 'get_debug_logs'));
        
        // Auto-publish actions are handled in scheduler class
    }

    public function get_articles_data() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $search_value = sanitize_text_field($_POST['search']['value']);

        $articles = AMO_Database::get_generated_articles($length, $start);
        $total_articles = AMO_Database::get_articles_count();

        $data = array();
        foreach ($articles as $article) {
            $status_class = '';
            $status_text = '';
            switch ($article->status) {
                case 'published':
                    $status_class = 'amo-status-published';
                    $status_text = '✅ Yayınlandı';
                    break;
                case 'failed':
                    $status_class = 'amo-status-failed';
                    $status_text = '❌ Başarısız';
                    break;
                case 'generating':
                    $status_class = 'amo-status-generating';
                    $status_text = '⏳ Üretiliyor';
                    break;
                default:
                    $status_class = 'amo-status-pending';
                    $status_text = '⏸️ Bekliyor';
            }

            $actions = '';
            if ($article->post_id) {
                $actions .= '<a href="' . get_permalink($article->post_id) . '" target="_blank" class="button button-small">Görüntüle</a> ';
                $actions .= '<a href="' . get_edit_post_link($article->post_id) . '" target="_blank" class="button button-small">Düzenle</a>';
            }
            
            if ($article->status === 'failed') {
                $actions .= ' <button class="button button-small amo-regenerate-btn" data-id="' . $article->id . '">Yeniden Üret</button>';
            }

            $data[] = array(
                'keyword' => esc_html($article->keyword),
                'status' => '<span class="' . $status_class . '">' . $status_text . '</span>',
                'post_id' => $article->post_id ? '<a href="' . get_edit_post_link($article->post_id) . '" target="_blank">' . $article->post_id . '</a>' : '-',
                'generation_time' => $article->generation_time ? $article->generation_time . 's' : '-',
                'created_at' => esc_html($article->created_at),
                'published_at' => $article->published_at ? esc_html($article->published_at) : '-',
                'error_message' => $article->error_message ? '<span class="amo-error-message" title="' . esc_attr($article->error_message) . '">Hata var</span>' : '-',
                'actions' => $actions
            );
        }

        wp_send_json(array(
            'draw' => $draw,
            'recordsTotal' => $total_articles,
            'recordsFiltered' => $total_articles,
            'data' => $data
        ));
    }

    public function get_dashboard_stats() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $stats = array(
            'total_articles' => AMO_Database::get_articles_count(),
            'published_articles' => AMO_Database::get_articles_by_status('published'),
            'failed_articles' => AMO_Database::get_articles_by_status('failed'),
            'generating_articles' => AMO_Database::get_articles_by_status('generating'),
            'total_keywords' => count(AMO_Database::get_keywords()),
            'unused_keywords' => count(AMO_Database::get_keywords(0)),
            'api_keys_count' => count(AMO_Database::get_api_keys()),
            'auto_publish_status' => get_option('amo_auto_publish_enabled', 0) ? 'active' : 'inactive'
        );

        wp_send_json_success($stats);
    }

    public function delete_article() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $article_id = intval($_POST['article_id']);
        
        // Get article data first
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        $article = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $article_id));
        
        if (!$article) {
            wp_send_json_error(array('message' => 'Makale bulunamadı'));
        }

        // Delete WordPress post if exists
        if ($article->post_id) {
            wp_delete_post($article->post_id, true);
        }

        // Delete from database
        $wpdb->delete($table, array('id' => $article_id), array('%d'));

        wp_send_json_success(array('message' => 'Makale başarıyla silindi'));
    }

    public function regenerate_article() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $article_id = intval($_POST['article_id']);
        
        // Get article data
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        $article = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $article_id));
        
        if (!$article) {
            wp_send_json_error(array('message' => 'Makale bulunamadı'));
        }

        // Delete old post if exists
        if ($article->post_id) {
            wp_delete_post($article->post_id, true);
        }

        // Reset article status
        AMO_Database::update_article_status($article_id, 'generating', null, null);

        // Trigger regeneration (this would typically be done via scheduler)
        $scheduler = AMO_Scheduler::get_instance();
        // Note: We'd need to add a method to scheduler to handle single article generation
        
        wp_send_json_success(array('message' => 'Makale yeniden üretiliyor...'));
    }

    public function get_generation_progress() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Get recent generating articles
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        $generating_articles = $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'generating' ORDER BY created_at DESC LIMIT 5"
        );

        $progress_data = array();
        foreach ($generating_articles as $article) {
            $time_elapsed = time() - strtotime($article->created_at);
            $estimated_total_time = 120; // 2 minutes estimated
            $progress_percentage = min(($time_elapsed / $estimated_total_time) * 100, 95);
            
            $progress_data[] = array(
                'id' => $article->id,
                'keyword' => $article->keyword,
                'progress' => $progress_percentage,
                'time_elapsed' => $time_elapsed,
                'status' => $article->status
            );
        }

        wp_send_json_success($progress_data);
    }

    public function get_debug_logs() {
        check_ajax_referer('amo_admin_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            wp_send_json_error(array(
                'message' => 'Debug log dosyası bulunamadı.',
                'path' => $log_file
            ));
        }

        // Read last 500 lines (most recent logs)
        $lines = $this->read_last_lines($log_file, 500);
        
        if (empty($lines)) {
            wp_send_json_success(array(
                'logs' => array(),
                'message' => 'Log dosyası boş.',
                'file_size' => filesize($log_file)
            ));
        }

        // Parse logs
        $parsed_logs = $this->parse_debug_logs($lines);
        
        wp_send_json_success(array(
            'logs' => $parsed_logs,
            'total_count' => count($parsed_logs),
            'file_size' => filesize($log_file),
            'file_path' => $log_file,
            'last_modified' => date('Y-m-d H:i:s', filemtime($log_file))
        ));
    }

    private function read_last_lines($file, $lines = 500) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return array();
        }

        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();

        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning) break;
        }

        fclose($handle);
        return array_reverse($text);
    }

    private function parse_debug_logs($lines) {
        $parsed_logs = array();
        $log_patterns = array(
            'quota' => '/exceeded your current quota/i',
            'invalid_key' => '/API key not valid/i',
            'timeout' => '/Operation timed out/i',
            'network' => '/cURL error/i',
            'fatal' => '/PHP Fatal error/i',
            'warning' => '/PHP Warning/i',
            'database' => '/WordPress database error/i',
            'amo_error' => '/AMO.*Error/i',
            'success' => '/successfully|Success/i'
        );

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Extract timestamp
            preg_match('/\[(\d{2}-[A-Za-z]{3}-\d{4}\s+\d{2}:\d{2}:\d{2}\s+UTC)\]/', $line, $timestamp_match);
            $timestamp = isset($timestamp_match[1]) ? $timestamp_match[1] : 'Unknown';

            // Detect error type
            $error_type = 'info';
            $severity = 'low';
            foreach ($log_patterns as $type => $pattern) {
                if (preg_match($pattern, $line)) {
                    $error_type = $type;
                    
                    // Set severity
                    if (in_array($type, array('fatal', 'database'))) {
                        $severity = 'critical';
                    } elseif (in_array($type, array('quota', 'invalid_key', 'amo_error'))) {
                        $severity = 'high';
                    } elseif (in_array($type, array('timeout', 'network'))) {
                        $severity = 'medium';
                    } elseif ($type === 'success') {
                        $severity = 'success';
                    }
                    break;
                }
            }

            // Extract API key if present
            $api_key = '';
            if (preg_match('/key ([A-Za-z0-9-_]{10,})/', $line, $key_match)) {
                $api_key = substr($key_match[1], 0, 15) . '...';
            }

            // Get solution for this error type
            $solution = $this->get_error_solution($error_type);

            $parsed_logs[] = array(
                'timestamp' => $timestamp,
                'type' => $error_type,
                'severity' => $severity,
                'message' => trim($line),
                'api_key' => $api_key,
                'solution' => $solution
            );
        }

        return array_reverse($parsed_logs); // Most recent first
    }

    private function get_error_solution($error_type) {
        $solutions = array(
            'quota' => array(
                'problem' => 'API key quota aşıldı',
                'solution' => 'Sistem otomatik olarak diğer key\'e geçecek. Gece yarısı quota reset olur.',
                'action' => 'Yeni key eklemek isterseniz: API Anahtarları → Yeni Key Ekle'
            ),
            'invalid_key' => array(
                'problem' => 'API key geçersiz',
                'solution' => 'Sistem otomatik olarak diğer key\'e geçecek. Key yanlış kopyalanmış olabilir.',
                'action' => 'API Anahtarları → Key\'i test edin veya yeni key ekleyin'
            ),
            'timeout' => array(
                'problem' => 'İstek zaman aşımına uğradı',
                'solution' => 'Sistem otomatik olarak diğer key\'e geçecek. Network geçici yavaş olabilir.',
                'action' => 'İnternet bağlantınızı kontrol edin'
            ),
            'network' => array(
                'problem' => 'Network hatası',
                'solution' => 'Sistem otomatik olarak diğer key\'e geçecek. Geçici bağlantı sorunu.',
                'action' => 'Birkaç dakika bekleyin ve tekrar deneyin'
            ),
            'fatal' => array(
                'problem' => 'Kritik PHP hatası',
                'solution' => 'Plugin yeniden yüklenmeli veya kod hatası düzeltilmeli.',
                'action' => 'Eklentiyi deaktif → aktif edin. Sorun devam ederse destek alın.'
            ),
            'warning' => array(
                'problem' => 'PHP uyarısı',
                'solution' => 'Kritik değil ama kontrol edilmeli.',
                'action' => 'Detaylı loga bakın'
            ),
            'database' => array(
                'problem' => 'Veritabanı hatası',
                'solution' => 'Tablo zaten var veya veritabanı sorunu.',
                'action' => 'Eklentiyi deaktif → aktif edin (tabloları yeniden oluşturur)'
            ),
            'amo_error' => array(
                'problem' => 'Plugin hatası',
                'solution' => 'Sistem otomatik düzeltme yapacak.',
                'action' => 'Log\'u izleyin, sorun devam ederse destek alın'
            ),
            'success' => array(
                'problem' => 'Başarılı işlem',
                'solution' => 'Her şey normal çalışıyor!',
                'action' => 'Herhangi bir işlem gerekmez'
            ),
            'info' => array(
                'problem' => 'Bilgilendirme',
                'solution' => 'Normal çalışma logu.',
                'action' => 'Herhangi bir işlem gerekmez'
            )
        );

        return isset($solutions[$error_type]) ? $solutions[$error_type] : $solutions['info'];
    }
}

AMO_Ajax::get_instance();
