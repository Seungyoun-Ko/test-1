/**
 * GreenPulse WordPress Plugin - Frontend JavaScript
 * Handles AJAX interactions for news loading, searching, and feed refresh
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // State variables
        let currentCountry = 'global';
        let currentQuery = '';

        // DOM elements
        const $grid = $('#greenpulse-news-grid');
        const $statusText = $('#greenpulse-status-text');
        const $searchInput = $('#greenpulse-search-input');
        const $refreshBtn = $('#greenpulse-refresh-btn');
        const $loading = $('#greenpulse-loading');
        const $empty = $('#greenpulse-empty');

        /**
         * Show loading skeleton with 6 placeholder cards
         */
        function showSkeleton() {
            let skeletonHTML = '';
            for (let i = 0; i < 6; i++) {
                skeletonHTML += '<div class="greenpulse-skeleton"></div>';
            }
            $grid.html(skeletonHTML);
            $loading.show();
            $empty.hide();
        }

        /**
         * Load news from server via AJAX
         */
        function loadNews() {
            showSkeleton();

            $.ajax({
                url: greenpulse_ajax.ajax_url,
                type: 'GET',
                data: {
                    action: 'greenpulse_load_news',
                    country: currentCountry,
                    q: currentQuery,
                    nonce: greenpulse_ajax.nonce
                },
                success: function(response) {
                    $loading.hide();

                    if (response.success && response.data) {
                        // Update grid with news HTML
                        $grid.html(response.data.html);

                        // Update status text
                        const count = response.data.count || 0;
                        const countryName = response.data.country_name || '전체';
                        $statusText.text(`${countryName} - ${count}개의 뉴스`);

                        // Show/hide empty state
                        if (count === 0) {
                            $empty.show();
                        } else {
                            $empty.hide();
                        }
                    } else {
                        $grid.html('<p class="greenpulse-error">뉴스를 불러올 수 없습니다.</p>');
                        $statusText.text('오류 발생');
                    }
                },
                error: function(xhr, status, error) {
                    $loading.hide();
                    $grid.html('<p class="greenpulse-error">서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</p>');
                    $statusText.text('오류: ' + error);
                    console.error('GreenPulse AJAX Error:', error);
                }
            });
        }

        /**
         * Refresh feeds from external sources
         */
        function refreshFeeds() {
            $refreshBtn.addClass('spinning');

            $.ajax({
                url: greenpulse_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'greenpulse_refresh',
                    nonce: greenpulse_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload news after successful refresh
                        loadNews();
                    } else {
                        alert('피드 갱신에 실패했습니다.');
                    }
                },
                error: function() {
                    alert('서버 오류가 발생했습니다.');
                },
                complete: function() {
                    $refreshBtn.removeClass('spinning');
                }
            });
        }

        // Event: Country tab click
        $(document).on('click', '.greenpulse-tab', function(e) {
            e.preventDefault();

            const $tab = $(this);
            currentCountry = $tab.data('country');

            // Update active state
            $('.greenpulse-tab').removeClass('active');
            $tab.addClass('active');

            // Load news for selected country
            loadNews();
        });

        // Event: Search button click
        $(document).on('click', '.greenpulse-search-btn', function(e) {
            e.preventDefault();
            currentQuery = $searchInput.val().trim();
            loadNews();
        });

        // Event: Enter key on search input
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                currentQuery = $(this).val().trim();
                loadNews();
            }
        });

        // Event: Refresh button click
        $refreshBtn.on('click', function(e) {
            e.preventDefault();
            refreshFeeds();
        });

        // Initial load
        loadNews();
    });

})(jQuery);
