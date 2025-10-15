<?php

if (!defined('ABSPATH')) {
    exit;
}

class AMO_Shortcode {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('otomatik_makale_olusturucu', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $gemini_key = get_option('amo_gemini_api_key', '');
        $pexels_key = get_option('amo_pexels_api_key', '');

        if (empty($gemini_key) || empty($pexels_key)) {
            if (current_user_can('manage_options')) {
                return '<div class="amo-error">' .
                       __('API anahtarları ayarlanmamış. Lütfen ', 'otomatik-makale-olusturucu') .
                       '<a href="' . admin_url('admin.php?page=otomatik-makale-olusturucu') . '">' .
                       __('ayarlar sayfasından', 'otomatik-makale-olusturucu') .
                       '</a> ' .
                       __('API anahtarlarını girin.', 'otomatik-makale-olusturucu') .
                       '</div>';
            }
            return '<div class="amo-error">' . __('Servis şu anda kullanılamıyor.', 'otomatik-makale-olusturucu') . '</div>';
        }

        ob_start();
        ?>
        <div id="amo-wrapper">
            <div id="amo-input-container" class="amo-initial-state-container">
                <h1><?php _e('Otomatik Makale Oluşturucu', 'otomatik-makale-olusturucu'); ?></h1>
                <p><?php _e('Hakkında makale oluşturmak istediğiniz konuyu girin.', 'otomatik-makale-olusturucu'); ?></p>
                <div class="amo-form-wrapper">
                    <input type="text" id="amo-topic-input" placeholder="<?php esc_attr_e('Örn: Yapay Zeka Etiği', 'otomatik-makale-olusturucu'); ?>">
                    <button id="amo-generate-btn"><?php _e('Oluştur', 'otomatik-makale-olusturucu'); ?></button>
                </div>
            </div>

            <div id="amo-loading-container" class="amo-initial-state-container amo-hidden">
                <h2><?php _e('Sizin için harika bir makale hazırlanıyor...', 'otomatik-makale-olusturucu'); ?></h2>
                <div class="amo-progress-bar">
                    <div class="amo-progress-bar-inner"></div>
                </div>
            </div>

            <div id="amo-result-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

AMO_Shortcode::get_instance();
