jQuery(document).ready(function($) {
    const $tabs = $('.content-tab');
    const $panels = $('.tab-content');
    const tabStorageKey = 'wp-system-info-active-tab';

    function setUrlTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }

    function updateTabAccessibility(activeTab) {
        $tabs.each(function(index) {
            const tabId = $(this).data('tab');
            const isActive = tabId === activeTab;
            const buttonId = 'hostcheckr-tab-' + tabId;
            const panelId = 'tab-' + tabId;

            $(this).attr({
                id: buttonId,
                role: 'tab',
                'aria-controls': panelId,
                'aria-selected': isActive ? 'true' : 'false',
                tabindex: isActive ? '0' : '-1',
                'data-tab-index': index
            });
        });

        $panels.each(function() {
            const panelId = $(this).attr('id');
            const tabId = panelId.replace('tab-', '');
            const isActive = tabId === activeTab;
            $(this).attr({
                role: 'tabpanel',
                'aria-labelledby': 'hostcheckr-tab-' + tabId,
                'aria-hidden': isActive ? 'false' : 'true'
            });
        });
    }

    function setActiveTab(targetTab, syncUrl = true) {
        const $targetTab = $('.content-tab[data-tab="' + targetTab + '"]');
        const $targetPanel = $('#tab-' + targetTab);
        if (!$targetTab.length || !$targetPanel.length) {
            return;
        }

        $tabs.removeClass('active');
        $panels.removeClass('active');
        $targetTab.addClass('active');
        $targetPanel.addClass('active');

        try {
            localStorage.setItem(tabStorageKey, targetTab);
        } catch (e) {
            // Ignore localStorage failures.
        }

        if (syncUrl) {
            setUrlTab(targetTab);
        }

        updateTabAccessibility(targetTab);
        $targetPanel.find('.modern-table').addClass('loaded');
    }

    // ===== TAB SWITCHING FUNCTIONALITY =====
    $('.content-tab').on('click', function(e) {
        e.preventDefault();
        setActiveTab($(this).data('tab'));
    });

    // Restore active tab from URL first, then localStorage.
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    let storedTab = null;
    try {
        storedTab = localStorage.getItem(tabStorageKey);
    } catch (e) {
        storedTab = null;
    }
    const initialTab = (urlTab && $('.content-tab[data-tab="' + urlTab + '"]').length)
        ? urlTab
        : ((storedTab && $('.content-tab[data-tab="' + storedTab + '"]').length) ? storedTab : $('.content-tab.active').data('tab'));
    if (initialTab) {
        setActiveTab(initialTab, !urlTab);
    }
    
    // ===== HEALTH DETAILS TOGGLE =====
    $('.toggle-details-btn').on('click', function() {
        const target = $(this).data('target');
        const detailsSection = $('#' + target);
        const button = $(this);
        
        if (detailsSection.is(':visible')) {
            detailsSection.slideUp(300);
            button.removeClass('expanded');
        } else {
            detailsSection.slideDown(300);
            button.addClass('expanded');
        }
    });
    
    // ===== FILTER FUNCTIONALITY =====
    $('.filter-tab').on('click', function() {
        const filter = $(this).data('filter');
        
        // Update active filter tab
        $('.filter-tab').removeClass('active');
        $(this).addClass('active');
        
        // Apply filter to all visible content
        applyFilter(filter);
    });
    
    function applyFilter(filter) {
        const $activeTab = $('.tab-content.active');
        
        if (filter === 'all') {
            $activeTab.find('.modern-table tbody tr, .extension-card, .info-item, .requirement-item, .extension-item').show();
        } else if (filter === 'issues') {
            $activeTab.find('.modern-table tbody tr, .extension-card, .requirement-item, .extension-item').each(function() {
                const hasIssue = $(this).hasClass('error') || $(this).hasClass('warning');
                $(this).toggle(hasIssue);
            });
            $activeTab.find('.info-item').hide(); // WordPress info doesn't have issues
        } else if (filter === 'success') {
            $activeTab.find('.modern-table tbody tr, .extension-card, .requirement-item, .extension-item').each(function() {
                const isSuccess = $(this).hasClass('success') || $(this).find('.status-cell.success').length > 0;
                $(this).toggle(isSuccess);
            });
            $activeTab.find('.info-item').show(); // WordPress info is always "success"
        }
        
        // Update empty state
        updateEmptyState($activeTab);
    }
    
    function updateEmptyState($container) {
        const visibleItems = $container.find('.modern-table tbody tr:visible, .extension-card:visible, .info-item:visible, .requirement-item:visible, .extension-item:visible');
        
        $container.find('.no-results').remove();
        
        if (visibleItems.length === 0) {
            $container.append(
                '<div class="no-results">' +
                    '<p>No items match your current filter/search.</p>' +
                    '<button type="button" class="button button-secondary reset-results-btn">Clear filters</button>' +
                '</div>'
            );
        }
    }

    $(document).on('click', '.reset-results-btn', function() {
        $('#system-info-search').val('');
        $('.filter-tab').removeClass('active');
        $('.filter-tab[data-filter="all"]').addClass('active');
        applyFilter('all');
    });
    
    // ===== SEARCH FUNCTIONALITY =====
    let searchTimeout;
    $('#system-info-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });
    
    function performSearch(searchTerm) {
        const $activeTab = $('.tab-content.active');
        
        if (searchTerm === '') {
            // Show all items and reapply current filter
            const activeFilter = $('.filter-tab.active').data('filter');
            applyFilter(activeFilter);
            return;
        }
        
        // Search in different content types
        $activeTab.find('.modern-table tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
        
        $activeTab.find('.extension-card, .info-item, .requirement-item, .extension-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
        
        updateEmptyState($activeTab);
    }
    
    // ===== COPY FUNCTIONALITY =====
    $('.info-value').on('click', function() {
        const text = $(this).text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('Copied to clipboard!', 'success');
            }).catch(function() {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    });

    $(document).on('click', '.copy-config-btn', function() {
        const text = $(this).data('copy');
        if (!text) {
            showNotification('Nothing to copy', 'error');
            return;
        }

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('wp-config fix copied!', 'success');
            }).catch(function() {
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    });
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('Copied to clipboard!', 'success');
        } catch (err) {
            showNotification('Failed to copy to clipboard', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.wp-system-info-notification').remove();
        
        const notification = $('<div class="wp-system-info-notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        // Show notification
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // ===== REFRESH FUNCTIONALITY =====
    $('.refresh-btn').on('click', function() {
        const $btn = $(this);
        $btn.addClass('refreshing');
        $btn.find('.dashicons').addClass('spin');
        
        // Add spinning animation
        if (!$('.refresh-spin-style').length) {
            $('<style class="refresh-spin-style">.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>').appendTo('head');
        }
        
        showNotification('Refreshing system information...', 'info');
        
        // Reload page after short delay
        setTimeout(() => {
            location.reload();
        }, 500);
    });
    
    // ===== EXPORT FUNCTIONALITY =====
    $('.export-btn').on('click', function() {
        showNotification('Generating system report...', 'info');
        setTimeout(() => {
            exportSystemInfo();
        }, 500);
    });
    
    function exportSystemInfo() {
        let exportData = 'HostCheckr - System Information Report\n';
        exportData += 'Know Your Hosting. Instantly.\n';
        exportData += '='.repeat(50) + '\n\n';
        exportData += 'Generated: ' + new Date().toLocaleString() + '\n';
        exportData += 'Site: ' + window.location.hostname + '\n';
        exportData += 'Plugin: HostCheckr v1.0.0\n';
        exportData += 'Developer: Bajpan Gosh\n';
        exportData += 'Company: KloudBoy\n';
        exportData += 'Plugin URI: https://hostcheckr.kloudboy.com\n\n';
        
        // Export system health summary
        const healthStatus = $('.health-status').text();
        const healthMessage = $('.health-summary p').first().text();
        exportData += 'SYSTEM HEALTH OVERVIEW\n';
        exportData += '-'.repeat(25) + '\n';
        exportData += 'Status: ' + healthStatus + '\n';
        exportData += 'Summary: ' + healthMessage + '\n\n';
        
        // Export quick stats
        exportData += 'QUICK STATISTICS\n';
        exportData += '-'.repeat(20) + '\n';
        $('.stat-item').each(function() {
            const label = $(this).find('.stat-label').text();
            const value = $(this).find('.stat-value').text();
            exportData += label + ': ' + value + '\n';
        });
        exportData += '\n';
        
        // Export detailed information from each tab
        $('.tab-content').each(function() {
            const tabId = $(this).attr('id');
            const tabName = $('.content-tab[data-tab="' + tabId.replace('tab-', '') + '"] .tab-label').text();
            
            if (tabName && tabName !== 'Overview') {
                exportData += tabName.toUpperCase() + '\n';
                exportData += '-'.repeat(tabName.length) + '\n';
                
                // Export table data
                $(this).find('table tbody tr').each(function() {
                    const cells = $(this).find('td');
                    if (cells.length > 1) {
                        const setting = cells.eq(0).text().trim().replace(/\s+/g, ' ');
                        const current = cells.last().text().trim();
                        exportData += setting + ': ' + current + '\n';
                    }
                });
                
                // Export info items (WordPress info)
                $(this).find('.info-item').each(function() {
                    const label = $(this).find('.info-label').text();
                    const value = $(this).find('.info-value').text();
                    exportData += label + ': ' + value + '\n';
                });
                
                exportData += '\n';
            }
        });
        
        // Export critical issues if any
        const criticalIssues = $('.issue-card.critical');
        if (criticalIssues.length > 0) {
            exportData += 'CRITICAL ISSUES\n';
            exportData += '-'.repeat(15) + '\n';
            criticalIssues.each(function() {
                const title = $(this).find('.issue-title h4').text();
                const current = $(this).find('.comparison-item.current .value').text();
                const required = $(this).find('.comparison-item.required .value').text();
                const recommendation = $(this).find('.recommendation-text').text();
                
                exportData += 'Issue: ' + title + '\n';
                exportData += 'Current: ' + current + '\n';
                exportData += 'Required: ' + required + '\n';
                exportData += 'Recommendation: ' + recommendation + '\n\n';
            });
        }
        
        // Create and download file
        const blob = new Blob([exportData], { type: 'text/plain;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'hostcheckr-report-' + new Date().toISOString().split('T')[0] + '.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('System report exported successfully!', 'success');
    }

    // ===== LIVE DATABASE MONITOR =====
    let liveDbInterval = null;
    let liveDbLoading = false;
    let liveDbSettings = Object.assign({ enabled: true, interval: 10, max_patterns: 5, lightweight_mode: false }, hostcheckr_ajax.live_db_settings || {});

    function hydrateLiveDbSettingsForm() {
        $('#live-db-enabled').prop('checked', !!liveDbSettings.enabled);
        $('#live-db-interval').val(liveDbSettings.interval || 10);
        $('#live-db-max-patterns').val(liveDbSettings.max_patterns || 5);
        $('#live-db-lightweight-mode').prop('checked', !!liveDbSettings.lightweight_mode);
    }

    function renderLiveDbMetrics(metrics) {
        const fields = ['response_ms', 'autoload_mb', 'db_size_mb', 'revisions', 'threads_connected', 'slow_queries'];
        fields.forEach((field) => {
            const value = metrics && metrics[field] ? metrics[field] : 'Not available';
            $('[data-db-metric="' + field + '"]').text(value);
        });

        const sampledAt = metrics && metrics.sampled_at ? metrics.sampled_at : '';
        if (sampledAt) {
            $('#live-db-last-updated').text('Updated: ' + sampledAt);
        }

        const warnings = (metrics && Array.isArray(metrics.warnings)) ? metrics.warnings : [];
        if (warnings.length) {
            const warningHtml = '<ul>' + warnings.map((item) => '<li>' + item + '</li>').join('') + '</ul>';
            $('#live-db-warnings').html(warningHtml).show();
        } else {
            $('#live-db-warnings').hide().empty();
        }

        const patterns = (metrics && Array.isArray(metrics.slow_query_patterns)) ? metrics.slow_query_patterns : [];
        if (patterns.length) {
            const rows = patterns.map((item) => {
                const sig = item.signature || '';
                const count = item.count || 0;
                const avg = item.avg_time || 0;
                const max = item.max_time || 0;
                return (
                    '<div class="live-db-pattern-row">' +
                        '<code class="live-db-pattern-signature">' + sig + '</code>' +
                        '<div class="live-db-pattern-metrics">' +
                            '<span>Count: ' + count + '</span>' +
                            '<span>Avg: ' + avg + 's</span>' +
                            '<span>Max: ' + max + 's</span>' +
                        '</div>' +
                    '</div>'
                );
            }).join('');

            $('#live-db-patterns').html('<h4>Top Slow Query Patterns</h4>' + rows).show();
        } else {
            $('#live-db-patterns').hide().empty();
        }
    }

    function fetchLiveDbMetrics() {
        if (!liveDbSettings.enabled) {
            return;
        }

        if (liveDbLoading || !$('#live-db-monitor').length) {
            return;
        }
        liveDbLoading = true;

        $.post(hostcheckr_ajax.ajax_url, {
            action: 'hostcheckr_live_db_metrics',
            nonce: hostcheckr_ajax.nonce
        }).done(function(response) {
            if (response && response.success && response.data) {
                renderLiveDbMetrics(response.data);
            } else {
                showNotification(hostcheckr_ajax.strings.live_db_unavailable || 'Live database monitoring is temporarily unavailable.', 'error');
            }
        }).fail(function() {
            showNotification(hostcheckr_ajax.strings.live_db_unavailable || 'Live database monitoring is temporarily unavailable.', 'error');
        }).always(function() {
            liveDbLoading = false;
        });
    }

    function manageLiveDbMonitor() {
        const isPerformanceTab = $('#tab-performance').hasClass('active');
        if (!isPerformanceTab || !liveDbSettings.enabled) {
            if (liveDbInterval) {
                clearInterval(liveDbInterval);
                liveDbInterval = null;
            }
            if (!liveDbSettings.enabled) {
                $('#live-db-last-updated').text('Live monitor disabled');
            }
            return;
        }

        fetchLiveDbMetrics();
        if (!liveDbInterval) {
            const interval = (parseInt(liveDbSettings.interval, 10) || 10) * 1000;
            liveDbInterval = setInterval(fetchLiveDbMetrics, interval);
        }
    }

    $('#live-db-refresh-btn').on('click', function() {
        fetchLiveDbMetrics();
    });

    $('#live-db-save-settings').on('click', function() {
        const enabled = $('#live-db-enabled').is(':checked') ? '1' : '0';
        const interval = parseInt($('#live-db-interval').val(), 10) || 10;
        const maxPatterns = parseInt($('#live-db-max-patterns').val(), 10) || 5;
        const lightweightMode = $('#live-db-lightweight-mode').is(':checked') ? '1' : '0';

        $.post(hostcheckr_ajax.ajax_url, {
            action: 'hostcheckr_save_live_db_settings',
            nonce: hostcheckr_ajax.nonce,
            enabled: enabled,
            interval: interval,
            max_patterns: maxPatterns,
            lightweight_mode: lightweightMode
        }).done(function(response) {
            if (response && response.success && response.data && response.data.settings) {
                liveDbSettings = response.data.settings;
                hydrateLiveDbSettingsForm();
                if (liveDbInterval) {
                    clearInterval(liveDbInterval);
                    liveDbInterval = null;
                }
                manageLiveDbMonitor();
                showNotification(hostcheckr_ajax.strings.live_db_saved || 'Live monitor settings saved.', 'success');
            } else {
                showNotification(hostcheckr_ajax.strings.error || 'Error occurred', 'error');
            }
        }).fail(function() {
            showNotification(hostcheckr_ajax.strings.error || 'Error occurred', 'error');
        });
    });
    
    // ===== SMOOTH ANIMATIONS =====
    // Add loading animation for tables
    $('.modern-table').each(function() {
        $(this).addClass('loaded');
    });
    
    // Add hover effects for interactive elements
    $('.stat-item, .health-card, .issue-card, .extension-card, .info-item').on('mouseenter', function() {
        $(this).addClass('hovered');
    }).on('mouseleave', function() {
        $(this).removeClass('hovered');
    });
    
    // ===== KEYBOARD NAVIGATION =====
    $(document).on('keydown', function(e) {
        // Tab navigation with keyboard
        if (e.altKey && e.key >= '1' && e.key <= '9') { // Alt + 1-9
            const tabIndex = parseInt(e.key, 10) - 1;
            const $tabs = $('.content-tab');
            if ($tabs.eq(tabIndex).length) {
                $tabs.eq(tabIndex).trigger('click');
                e.preventDefault();
            }
        }
        
        // Quick focus search with "/" unless typing in a field.
        const isTypingField = /input|textarea|select/i.test((e.target.tagName || '')) || $(e.target).is('[contenteditable="true"]');
        if (!isTypingField && e.key === '/') {
            $('#system-info-search').focus();
            e.preventDefault();
        }
    });
    
    // ===== ACCESSIBILITY IMPROVEMENTS =====
    // Add ARIA labels and roles
    $('.content-tabs-nav').attr({
        role: 'tablist',
        'aria-label': 'HostCheckr sections'
    });
    $('.filter-tab').attr('role', 'button');

    const $liveRegion = $('<div id="hostcheckr-live-region" class="sr-only" aria-live="polite"></div>');
    $('body').append($liveRegion);

    $('.content-tab').on('click', function() {
        const tabName = $(this).find('.tab-label').text();
        $liveRegion.text('Switched to ' + tabName + ' tab');
        setTimeout(manageLiveDbMonitor, 0);
    });

    // Arrow-key navigation between tabs.
    $('.content-tab').on('keydown', function(e) {
        const currentIndex = parseInt($(this).attr('data-tab-index'), 10) || 0;
        let nextIndex = null;

        if (e.key === 'ArrowRight') {
            nextIndex = (currentIndex + 1) % $tabs.length;
        } else if (e.key === 'ArrowLeft') {
            nextIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
        } else if (e.key === 'Home') {
            nextIndex = 0;
        } else if (e.key === 'End') {
            nextIndex = $tabs.length - 1;
        } else if (e.key === ' ' || e.key === 'Enter') {
            setActiveTab($(this).data('tab'));
            e.preventDefault();
            return;
        }

        if (nextIndex !== null) {
            $tabs.eq(nextIndex).focus();
            e.preventDefault();
        }
    });
    
    // ===== INITIALIZATION =====
    const checkedAt = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (!$('.last-checked-pill').length) {
        $('.header-actions').prepend('<span class="last-checked-pill">Last checked ' + checkedAt + '</span>');
    }

    hydrateLiveDbSettingsForm();
    manageLiveDbMonitor();
});
