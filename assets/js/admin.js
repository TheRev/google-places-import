jQuery(function($) {
    'use strict';

    // Handle inline imports
    function handleInline(btn) {
        var $btn = $(btn);
        var data = {
            action: 'gpd_inline_import',
            index: $btn.data('index'),
            query: $btn.data('query'),
            radius: $btn.data('radius'),
            limit: $btn.data('limit'),
            pagetoken: $btn.data('pagetoken'),
            nonce: GPD.import_nonce
        };
        
        $btn.prop('disabled', true).text('…');
        
        $.post(GPD.ajax_url, data, function(res) {
            if (res.success) {
                $btn.replaceWith('<span class="dashicons dashicons-yes-alt" title="Imported"></span>');
            } else {
                $btn.prop('disabled', false).text('Retry');
                alert(res.data.message || 'Error during import');
            }
        });
    }

    // Form submission handling
    $('form[name="gpd_import_form"]').on('submit', function(e) {
        var $selected = $(this).find('input.gpd-select-item:checked');
        
        if ($selected.length === 0) {
            e.preventDefault();
            alert(GPD.i18n.no_selection);
            return;
        }
    });

    // Delegate clicks on Import or Refresh buttons
    $('body').on('click', '.gpd-import-btn, .gpd-refresh-btn', function(e) {
        e.preventDefault();
        handleInline(this);
    });

    // Select all functionality
    $('#gpd-select-all').on('change', function() {
        $('.gpd-select-item').not(':disabled').prop('checked', this.checked);
    });
});
