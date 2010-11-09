(function($){
	$(document).ready(function(){
		$('.tp-update-product-info').click(function(e){
			e.preventDefault();
			var _this = this, id = $(this).prev('input.tp-update-product-id').val();
			
			if(!id)
				return false;
			
			data = {
				action: 'tp_updateproduct',
				_ajax_nonce: $('#tp_ajax_nonce_'+id).val(),
				post_id: id
			};
			
			$.post(ajaxurl, data, function(r, s, xhr){
				if(r == 'ok') {
					$(_this).parent().html($('<em>Yes</em>'));
				} else {
					
				}
			}, "text");
		});
	});
})(jQuery.noConflict());