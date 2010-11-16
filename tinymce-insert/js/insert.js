(function($){
	$.extend({
		tpChosenProductId: 0,
		tpChosenProductFeedId: 0,
	});
	$.fn.extend({
		tpProductClick: function(){
			return $(this).each(function(){
				$('#tp_product_' + $(this).tpplProductId() + '_button')
					.unbind('click')
					.click(function(){
						$.tp_insertProduct.insert($(this).tpplProductWrapper().tpplProductId(), $(this).tpplProductWrapper().tpplProductFeedId());
					})
				;
			});
		},
	});
	
	$(document).ready(function(){
		
		$('#tp_product_list_container').html($('<img />').attr('src', tpBaseUrl+'/img/loading.gif').css('display', 'block').css('margin','30px auto'));
		
		function productClick(a) {
			return $(a).tpProductClick();
		}

		$.post(
			ajaxurl,
			{
				action: 'tp_insertproduct_container',
				_ajax_nonce: $('#tp_ajax_nonce').val(),
				tp_insert_filter_feed: $('#tp_insert_filter_feed').val(),
				tp_insert_page: 1,
				s: $('#tp_insert_filter_search').val()
			},
			function(r) {
				$('#tp_product_list_container').html(r);
				var productEntrySelector = 'ul.tp-product-list > li.tp-product-list-entry';
				$.tpplProcessContent({
					containerSelector: 'ul.tp-product-list',
					productsSelector: productEntrySelector,
					navSelector: 'div.tablenav',
					nextPageSelector: 'a.next:last',
					infiniteScroll: {
						navSelector  : "div.tablenav", // selector for the paged navigation (it will be hidden)
						nextSelector : "a.next:last", // selector for the NEXT link (to page 2)
						itemSelector : productEntrySelector, // selector for all items you'll retrieve
						loadingImg   : tpBaseUrl + "/img/loading.gif",
						//debug: true,
						loadingText: 'Loading products...',
						localMode: false,
						bufferPx: 800,
						callback: productClick
					},
					afterProcess: function(settings) {
						productClick(settings.productsSelector);
					},
				});
			},
			"html"
		);
	});
})(jQuery.noConflict());
