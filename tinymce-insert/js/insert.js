(function($){
	$.fn.extend({
		prepareInputs: function() {
			return $(this).each(function(){
				$('#tp_product_' + $(this).tpplProductId() + '_button')
					.val('Insert')
					.unbind('click')
					.click(function(){
						$(this).callToolbox();
					})
				;
			});
		},
		detachToolbox: function(callback) {
			return $(this).each(function(){
				$('#tp-insert-toolbox').slideUp('fast', function(){
					$(this).tpplProductWrapper().removeClass('expanded').prepareInputs().tpActionButton()
						.removeClass('button-primary')
						.addClass('button-secondary')
						.nextAll('.cancel-button, .finish-button')
							.remove()
					;
					
					if('function' == typeof callback)
						callback.call(this);
				});
			});
		},
		callToolbox: function() {
			return $(this).each(function(){
				var wrapper = $(this).tpplProductWrapper(),
					destination = wrapper.children('.tp-product-toolbox').first(),
					toolbox = $('#tp-insert-toolbox');
				$('#tp-insert-toolbox select').css('font-size','11px');
				$('#tp-insert-toolbox select').css('padding','0');
				wrapper.addClass('expanded');
				toolbox.detachToolbox(function(){
					toolbox.appendTo(destination).slideDown('fast', function(){
						wrapper.tpActionButton()
							.removeClass('button-secondary')
							.addClass('button-primary')
							.after(
								$('<input type="button" />')
									.val('Cancel')
									.addClass('button-secondary cancel-button')
									.click(function(e){
										e.preventDefault();
										$(this).detachToolbox();
									})
							).after(' ')
							.unbind('click')
							.click(function(){
								html += '[tp_product id="'+$(this).tpplProductWrapper().tpplProductId()+'" feed="'+$(this).tpplProductWrapper().tpplProductFeedId()+'" template="'+$(this).tpplProductWrapper().tpplProductTemplate()+'"]';
								if(html!='') html += '<br />';
								if( $('#done-insert').attr("checked") ){
									var win = window.dialogArguments || opener || parent || top;
									win.send_to_editor(html);
									return false;
								}
								$(this).tpplProductWrapper().addClass('existing');
								$(this).detachToolbox();
							})
						;
					});
				});
			});
		},
		
		tpActionButton: function() {
			if($(this).is('.tp-product-action-button'))
				return $(this);
			return $(this).find('.tp-product-action-button');
		},
	});
	
	function prepareInputs(a) {
		$(a).prepareInputs();
	}
	
	$(document).ready(function(){
		$('#tp_product_list_container').html($('<img />').attr('src', tpBaseUrl+'/img/loading.gif').css('display', 'block').css('margin','30px auto'));
		$('#tp_templates_list').change(function(){
			$(this).tpplProductWrapper().children('.tp-product-template').val($(this).val());
		});
		$('.tp_templates_edit').click(function(){
			window.open(wpurl + "/wp-admin/options-general.php?page=2performant-product-importer#tp_options_templates_list");
		});
		$('.tp_template_preview').click(function(){
			var product_id = $(this).tpplProductWrapper().tpplProductId();
			var feed_id = $(this).tpplProductWrapper().tpplProductFeedId();
			var template = $(this).tpplProductWrapper().tpplProductTemplate();
			window.open(wpurl + "/?tp_preview_template=true&product_id=" + product_id + "&feed_id=" + feed_id + "&template=" + template);
			return false;
		});
		$.post(
			ajaxurl,
			{
				action: 'tp_insertproduct_container',
				_ajax_nonce: $('#tp_ajax_nonce').val(),
				tp_add_filter_feed: $('#tp_insert_filter_feed').val(),
				tp_add_page: 1,
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
						callback: prepareInputs
					},
					afterProcess: function(settings) {
						prepareInputs(settings.productsSelector);
					},
				});
			},
			"html"
		);
	});
})(jQuery.noConflict());