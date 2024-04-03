/**
 * 	This file handles ajax related actions
 */
var pklist_dc_ajax=(function( $ ) {
	var pklist_dc_ajax=
	{
		load_template:function()
		{
			var ajax_data=this.prepare_ajax_data(this.customizer_ajax_hook, 'get_template_data');
			this.set_ajax_loader();
			pklist_dc.template_loaded=0;
			$.ajax({
				type: 'GET',
            	url:wt_pklist_dc_params.ajax_url,
            	data:ajax_data,
            	dataType:'json',
				success:function(data)
				{
					pklist_dc.remove_ajax_loader();
					if(data.status==1)
					{
						data.codeview_html=pklist_dc.apply_dummy_value_to_img_urls(data.codeview_html);
						$('.wt_pklist_dc_head_page_title').html(data.name);
						$('.wt_pklist_dc_visual_editor').html(data.html);
						$('.wt_pklist_dc_code_editor').html(data.codeview_html);

						pklist_dc.template_is_active=data.is_active;
						pklist_dc.template_loaded=1;
						
						pklist_dc.prepare_canvas();
						pklist_dc.prepare_template_elements();
						pklist_dc.set_page_property_editor();
						pklist_dc.show_empty_block_editor_msg();
						pklist_dc.active_template_options();

						pklist_dc.reset_undo_redo();
						pklist_dc.check_template_compatibility();
						
					}else
					{
						$('.wt_pklist_dc_head_page_title').html('');
						$('.wt_pklist_dc_visual_editor').html('');
						$('.wt_pklist_dc_code_editor').html('');
						wf_notify_msg.error(data.msg);
					}
				},
				error:function()
				{
					pklist_dc.remove_ajax_loader();
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});
		},

		/**
		 * 	Load `my templates` list
		 */
		load_my_templates:function(template_action, template_id)
		{

			var ajax_data=this.prepare_ajax_data(this.customizer_ajax_hook, 'my_templates');
	        if(template_action)
	        {
	        	ajax_data.template_action=template_action;
	        	ajax_data.template_id=template_id;
	        }

	        $('.wt_pklist_dc_my_template_list').addClass('wt_pklist_dc_loader_bg').html('');
			$.ajax({
				type: 'POST',
            	url:wt_pklist_dc_params.ajax_url,
            	data:ajax_data,
            	dataType:'json',
				success:function(data)
				{
					$('.wt_pklist_dc_my_template_list').removeClass('wt_pklist_dc_loader_bg');
					if(data.status==1)
					{
						$('.wt_pklist_dc_my_template_list').html(data.html);
						if(template_action)
						{
							if(template_action=='activate')
							{
								pklist_dc.load_template();
							}
							else if(template_action=='delete' && template_id==pklist_dc.template_id)
							{
								window.location.reload();
							}
						}
					}else
					{
						$('.wt_pklist_dc_my_template_list').html(data.msg);
					}
				},
				error:function()
				{
					$('.wt_pklist_dc_my_template_list').removeClass('wt_pklist_dc_loader_bg');
					$('.wt_pklist_dc_my_template_list').html(wt_pklist_dc_params.labels.error);
				}
			});
		},

		/**
		 * 	Prepare the data object to send via ajax
		 */
		prepare_ajax_data:function(ajax_hook, ajax_action)
		{
			return {
	            _wpnonce:wt_pklist_dc_params.nonces.main,
	            action: ajax_hook,
	            customizer_action: ajax_action,
	            template_type:this.template_type,
	            template_id:this.template_id,
	            def_template:this.default_template_id,
	        };
		},
		remove_ajax_loader:function()
		{
			$('.wt_pklist_dc_ajax_loader_overlay').hide();

			/* save button at popup */
			$('.wt_pklist_dc_save_btn_sub').show();
			$('.wt_pklist_dc_save_theme_sub_loading').hide();
		},
		set_ajax_loader:function()
		{
			if($('.wt_pklist_dc_ajax_loader_overlay').length==0)
			{
				$('body').prepend('<div class="wt_pklist_dc_ajax_loader_overlay"></div>');
			}
			if($('.wt_pklist_dc_ajax_loader').length==0)
			{
				$('.wt_pklist_dc_ajax_loader_overlay').html('<div class="wt_pklist_dc_ajax_loader">'+wt_pklist_dc_params.labels.please_wait+'</div>');
			}
			$('.wt_pklist_dc_ajax_loader_overlay').show();

			/* save button at popup */
			$('.wt_pklist_dc_save_btn_sub').hide();
			$('.wt_pklist_dc_save_theme_sub_loading').show();
		},

		/**
		 * 	Enable/Disable buttons when doing an ajax action
		 * 
		 */
		enable_disable_button:function(btn_elm, action)
		{
			if(action=='disable')
			{
				btn_elm.attr('disabled', "disabled").prop('disabled', true).css({'opacity': .5, 'cursor': 'not-allowed'});
			}else
			{
				btn_elm.prop('disabled', false).removeAttr('disabled').css({'opacity':1, 'cursor':'pointer'});
			}
		},
		reg_ajax_events:function()
		{
			/* save button click */
			$('.wt_pklist_dc_save_btn, .wt_pklist_dc_save_btn_sub').on('click', function(){
				
				if(pklist_dc.template_id==0) /* new theme, then prompt for name */
				{
					$('.wt_pklist_dc_template_name_wrn').hide();
					$('.wt_pklist_dc_template_name_field').val('');
					wf_popup.showPopup($('.wt_pklist_dc_template_name'));
					$('.wt_pklist_dc_template_name_field').focus();
				}else
				{
					pklist_dc.save_template();
				}

			});

			/* save button on template name prompt popup */
			$('.wt_pklist_dc_template_create_btn').on('click', function(){
				var name=$('.wt_pklist_dc_template_name_field').val().trim();
				if(name=='')
				{
					$('.wt_pklist_dc_template_name_wrn').show();
					$('.wt_pklist_dc_template_name_field').focus();
				}else
				{
					pklist_dc.save_template();
					$('.wt_pklist_dc_template_name_wrn').hide();
					wf_popup.hidePopup();
				}
			});

			$('.wt_pklist_dc_template_name_field').on('keypress', function(e){
				if(e.keyCode==13) /* save */
				{
					$('.wt_pklist_dc_template_create_btn').click();
				}
			});

			this.my_templates_events();
			this.pdf_preview_events();		
		},
		pdf_preview_events:function()
		{
			$('.wt_pklist_dc_preview_pdf').click(function(){
				var popup_elm=$('.wt_pklist_dc_pdf_preview');
				wf_popup.showPopup(popup_elm);
				$('.wt_pklist_dc_dropdown_menu').trigger('blur');
				var order_id=$(this).attr('data-order-id');
				pklist_dc.generate_preview_pdf(order_id);
			});
		},
		generate_preview_pdf:function(order_id)
		{
			var ajax_data=this.prepare_ajax_data(this.customizer_ajax_hook, 'prepare_sample_pdf');
			ajax_data.codeview_html=this.get_source_html();
			ajax_data.order_id=order_id;
			$('.wt_pklist_dc_pdf_preview_content').addClass('wt_pklist_dc_loader_bg').html('');
			jQuery.ajax({
				url:wt_pklist_dc_params.ajax_url,
				type:'POST',
				data:ajax_data,
				dataType:'json',
				success:function(data)
				{
					if(data.status==1)
					{
						var preview_url = data.pdf_url.replace(/&amp;/g, '&');
						$('.wt_pklist_dc_pdf_preview_content').css({'max-height': 'none'}).html('<iframe src="'+preview_url+'" style="width:100%; min-height:600px; height:auto;"></iframe>');
					}else
					{
						wf_notify_msg.error(data.msg);
					}				
				},
				error:function() 
				{
					$('.wt_pklist_dc_pdf_preview_content').removeClass('wt_pklist_dc_loader_bg').html(wt_pklist_dc_params.labels.error);
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});

		},
		my_templates_events:function()
		{
			/* my template popup */
			$('.wt_pklist_dc_my_templates').click(function(){
				var popup_elm=$('.wt_pklist_dc_my_template');
				wf_popup.showPopup(popup_elm);
				$('.wt_pklist_dc_my_template_search').val('');
				$('.wt_pklist_dc_dropdown_menu').trigger('blur');
				pklist_dc.load_my_templates();				
			});

			/* my template search */
			$('.wt_pklist_dc_my_template_search').on('keyup',function(){
				var vl=$(this).val().trim();
				if(vl!="")
				{
					vl=vl.toLowerCase();
					$('.wf_my_template_item').hide();
					var template_item=$('.wf_my_template_item').filter(function(){
						var name=$(this).find('.wf_my_template_item_name').text();
						name=name.toLowerCase();
						if(name.search(vl)!=-1)
						{
							return true;
						}else
						{
							return false;
						}
					});
					template_item.show();
				}else
				{
					$('.wf_my_template_item').show();
				}
			});

			/* template delete */
			$(document).on("click", '.wt_pklist_dc_delete_theme, .wf_delete_theme', function(event) { 
			    if(confirm(wt_pklist_dc_params.labels.sure))
			    {
			    	pklist_dc.set_ajax_loader();
			    	var template_id=$(this).attr('data-id');
			    	pklist_dc.load_my_templates('delete', template_id);
			    }				    
			});

			/* activate theme */
			$(document).on("click", '.wt_pklist_dc_activate_theme, .wf_activate_theme', function(event) { 
			    pklist_dc.set_ajax_loader();
			    var template_id=$(this).attr('data-id');
			    pklist_dc.load_my_templates('activate', template_id);
			});

			/* edit the current theme */
			$(document).on("click", '.wf_customize_theme', function(event) { 
			    pklist_dc.template_id=$(this).attr('data-id');
			    wf_popup.hidePopup();
				pklist_dc.load_template();
			});

		},
		save_template:function()
		{	
	        var ajax_data=this.prepare_ajax_data(this.customizer_ajax_hook, 'save_theme');
			ajax_data.codeview_html=this.get_source_html();
			ajax_data.name=$('.wt_pklist_dc_template_name_field').val();
			ajax_data.dc_compatible=1;

			this.set_ajax_loader();
	        $.ajax({
				type: 'POST',
            	url:wt_pklist_dc_params.ajax_url,
            	data:ajax_data,
            	dataType:'json',
				success:function(data)
				{
					pklist_dc.remove_ajax_loader();					
					if(data.status==1)
					{
						pklist_dc.template_id=data.template_id;
						pklist_dc.template_is_active=data.is_active;
						$('.wt_pklist_dc_head_page_title').html(data.name);
						wf_notify_msg.success(data.msg);
						pklist_dc.active_template_options();
					}else
					{
						wf_notify_msg.error(data.msg);
					}
				},
				error:function()
				{
					pklist_dc.remove_ajax_loader();
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});
		},

		/**
		 * 	Prepare design view HTML when user edits the HTML(Full code view editor)
		 */
		update_on_codeview_change:function(source_html_backup)
		{
			if($('.wt_pklist_dc_html_editor_popup_footer button').attr('disabled')=='disabled') /* not mandatory */
			{
				return false;
			}

	        var ajax_data=this.prepare_ajax_data(this.customizer_ajax_hook, 'update_from_codeview');
			ajax_data.codeview_html=this.get_source_html();
			
			$('.wt_pklist_dc_code_editor').html(source_html_backup); /* revert back the updated code, It will only update after ajax success */
	        
	        this.set_ajax_loader();
	        this.enable_disable_button($('.wt_pklist_dc_html_editor_popup_footer button'), 'disable');
			$.ajax({
				type: 'POST',
            	url:wt_pklist_dc_params.ajax_url,
            	data:ajax_data,
            	dataType:'json',
				success:function(data)
				{
					pklist_dc.remove_ajax_loader();
					pklist_dc.enable_disable_button($('.wt_pklist_dc_html_editor_popup_footer button'), 'enable');
					if(data.status==1)
					{
						data.codeview_html=pklist_dc.apply_dummy_value_to_img_urls(data.codeview_html);
						$('.wt_pklist_dc_visual_editor').html(data.html);
						$('.wt_pklist_dc_code_editor').html(data.codeview_html);
						pklist_dc.prepare_template_elements();
						pklist_dc.add_history(); /* save for undo redo */
						
						if(!pklist_dc.full_code_editor)
						{
							setTimeout(function(){
								pklist_dc.refresh_editable_selected($('.wt_pklist_dc_visual_editor .wt_pklist_dc_on_current_code_editor').eq(0));
								$('.wt_pklist_dc_visual_editor .wt_pklist_dc_on_current_code_editor').removeClass('wt_pklist_dc_on_current_code_editor');
								$('.wt_pklist_dc_code_editor .wt_pklist_dc_on_current_code_editor').removeClass('wt_pklist_dc_on_current_code_editor wt_pklist_dc_editable_selected');
							}, 200);
						}

						wf_popup.hidePopup(); /* popup will only close if the ajax was successfull */

					}else
					{
						wf_notify_msg.error(data.msg);
					}
				},
				error:function()
				{
					pklist_dc.enable_disable_button($('.wt_pklist_dc_html_editor_popup_footer button'), 'enable');
					pklist_dc.removeLoader();
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});
		},


		/**
		 *  Adjust the menu options based on current template's  status
		 * 
		 */
		active_template_options:function()
		{
			var act_btn_elms=$('.wt_pklist_dc_activate_theme, .wt_pklist_dc_delete_theme');
			act_btn_elms.attr('data-id', this.template_id).hide();

			if(this.template_id>0)
			{
				$('.wt_pklist_dc_new_template').html(wt_pklist_dc_params.labels.create_new);
				$('.wt_pklist_dc_new_template_wrn_sub').show(); /* save button and msg at new template popup */
			}else
			{
				$('.wt_pklist_dc_new_template').html(wt_pklist_dc_params.labels.change_theme);
				$('.wt_pklist_dc_new_template_wrn_sub').hide(); /* save button and msg at new template popup */
			}
			if(this.template_is_active==0 && this.template_id>0)
			{
				act_btn_elms.show();
			}
		},

		/**
		 * 	Delete custom created order meta from assets section
		 */
		delete_order_meta:function(btn_elm)
		{
			var meta_key=btn_elm.attr('data-meta-key');
			var ajx_data='action=wt_pklist_custom_field_list_view&_wpnonce='+wf_pklist_params.nonces.wf_packlist+'&wt_pklist_custom_field_type=order_meta&wf_delete_custom_field='+meta_key;
			
			var sidebar_asset_elm=btn_elm.parents('.wt_pklist_dc_asset_item');
			sidebar_asset_elm.removeClass('wfte_draggable wt_pklist_dc_asset_item').removeAttr('draggable').addClass('wt_pklist_dc_asset_item_on_delete').attr('title', wt_pklist_dc_params.labels.deleting);
			pklist_dc.enable_disable_button(btn_elm, 'disable');

			jQuery.ajax({
				url:wt_pklist_dc_params.ajax_url,
				data:ajx_data,
            	type: 'POST',
				success:function(data)
				{ 
					/* remove from assets */
					var slug=btn_elm.attr('data-slug');
					var parent_slug=btn_elm.attr('data-parent-slug');
					delete wt_pklist_dc_params.assets_elements[parent_slug][slug];
					delete wt_pklist_dc_params.assets_editable_properties[parent_slug][slug];

					pklist_dc.get_asset_visual_elm_by_wfte_name(slug).remove(); /* remove from asset design view */

					pklist_dc.get_asset_source_elm_by_wfte_name(slug).remove(); /* remove from asset code view */

					sidebar_asset_elm.remove();

					/* if any item is selected for editing in the other tab */
					var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
					if(sidebar_tab_elm.data('target_elm'))
					{
						if(sidebar_tab_elm.data('target_elm').attr('data-wfte_name')==parent_slug) /* here invoice_data */
						{ 
							pklist_dc.refresh_editable_selected(sidebar_tab_elm.data('target_elm')); /* reload the editor panel to update other available items section */
						}
					}

					/* !Imp: We are not removing the item from template */

				},
				error:function()
				{
					sidebar_asset_elm.addClass('wfte_draggable wt_pklist_dc_asset_item').attr('draggable', true).removeClass('wt_pklist_dc_asset_item_on_delete').attr('title', wt_pklist_dc_params.labels.drag_item_tooltip);
					pklist_dc.enable_disable_button(btn_elm, 'enable');
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});
		},

		/**
		 * 	Add new order meta
		 */
		add_new_order_meta:function(title, meta_key, trigger_from_assets)
		{
			if($('.wt_pklist_dc_add_new_sub_item_popup_footer button').attr('disabled')=='disabled') /* not mandatory */
			{
				return false;
			}


			var ajax_data={
				'action':wt_pklist_dc_params.advanced_fields_ajax_hook,
				'_wpnonce':wf_pklist_params.nonces.wf_packlist,
				'wt_pklist_custom_field_btn':1,
				'wt_pklist_custom_field_type':'order_meta',
				'wt_pklist_new_custom_field_title':title,
				'wt_pklist_new_custom_field_key':meta_key,
				'wt_pklist_settings_base':this.template_type,
				/**
				  * 1. shows a warning if item with same meta key was found, otherwise it will update 
				  * 2. Will not add to user selected list 
				  */
				'add_only':1, 
			};
			var action_btns=$('.wt_pklist_dc_add_new_sub_item_popup_footer button');
			pklist_dc.enable_disable_button(action_btns, 'disable');
			$.ajax({
				url:wt_pklist_dc_params.ajax_url,
				data:ajax_data,
				dataType:'json',
            	type: 'POST',
				success:function(data)
				{
					pklist_dc.enable_disable_button(action_btns, 'enable');
					if(data.success==true)
					{						
						if(typeof data.dc_slug!="undefined")
						{
							/** 
							 * add item into assets 
							 */
							var add_new_item_obj=wt_pklist_dc_params.assets_add_new_item[pklist_dc.wfte_name];
							var wfte_name=wt_pklist_dc_params.extra_field_slug_prefix+data.dc_slug; /* current sub item wfte_name */
							var css_class='wfte_'+wfte_name;
							var data_placeholder='[wfte_'+wfte_name+']';
							var sample_html=add_new_item_obj.sample_html;

							wt_pklist_dc_params.assets_elements[pklist_dc.wfte_name][wfte_name]=title; /* add to asset elements list */
							wt_pklist_dc_params.assets_editable_properties[pklist_dc.wfte_name][wfte_name]=add_new_item_obj.editable_property; /* add to asset editable property list */
							
							sample_html=sample_html.replace(/{pklist_dc_custom_meta_css}/g, css_class); /* adding class names based on slug */
							var temp_elm=$('<div />').html(sample_html);
							temp_elm.find('.'+css_class).attr({'data-wfte_name':wfte_name, 'data-wfte_parent':pklist_dc.wfte_name});
							var label_temp_elm=temp_elm.find('.'+css_class+'_label');
							var val_temp_elm=temp_elm.find('.'+css_class+'_val');
							
							
							/* Add to asset design view */
							var asset_elm=pklist_dc.get_asset_visual_elm_by_wfte_name(pklist_dc.wfte_name);
							label_temp_elm.html(title+': ');
							val_temp_elm.html(meta_key);
							asset_elm.append(temp_elm.html());

							/* Add to asset code view */
							var source_asset_elm=pklist_dc.get_asset_source_elm_by_wfte_name(pklist_dc.wfte_name);
							label_temp_elm.html('__['+title+':]__ '); /* translation compatible */
							val_temp_elm.html(data_placeholder);
							source_asset_elm.append(temp_elm.html());

							/* add to sidebar assets section */
							var side_bar_asset_html='<div class="wt_pklist_dc_asset_item wfte_draggable wt_pklist_dc_asset_element_group_sub" title="'+wt_pklist_dc_params.labels.drag_item_tooltip+'" draggable="true" data-slug="'+wfte_name+'">'
													+'<input type="checkbox" class="wt_pklist_dc_asset_element_group_checkbox" checked="checked" data-slug="'+wfte_name+'" data-parent-slug="'+pklist_dc.wfte_name+'">'
													+data.val+'</div>';
							var sidebar_asset_elm=$(side_bar_asset_html).insertBefore('.wt_pklist_dc_assets_add_new_panel[data-slug="'+pklist_dc.wfte_name+'"]');
							pklist_dc.add_asset_delete_btn(sidebar_asset_elm, wfte_name, pklist_dc.wfte_name, meta_key);


							/**
							 * 	Add item into editor (If the user add the item via block property editor panel) 
							 * 
							 */
							if(!trigger_from_assets)
							{
								var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
								var target_elm=sidebar_tab_elm.data('target_elm')
								if(target_elm.length>0) /* if an item was selected */
								{
									temp_elm=pklist_dc.add_dom_element_id(temp_elm);

									/* source code */
									var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm); /* source element */
									source_target_elm.append(temp_elm.html());

									label_temp_elm.html(title+': '); /* remove translation function added for codeview */
									val_temp_elm.html(meta_key); /* remove placeholder and add meta key for preview */
									target_elm.append(temp_elm.html());

									pklist_dc.add_history(); /* save for undo redo */
									pklist_dc.refresh_editable_selected(target_elm);
								}
							}

							wf_popup.hidePopup();
						}else
						{
							wf_notify_msg.error(wt_pklist_dc_params.labels.error);
						}

					}else
					{
						wf_notify_msg.error(data.msg);
					}
				},
				error:function()
				{
					pklist_dc.enable_disable_button(action_btns, 'enable');
					wf_notify_msg.error(wt_pklist_dc_params.labels.error);
				}
			});
		},

		/**
		 * 	Update settings.
		 * 	This method will call when new logo/signature was choosed
		 */
		update_settings:function(option_name, val)
		{ 
			var ajx_data={
				'action': pklist_dc.dc_ajax_hook, 
				'_wpnonce': wt_pklist_dc_params.nonces.dc_nonce, 
				'wt_pklist_dc_action':'update_settings', 
				'template_type':this.template_type, 
				'option_name':option_name, 
				'option_value':val, 
			};
			jQuery.ajax({
				url:wt_pklist_dc_params.ajax_url,  
				data:ajx_data,
            	type:'POST',
				success:function(data)
				{ }
			});
		}
	}

	return pklist_dc_ajax;

})( jQuery );