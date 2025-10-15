jQuery(document).ready(function($) {
    
    // API Key Test Functionality
    $('.test-api-key-btn').on('click', function() {
        var $btn = $(this);
        var apiKey = $btn.data('key');
        var provider = $btn.data('provider');
        var keyId = $btn.data('key-id');
        var $resultRow = $('#test-result-' + keyId);
        var $resultContainer = $resultRow.find('.test-result-container');
        
        // Show result row
        $resultRow.show();
        
        // Show loading state
        $resultContainer.removeClass('success error').addClass('loading');
        $resultContainer.html('<strong>⏳ Testing API key...</strong><p>Gemini 2.5 Pro endpoint kontrol ediliyor...</p>');
        
        // Disable button
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
        
        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_test_api_key',
                api_key: apiKey,
                provider: provider,
                nonce: amoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $resultContainer.removeClass('loading error').addClass('success');
                    $resultContainer.html(
                        '<strong>✅ ' + response.data.message + '</strong>' +
                        '<p><strong>Model:</strong> ' + response.data.model + '</p>' +
                        '<p><strong>Response Time:</strong> ' + response.data.response_time + '</p>' +
                        '<p><strong>API Response:</strong> ' + response.data.api_response + '</p>'
                    );
                    
                    showNotice('success', 'API key is valid and working!');
                } else {
                    $resultContainer.removeClass('loading success').addClass('error');
                    var errorHtml = '<strong>❌ ' + response.data.message + '</strong>';
                    
                    if (response.data.status_code) {
                        errorHtml += '<p><strong>Status Code:</strong> ' + response.data.status_code + '</p>';
                    }
                    if (response.data.response_time) {
                        errorHtml += '<p><strong>Response Time:</strong> ' + response.data.response_time + '</p>';
                    }
                    if (response.data.error) {
                        errorHtml += '<p><strong>Error:</strong> ' + response.data.error + '</p>';
                    }
                    if (response.data.error_status) {
                        errorHtml += '<p><strong>Error Status:</strong> ' + response.data.error_status + '</p>';
                    }
                    if (response.data.raw_response) {
                        errorHtml += '<details style="margin-top: 10px;"><summary style="cursor: pointer; font-weight: bold;">🔍 Raw Response (click to expand)</summary><pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; margin-top: 5px;">' + response.data.raw_response + '</pre></details>';
                    }
                    
                    $resultContainer.html(errorHtml);
                    showNotice('error', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                $resultContainer.removeClass('loading success').addClass('error');
                $resultContainer.html(
                    '<strong>❌ Network error occurred</strong>' +
                    '<p>' + error + '</p>'
                );
                showNotice('error', 'Network error: ' + error);
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Test');
                
                // Auto-hide after 10 seconds
                setTimeout(function() {
                    $resultRow.fadeOut();
                }, 10000);
            }
        });
    });
    
    // Copy API key to clipboard
    $('.copy-key-btn').on('click', function(e) {
        e.preventDefault();
        var apiKey = $(this).data('key');
        var $btn = $(this);
        
        // Create temporary input
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(apiKey).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        var originalHtml = $btn.html();
        $btn.html('<span class="dashicons dashicons-yes"></span>');
        $btn.css('color', '#10b981');
        
        setTimeout(function() {
            $btn.html(originalHtml);
            $btn.css('color', '');
        }, 2000);
        
        showNotice('success', 'API key copied to clipboard!');
    });
    
    // Add spin animation for loading icon
    $('<style>.spin { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>').appendTo('head');
    
    // Debug Log Viewer
    $('.view-debug-logs-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Yükleniyor...');
        
        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_get_debug_logs',
                nonce: amoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showDebugLogsModal(response.data);
                } else {
                    showNotice('error', response.data.message || 'Log yüklenemedi');
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'AJAX hatası: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-media-text"></span> Log Görüntüle');
            }
        });
    });
    
    function showDebugLogsModal(data) {
        // Remove existing modal if any
        $('#amo-debug-logs-modal').remove();
        
        // Create modal
        var modalHtml = '<div id="amo-debug-logs-modal" class="amo-modal">' +
            '<div class="amo-modal-overlay"></div>' +
            '<div class="amo-modal-content">' +
                '<div class="amo-modal-header">' +
                    '<h2>📊 Debug Log Görüntüleyici</h2>' +
                    '<div class="amo-log-stats">' +
                        '<span class="stat-item">📝 <strong>' + data.total_count + '</strong> Log</span>' +
                        '<span class="stat-item">💾 <strong>' + formatBytes(data.file_size) + '</strong></span>' +
                        '<span class="stat-item">🕐 <strong>' + data.last_modified + '</strong></span>' +
                    '</div>' +
                    '<button class="amo-modal-close">&times;</button>' +
                '</div>' +
                '<div class="amo-modal-body">' +
                    '<div class="amo-log-filters">' +
                        '<button class="filter-btn active" data-filter="all">Tümü (' + data.total_count + ')</button>' +
                        '<button class="filter-btn" data-filter="critical">🔴 Kritik</button>' +
                        '<button class="filter-btn" data-filter="high">🟠 Yüksek</button>' +
                        '<button class="filter-btn" data-filter="medium">🟡 Orta</button>' +
                        '<button class="filter-btn" data-filter="success">🟢 Başarılı</button>' +
                    '</div>' +
                    '<div class="amo-logs-table-container">' +
                        '<table class="amo-logs-table">' +
                            '<thead>' +
                                '<tr>' +
                                    '<th>Zaman</th>' +
                                    '<th>Seviye</th>' +
                                    '<th>Tip</th>' +
                                    '<th>Mesaj</th>' +
                                    '<th>API Key</th>' +
                                    '<th>İşlem</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody id="amo-logs-tbody">' +
                            '</tbody>' +
                        '</table>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('body').append(modalHtml);
        
        // Populate table
        populateLogsTable(data.logs);
        
        // Modal close handlers
        $('.amo-modal-close, .amo-modal-overlay').on('click', function() {
            $('#amo-debug-logs-modal').fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Filter buttons
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            var filter = $(this).data('filter');
            filterLogs(filter, data.logs);
        });
        
        // Show modal
        $('#amo-debug-logs-modal').fadeIn();
    }
    
    function populateLogsTable(logs) {
        var tbody = $('#amo-logs-tbody');
        tbody.empty();
        
        if (logs.length === 0) {
            tbody.append('<tr><td colspan="6" style="text-align: center;">Henüz log kaydı yok.</td></tr>');
            return;
        }
        
        logs.forEach(function(log, index) {
            var severityIcon = getSeverityIcon(log.severity);
            var severityClass = 'severity-' + log.severity;
            var typeLabel = getTypeLabel(log.type);
            
            var row = '<tr class="log-row ' + severityClass + '" data-severity="' + log.severity + '" data-index="' + index + '">' +
                '<td class="log-time">' + formatTimestamp(log.timestamp) + '</td>' +
                '<td class="log-severity">' + severityIcon + '</td>' +
                '<td class="log-type"><span class="type-badge type-' + log.type + '">' + typeLabel + '</span></td>' +
                '<td class="log-message">' + escapeHtml(truncateMessage(log.message, 80)) + '</td>' +
                '<td class="log-key">' + (log.api_key || '-') + '</td>' +
                '<td class="log-action">' +
                    '<button class="view-details-btn" data-index="' + index + '">Detay</button>' +
                '</td>' +
            '</tr>';
            
            tbody.append(row);
        });
        
        // Store logs data for detail view
        window.amoLogsData = logs;
        
        // Detail button click
        $('.view-details-btn').on('click', function() {
            var index = $(this).data('index');
            showLogDetail(window.amoLogsData[index]);
        });
    }
    
    function filterLogs(filter, allLogs) {
        if (filter === 'all') {
            populateLogsTable(allLogs);
        } else {
            var filtered = allLogs.filter(function(log) {
                return log.severity === filter;
            });
            populateLogsTable(filtered);
        }
    }
    
    function showLogDetail(log) {
        var solution = log.solution;
        var detailHtml = '<div class="amo-log-detail-modal">' +
            '<div class="amo-modal-overlay detail-overlay"></div>' +
            '<div class="amo-detail-content">' +
                '<div class="detail-header">' +
                    '<h3>🔍 Log Detayı</h3>' +
                    '<button class="detail-close">&times;</button>' +
                '</div>' +
                '<div class="detail-body">' +
                    '<div class="detail-section">' +
                        '<h4>📅 Zaman</h4>' +
                        '<p>' + log.timestamp + '</p>' +
                    '</div>' +
                    '<div class="detail-section">' +
                        '<h4>⚠️ Sorun</h4>' +
                        '<p class="problem-text">' + solution.problem + '</p>' +
                    '</div>' +
                    '<div class="detail-section">' +
                        '<h4>💬 Tam Mesaj</h4>' +
                        '<pre class="log-message-full">' + escapeHtml(log.message) + '</pre>' +
                    '</div>' +
                    (log.api_key ? '<div class="detail-section"><h4>🔑 API Key</h4><p>' + log.api_key + '</p></div>' : '') +
                    '<div class="detail-section solution-section">' +
                        '<h4>✅ Çözüm</h4>' +
                        '<p class="solution-text">' + solution.solution + '</p>' +
                    '</div>' +
                    '<div class="detail-section action-section">' +
                        '<h4>🎯 Yapılması Gereken</h4>' +
                        '<p class="action-text">' + solution.action + '</p>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('body').append(detailHtml);
        
        $('.detail-close, .detail-overlay').on('click', function() {
            $('.amo-log-detail-modal').fadeOut(function() {
                $(this).remove();
            });
        });
        
        $('.amo-log-detail-modal').fadeIn();
    }
    
    function getSeverityIcon(severity) {
        var icons = {
            'critical': '🔴',
            'high': '🟠',
            'medium': '🟡',
            'low': '🔵',
            'success': '🟢'
        };
        return icons[severity] || '⚪';
    }
    
    function getTypeLabel(type) {
        var labels = {
            'quota': 'Quota',
            'invalid_key': 'Geçersiz Key',
            'timeout': 'Timeout',
            'network': 'Network',
            'fatal': 'Fatal',
            'warning': 'Warning',
            'database': 'Database',
            'amo_error': 'AMO Error',
            'success': 'Success',
            'info': 'Info'
        };
        return labels[type] || type;
    }
    
    function formatTimestamp(timestamp) {
        // Convert "14-Oct-2025 12:00:00 UTC" to readable format
        try {
            var parts = timestamp.split(' ');
            return parts[0] + ' ' + parts[1];
        } catch(e) {
            return timestamp;
        }
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    function truncateMessage(message, length) {
        if (message.length <= length) return message;
        return message.substring(0, length) + '...';
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Initialize DataTable for articles page
    if ($('#amo-articles-table').length) {
        $('#amo-articles-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: amoAdmin.ajaxUrl,
                type: 'POST',
                data: function(d) {
                    d.action = 'amo_get_articles_data';
                    d.nonce = amoAdmin.nonce;
                }
            },
            columns: [
                { data: 'keyword' },
                { data: 'status', orderable: false },
                { data: 'post_id', orderable: false },
                { data: 'generation_time' },
                { data: 'created_at' },
                { data: 'published_at' },
                { data: 'error_message', orderable: false },
                { data: 'actions', orderable: false }
            ],
            order: [[4, 'desc']],
            pageLength: 25,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/Turkish.json'
            },
            drawCallback: function() {
                // Reinitialize event handlers after table redraw
                initializeTableActions();
            }
        });
    }

    // Auto-publish control buttons
    $('#amo-start-auto-publish').on('click', function() {
        var $button = $(this);
        var $status = $('#amo-auto-publish-status');
        var $progress = $('#amo-auto-publish-progress');
        
        $button.prop('disabled', true);
        $progress.show();
        
        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_start_auto_publish',
                nonce: amoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<p class="amo-status-active">✅ Otomatik yayınlama aktif</p><button id="amo-stop-auto-publish" class="button button-secondary">Durdur</button>');
                    showNotice('success', response.data.message);
                    
                    // Reinitialize stop button
                    initializeStopButton();
                } else {
                    showNotice('error', response.data.message || 'Bir hata oluştu');
                }
            },
            error: function() {
                showNotice('error', 'AJAX hatası oluştu');
            },
            complete: function() {
                $button.prop('disabled', false);
                $progress.hide();
            }
        });
    });

    $('#amo-stop-auto-publish').on('click', function() {
        var $button = $(this);
        var $status = $('#amo-auto-publish-status');
        var $progress = $('#amo-auto-publish-progress');
        
        $button.prop('disabled', true);
        $progress.show();
        
        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_stop_auto_publish',
                nonce: amoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<p class="amo-status-inactive">⏸️ Otomatik yayınlama durduruldu</p><button id="amo-start-auto-publish" class="button button-primary">Başlat</button>');
                    showNotice('success', response.data.message);
                    
                    // Reinitialize start button
                    initializeStartButton();
                } else {
                    showNotice('error', response.data.message || 'Bir hata oluştu');
                }
            },
            error: function() {
                showNotice('error', 'AJAX hatası oluştu');
            },
            complete: function() {
                $button.prop('disabled', false);
                $progress.hide();
            }
        });
    });

    // Manual article generation
    $('#amo-generate-now').on('click', function() {
        var $button = $(this);
        var $status = $('#amo-generation-status');
        var $message = $('#amo-generation-message');
        var $progressFill = $status.find('.amo-progress-fill');
        
        // Her zaman prompt ile konu sor (dashboard ile aynı)
        var topic = prompt('Lütfen makale konusu girin (örn: yapay zeka ile beslenme):');
        if (!topic || topic.trim() === '') {
            alert('Makale konusu boş olamaz!');
            return;
        }
        topic = topic.trim();

        // Butonu gizle, ilerleme göster
        $button.hide();
        $status.show();
        $message.html('⏳ Makale üretiliyor, lütfen bekleyin...');
        
        // Progress bar'ı sıfırla ve animasyonu başlat
        $progressFill.css('width', '0%');
        animateProgress($progressFill);

        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_generate_article',
                nonce: amoAdmin.generateArticleNonce || amoAdmin.nonce,
                topic: topic
            },
            timeout: 120000, // 2 dakika timeout
            success: function(response) {
                // Progress bar'ı %100'e getir
                $progressFill.css('width', '100%');
                
                if (response.success) {
                    $message.html('✅ Makale başarıyla üretildi ve kaydedildi!');
                    showNotice('success', 'Makale üretildi: ' + topic);

                    // Dashboard'daki result container'ı varsa göster
                    if ($('#result').length && response.data && response.data.htmlContent) {
                        $('#result').html(response.data.htmlContent).show();
                    }

                    // Dashboard istatistiklerini yenile
                    if ($('.amo-dashboard-stats').length) {
                        refreshDashboardStats();
                    }
                    
                    // Üretilen makaleler tablosunu yenile
                    if ($('.amo-articles-table').length) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    var errorMsg = (response.data && response.data.message) ? response.data.message : (response.data || 'Bilinmeyen hata');
                    $progressFill.css('width', '95%'); // Hata durumunda %95'te bırak
                    $message.html('<img draggable="false" role="img" class="emoji" alt="❌" src="https://s.w.org/images/core/emoji/16.0.1/svg/274c.svg"> Hata: ' + errorMsg);
                    showNotice('error', 'Makale üretilemedi: ' + errorMsg);
                }
            },
            error: function(xhr, status, err) {
                $progressFill.css('width', '95%'); // Hata durumunda %95'te bırak
                var errorMsg = 'AJAX hatası oluştu';
                if (status === 'timeout') {
                    errorMsg = 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                } else if (err) {
                    errorMsg = err;
                }
                $message.html('<img draggable="false" role="img" class="emoji" alt="❌" src="https://s.w.org/images/core/emoji/16.0.1/svg/274c.svg"> Hata: ' + errorMsg);
                showNotice('error', errorMsg);
            },
            complete: function() {
                // 5 saniye sonra butonu tekrar göster ve durumu gizle
                setTimeout(function() {
                    $status.fadeOut(400, function() {
                        $button.fadeIn(400);
                        $progressFill.css('width', '0%');
                    });
                }, 5000);
            }
        });
    });

    // Initialize dashboard auto-refresh
    if ($('.amo-dashboard-stats').length) {
        setInterval(refreshDashboardStats, 30000); // Refresh every 30 seconds
    }

    // Helper functions
    function initializeStartButton() {
        $('#amo-start-auto-publish').off('click').on('click', function() {
            // Same logic as above start button
            $(this).trigger('click');
        });
    }

    function initializeStopButton() {
        $('#amo-stop-auto-publish').off('click').on('click', function() {
            // Same logic as above stop button
            $(this).trigger('click');
        });
    }

    function initializeTableActions() {
        // Regenerate article button
        $('.amo-regenerate-btn').off('click').on('click', function() {
            var articleId = $(this).data('id');
            var $button = $(this);
            
            if (!confirm('Bu makaleyi yeniden üretmek istediğinizden emin misiniz?')) {
                return;
            }
            
            $button.prop('disabled', true).text('Üretiliyor...');
            
            $.ajax({
                url: amoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amo_regenerate_article',
                    article_id: articleId,
                    nonce: amoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        // Refresh table
                        $('#amo-articles-table').DataTable().ajax.reload();
                    } else {
                        showNotice('error', response.data.message);
                        $button.prop('disabled', false).text('Yeniden Üret');
                    }
                },
                error: function() {
                    showNotice('error', 'AJAX hatası oluştu');
                    $button.prop('disabled', false).text('Yeniden Üret');
                }
            });
        });
    }

    function animateProgress($progressFill) {
        var width = 0;
        var interval = setInterval(function() {
            width += Math.random() * 10;
            if (width >= 95) {
                width = 95;
                clearInterval(interval);
            }
            $progressFill.css('width', width + '%');
        }, 1000);
    }

    function refreshDashboardStats() {
        $.ajax({
            url: amoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amo_get_dashboard_stats',
                nonce: amoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    $('.amo-dashboard-stats .amo-stat-box').each(function() {
                        var $box = $(this);
                        var title = $box.find('h3').text();
                        
                        if (title === 'Toplam Makale') {
                            $box.find('.amo-stat-number').text(stats.total_articles);
                        } else if (title === 'Yayınlanan') {
                            $box.find('.amo-stat-number').text(stats.published_articles);
                        } else if (title === 'Başarısız') {
                            $box.find('.amo-stat-number').text(stats.failed_articles);
                        } else if (title === 'Toplam Kelime') {
                            $box.find('.amo-stat-number').text(stats.total_keywords);
                        } else if (title === 'Kullanılmayan Kelime') {
                            $box.find('.amo-stat-number').text(stats.unused_keywords);
                        }
                    });
                }
            }
        });
    }

    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Progress bar animation CSS
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .amo-progress-bar {
                width: 100%;
                height: 20px;
                background-color: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
                margin: 20px 0;
            }
            .amo-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #007cba, #00a0d2);
                border-radius: 10px;
                width: 0%;
                transition: width 0.3s ease;
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            .amo-dashboard-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .amo-stat-box {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                border-left: 4px solid #007cba;
            }
            .amo-stat-box h3 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .amo-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #007cba;
                display: block;
            }
            .amo-status-published { color: #46b450; font-weight: bold; }
            .amo-status-failed { color: #dc3232; font-weight: bold; }
            .amo-status-generating { color: #ffb900; font-weight: bold; }
            .amo-status-pending { color: #72777c; font-weight: bold; }
            .amo-status-used { color: #46b450; font-weight: bold; }
            .amo-status-unused { color: #72777c; font-weight: bold; }
            .amo-status-active { color: #46b450; font-weight: bold; }
            .amo-status-inactive { color: #dc3232; font-weight: bold; }
            .amo-error-message {
                color: #dc3232;
                cursor: help;
                text-decoration: underline;
            }
            .amo-add-keyword-forms {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin: 30px 0;
            }
            .amo-single-keyword-form,
            .amo-bulk-keywords-form {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        `)
        .appendTo('head');
});
