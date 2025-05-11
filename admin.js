jQuery(function($){
  function handleInline(btn){
    var $btn = $(btn);
    var data = {
      action:    'gpd_inline_import',
      index:     $btn.data('index'),
      query:     $btn.data('query'),
      radius:    $btn.data('radius'),
      limit:     $btn.data('limit'),
      pagetoken: $btn.data('pagetoken'),
      nonce:     GPD.import_nonce
    };
    $btn.prop('disabled', true).text('…');
    $.post(GPD.ajax_url, data, function(res){
      if(res.success){
        $btn.replaceWith('<span class="dashicons dashicons-yes-alt" title="Imported"></span>');
      } else {
        $btn.prop('disabled', false).text('Retry');
        alert(res.data || 'Error during import');
      }
    });
  }

  // Delegate clicks on Import or Refresh
  $('body').on('click', '.gpd-import-btn, .gpd-refresh-btn', function(e){
    e.preventDefault();
    handleInline(this);
  });
});
