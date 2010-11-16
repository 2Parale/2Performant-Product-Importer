(function($){
	$(document).ready(function(){
		tp_insert_tb_setsize = function() {
			var tbWindow = $('#TB_window'),
				w=980,
				h=800;
			
			if(tbWindow.size()) {
				tbWindow.width(w).height(h);
				$('#TB_iframeContent').width(w).height(h - 27);
			}
		}
	});
})(jQuery.noConflict());