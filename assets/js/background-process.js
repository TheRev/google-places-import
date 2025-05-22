// Background processing status checker
jQuery(document).ready(function($) {
    let processInterval;
    
    function startProcessingCheck() {
        updateProcessStatus();
        processInterval = setInterval(updateProcessStatus, 5000);
    }
    
    function stopProcessingCheck() {
        clearInterval(processInterval);
    }
    
    function updateProcessStatus() {
        $.ajax({
            url: gpdProcess.ajaxurl,
            type: 'POST',
            data: {
                action: 'gpd_get_process_status',
                nonce: gpdProcess.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgressUI(response.data);
                    
                    if (response.data.remaining === 0) {
                        stopProcessingCheck();
                    }
                }
            }
        });
    }
    
    function updateProgressUI(data) {
        $('.gpd-progress-complete').css('width', data.percent + '%');
        $('.gpd-processed').text(data.processed);
        $('.gpd-total').text(data.total);
        
        if (data.remaining === 0) {
            $('.gpd-batch-results').html(
                '<p class="gpd-success">' +
                'All tasks completed successfully! ' +
                data.processed + ' items processed.' +
                '</p>'
            );
        }
    }
    
    // Start checking if we're on the photo management page and have active processes
    if ($('.gpd-batch-processing').length && $('.gpd-progress-bar').length) {
        startProcessingCheck();
    }
});
