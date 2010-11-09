(function($){
	$.extend({
		tpFieldTable: function(data,name){
			var fields = $('<tbody />');
			for(var i in data) {
				data[i]['key'] = i;
				fields.append(
					$('<tr />')
						.tpFieldRow(data[i],name)
				);
			}
			
			var res = $('<table />')
				.addClass('tp-field-table')
				//.data('nameStem',name)
				.append(
					// head
					$('<thead />')
						.append($('<th scope="column" />').html('Label'))
						.append($('<th scope="column" />').html('Key'))
						.append($('<th scope="column" />').html('Value'))
						.append($('<td />'))
				).append(
					// fields
					fields
				).append(
					// add
					$('<tr class="tp-field-add" />')
						.append(
							$('<td />')
								.attr('colspan',fields.find('tr:first').children().size())
								.append(
									$('<a href="#" />')
										.html('Add field')
										.addClass('button-secondary')
										.click(function(e){
											e.preventDefault();
											var row = $('<tr />').tpFieldRow({},name).tpEditField(name);
											$(this).parents('.tp-field-add').before(
												row
											);
											row.find('.tp-field-label input:first').focus();
										})
								)
						)
				)
			;
			
			
			
			return res;
		},
	});
	
	$.fn.extend({
		tpFieldRow: function(data, name) {
			data = $.extend({
				label: '',
				key: '',
				value: '',
				type: ''
			}, data);
			
			return $(this)
				.html('')
				.addClass('tp-field')
				.addClass('tp-'+data['key'])
				.append(
					$('<th class="tp-editable tp-field-label" scope="row" />')
						.append(data['label'])
						.append(
							$('<input type="hidden" />')
								.attr('name',name+'['+data['key']+'][type]')
								.val(data['type'])
						).append(
							$('<input type="hidden" />')
								.attr('name',name+'['+data['key']+'][label]')
								.val(data['label'])
						)
				).append(
					$('<td class="tp-editable tp-field-key" />')
						.append(data['key'])
				).append(
					$('<td class="tp-editable tp-field-value" />')
						.append(data['value'])
						.append(
								$('<input type="hidden" />')
									.attr('name',name+'['+data['key']+'][value]')
									.val(data['value'])
							)
				).append(
					$('<td class="tp-field-action" />')
						.append(
							$('<a />')
							.addClass('tp-edit')
							.html('Edit')
							.attr('href','#')
							.click(function(e){
								e.preventDefault();
								$(this).parents('tr:first').tpEditField(name);
							})
						)
				)
				.data('fieldData',data)
			;
		},
		tpEditField: function(name) {
//			$(destination).hide().siblings('tr').show();
//			$('.tp-field-editor', $(this))
//				.insertAfter($(destination))
//				.show()
//			;
			return $(this).each(function(){
				var target = {
						label: $('.tp-field-label', this),
						key: $('.tp-field-key', this),
						value: $('.tp-field-value', this),
					},
					data = $(this).data('fieldData')
				;
				for(var i in target) {
					target[i].html(
						$('<input type="text" />')
							.attr('name',i)
							.val(data[i])
					);
				}
				target['label'].append(
					$('<select />')
						.attr('name','type')
						.append(
							$('<option />')
								.val('text')
								.html('Single line')
						).append(
							$('<option />')
								.val('textarea')
								.html('Multi-line')
						)
						.val(data['type'])
				);
				
				$('.tp-field-action > a.tp-edit',this).remove();
				$('.tp-field-action',this).append(
					$('<a href="#" title="OK" />')
					//.addClass('button-primary')
					//.val('OK')
					.html($('<img src="../wp-content/plugins/2performant-product-importer/img/ok.png" alt="OK" />'))
					.click(function(e){
						e.preventDefault();
						var row = $(this).parents('tr:first'),
							data = {
								label: '',
								key: '',
								value: '',
								type: '',
							},
							ok = true
						;
						for(var i in data) {
							var t = row.find(':input[name='+i+']').css('border-color',null).val();
							if(t == '') {
								row.find(':input[name='+i+']').css('border-color','#900')
								ok = false;
							} else {
								data[i] = t;
							}
						}
						if(ok)
							$(row).tpFieldRow(data,name);
					})
				).append(
					' '
				).append(
					$('<a href="#" title="Cancel" />')
						.html($('<img src="../wp-content/plugins/2performant-product-importer/img/cancel.png" alt="Cancel" />'))
						.click(function(e){
							e.preventDefault();
							var row = $(this).parents('tr:first'),
								data = row.data('fieldData');
							if(data.key != '')
								$(row).tpFieldRow(data,name);
							else
								$(row).remove();
						})
				).append(
					' '
				).append(
					$('<a href="#" title="Delete" />')
						.html($('<img src="../wp-content/plugins/2performant-product-importer/img/delete.png" alt="Delete" />'))
						.click(function(e){
							e.preventDefault();
							if(confirm('Are you sure you want to delete this field?'))
								$(this).parents('tr:first').remove();
						})
				);
			});
		},
	});
	
	$(document).ready(function(){
		$('table.fields#tp_fields_fields').remove();
		$.tpFieldTable(tp_options_fields_fields,tp_options_fields_fields_name).insertAfter('#tp_options_fields_fields_anchor');
		$('#tp_options_fields_fields_help, #tp_options_fields_other_fields_help').click(function(e){
			e.preventDefault();
			$('#contextual-help-link').click();
		});
		$('table.fields#tp_fields_other_fields').remove();
		$.tpFieldTable(tp_options_fields_other_fields,tp_options_fields_other_fields_name).insertAfter('#tp_options_fields_other_fields_anchor');
	});
})(jQuery.noConflict());