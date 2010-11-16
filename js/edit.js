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
		
		tp_insert_tb_setsize = function() {
			var tbWindow = $('#TB_window'),
				w=940,
				h=$(window).height()-60;
			
			console.log(tbWindow.width(), tbWindow.height());
			if(tbWindow.size()) {
				tbWindow.width(w).height(h);
				$('#TB_iframeContent').width(w).height(h - 27);
				tbWindow.css({'margin-left': '-' + parseInt((w / 2),10) + 'px'});
				if ( typeof document.body.style.maxWidth != 'undefined' )
					tbWindow.css({'top':'30px','margin-top':'0'});
			}
		};
		$(window).resize( function() { tp_insert_tb_setsize() } );
	});
})(jQuery.noConflict());