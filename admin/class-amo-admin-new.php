<?php

if (!defined('ABSPATH')) {
    exit;
}

class AMO_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Otomatik Makale Oluşturucu', 'otomatik-makale-olusturucu'),
            __('Makale Oluşturucu', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-write-blog',
            30
        );

        add_submenu_page(
            'amo-dashboard',
            __('Dashboard', 'otomatik-makale-olusturucu'),
            __('Dashboard', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-dashboard',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'amo-dashboard',
            __('API Anahtarları', 'otomatik-makale-olusturucu'),
            __('API Anahtarları', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-api-keys',
            array($this, 'render_api_keys_page')
        );

        add_submenu_page(
            'amo-dashboard',
            __('Kelime Listesi', 'otomatik-makale-olusturucu'),
            __('Kelime Listesi', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-keywords',
            array($this, 'render_keywords_page')
        );

        add_submenu_page(
            'amo-dashboard',
            __('Otomatik Yayınlama', 'otomatik-makale-olusturucu'),
            __('Otomatik Yayınlama', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-auto-publish',
            array($this, 'render_auto_publish_page')
        );

        add_submenu_page(
            'amo-dashboard',
            __('Üretilen Makaleler', 'otomatik-makale-olusturucu'),
            __('Üretilen Makaleler', 'otomatik-makale-olusturucu'),
            'manage_options',
            'amo-generated-articles',
            array($this, 'render_generated_articles_page')
        );
    }

    public function register_settings() {
        register_setting('amo_settings_group', 'amo_auto_publish_enabled');
        register_setting('amo_settings_group', 'amo_articles_per_hour');
    }

    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="index, follow">
            <title>SEO Uyumlu Makale Oluşturucu - Google Gemini ile</title>
            <meta name="description" content="GEMINI API ile anahtar kelimenize göre SEO uyumlu 1500-2000 kelimelik makale oluşturun. Tamamen zengin içerikli, okunabilir ve arama motorları için optimize edilmiştir.">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.8;
                    color: #333;
                    max-width: 900px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                h1, h2, h3 {
                    color: #2c3e50;
                }
                h1 {
                    border-bottom: 2px solid #3498db;
                    padding-bottom: 10px;
                }
                h2 {
                    margin-top: 30px;
                    border-left: 4px solid #3498db;
                    padding-left: 15px;
                }
                h3 {
                    color: #2980b9;
                }
                p {
                    margin-bottom: 18px;
                    text-align: justify;
                }
                ul, ol {
                    margin-bottom: 20px;
                    padding-left: 20px;
                }
                li {
                    margin-bottom: 8px;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                }
                input[type="text"] {
                    width: 70%;
                    padding: 10px;
                    font-size: 16px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                button {
                    padding: 10px 20px;
                    background-color: #3498db;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                }
                button:hover {
                    background-color: #2980b9;
                }
                #loading {
                    color: #e74c3c;
                    font-weight: bold;
                    display: none;
                    margin: 20px 0;
                }
                #result {
                    margin-top: 30px;
                    padding: 20px;
                    border: 1px dashed #ddd;
                    border-radius: 8px;
                    background-color: #f8f9fa;
                }
                .seo-badge {
                    display: inline-block;
                    background-color: #27ae60;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 15px;
                    font-size: 14px;
                    margin-left: 10px;
                }
                .warning {
                    background-color: #fdf2e8;
                    border-left: 4px solid #e67e22;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 5px;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔍 SEO Uyumlu Makale Oluşturucu</h1>
                <p>Google Gemini AI ile anahtar kelimenize göre tamamen SEO optimize edilmiş, 1500–2000 kelime arasında zengin içerikli makale oluşturun.</p>

                <div class="warning">
                    ⚠️ Bu araç, API anahtarınızı tarayıcıda kullanır. Üretim ortamlarında bu yöntem güvenli değildir. API anahtarınızı sunucuda saklayın.
                </div>

                <input type="text" id="keywordInput" placeholder="Anahtar kelimeyi girin (örn. 'evde yapay zeka ile beslenme')" />
                <button onclick="generateArticle()">Makaleyi Oluştur</button>
                <p id="loading">⏳ Makale oluşturuluyor, lütfen bekleyin...</p>

                <div id="result" style="display:none;"></div>
            </div>

            <script>
                // Use server-side production generation via admin-ajax (uses rotation, blacklist, settings)
                const AMO_AJAX = {
                    ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                    nonce: '<?php echo wp_create_nonce('amo_generate_article'); ?>'
                };

                async function generateArticle() {
                    const keyword = document.getElementById("keywordInput").value.trim();
                    if (!keyword) {
                        alert("Lütfen bir anahtar kelime girin!");
                        return;
                    }
                    document.getElementById("loading").style.display = "block";
                    document.getElementById("result").style.display = "none";

                    try {
                        const resp = await fetch(AMO_AJAX.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: new URLSearchParams({
                                action: 'amo_generate_article',
                                nonce: AMO_AJAX.nonce,
                                topic: keyword
                            })
                        });

                        const json = await resp.json();

                        if (!json || typeof json !== 'object') {
                            throw new Error('Sunucudan beklenmeyen yanıt alındı.');
                        }

                        if (!json.success) {
                            const msg = (json.data && json.data.message) ? json.data.message : (json.data || 'Bilinmeyen hata');
                            throw new Error(msg);
                        }

                        // Success - expected data: { htmlContent: '<article>...</article>', chartData: {...} }
                        const articleData = json.data;
                        let html = '';
                        if (articleData.htmlContent) {
                            html = articleData.htmlContent;
                        } else if (articleData.html) {
                            html = articleData.html;
                        } else {
                            // Fallback: stringify whole payload
                            html = '<pre>' + escapeHtml(JSON.stringify(articleData, null, 2)) + '</pre>';
                        }

                        document.getElementById('result').innerHTML = html;
                        document.getElementById('result').style.display = 'block';
                        document.getElementById('loading').style.display = 'none';

                    } catch (error) {
                        console.error('Hata:', error);
                        document.getElementById('result').innerHTML = `\n                            <h2>❌ Hata Oluştu</h2>\n                            <p>${escapeHtml(error.message || String(error))}</p>\n                        `;
                        document.getElementById('result').style.display = 'block';
                        document.getElementById('loading').style.display = 'none';
                    }
                }

                // Simple HTML escaper for fallback/error display
                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }
            </script>
        </body>
        </html>
        <?php
    }

    public function render_api_keys_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submissions
        if (isset($_POST['add_api_key']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_add_api_key')) {
            $api_key = sanitize_text_field($_POST['api_key']);
            $provider = sanitize_text_field($_POST['provider']);
            
            if (!empty($api_key)) {
                AMO_Database::add_api_key($api_key, $provider);
                echo '<div class="notice notice-success"><p>API anahtarı başarıyla eklendi.</p></div>';
            }
        }

        if (isset($_POST['delete_api_key']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_delete_api_key')) {
            $key_id = intval($_POST['key_id']);
            AMO_Database::delete_api_key($key_id);
            echo '<div class="notice notice-success"><p>API anahtarı silindi.</p></div>';
        }

        $api_keys = AMO_Database::get_api_keys(null, false); // Get all keys including inactive
        ?>
        <div class="wrap amo-api-keys-wrap">
            <h1>🔑 <?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amo-api-info-banner">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                    <strong>API Konfigürasyonu:</strong>
                    <ul>
                        <li>Model: <code>gemini-2.5-pro</code></li>
                        <li>Endpoint: <code>generateContent</code></li>
                        <li>Rotation: Otomatik key rotation aktif (başarısız/başarılı tüm istekler rotation'a devam eder)</li>
                        <li>Test butonu gerçek zamanlı API kontrolü yapar</li>
                    </ul>
                </div>
            </div>
            
            <div class="amo-add-api-key-form card">
                <h2>➕ Yeni API Anahtarı Ekle</h2>
                <form method="post" id="add-api-key-form">
                    <?php wp_nonce_field('amo_add_api_key'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="api_key">API Anahtarı</label></th>
                            <td>
                                <input type="text" id="api_key" name="api_key" class="regular-text code" required placeholder="AIzaSyB..." />
                                <p class="description">
                                    <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio'dan API Key alın</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="provider">Sağlayıcı</label></th>
                            <td>
                                <select id="provider" name="provider">
                                    <option value="gemini" selected>Gemini (Önerilen - 2.5 Pro)</option>
                                </select>
                                <p class="description">Şu anda sadece Gemini desteklenmektedir.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="add_api_key" class="button-primary" value="API Anahtarı Ekle" />
                    </p>
                </form>
            </div>

            <div class="amo-api-keys-list card">
                <div class="card-header">
                    <h2>🗝️ Mevcut API Anahtarları</h2>
                    <div class="header-stats">
                        <span class="stat-badge">Toplam: <?php echo count($api_keys); ?></span>
                        <span class="stat-badge active">Aktif: <?php echo count(array_filter($api_keys, function($k) { return $k->is_active; })); ?></span>
                    </div>
                </div>
                
                <?php if (empty($api_keys)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔐</div>
                        <h3>Henüz API anahtarı eklenmemiş</h3>
                        <p>İçerik oluşturmaya başlamak için yukarıdaki formdan bir Gemini API anahtarı ekleyin.</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped amo-keys-table">
                        <thead>
                            <tr>
                                <th width="5%">Durum</th>
                                <th width="30%">API Anahtarı</th>
                                <th width="12%">Model</th>
                                <th width="10%">Kullanım</th>
                                <th width="15%">Son Kullanım</th>
                                <th width="13%">Oluşturulma</th>
                                <th width="15%">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_keys as $key): ?>
                                <tr class="api-key-row <?php echo $key->is_active ? 'active' : 'inactive'; ?>" data-key-id="<?php echo $key->id; ?>">
                                    <td>
                                        <?php if ($key->is_active): ?>
                                            <span class="status-badge status-active" title="Aktif">✓</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive" title="Deaktif">✗</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="api-key-display"><?php echo esc_html(substr($key->api_key, 0, 25) . '...' . substr($key->api_key, -5)); ?></code>
                                        <button class="button-link copy-key-btn" data-key="<?php echo esc_attr($key->api_key); ?>" title="Kopyala">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="model-badge">
                                            <?php echo $key->provider === 'gemini' ? 'Gemini 2.5 Pro' : ucfirst($key->provider); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="usage-count"><?php echo number_format($key->usage_count); ?></span>
                                        <span class="usage-label">istek</span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($key->last_used) {
                                            $time_diff = human_time_diff(strtotime($key->last_used), current_time('timestamp'));
                                            echo '<span class="time-ago" title="' . esc_attr($key->last_used) . '">' . esc_html($time_diff) . ' önce</span>';
                                        } else {
                                            echo '<span class="never-used">Hiç kullanılmadı</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date('d.m.Y', strtotime($key->created_at))); ?>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="button button-small test-api-key-btn" 
                                                data-key="<?php echo esc_attr($key->api_key); ?>" 
                                                data-provider="<?php echo esc_attr($key->provider); ?>"
                                                data-key-id="<?php echo $key->id; ?>">
                                            <span class="dashicons dashicons-yes-alt"></span> Test
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('amo_delete_api_key'); ?>
                                            <input type="hidden" name="key_id" value="<?php echo $key->id; ?>" />
                                            <button type="submit" name="delete_api_key" class="button button-small button-link-delete" 
                                                    onclick="return confirm('Bu API anahtarını silmek istediğinizden emin misiniz?')">
                                                <span class="dashicons dashicons-trash"></span> Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="test-result-row" id="test-result-<?php echo $key->id; ?>" style="display: none;">
                                    <td colspan="7">
                                        <div class="test-result-container"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php
            // Display blacklist status
            $blacklist = get_transient('amo_key_blacklist');
            if ($blacklist && is_array($blacklist) && !empty($blacklist)):
            ?>
            <div class="amo-blacklist-status card">
                <div class="card-header">
                    <h2>⏱️ Geçici Olarak Kara Listeye Alınan Anahtarlar</h2>
                    <div class="header-stats">
                        <span class="stat-badge error">Kara Listede: <?php echo count($blacklist); ?></span>
                    </div>
                </div>
                
                <div class="blacklist-info-banner">
                    <div class="info-icon">ℹ️</div>
                    <div class="info-content">
                        <p><strong>Not:</strong> Bu anahtarlar geçici olarak kara listeye alınmıştır. Belirtilen süre sonunda otomatik olarak tekrar denenecektir.</p>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="25%">API Anahtarı</th>
                            <th width="15%">Hata Tipi</th>
                            <th width="35%">Hata Mesajı</th>
                            <th width="15%">Kara Listeye Eklenme</th>
                            <th width="10%">Kalan Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($blacklist as $key_id => $blacklist_data):
                            // Find the key details
                            $key_obj = null;
                            foreach ($api_keys as $k) {
                                if ($k->id == $key_id) {
                                    $key_obj = $k;
                                    break;
                                }
                            }
                            
                            if (!$key_obj) continue;
                            
                            $remaining_seconds = $blacklist_data['expiry'] - time();
                            $remaining_minutes = ceil($remaining_seconds / 60);
                            
                            // Error type translation
                            $error_type_labels = array(
                                'quota_exceeded' => '❌ Kota Aşıldı',
                                'timeout' => '⏱️ Zaman Aşımı',
                                'overloaded' => '🔥 API Aşırı Yüklü',
                                'invalid_key' => '🚫 Geçersiz Key',
                                'network' => '🌐 Ağ Hatası'
                            );
                            
                            $error_type_label = $error_type_labels[$blacklist_data['error_type']] ?? $blacklist_data['error_type'];
                        ?>
                        <tr class="blacklist-row blacklist-type-<?php echo esc_attr($blacklist_data['error_type']); ?>">
                            <td>
                                <code class="api-key-display"><?php echo esc_html(substr($key_obj->api_key, 0, 20) . '...' . substr($key_obj->api_key, -5)); ?></code>
                            </td>
                            <td>
                                <span class="error-type-badge"><?php echo esc_html($error_type_label); ?></span>
                            </td>
                            <td>
                                <span class="error-message-truncated" title="<?php echo esc_attr($blacklist_data['error_message']); ?>">
                                    <?php echo esc_html(strlen($blacklist_data['error_message']) > 80 ? substr($blacklist_data['error_message'], 0, 80) . '...' : $blacklist_data['error_message']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($blacklist_data['blacklisted_at']); ?>
                            </td>
                            <td>
                                <?php if ($remaining_seconds > 0): ?>
                                    <span class="remaining-time" data-expiry="<?php echo $blacklist_data['expiry']; ?>">
                                        <?php 
                                        if ($remaining_minutes > 60) {
                                            echo ceil($remaining_minutes / 60) . ' saat';
                                        } else {
                                            echo $remaining_minutes . ' dk';
                                        }
                                        ?>
                                    </span>
                                <?php else: ?>
                                    <span class="expired-badge">Süresi Doldu</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <style>
                .amo-api-keys-wrap { background: #f0f0f1; padding: 20px; margin: 20px 20px 20px 0; border-radius: 8px; }
                .amo-api-info-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; }
                .amo-api-info-banner .info-icon { font-size: 32px; }
                .amo-api-info-banner ul { margin: 10px 0 0 0; padding-left: 20px; }
                .amo-api-info-banner code { background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px; font-family: monospace; }
                .card { position: relative; margin-top: 20px; padding: 0.7em 2em 1em; min-width: 255px; max-width: 100%; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04); background: #fff; box-sizing: border-box; }
                .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f1; }
                .card-header h2 { margin: 0; }
                .header-stats { display: flex; gap: 10px; }
                .stat-badge { background: #f0f0f1; padding: 5px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; }
                .stat-badge.active { background: #d1fae5; color: #065f46; }
                .amo-keys-table { margin-top: 0 !important; }
                .status-badge { display: inline-block; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; }
                .status-active { background: #10b981; color: white; }
                .status-inactive { background: #ef4444; color: white; }
                .api-key-display { background: #f9fafb; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                .copy-key-btn { color: #6b7280; padding: 0 5px; }
                .copy-key-btn:hover { color: #2563eb; }
                .model-badge { background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
                .usage-count { font-weight: 600; color: #1f2937; }
                .usage-label { color: #6b7280; font-size: 12px; margin-left: 3px; }
                .time-ago { color: #6b7280; font-size: 13px; }
                .never-used { color: #9ca3af; font-style: italic; font-size: 13px; }
                .test-api-key-btn { background: #10b981; color: white; border: none; }
                .test-api-key-btn:hover { background: #059669; color: white; }
                .test-api-key-btn .dashicons { font-size: 16px; width: 16px; height: 16px; }
                .test-result-container { padding: 15px; border-radius: 6px; }
                .test-result-container.success { background: #d1fae5; border-left: 4px solid #10b981; }
                .test-result-container.error { background: #fee2e2; border-left: 4px solid #ef4444; }
                .test-result-container.loading { background: #e0e7ff; border-left: 4px solid #6366f1; }
                .empty-state { text-align: center; padding: 60px 20px; }
                .empty-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
                .empty-state h3 { color: #1f2937; margin-bottom: 10px; }
                .empty-state p { color: #6b7280; }
                .api-key-row.inactive { opacity: 0.6; background: #f9fafb; }
                
                /* Blacklist Status Styles */
                .amo-blacklist-status { margin-top: 20px; }
                .blacklist-info-banner { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin-bottom: 15px; display: flex; gap: 10px; align-items: flex-start; }
                .blacklist-info-banner .info-icon { font-size: 20px; }
                .blacklist-info-banner p { margin: 0; color: #92400e; }
                .stat-badge.error { background: #fee2e2; color: #991b1b; }
                .error-type-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
                .blacklist-type-quota_exceeded .error-type-badge { background: #fee2e2; color: #991b1b; }
                .blacklist-type-timeout .error-type-badge { background: #fed7aa; color: #9a3412; }
                .blacklist-type-overloaded .error-type-badge { background: #fef3c7; color: #92400e; }
                .blacklist-type-invalid_key .error-type-badge { background: #fecaca; color: #7f1d1d; }
                .blacklist-type-network .error-type-badge { background: #e0e7ff; color: #3730a3; }
                .error-message-truncated { color: #6b7280; font-size: 13px; }
                .remaining-time { color: #dc2626; font-weight: 600; font-size: 13px; }
                .expired-badge { color: #16a34a; font-style: italic; font-size: 12px; }
            </style>
        </div>
        <?php
    }

    public function render_keywords_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submissions
        if (isset($_POST['add_keyword']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_add_keyword')) {
            $keyword = sanitize_text_field($_POST['keyword']);
            if (!empty($keyword)) {
                AMO_Database::add_keyword($keyword);
                echo '<div class="notice notice-success"><p>Kelime başarıyla eklendi.</p></div>';
            }
        }

        if (isset($_POST['add_bulk_keywords']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_add_bulk_keywords')) {
            $keywords_text = sanitize_textarea_field($_POST['bulk_keywords']);
            $keywords = array_filter(array_map('trim', explode("\n", $keywords_text)));
            
            if (!empty($keywords)) {
                AMO_Database::add_bulk_keywords($keywords);
                echo '<div class="notice notice-success"><p>' . count($keywords) . ' kelime başarıyla eklendi.</p></div>';
            }
        }

        if (isset($_POST['delete_keyword']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_delete_keyword')) {
            $keyword_id = intval($_POST['keyword_id']);
            AMO_Database::delete_keyword($keyword_id);
            echo '<div class="notice notice-success"><p>Kelime silindi.</p></div>';
        }

        $keywords = AMO_Database::get_keywords();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amo-add-keyword-forms">
                <div class="amo-single-keyword-form">
                    <h2>Tek Kelime Ekle</h2>
                    <form method="post">
                        <?php wp_nonce_field('amo_add_keyword'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Kelime</th>
                                <td><input type="text" name="keyword" class="regular-text" required /></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="add_keyword" class="button-primary" value="Kelime Ekle" />
                        </p>
                    </form>
                </div>

                <div class="amo-bulk-keywords-form">
                    <h2>Toplu Kelime Ekle</h2>
                    <form method="post">
                        <?php wp_nonce_field('amo_add_bulk_keywords'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Kelimeler</th>
                                <td>
                                    <textarea name="bulk_keywords" rows="10" cols="50" class="large-text" placeholder="Her satıra bir kelime yazın..."></textarea>
                                    <p class="description">Her satıra bir kelime yazın.</p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="add_bulk_keywords" class="button-primary" value="Kelimeleri Ekle" />
                        </p>
                    </form>
                </div>
            </div>

            <div class="amo-keywords-list">
                <h2>Kelime Listesi</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Kelime</th>
                            <th>Durum</th>
                            <th>Eklenme Tarihi</th>
                            <th>Kullanılma Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($keywords)): ?>
                            <tr>
                                <td colspan="5">Henüz kelime eklenmemiş.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($keywords as $keyword): ?>
                                <tr>
                                    <td><?php echo esc_html($keyword->keyword); ?></td>
                                    <td>
                                        <?php if ($keyword->is_used): ?>
                                            <span class="amo-status-used">Kullanıldı</span>
                                        <?php else: ?>
                                            <span class="amo-status-unused">Kullanılmadı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($keyword->created_at); ?></td>
                                    <td><?php echo $keyword->used_at ? esc_html($keyword->used_at) : '-'; ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('amo_delete_keyword'); ?>
                                            <input type="hidden" name="keyword_id" value="<?php echo $keyword->id; ?>" />
                                            <input type="submit" name="delete_keyword" class="button button-small" value="Sil" onclick="return confirm('Bu kelimeyi silmek istediğinizden emin misiniz?')" />
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_auto_publish_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle settings save
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'amo_save_settings')) {
            update_option('amo_auto_publish_enabled', isset($_POST['auto_publish_enabled']) ? 1 : 0);
            update_option('amo_articles_per_hour', intval($_POST['articles_per_hour']));
            echo '<div class="notice notice-success"><p>Ayarlar kaydedildi.</p></div>';
        }

        $auto_publish_enabled = get_option('amo_auto_publish_enabled', 0);
        $articles_per_hour = get_option('amo_articles_per_hour', 1);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amo-auto-publish-settings">
                <h2>Otomatik Yayınlama Ayarları</h2>
                <form method="post">
                    <?php wp_nonce_field('amo_save_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Otomatik Yayınlama</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_publish_enabled" value="1" <?php checked($auto_publish_enabled, 1); ?> />
                                    Otomatik yayınlamayı etkinleştir
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Saatte Makale Sayısı</th>
                            <td>
                                <input type="number" name="articles_per_hour" value="<?php echo esc_attr($articles_per_hour); ?>" min="1" max="10" />
                                <p class="description">Saatte kaç makale üretileceğini belirleyin (1-10 arası).</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="save_settings" class="button-primary" value="Ayarları Kaydet" />
                    </p>
                </form>
            </div>

            <div class="amo-manual-generation">
                <h2>Manuel Makale Üretimi</h2>

                <div class="warning">
                    ⚠️ Bu araç, API anahtarınızı tarayıcıda kullanır. Üretim ortamlarında bu yöntem güvenli değildir. API anahtarınızı sunucuda saklayın.
                </div>

                <input type="text" id="keywordInput" placeholder="Anahtar kelimeyi girin (örn. 'evde yapay zeka ile beslenme')" />
                <button onclick="generateArticle()">Makaleyi Oluştur</button>
                <p id="loading" style="display: none;">⏳ Makale oluşturuluyor, lütfen bekleyin...</p>

                <div id="result" style="display:none;"></div>
            </div>

            <script>
                // Use server-side production generation via admin-ajax (uses rotation, blacklist, settings)
                const AMO_AJAX = {
                    ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                    nonce: '<?php echo wp_create_nonce('amo_generate_article'); ?>'
                };

                async function generateArticle() {
                    const keyword = document.getElementById("keywordInput").value.trim();
                    if (!keyword) {
                        alert("Lütfen bir anahtar kelime girin!");
                        return;
                    }
                    document.getElementById("loading").style.display = "block";
                    document.getElementById("result").style.display = "none";

                    try {
                        const resp = await fetch(AMO_AJAX.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: new URLSearchParams({
                                action: 'amo_generate_article',
                                nonce: AMO_AJAX.nonce,
                                topic: keyword
                            })
                        });

                        const json = await resp.json();

                        if (!json || typeof json !== 'object') {
                            throw new Error('Sunucudan beklenmeyen yanıt alındı.');
                        }

                        if (!json.success) {
                            const msg = (json.data && json.data.message) ? json.data.message : (json.data || 'Bilinmeyen hata');
                            throw new Error(msg);
                        }

                        // Success - expected data: { htmlContent: '<article>...</article>', chartData: {...} }
                        const articleData = json.data;
                        let html = '';
                        if (articleData.htmlContent) {
                            html = articleData.htmlContent;
                        } else if (articleData.html) {
                            html = articleData.html;
                        } else {
                            // Fallback: stringify whole payload
                            html = '<pre>' + escapeHtml(JSON.stringify(articleData, null, 2)) + '</pre>';
                        }

                        document.getElementById('result').innerHTML = html;
                        document.getElementById('result').style.display = 'block';
                        document.getElementById('loading').style.display = 'none';

                    } catch (error) {
                        console.error('Hata:', error);
                        document.getElementById('result').innerHTML = `
                            <h2>❌ Hata Oluştu</h2>
                            <p>${escapeHtml(error.message || String(error))}</p>
                        `;
                        document.getElementById('result').style.display = 'block';
                        document.getElementById('loading').style.display = 'none';
                    }
                }

                // Simple HTML escaper for fallback/error display
                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }
            </script>

            <div class="amo-auto-publish-control">
                <h2>Otomatik Yayınlama Kontrolü</h2>
                <div id="amo-auto-publish-status">
                    <?php if ($auto_publish_enabled): ?>
                        <p class="amo-status-active">✅ Otomatik yayınlama aktif</p>
                        <button id="amo-stop-auto-publish" class="button button-secondary">Durdur</button>
                    <?php else: ?>
                        <p class="amo-status-inactive">⏸️ Otomatik yayınlama durduruldu</p>
                        <button id="amo-start-auto-publish" class="button button-primary">Başlat</button>
                    <?php endif; ?>
                </div>
                <div id="amo-auto-publish-progress" style="display: none;">
                    <div class="amo-progress-bar">
                        <div class="amo-progress-fill"></div>
                    </div>
                    <p id="amo-auto-publish-message">Otomatik yayınlama başlatılıyor...</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_generated_articles_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $articles = AMO_Database::get_generated_articles();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amo-articles-table-container">
                <table id="amo-articles-table" class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Kelime</th>
                            <th>Durum</th>
                            <th>Makale ID</th>
                            <th>Üretim Süresi</th>
                            <th>Oluşturulma Tarihi</th>
                            <th>Yayınlanma Tarihi</th>
                            <th>Hata Mesajı</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="8">Henüz makale üretilmemiş.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td><?php echo esc_html($article->keyword); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($article->status) {
                                            case 'published':
                                                $status_class = 'amo-status-published';
                                                $status_text = 'Yayınlandı';
                                                break;
                                            case 'failed':
                                                $status_class = 'amo-status-failed';
                                                $status_text = 'Başarısız';
                                                break;
                                            case 'generating':
                                                $status_class = 'amo-status-generating';
                                                $status_text = 'Üretiliyor';
                                                break;
                                            default:
                                                $status_class = 'amo-status-pending';
                                                $status_text = 'Bekliyor';
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($article->post_id): ?>
                                            <a href="<?php echo get_edit_post_link($article->post_id); ?>" target="_blank">
                                                <?php echo $article->post_id; ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $article->generation_time ? $article->generation_time . 's' : '-'; ?></td>
                                    <td><?php echo esc_html($article->created_at); ?></td>
                                    <td><?php echo $article->published_at ? esc_html($article->published_at) : '-'; ?></td>
                                    <td>
                                        <?php if ($article->error_message): ?>
                                            <span class="amo-error-message" title="<?php echo esc_attr($article->error_message); ?>">
                                                Hata var
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($article->post_id): ?>
                                            <a href="<?php echo get_permalink($article->post_id); ?>" target="_blank" class="button button-small">Görüntüle</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'amo-') === false) {
            return;
        }

        wp_enqueue_style('amo-admin-style', AMO_PLUGIN_URL . 'assets/css/admin.css', array(), AMO_VERSION);
        wp_enqueue_script('amo-admin-script', AMO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AMO_VERSION, true);
        
        wp_localize_script('amo-admin-script', 'amoAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amo_admin_action'),
            // Nonce specifically for server-side article generation (dashboard uses its own AMO_AJAX.nonce)
            'generateArticleNonce' => wp_create_nonce('amo_generate_article')
        ));

        // DataTables for articles page
        if ($hook === 'makale-oluşturucu_page_amo-generated-articles') {
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css');
            wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
        }
    }
}

AMO_Admin::get_instance();
