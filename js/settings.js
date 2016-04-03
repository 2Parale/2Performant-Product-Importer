(function($){
	$.extend({
		tpFieldTable: function(data,name,options){
			var defaults = {
				selectable_type: false
			};
			options = $.extend({}, defaults, options);
			
			var fields = $('<tbody />');
			for(var i in data) {
				data[i]['key'] = i;
				data[i]['selectable_type'] = options.selectable_type;
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
											var tableOptions = options;
											e.preventDefault();
											var row = $('<tr />').tpFieldRow({selectable_type: tableOptions.selectable_type},name).tpEditField(name);
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
				type: '',
				selectable_type: false
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
			return $(this).each(function(){
				var target = {
						label: $('.tp-field-label', this),
						key: $('.tp-field-key', this),
						value: $('.tp-field-value', this),
					},
					data = $(this).data('fieldData'),
					t = $('<td />').html(target.label.html()).addClass('tp-field-label')
				;
				target.label.replaceWith(t);
				target.label = t;
				for(var i in target) {
					var edit_control = (i != 'value') ? ( 
							$('<input type="text" />')
								.attr('name',i)
								.val(data[i])
						) : (
							$('<textarea />')
								.attr('name',i)
								.text(data[i])
						);
					target[i].html(edit_control);
				}
				if(data.selectable_type) {
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
				}
				
				$('.tp-field-action > a.tp-edit',this).remove();
				$('.tp-field-action',this).append(
					$('<a href="#" title="OK" />')
					//.addClass('button-primary')
					//.val('OK')
					.html($('<img src="../wp-content/plugins/2performant-product-importer/img/ok.png" alt="OK" />'))
					.click(function(e){
						e.preventDefault();
						var row = $(this).parents('tr:first'),
							data = row.data('fieldData'),
//							data = {
//								label: '',
//								key: '',
//								value: '',
//								type: '',
//							},
							ok = true
						;
						for(var i in data) {
							var t = row.find(':input[name="'+i+'"]').css('border-color',null).val();
							if(t == '') {
								row.find(':input[name="'+i+'"]').css('border-color','#900')
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
		$.tpFieldTable(tp_options_fields_fields,tp_options_fields_fields_name, {selectable_type: true}).insertAfter('#tp_options_fields_fields_anchor');
		$('#tp_options_fields_fields_help, #tp_options_fields_other_fields_help').click(function(e){
			e.preventDefault();
			$('html, body').animate({scrollTop:0}, 'fast');
			$('#contextual-help-link').click();
		});
		$('table.fields#tp_fields_other_fields').remove();
		$.tpFieldTable(tp_options_fields_other_fields,tp_options_fields_other_fields_name).insertAfter('#tp_options_fields_other_fields_anchor');
	});
})(jQuery.noConflict());