(function($){
	$(document).ready(function(){
		$('#tp_update_product_info').click(function(e){
			e.preventDefault();
			
			data = {
				action: 'tp_updateproduct',
				_ajax_nonce: $('#tp_ajax_nonce').val(),
				post_id: $('#tp_post_id').val()
			};
			
			$.post(ajaxurl, data, function(r, s, xhr){
				if(r == 'ok') {
					$('#tp-update-error').remove();
					location.reload();
				} else {
//					$('#tp-update-error').append(r);
				}
			}, "text");
		});
		
		$(window).resize( function() { tp_insert_tb_setsize() } );
	});
})(jQuery.noConflict());