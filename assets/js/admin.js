jQuery(document).ready(function($) {
    // ===== TAB SWITCHING FUNCTIONALITY =====
    $('.content-tab').on('click', function(e) {
        e.preventDefault();
        
        const targetTab = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.content-tab').removeClass('active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#tab-' + targetTab).addClass('active');
        
        // Store active tab in localStorage
        localStorage.setItem('wp-system-info-active-tab', targetTab);
        
        // Trigger animations for newly visible content
        $('#tab-' + targetTab + ' .modern-table').addClass('loaded');
    });
    
    // Restore active tab from localStorage
    const activeTab = localStorage.getItem('wp-system-info-active-tab');
    if (activeTab && $('.content-tab[data-tab="' + activeTab + '"]').length) {
        $('.content-tab[data-tab="' + activeTab + '"]').trigger('click');
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
            $container.append('<div class="no-results"><p>No items match the current filter.</p></div>');
        }
    }
    
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
        if (e.altKey && e.keyCode >= 49 && e.keyCode <= 53) { // Alt + 1-5
            const tabIndex = e.keyCode - 49;
            const $tabs = $('.content-tab');
            if ($tabs.eq(tabIndex).length) {
                $tabs.eq(tabIndex).trigger('click');
                e.preventDefault();
            }
        }
        
        // Search focus with Ctrl/Cmd + F
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
            $('#system-info-search').focus();
            e.preventDefault();
        }
        
        // Refresh with F5 or Ctrl/Cmd + R
        if (e.keyCode === 116 || ((e.ctrlKey || e.metaKey) && e.keyCode === 82)) {
            $('.refresh-btn').trigger('click');
            e.preventDefault();
        }
    });
    
    // ===== ACCESSIBILITY IMPROVEMENTS =====
    // Add ARIA labels and roles
    $('.content-tab').attr('role', 'tab');
    $('.tab-content').attr('role', 'tabpanel');
    $('.filter-tab').attr('role', 'button');
    
    // Announce tab changes to screen readers
    $('.content-tab').on('click', function() {
        const tabName = $(this).find('.tab-label').text();
        $('<div class="sr-only" aria-live="polite">Switched to ' + tabName + ' tab</div>')
            .appendTo('body')
            .delay(1000)
            .remove();
    });
    
    // ===== PERFORMANCE OPTIMIZATIONS =====
    // Lazy load content for inactive tabs
    let tabsLoaded = { overview: true };
    
    $('.content-tab').on('click', function() {
        const tabId = $(this).data('tab');
        if (!tabsLoaded[tabId]) {
            // Add loading indicator
            const $tabContent = $('#tab-' + tabId);
            $tabContent.append('<div class="loading-indicator">Loading...</div>');
            
            // Simulate content loading (in real implementation, this might load via AJAX)
            setTimeout(() => {
                $tabContent.find('.loading-indicator').remove();
                tabsLoaded[tabId] = true;
            }, 300);
        }
    });
    
    // ===== INITIALIZATION =====
    // Set initial state
    showNotification('System information loaded successfully', 'success');
    
    // Add version info to console for debugging
    console.log('HostCheckr - Know Your Hosting. Instantly.');
    console.log('Developed by Bajpan Gosh for KloudBoy');
    console.log('Plugin URI: https://hostcheckr.kloudboy.com');
    console.log('Version: 1.0.0');
    console.log('Loaded at:', new Date().toISOString());
});