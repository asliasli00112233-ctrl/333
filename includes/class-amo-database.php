<?php

if (!defined('ABSPATH')) {
    exit;
}

class AMO_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // API Keys table
        $api_keys_table = $wpdb->prefix . 'amo_api_keys';
        $api_keys_sql = "CREATE TABLE $api_keys_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            api_key varchar(255) NOT NULL,
            provider varchar(50) NOT NULL DEFAULT 'gemini',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            usage_count int(11) NOT NULL DEFAULT 0,
            last_used datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Keywords table
        $keywords_table = $wpdb->prefix . 'amo_keywords';
        $keywords_sql = "CREATE TABLE $keywords_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            is_used tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            used_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Generated Articles table
        $articles_table = $wpdb->prefix . 'amo_generated_articles';
        $articles_sql = "CREATE TABLE $articles_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            post_id int(11) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            api_key_used varchar(255) DEFAULT NULL,
            generation_time int(11) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            published_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($api_keys_sql);
        dbDelta($keywords_sql);
        dbDelta($articles_sql);
    }

    public static function get_api_keys($provider = null, $active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        $where_clauses = array();
        
        if ($active_only) {
            $where_clauses[] = "is_active = 1";
        }
        
        if ($provider !== null) {
            $where_clauses[] = $wpdb->prepare("provider = %s", $provider);
        }
        
        $where = '';
        if (!empty($where_clauses)) {
            $where = ' WHERE ' . implode(' AND ', $where_clauses);
        }
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY usage_count ASC");
    }

    public static function add_api_key($api_key, $provider = 'gemini') {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        return $wpdb->insert(
            $table,
            array(
                'api_key' => sanitize_text_field($api_key),
                'provider' => sanitize_text_field($provider),
                'is_active' => 1
            ),
            array('%s', '%s', '%d')
        );
    }

    public static function update_api_key_usage($api_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET usage_count = usage_count + 1, last_used = NOW() WHERE api_key = %s",
            $api_key
        ));
    }

    public static function delete_api_key($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
    
    public static function increment_key_usage($key_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET usage_count = usage_count + 1, last_used = NOW() WHERE id = %d",
            $key_id
        ));
    }
    
    public static function update_key_status($key_id, $status = 'active', $note = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_api_keys';
        
        $is_active = ($status === 'active') ? 1 : 0;
        
        return $wpdb->update(
            $table,
            array('is_active' => $is_active),
            array('id' => $key_id),
            array('%d'),
            array('%d')
        );
    }

    public static function get_keywords($used = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        $where = '';
        if ($used !== null) {
            $where = $wpdb->prepare(" WHERE is_used = %d", $used);
        }
        
        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY created_at DESC");
    }

    public static function get_unused_keyword() {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        return $wpdb->get_row("SELECT * FROM $table WHERE is_used = 0 ORDER BY created_at ASC LIMIT 1");
    }

    public static function add_keyword($keyword) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        return $wpdb->insert(
            $table,
            array(
                'keyword' => sanitize_text_field($keyword),
                'is_used' => 0
            ),
            array('%s', '%d')
        );
    }

    public static function mark_keyword_used($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        return $wpdb->update(
            $table,
            array('is_used' => 1, 'used_at' => current_time('mysql')),
            array('id' => $id),
            array('%d', '%s'),
            array('%d')
        );
    }

    public static function delete_keyword($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }

    public static function add_bulk_keywords($keywords) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_keywords';
        
        $values = array();
        $placeholders = array();
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword)) {
                $values[] = sanitize_text_field($keyword);
                $values[] = 0;
                $placeholders[] = "(%s, %d)";
            }
        }
        
        if (empty($placeholders)) {
            return false;
        }
        
        $query = "INSERT INTO $table (keyword, is_used) VALUES " . implode(', ', $placeholders);
        return $wpdb->query($wpdb->prepare($query, $values));
    }

    public static function get_generated_articles($limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    public static function add_generated_article($keyword, $post_id = null, $status = 'pending', $api_key_used = null, $generation_time = null, $error_message = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        $res = $wpdb->insert(
            $table,
            array(
                'keyword' => sanitize_text_field($keyword),
                'post_id' => $post_id,
                'status' => sanitize_text_field($status),
                'api_key_used' => $api_key_used,
                'generation_time' => $generation_time,
                'error_message' => $error_message,
                'published_at' => ($status === 'published') ? current_time('mysql') : null
            ),
            array('%s', '%d', '%s', '%s', '%d', '%s', '%s')
        );

        if ($res === false) {
            return false;
        }

        // Return the inserted row ID for callers that need to update status later
        return $wpdb->insert_id;
    }

    public static function update_article_status($id, $status, $post_id = null, $error_message = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        
        $data = array('status' => sanitize_text_field($status));
        $format = array('%s');
        
        if ($post_id !== null) {
            $data['post_id'] = $post_id;
            $format[] = '%d';
        }
        
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }
        
        if ($status === 'published') {
            $data['published_at'] = current_time('mysql');
            $format[] = '%s';
        }
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );
    }

    public static function get_articles_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    public static function get_articles_by_status($status) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE status = %s",
            $status
        ));
    }

    /**
     * Update the latest generated_articles row for a given keyword.
     * Useful when callers only know the keyword (e.g. scheduler auto-generate).
     */
    public static function update_article_status_by_keyword($keyword, $status, $post_id = null, $error_message = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'amo_generated_articles';

        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE keyword = %s ORDER BY created_at DESC LIMIT 1", $keyword));

        if (!$row) {
            return false;
        }

        return self::update_article_status($row->id, $status, $post_id, $error_message);
    }
}
