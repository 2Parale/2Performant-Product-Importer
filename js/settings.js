(function($){
	$.extend({
		tpFieldTable: function(data,name,options){
			var defaults = {
				selectable_type: false,
				button: "Add field",
				template: 0
			};
			options = $.extend({}, defaults, options);
			
			var fields = $('<tbody />');
			for(var i in data) {
				data[i]['key'] = i;
				data[i]['selectable_type'] = options.selectable_type;
				fields.append(
					$('<tr />')
						.tpFieldRow(data[i],name,options.template)
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
										.html(options.button)
										.addClass('button-secondary')
										.click(function(e){
											var tableOptions = options;
											e.preventDefault();
											var row = $('<tr />').tpFieldRow({selectable_type: tableOptions.selectable_type},name,tableOptions.template).tpEditField(name,tableOptions.template);
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
	function htmlEntities(str) {
	    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	$.fn.extend({
		tpFieldRow: function(data, name, template) {
			data = $.extend({
				label: '',
				key: '',
				value: '',
				type: '',
				selectable_type: false
			}, data);
			var sizeTd = ( template == 1 ) ? "70%" : "30%";
			if( template == 1 ) {
				
				var defaultTemplate = $('#tp_options_templates_default_template').val();
				var buttonNotChecked = $('<img src="../wp-content/plugins/2performant-product-importer/img/template.png" alt="Set template as default" />');
				var buttonChecked = $('<img src="../wp-content/plugins/2performant-product-importer/img/default_template.png" alt="This is the default template" />');
				var defaultButton = ( data['key'] != defaultTemplate ) ? buttonNotChecked : buttonChecked ;
				var defaultTitle = ( data['key'] != defaultTemplate ) ? "Set this template as default" : "This is the default template";
				var templateButton = $('<a title="Set as default" />')
									.addClass('tp-default-template')
									.html(defaultButton)
									.attr('href','#').attr('title',defaultTitle)
									.click(function(e){
										e.preventDefault();
										$('#tp_options_templates_default_template').val(data['key']);
										$('.tp-default-template').html(buttonNotChecked);
										$('.tp-default-template').attr('title','Set this template as default');
										$(this).html(buttonChecked);
										$(this).attr('title','This is the default template');
									})	
			}
			var button1 = ( template == 1 ) ? $('<a />')
					.addClass('tp-edit')
					.html($('<img src="../wp-content/plugins/2performant-product-importer/img/edit.png" alt="Edit template" />'))
					.attr('href','#')
					.click(function(e){
						e.preventDefault();
						$(this).parents('tr:first').tpEditField(name,template);
					}) :
						$('<a />')
						.addClass('tp-edit')
						.html('Edit')
						.attr('href','#')
						.click(function(e){
							e.preventDefault();
							$(this).parents('tr:first').tpEditField(name,template);
						})
			var button2 = ( template == 1 ) ? templateButton : '';
			return $(this)
				.html('')
				.addClass('tp-field')
				.addClass('tp-'+data['key'])
				.append(
					$('<th class="tp-editable tp-field-label" scope="row"  valign="top"/>')
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
					$('<td class="tp-editable tp-field-key" valign="top" />')
						.append(data['key'])
				).append(
					$('<td class="tp-editable tp-field-value" width="' + sizeTd + '"  valign="top"/>')
						.append("<pre><code>" + htmlEntities ( data['value'] ) + "</code></pre>")
						.append(
								$('<input type="hidden" />')
									.attr('name',name+'['+data['key']+'][value]')
									.val(data['value'])
							)
				).append(
					
					$('<td class="tp-field-action" valign="top" />')
						.append(
							button1
						).append(
							button2
						)
				)
				.data('fieldData',data)
			;
		},
		tpEditField: function(name,template) {
			return $(this).each(function(){
				var target = {
						label: $('.tp-field-label', this),
						key: $('.tp-field-key', this),
						value: $('.tp-field-value', this),
					},
					data = $(this).data('fieldData'),
					t = $('<td valign="top" />').html(target.label.html()).addClass('tp-field-label')
				;
				target.label.replaceWith(t);
				target.label = t;
				
				for(var i in target) {
					var textarea = ( template == 1 ) ? $( '<textarea rows="10" cols="80" />' ).attr( 'name' ,i ).text( data[i] ) :  $( '<textarea />' ).attr( 'name' ,i ).text( data[i] ); 
					var edit_control = (i != 'value') ? ( 
							$('<input type="text" />')
								.attr('name',i)
								.val(data[i])
						) : (
								textarea
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
				$('.tp-field-action > a.tp-default-template',this).remove();
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
							$(row).tpFieldRow(data,name,template);
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
								$(row).tpFieldRow(data,name,template);
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
		$.tpFieldTable(tp_options_templates_list,tp_options_templates_list_name, {button: "Add template", template: 1}).insertAfter('#tp_options_templates_list_anchor');
		
		$('#tp_options_fields_fields_help, #tp_options_fields_other_fields_help, #tp_options_templates_help').click(function(e){
			e.preventDefault();
			$('html, body').animate({scrollTop:0}, 'fast');
			$('#contextual-help-link').click();
		});
		$('table.fields#tp_fields_other_fields').remove();
		$.tpFieldTable(tp_options_fields_other_fields,tp_options_fields_other_fields_name).insertAfter('#tp_options_fields_other_fields_anchor');
		
	});
})(jQuery.noConflict());