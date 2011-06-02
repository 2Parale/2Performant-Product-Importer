(function($){
	$.fn.extend({
		tpplProductId: function() {
			var IDs = new Array();
			$(this).each(function(index){
				if($(this).is(':not(.tp-product-list-entry)'))
					return false;
				IDs[index] = $('.tp-product-id:input[type="hidden"]', $(this)).first().val();
			});
	
			if(IDs.length > 1)
				return IDs;
			else
				return IDs.pop();
		},
		tpplProductFeedId: function() {
			var IDs = new Array();
			$(this).each(function(index){
				if($(this).is(':not(.tp-product-list-entry)'))
					return false;
				IDs[index] = $('.tp-product-feed-id:input[type="hidden"]', $(this)).first().val();
			});
	
			if(IDs.length > 1)
				return IDs;
			else
				return IDs.pop();
		},
		tpplProductTemplate: function() {
			var IDs = new Array();
			$(this).each(function(index){
				if($(this).is(':not(.tp-product-list-entry)'))
					return false;
				IDs[index] = $('input.tp-product-template[type=hidden]', $(this)).first().val();
			});
	
			if(IDs.length > 1)
				return IDs;
			else
				return IDs.pop();
		},
		tpplProductWrapper: function() {
			if($(this).is('.tp-product-list-entry'))
				return $(this);
			return $(this).parents('.tp-product-list-entry').first();
		},
	});
	
	$.fn.extend({
		tpplBindCategoryIds: function() {
			return $(this).each(function(){
				var cats = new Array();
				$('.tp-category-id', $(this)).each(function(){
					cats.push($(this).val());
				}).remove();
				$(this).tpplProductWrapper().data('cats', cats);
			});
		},
		tpplInfiniteScroll: function(settings) {
			var defaults = {
				navSelector  : "div.tablenav", // selector for the paged navigation (it will be hidden)
				nextSelector : "a.next:last", // selector for the NEXT link (to page 2)
				itemSelector : 'li', // selector for all items you'll retrieve
				loadingImg   : "../wp-content/plugins/2performant-product-importer/img/loading.gif",
				//debug: true,
				loadingText: 'Loading products...',
				localMode: false,
				bufferPx: 800
			};
			settings = $.extend({}, defaults, settings);
			var callback;
			if(typeof(settings.callback == 'function'))
				callback = settings.callback;
			
			$(this).unbind('scroll').infinitescroll(settings, callback);
		},
	});
	
	$.extend({
		tpplProcessContent: function(settings){
			var defaults = {
				containerSelector: 'ul.tp-product-list',
				productsSelector: 'ul.tp-product-list > li.tp-product-list-entry',
				nextPageSelector: 'a.next:last',
				infiniteScroll: {
					bufferPx: 800
				}
			};
			settings = $.extend({}, defaults, settings);
			settings.infiniteScroll = $.extend({}, defaults.infiniteScroll, settings.infiniteScroll);
			
			$(settings.containerSelector).tpplInfiniteScroll({
				navSelector  : settings.navSelector, // selector for the paged navigation (it will be hidden)
				nextSelector : settings.nextPageSelector, // selector for the NEXT link (to page 2)
				itemSelector : settings.productsSelector, // selector for all items you'll retrieve
				loadingImg   : settings.infiniteScroll.loadingImg,
				loadingText: settings.infiniteScroll.loadingText,
				localMode: false,
				bufferPx: settings.infiniteScroll.bufferPx,
				callback: settings.infiniteScroll.callback
			});
			
			$(settings.productsSelector).tpplBindCategoryIds();
			if(typeof(settings.afterProcess) == 'function')
				settings.afterProcess.call($(settings.containerSelector), settings);
		},
	});
})(jQuery.noConflict());