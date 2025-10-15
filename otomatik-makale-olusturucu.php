<?php
/**
 * Plugin Name: Otomatik Makale Oluşturucu
 * Plugin URI: https://example.com/otomatik-makale-olusturucu
 * Description: Gemini AI kullanarak otomatik olarak zengin, görsel içerikli makaleler oluşturur ve otomatik yayınlar
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: otomatik-makale-olusturucu
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AMO_VERSION', '2.0.0');
define('AMO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AMO_PLUGIN_URL', plugin_dir_url(__FILE__));
// PEXELS API anahtarı: Lütfen eklenti ayarlarından girin. Kod içinde sabit anahtar bırakmayın.
define('AMO_PEXELS_API_KEY', '');

class OtomatikMakaleOlusturucu {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once AMO_PLUGIN_DIR . 'admin/class-amo-admin-new.php';
        require_once AMO_PLUGIN_DIR . 'includes/class-amo-database.php';
        require_once AMO_PLUGIN_DIR . 'includes/class-amo-api-handler.php';
        require_once AMO_PLUGIN_DIR . 'includes/class-amo-scheduler.php';
        require_once AMO_PLUGIN_DIR . 'includes/class-amo-ajax.php';
    }

    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('init', array($this, 'init_cron'));
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'otomatik-makale-olusturucu',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap', array(), null);
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', array(), '6.5.0');
        wp_enqueue_script('chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js', array(), '4.4.0', true);

        wp_enqueue_style('amo-frontend-style', AMO_PLUGIN_URL . 'assets/css/frontend.css', array(), AMO_VERSION);
        wp_enqueue_script('amo-frontend-script', AMO_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'chartjs'), AMO_VERSION, true);

        wp_localize_script('amo-frontend-script', 'amoData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amo_generate_article')
        ));
    }

    public function init_cron() {
        if (!wp_next_scheduled('amo_auto_generate_article')) {
            wp_schedule_event(time(), 'hourly', 'amo_auto_generate_article');
        }
    }
}

function amo_init() {
    return OtomatikMakaleOlusturucu::get_instance();
}

amo_init();

register_activation_hook(__FILE__, 'amo_activate');
function amo_activate() {
    // Create database tables
    AMO_Database::create_tables();
    
    // Set default options
    add_option('amo_auto_publish_enabled', 0);
    add_option('amo_articles_per_hour', 1);
    add_option('amo_last_generation_time', 0);
    
    // Schedule cron job
    if (!wp_next_scheduled('amo_auto_generate_article')) {
        wp_schedule_event(time(), 'hourly', 'amo_auto_generate_article');
    }
}

register_deactivation_hook(__FILE__, 'amo_deactivate');
function amo_deactivate() {
    wp_clear_scheduled_hook('amo_auto_generate_article');
}
