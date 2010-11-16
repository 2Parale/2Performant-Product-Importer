(function($){
	function getObjectLength(obj) {
		var count = 0;
		
		for(var prop in obj) {
			if(obj.hasOwnProperty(prop))
				++count;
		}
		
		return count;
	}
	
	$.fn.extend({
		tpLoggingBox: function(_settings){
			var defaults = {
				height: 300,
				autoScroll: true,
			};
			var settings = $.extend({}, defaults, _settings);
			return $(this).each(function(){
				$(this)
					.css('height', settings.height+'px')
					.css('overflow', 'scroll')
					.html(
						$('<ul />')
							.attr('id', $(this).attr('id')+'_entrylist')
					)
					.data('tpSettings', settings)
				;
			});
		},
		tpLog: function(message, type){
			return $(this).each(function(){
				$('#'+$(this).attr('id')+'_entrylist').append(
					$('<li />').html(message).addClass('tp-logentry tp-logentry-'+type)
				);
				
				if($(this).data('tpSettings').autoScroll) {
					$(this).attr({ scrollTop: $(this).attr('scrollHeight') });
				}
			});
		},
		tpLogError: function(message){
			return $(this).tpLog(message, 'error');
		},
		tpLogWarning: function(message){
			return $(this).tpLog(message, 'warning');
		},
		tpLogMessage: function(message){
			return $(this).tpLog(message, 'message');
		},
	});
	$.extend({
		unserialize: function(serializedString) {
			var str = decodeURI(serializedString);
			var pairs = str.split('&');
			var obj = {}, p, idx, val;
			for (var i=0, n=pairs.length; i < n; i++) {
				p = pairs[i].split('=');
				idx = p[0];
	
				if (idx.indexOf("[]") == (idx.length - 2)) {
					// Eh um vetor
					var ind = idx.substring(0, idx.length-2)
					if (obj[ind] === undefined) {
						obj[ind] = [];
					}
					obj[ind].push(p[1]);
				}
				else {
					obj[idx] = p[1];
				}
			}
			return obj;
		},
		tpUpdateInfo: {
			products: 0,
			processedProducts: 0,
		},
		tpDelCampInfo: {
			products: 0,
			processedProducts: 0,
		},
		tpPost: function(action, data, callback) {
			var xdata = $.extend({
				action: 'tp_'+action,
				_ajax_nonce: $('#tp_ajax_nonce').val()
			}, data);
			return $.post(ajaxurl, xdata, callback, "json");
		},
		tpUpdateAll: function(target) {
			target = $(target);
			
			target.html('');
			
			$.tpPost(
				'getProducts',
				{},
				function(r){
					if(r.responseStatus != 'ok') {
						var log = $('<div />').attr('id','tp_updateall_log').addClass('tp-logger').tpLoggingBox({height:400}).appendTo(target);
						log.tpLogError('Error updating product: '+r.error);
						$.tpUpdateAllFinish();
						return false;
					}
					
					$.tpUpdateInfo.products = r.response.ids;
					$.tpUpdateInfo.products.length = getObjectLength($.tpUpdateInfo.products);
					$.tpUpdateInfo.processedProducts = 0;
					$.tpUpdateInfo.successfullyProcessedProducts = 0;
					
					if($.tpUpdateInfo.products.length == 0) {
						alert('No products found');
						$.tpUpdateAllFinish();
						return false;
					}
					
					target.append(
						$('<p />').html($.tpUpdateInfo.products.length+' products to update.')
					).append(
						$('<div />').attr('id','tp_updateall_progressbar').progressbar()
					).append(
						$('<div />').attr('id','tp_updateall_log').addClass('tp-logger').tpLoggingBox({height:400})
					);
					
					for(k in $.tpUpdateInfo.products) {
						if(k == 'length')
							continue;
						var pid = $.tpUpdateInfo.products[k];
						$.tpPost(
							'updateProduct',
							{
								post_id: pid
							},
							function(r,s,x){
								var data = $.unserialize(this.data), _pid = data.post_id;
								if(r.responseStatus == 'ok'){
									$.tpUpdateInfo.successfullyProcessedProducts++;
									if(r.response.errors)
										for(j in r.response.errors){
											$('#tp_updateall_log').tpLogWarning('Updating product '+_pid+': '+r.response.errors[j]);
										}
									$('#tp_updateall_log').tpLogMessage('Product '+_pid+' updated');
								} else {
									$('#tp_updateall_log').tpLogError('Error updating product: '+r.error);
								}
								$.tpUpdateInfo.processedProducts++;
								$('#tp_updateall_progressbar').progressbar('option','value',parseInt(100*$.tpUpdateInfo.processedProducts/$.tpUpdateInfo.products.length))
								if($.tpUpdateInfo.processedProducts == $.tpUpdateInfo.products.length) {
									$.tpUpdateAllFinish();
								}
							}
						);
					}
				}
			);
		},
		tpUpdateAllFinish: function(){
			var message = 'All done! Successfully updated '+$.tpUpdateInfo.successfullyProcessedProducts+' out of '+$.tpUpdateInfo.products.length+' products.';
			if($.tpUpdateInfo.successfullyProcessedProducts == $.tpUpdateInfo.products.length) {
				$('#tp_updateall_log').tpLogMessage(message);
			} else {
				$('#tp_updateall_log').tpLogWarning(message);
			}
			$('#tp_toolbox_do_updateall').attr('disabled', null);
		},
		tpDeleteCampaign: function(target) {
			target = $(target);
			
			target.html('');
			
			$.tpPost(
				'getCampaignProducts',
				{
					campaign_id: $('#tp_toolbox_deletecampaign_campaign').val()
				},
				function(r){
					if(r.responseStatus != 'ok') {
						var log = $('<div />').attr('id','tp_deletecampaign_log').addClass('tp-logger').tpLoggingBox({height:150}).appendTo(target);
						log.tpLogError('Error deleting campaign products: '+r.error);
						$.tpDeleteCampaignFinish();
						return false;
					}
					
					var force = parseInt($('#tp_toolbox_deletecampaign_force:checked').size()) > 0;
					console.log($('#tp_toolbox_deletecampaign_force:checked').size(), force);
					
					$.tpDelCampInfo.products = r.response.ids;
					$.tpDelCampInfo.products.length = getObjectLength($.tpDelCampInfo.products);
					$.tpDelCampInfo.processedProducts = 0;
					$.tpDelCampInfo.successfullyProcessedProducts = 0;
					
					if($.tpDelCampInfo.products.length == 0) {
						alert('No products found from that campaign');
						$.tpDeleteCampaignFinish();
						return false;
					}
					
					if(!confirm('Are you 100% sure you want to '+(force?'permanently delete':'send to trash')+' all '+$.tpDelCampInfo.products.length+' products from that campaign?')) {
						$.tpDeleteCampaignFinish();
						return false;
					}
					
					target.append(
						$('<p />').html($.tpDelCampInfo.products.length+' products to delete.')
					).append(
						$('<div />').attr('id','tp_deletecampaign_progressbar').progressbar()
					).append(
						$('<div />').attr('id','tp_deletecampaign_log').addClass('tp-logger').tpLoggingBox({height:400})
					);
					
					for(k in $.tpDelCampInfo.products) {
						if(k == 'length')
							continue;
						var pid = $.tpDelCampInfo.products[k];
						$.tpPost(
							'deleteProduct',
							{
								post_id: pid,
								force: force
							},
							function(r,s,x){
								var data = $.unserialize(this.data), _pid = data.post_id;
								
								if(r.responseStatus == 'ok'){
									$.tpDelCampInfo.successfullyProcessedProducts++;
									if(r.response.errors)
										for(j in r.response.errors){
											$('#tp_deletecampaign_log').tpLogWarning('Updating product '+_pid+': '+r.response.errors[j]);
										}
									$('#tp_deletecampaign_log').tpLogMessage('Product '+_pid+' '+(force?'deleted':'sent to trash'));
								} else {
									$('#tp_deletecampaign_log').tpLogError('Error updating product: '+r.error);
								}
								$.tpDelCampInfo.processedProducts++;
								$('#tp_deletecampaign_progressbar').progressbar('option','value',parseInt(100*$.tpDelCampInfo.processedProducts/$.tpDelCampInfo.products.length));
								if($.tpDelCampInfo.processedProducts == $.tpDelCampInfo.products.length) {
									$.tpDeleteCampaignFinish();
								}
							}
						);
					}
					
					if($.tpDelCampInfo.products.length == 0) {
						$.tpDeleteCampaignFinish();
						return true;
					}
				}
			);
		},
		tpDeleteCampaignFinish: function(){
			if($.tpDelCampInfo.products.length > 0) {
				var message = 'All done! Successfully deleted '+$.tpDelCampInfo.successfullyProcessedProducts+' out of '+$.tpDelCampInfo.products.length+' products.';
				if($.tpDelCampInfo.successfullyProcessedProducts == $.tpDelCampInfo.products.length) {
					$('#tp_deletecampaign_log').tpLogMessage(message);
				} else {
					$('#tp_deletecampaign_log').tpLogWarning(message);
				}
			}
			$('#tp_toolbox_do_deletecampaign').attr('disabled', null);
		},
	});
	
	$(document).ready(function(){
		$('#tp_toolbox_do_updateall').click(function(){
			$(this).attr('disabled', 'disabled');
			$.tpUpdateAll($('#tp_toolbox_updateall .tp-container'));
		});
		$('#tp_toolbox_do_deletecampaign').click(function(){
			$(this).attr('disabled', 'disabled');
			$.tpDeleteCampaign($('#tp_toolbox_deletecampaign .tp-container'));
		});
	});
})(jQuery.noConflict());