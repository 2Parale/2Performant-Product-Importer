(function($){
	$(document).ready(function(){
		$('.tp_product_field').change(function(){
			var fieldName = $(this).attr('rel');
			var fieldValue = $(this).val();
			var defaultProductValue = $('#tp_hidden_product_'+fieldName).val();
			if( fieldValue != defaultProductValue ){
				$('#note_'+fieldName).show();
				$('#tp_modified_product_'+fieldName).val( fieldValue );
			}else{
				$('#note_'+fieldName).hide();
			}
			$('#undo_'+fieldName).hide();
		});
		$('.tp_undo_revert').click(function(){
			var fieldName = $(this).attr('rel');
			$('#tp_product_'+fieldName).val($('#tp_modified_product_'+fieldName).val());
			$('#note_'+fieldName).show();
			$('#undo_'+fieldName).hide();
			return false;
		});
		$('.tp_product_revert').click(function(){
			var fieldName = $(this).attr('rel');
			$('#tp_product_'+fieldName).val($('#tp_hidden_product_'+fieldName).val());
			$('#note_'+fieldName).hide();
			$('#undo_'+fieldName).show();
			return false;
		});
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