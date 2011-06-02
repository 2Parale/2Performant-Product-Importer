(function($){
	$.fn.extend({
		checkCheckbox: function(checkit) {
			if(typeof checkit == 'undefined')
				checkit = true;
			if(!checkit)
				return $(this.unckeckCheckbox());
			return $(this).each(function(){
				if(typeof $(this).prop == 'function')
					$(this).prop('checked', true);
				else
					$(this).attr('checked','checked');
			});
		},
		uncheckCheckbox: function() {
			return $(this).each(function(){
				if(typeof $(this).prop == 'function')
					$(this).prop('checked', false);
				else
					$(this).attr('checked',null);
			});
		},
		prepareInputs: function() {
			return $(this).each(function(){
				$('#tp_product_' + $(this).tpplProductId() + '_button')
					.val($(this).hasClass('existing') ? 'Update' : 'Add')
					.unbind('click')
					.click(function(){
						$(this).callToolbox();
					})
				;
				if($(this).hasClass('existing')) {
					$(this).tpActionButton().nextAll('.delete-button').remove();
					$(this).tpActionButton().after(
						$(' <a href="#" />')
							.html('Delete')
							.addClass('submitdelete delete-button')
							.click(function(){
								if(confirm('Are you sure?'))
									$(this).tpplProductWrapper().tpDeleteProduct();
							})
					).after(' ');
				}
			});
		},
		detachToolbox: function(callback) {
			return $(this).each(function(){
				$('#tp-insert-toolbox').slideUp('fast', function(){
					$(this).tpplProductWrapper().removeClass('expanded').prepareInputs().tpActionButton()
						.removeClass('button-primary')
						.addClass('button-secondary')
						.nextAll('.cancel-button')
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
					toolbox = $('#tp-insert-toolbox'),
					cats = wrapper.data('cats');
				wrapper.addClass('expanded');
				toolbox.detachToolbox(function(){
					toolbox.find(':checkbox[name^="post_category"]').each(function(){
						if($.inArray($(this).val(), cats) !== -1)
							$(this).checkCheckbox();
						else
							$(this).uncheckCheckbox();
					});
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
								$(this).tpplProductWrapper().tpAddProduct();
							})
						;
					});
				});
			});
		},
		tpAddProduct: function() {
			return $(this).tpplProductWrapper().each(function(){
				var data, categories = new Array(), _this = this;
				$('#tp-insert-toolbox :checked[name^="post_category"]').each(function(){
					categories.push($(this).val());
				});
				
				$(_this).data('cats', categories);
				
				data = {
					action: 'tp_addproduct',
					_ajax_nonce: $('#tp_ajax_nonce').val(),
					product_id: $(this).tpplProductWrapper().tpplProductId(),
					feed_id: $(this).tpplProductWrapper().tpplProductFeedId(),
					category: categories,
				};
				
				$.post(ajaxurl, data, function(r, s, xhr){
					if(r == 'ok') {
						$('#tp-insert-toolbox').detachToolbox();
						$(_this).tpplProductWrapper().addClass('existing').removeClass('outdated').prepareInputs();
					} else {
						
					}
				}, "text");
			});
		},
		tpDeleteProduct: function() {
			return $(this).tpplProductWrapper().each(function(){
				var _this = this, data = {
					action: 'tp_deleteproduct',
					_ajax_nonce: $('#tp_ajax_nonce').val(),
					product_id: $(this).tpplProductWrapper().tpplProductId(),
					feed_id: $(this).tpplProductWrapper().tpplProductFeedId(),
				};
				
				$.post(ajaxurl, data, function(r, s, xhr){
					if(r == 'ok') {
						$('#tp-insert-toolbox').detachToolbox();
						$(_this).tpplProductWrapper().removeClass('existing').removeClass('outdated').removeClass('trash').prepareInputs();
						$(_this).tpplProductWrapper().tpActionButton().nextAll('.delete-button').remove();
					}
				}, "text");
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

		$.post(
			ajaxurl,
			{
				action: 'tp_addproduct_container',
				_ajax_nonce: $('#tp_ajax_nonce').val(),
				tp_add_filter_feed: $('#tp_add_filter_feed').val(),
				tp_add_page: 1,
				s: $('#tp_add_filter_search').val()
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
						loadingImg   : "../wp-content/plugins/2performant-product-importer/img/loading.gif",
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
				
				$('.categorydiv').each( function(){
					var this_id = $(this).attr('id'), noSyncChecks = false, syncChecks, catAddAfter, taxonomyParts, taxonomy, settingName;
		
					taxonomyParts = this_id.split('-');
					taxonomyParts.shift();
					taxonomy = taxonomyParts.join('-');
			 		settingName = taxonomy + '_tab';
			 		if ( taxonomy == 'category' )
			 			settingName = 'cats';
		
					// TODO: move to jQuery 1.3+, support for multiple hierarchical taxonomies, see wp-lists.dev.js
					$('a', '#' + taxonomy + '-tabs').click( function(){
						var t = $(this).attr('href');
						$(this).parent().addClass('tabs').siblings('li').removeClass('tabs');
						$('#' + taxonomy + '-tabs').siblings('.tabs-panel').hide();
						$(t).show();
						if ( '#' + taxonomy + '-all' == t )
							deleteUserSetting(settingName);
						else
							setUserSetting(settingName, 'pop');
						return false;
					});
		
					if ( getUserSetting(settingName) )
						$('a[href="#' + taxonomy + '-pop"]', '#' + taxonomy + '-tabs').click();
		
					// Ajax Cat
					$('#new' + taxonomy).one( 'focus', function() { $(this).val( '' ).removeClass( 'form-input-tip' ) } );
					$('#' + taxonomy + '-add-submit').click( function(){ $('#new' + taxonomy).focus(); });
		
					syncChecks = function() {
						if ( noSyncChecks )
							return;
						noSyncChecks = true;
						var th = jQuery(this), c = th.is(':checked'), id = th.val().toString();
						$('#in-' + taxonomy + '-' + id + ', #in-' + taxonomy + '-category-' + id).checkCheckbox( c );
						noSyncChecks = false;
					};
		
					catAddBefore = function( s ) {
						if ( !$('#new'+taxonomy).val() )
							return false;
						s.data += '&' + $( ':checked', '#'+taxonomy+'checklist' ).serialize();
						return s;
					};
		
					catAddAfter = function( r, s ) {
						var sup, drop = $('#new'+taxonomy+'_parent');
		
						if ( 'undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent) ) {
							drop.before(sup);
							drop.remove();
						}
					};
		
					$('#' + taxonomy + 'checklist').wpList({
						alt: '',
						response: taxonomy + '-ajax-response',
						addBefore: catAddBefore,
						addAfter: catAddAfter
					});
		
					$('#' + taxonomy + '-add-toggle').click( function() {
						$('#' + taxonomy + '-adder').toggleClass( 'wp-hidden-children' );
						$('a[href="#' + taxonomy + '-all"]', '#' + taxonomy + '-tabs').click();
						$('#new'+taxonomy).focus();
						return false;
					});
		
					$('#' + taxonomy + 'checklist li.popular-category :checkbox, #' + taxonomy + 'checklist-pop :checkbox').live( 'click', function(){
						var t = $(this), c = t.is(':checked'), id = t.val();
						if ( id && t.parents('#taxonomy-'+taxonomy).length )
							$('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).checkCheckbox( c );
					});
		
				}); // end cats
			},
			"html"
		);
	});
})(jQuery.noConflict());