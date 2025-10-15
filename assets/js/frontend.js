(function($) {
    'use strict';

    $(document).ready(function() {
        const inputContainer = $('#amo-input-container');
        const loadingContainer = $('#amo-loading-container');
        const resultContainer = $('#amo-result-container');
        const topicInput = $('#amo-topic-input');
        const generateBtn = $('#amo-generate-btn');

        generateBtn.on('click', generateArticle);
        topicInput.on('keydown', function(event) {
            if (event.key === 'Enter') {
                generateArticle();
            }
        });

        function generateArticle() {
            const topic = topicInput.val().trim();

            if (!topic) {
                alert('Lütfen bir konu girin.');
                return;
            }

            inputContainer.addClass('amo-hidden');
            loadingContainer.removeClass('amo-hidden');
            resultContainer.html('');

            $.ajax({
                url: amoData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amo_generate_article',
                    nonce: amoData.nonce,
                    topic: topic
                },
                success: function(response) {
                    loadingContainer.addClass('amo-hidden');

                    if (response.success && response.data) {
                        resultContainer.html(response.data.htmlContent);
                        initializeArticleScripts(response.data.chartData);
                    } else {
                        const errorMessage = response.data && response.data.message
                            ? response.data.message
                            : 'Bir hata oluştu. Lütfen tekrar deneyin.';
                        alert(errorMessage);
                        inputContainer.removeClass('amo-hidden');
                    }
                },
                error: function(xhr, status, error) {
                    loadingContainer.addClass('amo-hidden');
                    alert('Bir hata oluştu: ' + error);
                    inputContainer.removeClass('amo-hidden');
                }
            });
        }

        function initializeArticleScripts(chartData) {
            $('.faq .question').off('click').on('click', function() {
                const answer = $(this).next('.answer');
                const icon = $(this).find('i');
                const isOpen = answer.is(':visible');

                $('.faq .answer').slideUp();
                $('.faq .question i').removeClass('fa-chevron-up').addClass('fa-chevron-down');

                if (!isOpen) {
                    answer.slideDown();
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                }
            });

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
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            $('.share-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const platform = $(this).data('platform');
                const url = encodeURIComponent(window.location.href);
                const text = encodeURIComponent(document.title);
                let shareUrl = '';

                if (platform === 'twitter') {
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
                } else if (platform === 'facebook') {
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                } else if (platform === 'linkedin') {
                    shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${text}`;
                }

                if (shareUrl) {
                    window.open(shareUrl, '_blank', 'width=600,height=400');
                }
            });
        }
    });

})(jQuery);
