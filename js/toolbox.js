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
		tpPost: function(action, data, callbacks, enqueue) {
			var xdata = $.extend({
				action: 'tp_'+action,
				_ajax_nonce: $('#tp_ajax_nonce').val()
			}, data);
			return $.ajax($.extend({
				type: "POST",
				url: ajaxurl,
				data: xdata,
				dataType: "json",
				enqueue: enqueue
			},callbacks));
		},
		tpUpdateAll: function(target, options) {
			target = $(target);
			
			target.html('Preparing to update...');
			
			$.tpPost(
				'getNumProducts',
				{},
				{
					success: function(r,s,x){
						//console.log('getNumProducts',r);
						target.html('');
						if(r.responseStatus != 'ok') {
							var log = $('<div />').attr('id','tp_updateall_log').addClass('tp-logger').tpLoggingBox({height:400}).appendTo(target);
							log.tpLogError('Error getting number of products: '+r.error);
							$.tpUpdateAllFinish();
							return false;
						}
						
						if(r.response.numProducts == 0) {
							alert('No products found');
							$.tpUpdateAllFinish();
							return false;
						}
						
						$.tpUpdateInfo.numProducts = parseInt(r.response.numProducts);
						$.tpUpdateInfo.processedProducts = 0;
						$.tpUpdateInfo.successfullyProcessedProducts = 0;
						var perBatch = parseInt(r.response.perBatch);
						
						var log = $('<div />').attr('id','tp_updateall_log').addClass('tp-logger').tpLoggingBox({height:400});
						
						target.append(
							$('<p />').html($.tpUpdateInfo.numProducts+' products to update.')
						).append(
							$('<div />').attr('id','tp_updateall_progressbar').progressbar()
						).append(
							log
						);
						
						log.tpLogMessage('Update started');
						
						for(var i=0; i*perBatch<$.tpUpdateInfo.numProducts; i++) {
							$.tpPost(
								'getProducts',
								{
									page: i
								},
								{
									success: function(r,s,x){
//										console.log('getProducts',r, r.response.ids.length);
										if(r.responseStatus != 'ok') {
											log.tpLogError('Error updating product: '+r.error);
											$.tpUpdateAllFinish();
											return false;
										}
										
										var products = r.response.ids;
//										console.log(products.length);
										
										for(k in products) {
											if(k == 'length')
												continue;
											var pid = products[k];
											var overwrites = options.overwrites || null;
											
											$.tpPost(
												'updateProduct',
												{
													post_id: pid,
													overwrites: overwrites
												},
												{
													success: function(r,s,x) {
														var data = $.unserialize(this.data), _pid = data.post_id;
														if(r.responseStatus == 'ok') {
															var post_name = r.response.name || _pid;
															$.tpUpdateInfo.successfullyProcessedProducts++;
															if(r.response.errors)
																for(j in r.response.errors){
																	$('#tp_updateall_log').tpLogWarning('Updating product '+post_name+': '+r.response.errors[j]);
																}
//															$('#tp_updateall_log').tpLogMessage('Product '+post_name+' updated');
														} else {
															$('#tp_updateall_log').tpLogError('Error updating product: '+r.error);
														}
														$.tpUpdateInfo.processedProducts++;
														$('#tp_updateall_progressbar').progressbar('option','value',100*$.tpUpdateInfo.processedProducts/$.tpUpdateInfo.numProducts)
														if($.tpUpdateInfo.processedProducts == $.tpUpdateInfo.numProducts) {
															$.tpUpdateAllFinish();
															//console.log(allProducts);
														}
													},
													error: function() {
														$.tpUpdateInfo.processedProducts++;
														$('#tp_updateall_progressbar').progressbar('option','value',100*$.tpUpdateInfo.processedProducts/$.tpUpdateInfo.numProducts)
														log.tpLogError('Connection/server error while updating product');
														if($.tpUpdateInfo.processedProducts >= $.tpUpdateInfo.numProducts) {
															$.tpUpdateAllFinish();
														}
														return false;
													}
												}
											);
										}
									},
									error: function(x) {
										$.tpUpdateInfo.processedProducts+=perBatch;
										$('#tp_updateall_progressbar').progressbar('option','value',100*$.tpUpdateInfo.processedProducts/$.tpUpdateInfo.numProducts)
										log.tpLogError('Connection/server error while loading batch');
										if($.tpUpdateInfo.processedProducts >= $.tpUpdateInfo.numProducts) {
											$.tpUpdateAllFinish();
										}
										return false;
									}
								}
							);
						}
					}
				}
			);
			return false;
		},
		tpUpdateAllFinish: function(){
			var message = 'All done! Successfully updated '+$.tpUpdateInfo.successfullyProcessedProducts+' out of '+$.tpUpdateInfo.numProducts+' products.';
			if($.tpUpdateInfo.successfullyProcessedProducts == $.tpUpdateInfo.numProducts) {
				$('#tp_updateall_log').tpLogMessage(message);
			} else {
				$('#tp_updateall_log').tpLogWarning(message);
			}
			typeof($('#tp_toolbox_do_updateall').prop)=='function'
				? $('#tp_toolbox_do_updateall').prop('disabled', false)
				: $('#tp_toolbox_do_updateall').attr('disabled', null)
			;
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
								var data = $.unserialize(this.data), _pid = data.post_id, post_name = r.response.name || _pid;
								
								if(r.responseStatus == 'ok'){
									$.tpDelCampInfo.successfullyProcessedProducts++;
									if(r.response.errors)
										for(j in r.response.errors){
											$('#tp_deletecampaign_log').tpLogWarning('Updating product '+post_name+': '+r.response.errors[j]);
										}
									$('#tp_deletecampaign_log').tpLogMessage('Product '+post_name+' '+(force?'deleted':'sent to trash'));
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
			typeof($('#tp_toolbox_do_deletecampaign').prop)=='function'
				? $('#tp_toolbox_do_deletecampaign').prop('disabled', false)
				: $('#tp_toolbox_do_deletecampaign').attr('disabled', null)
			;
		},
	});
	
	$(document).ready(function(){
		$('#tp_toolbox_do_updateall').click(function(){
			var overwrites = new Array();
			$('#update_overwrites').find(':checkbox').each(function(){
				var value = $('label[for="'+$(this).attr('id')+'"]').first().text();
				if($(this).is(':checked'))
					if('yes' == (prompt('Type in "yes" (without the quotes) if you are sure you want to force the update of "'+value+'" for every product, including where you modified it by hand.')+'').toLowerCase()) {
						var key = $(this).val();
						overwrites.push(key);
					} else {
						alert(value+' will not be modified for the products where you edited it by hand.');
					}
			});
			typeof($(this).prop)=='function'
				? $(this).prop('disabled', true)
				: $(this).attr('disabled', 'disabled')
			;
			
			$.tpUpdateAll($('#tp_toolbox_updateall .tp-container'), {'overwrites': overwrites});
		});
		$('#tp_toolbox_do_deletecampaign').click(function(){
			typeof($(this).prop)=='function'
				? $(this).prop('disabled', true)
				: $(this).attr('disabled', 'disabled')
			;
			$.tpDeleteCampaign($('#tp_toolbox_deletecampaign .tp-container'));
		});
	});
})(jQuery.noConflict());