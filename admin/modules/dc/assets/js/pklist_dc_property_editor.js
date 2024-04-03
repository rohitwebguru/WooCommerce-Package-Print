var pklist_dc_property_editor=(function( $ ) {

	var pklist_dc_property_editor=
	{
		/**
		*	Register events for property editor panel
		*
		*/
		reg_property_editor_events:function()
		{
			/**
			*	Handle button actions. EG: bold, italic
			*/
			$(document).on('click', '.wt_pklist_button_checkbox', function(){
				var elm=$(this);
				var values=elm.attr('data-values');
				if(typeof values=='string')
				{
					var value_arr=values.split('|');
					var cr_val=elm.attr('data-value');
					if(value_arr.length==2 && typeof cr_val!='undefined')
					{
						if(value_arr[0]==cr_val)
						{
							var new_vl=value_arr[1];
						}else
						{
							var new_vl=value_arr[0];
						}
						elm.attr('data-value', new_vl);
						if(new_vl==elm.attr('data-main-value'))
						{
							elm.addClass('button_selected');
						}else
						{
							elm.removeClass('button_selected');
						}
						var target_elm=pklist_dc.get_target_element(elm);
						var prop=elm.attr('data-property');
						target_elm.css(prop, new_vl);

						pklist_dc.source_apply_prop(target_elm, prop, new_vl, 'css'); /* to source code */
						pklist_dc.add_history(); /* save for undo redo */
					}
				}
			});

			/**
			*	Handle group button actions. Eg: Text align, Text decoration
			*/
			$(document).on('click', '.wt_pklist_button_radio', function(){
				var elm=$(this);
				var value=elm.attr('data-value');
				if(!elm.hasClass('button_selected')) /* not already selected */
				{
					var new_vl=elm.attr('data-value');
					var target_elm=pklist_dc.get_target_element(elm);
					var prop=elm.parents('.wt_pklist_dc_sidebar_property_button_radio_group').attr('data-property');					
					elm.addClass('button_selected').siblings().removeClass('button_selected');

					target_elm.css(prop, new_vl);
					pklist_dc.source_apply_prop(target_elm, prop, new_vl, 'css'); /* to source code */
					pklist_dc.add_history(); /* save for undo redo */
				}
			});

			/**
			*	Handle keyup, change action. Eg: HTML, font-size, line-height, font-family
			*/
			$(document).on('keyup change', '.wt_pklist_dc_keyup, .wt_pklist_dc_change', function(){
				pklist_dc.apply_property_to_target_element($(this));
			});

			/**
			* 	This is for handling custom/preset type fields Eg: Invoice date format
			*/
			$(document).on('change', '.wt_pklist_dc_preset_input', function(){
				pklist_dc.apply_property_to_target_element($(this).parents('.wt_pklist_dc_sidebar_property_block').find('.wt_pklist_dc_property_editor_input').val($(this).val()));
			});


			/**
			*	Events for sidebar action buttons
			*	Eg: Sub item delete, Merged sub item split
			*/
			$(document).on('click', '.wt_pklist_dc_sidebar_tabaccord_btn, .wt_pklist_dc_property_editor_other_available_items_add_btn', function(e){
				e.stopPropagation();
				var elm=$(this); 
				var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');

				if(elm.hasClass('wt_pklist_dc_sidebar_tabaccord_hideable'))
				{
					pklist_dc.hide_show_elements(elm, sidebar_tab_elm);

				}else if(elm.hasClass('wt_pklist_dc_sidebar_tabaccord_removable')) /* tab item remove button event */
				{
					pklist_dc.remove_block_subitem(elm, sidebar_tab_elm);

				}else if(elm.hasClass('wt_pklist_dc_property_editor_other_available_items_add_btn')) /* Other item add button event */
				{
					pklist_dc.add_from_available_items(elm, sidebar_tab_elm);

				}else if(elm.hasClass('wt_pklist_dc_sidebar_tabaccord_splitable')) /* Split the merged items */
				{
					pklist_dc.split_merged_item(elm, sidebar_tab_elm);
				}

			});


			/**
			*	Bulk action initiate buttons, on sidebar top panel
			*/
			$(document).on('click', '.wt_pklist_dc_property_editor_top_panel_btn', function(){				
				pklist_dc.initiate_sidebar_bulk_actions($(this));
			});
			
			/**
			*	Bulk action trigger
			*/
			$(document).on('click', '.wt_pklist_dc_sidebar_bulk_action_trigger_btn', function(e){
				pklist_dc.trigger_sidebar_bulk_actions($(this));
			});

			$(document).on('click', '.wt_pklist_dc_four_side_prop_all_sides', function(){
				pklist_dc.toggle_four_side_prop_input($(this));
			});

			this.add_new_subitem_events();
			
		},

		/**
		*	Add new sub item popup, save events
		*/
		add_new_subitem_events:function()
		{
			/* handle click event of add new sub item button on sidebar top panel. This will show a popup with add new form */
			$(document).on('click', '.wt_pklist_dc_property_editor_top_panel_add_new_subitem_btn, .wt_pklist_dc_sidebar_assets_add_new', function(){
				var popup_elm=$('.wt_pklist_dc_add_new_sub_item');
				popup_elm.find('.wt_pklist_dc_add_new_sub_item_popup_title').html($(this).attr('title'));
				var trigger_from_assets=false;

				if($(this).hasClass('wt_pklist_dc_sidebar_assets_add_new')) /* from assets section */
				{
					pklist_dc.wfte_name=$(this).attr('data-slug');
					trigger_from_assets=true;
				}

				$('.wt_pklist_dc_add_new_sub_item_submit_btn').data('trigger_from_assets', trigger_from_assets);

				if(wt_pklist_dc_params.assets_add_new_item.hasOwnProperty(pklist_dc.wfte_name))
				{
					popup_elm.find('.wt_pklist_dc_add_new_sub_item_popup_content').html(wt_pklist_dc_params.assets_add_new_item[pklist_dc.wfte_name].html);
					
				}
				wf_popup.showPopup(popup_elm);
				popup_elm.find('.wt_pklist_dc_add_new_sub_item_popup_content input:eq(0)').select();
			});


			/**
			*	Add new sub item save event
			*/
			$(document).on('click', '.wt_pklist_dc_add_new_sub_item_submit_btn', function(){

				var empty_fields=0;
				var title='';	 /* title Eg: column title for product table */			
				var value='';	 /* value Eg: column value for product table */	
				var display_value='';	 /* preview value  for design view */	
				var value_type=''; /* Value Eg: Meta, Filter */	
				var value_data=''; /* Value data Eg: If Meta then meta key */


				/**
				*	Validate
				*/
				$('.wt_pklist_dc_add_new_sub_item_popup_content').find('input:visible, select:visible').each(function(){
					var vl=$(this).val().trim();
					if(vl=="")
					{
						empty_fields++;
					}else
					{
						if($(this).hasClass('wt_pklist_dc_property_editor_add_new_item_title'))
						{
							title=vl;
						}else if($(this).hasClass('wt_pklist_dc_property_editor_add_new_item_value'))
						{
							value=vl;
							if($(this).prop('tagName').toLowerCase()=='select')
							{
								display_value=$(this).find('option:selected').text();
							}else
							{
								display_value=vl;
							}

							var vl_arr=vl.split('_');
							if(vl_arr[0]=='custom') /* custom value type. Eg: filter meta, Not applicable for Add new meta section */
							{
								value_type=vl;
							}else
							{
								value_type='default_column';
							}
						}else if($(this).hasClass('wt_pklist_dc_property_editor_add_new_item_value_data'))
						{
							value_data=vl;
							/* cleanup the value data */
							var regex=/\s\s+/g;
							value_data= value_data.replace(regex, ' '); /* remove continues spaces to single space */

							regex = /[^a-zA-Z0-9_-\s]/g;
							value_data= value_data.replace(regex, '').trim(); /* removes not allowed characters */
							if(value_data=="")
							{
								empty_fields++;
							}
						}
					}
				});


				if(empty_fields>0)
				{
					$('.wt_pklist_dc_add_new_sub_item_wrn').show().html(wt_pklist_dc_params.labels.all_fields_mandatory);
				}else
				{
					$('.wt_pklist_dc_add_new_sub_item_wrn').hide(); /* hide error message if shown */

					var trigger_from_assets=$(this).data('trigger_from_assets');

					if(wt_pklist_dc_params.assets_add_new_item.hasOwnProperty(pklist_dc.wfte_name) && wt_pklist_dc_params.assets_add_new_item[pklist_dc.wfte_name].hasOwnProperty('sample_html'))
					{
						if(pklist_dc.wfte_name=='invoice_data')
						{
							pklist_dc.add_new_order_meta(title, value, trigger_from_assets);	
						}else
						{
							var sample_html=wt_pklist_dc_params.assets_add_new_item[pklist_dc.wfte_name].sample_html;
							var temp_elm=$('<div />').html(sample_html);
							var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
							var target_elm=sidebar_tab_elm.data('target_elm');
							if(target_elm.length>0)
							{
								var col_type=(value_data=="" ? value : value_type+'_'+value_data);
								var placeholder='wfte_'+pklist_dc.wfte_name+'_'+col_type;		
								var wfte_name=pklist_dc.wfte_name+'_'+col_type.replace(/\s+/g, '');	

								var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm); /* source element */

								if(pklist_dc.wfte_name=='product_table') /* add custom column */
								{
									temp_elm.find('th').addClass('wfte_product_table_head_'+col_type).attr({'data-wfte_name':wfte_name, 'data-wfte_parent':pklist_dc.wfte_name, 'col-type':col_type, 'data-value':value, 'data-value-type':value_type, 'data-value-data':value_data}).html(title);								
									temp_elm=pklist_dc.add_dom_element_id(temp_elm);
									var style_data=target_elm.find('tr:eq(0) th:last-child').attr('style');
									temp_elm.find('th').attr('style', style_data);

									target_elm.find('tr:eq(0)').append(temp_elm.html());
									target_elm.find('tr').not(':eq(0)').append('<td>'+(value_data=="" ? display_value : value_data)+'</td>');
									pklist_dc.update_td_right_column_css_class(target_elm, 'th');

									/* source code */
									temp_elm.find('th').html('__['+title+']__');
									source_target_elm.find('tr:eq(0)').append(temp_elm.html());	
									pklist_dc.update_td_right_column_css_class(source_target_elm, 'th');							

								}else if(pklist_dc.wfte_name=='payment_summary_table')
								{
									temp_elm.find('tr').addClass('wfte_'+wfte_name).attr({'data-wfte_name':wfte_name, 'data-wfte_parent':pklist_dc.wfte_name});
									temp_elm.find('td:first').html(title).addClass('wfte_'+wfte_name+'_label');
									if(value_type=='custom_filter')
									{
										/* we have to add the square bracket for placeholder, because the above value_data sanitization section will remove the brackets */
										temp_elm.find('td:last').html('['+value_data+']');
										placeholder=value_data;
									}else
									{
										temp_elm.find('td:last')
										.attr({'col-type':col_type, 'data-value':value, 'data-value-type':value_type, 'data-value-data':value_data})
										.html((value_data=="" ? display_value : value_data));	
									}
									temp_elm=pklist_dc.add_dom_element_id(temp_elm);

									target_elm.find('tr:first').before(temp_elm.html());

									/* source code */
									temp_elm.find('td:first').html('__['+title+']__');
									temp_elm.find('td:last').html('['+placeholder+']');
									source_target_elm.find('tr:first').before(temp_elm.html());									
								}
								temp_elm.remove(); 
								pklist_dc.add_history(); /* save for undo redo */

								pklist_dc.refresh_editable_selected(target_elm);
								wf_popup.hidePopup();
							}
						}					
					}
				}

			});
		},

		/**
		*	Trigger the bulk actions
		*	Eg: Delete, Merge
		*/
		trigger_sidebar_bulk_actions:function(elm)
		{
			var action=elm.attr('data-action');
			if(action=='cancel')
			{
				pklist_dc.reset_sidebar_bulk_action_panel();
				return false;
			}
			var check_boxes=$('.wt_pklist_dc_sidebar_tabaccord_checkbox[data-'+action+'] input[type="checkbox"]:checked');
			if(check_boxes.length==0)
			{
				alert(wt_pklist_dc_params.labels.please_select_one);
				return false;
			}else
			{
				var editable_elm=null;
				if(action=='removable')
				{					
					check_boxes.each(function(){
						var target_elm=pklist_dc.get_target_element($(this));
						
						if(!editable_elm) /* take only once */
						{
							/* for refreshing the sidebar */
							editable_elm=pklist_dc.get_editable_item(target_elm);
						}
						
						var source_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm); /* source element */
						source_elm.remove();
						target_elm.remove();
					});					

				}else if(action=='mergable')
				{
					var temp_elm=$('<span />'); /* we are using span for merged groups */
					var source_temp_elm=$('<span />');

					var loop_inc=1; /* loop increment value to find first item */
					var total_items=check_boxes.length;
					
					check_boxes.each(function(){							
						var elm=$(this);
						var tab_accord_elm=elm.parents('.wt_pklist_dc_sidebar_tabaccord');
						var target_elm=tab_accord_elm.data('target_elm');
						var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm); /* for source code */
						var sub_wfte_name=target_elm.attr('data-wfte_name');

						if(pklist_dc.is_merged_group_wfte_name(sub_wfte_name)) /* current item is already a merged one, then split and add it to the newly creating item */
						{
							var sub_elm_wfte_name_arr=sub_wfte_name.split("=");
							var sub_item_inc=1;
							var total_sub_items=sub_elm_wfte_name_arr.length;
							$.each(sub_elm_wfte_name_arr, function(indx, sub_elm_wfte_name){
																
								/* for visual element */
								var target_sub_elm=target_elm.find('[data-wfte_name="'+sub_elm_wfte_name+'"]');
								target_sub_elm.appendTo(temp_elm);
								pklist_dc.process_mergeable_item_html(target_sub_elm, action, (total_items==loop_inc && total_sub_items==sub_item_inc));

								/* for source element */
								var source_target_sub_elm=pklist_dc.get_source_elm_from_visual_elm(target_sub_elm);
								source_target_sub_elm.appendTo(source_temp_elm);
								pklist_dc.process_mergeable_item_html(source_target_sub_elm, action, (total_items==loop_inc && total_sub_items==sub_item_inc));

								sub_item_inc++;
							});

							if(loop_inc==1) /* if this is the first item then replace it */
							{
								target_elm.replaceWith(temp_elm);
								source_target_elm.replaceWith(source_temp_elm); /* for source code */
							}else
							{
								/* Not first item, then remove it. (this only a blank group item) */
								target_elm.remove();
								source_target_elm.remove(); /* for source code */
							}

						}else
						{
							if(loop_inc==1)
							{
								/* for visual element */
								target_elm.wrap('<span class="wfte_temp_merg_container"></span>'); /* here we are adding a placholder to existing element */
								target_elm.appendTo(temp_elm); /* move the element to temp element and keep the placeholder there */
								$('.wfte_temp_merg_container').replaceWith(temp_elm); /* replace the placeholder with temp element */

								/* for source element */
								source_target_elm.wrap('<span class="wfte_temp_merg_container"></span>');
								source_target_elm.appendTo(source_temp_elm);
								$('.wfte_temp_merg_container').replaceWith(source_temp_elm);
							}else
							{
								target_elm.appendTo(temp_elm);
								source_target_elm.appendTo(source_temp_elm); /* for source code */
							}
							pklist_dc.process_mergeable_item_html(target_elm, action, (total_items==loop_inc));
							pklist_dc.process_mergeable_item_html(source_target_elm, action, (total_items==loop_inc)); /* for source code */

						}
						tab_accord_elm.remove(); /* remove the sidebar item */							
						loop_inc++;	
					});

					temp_elm.append('<br>');
					source_temp_elm.append('<br>');
					this.add_dom_element_id(temp_elm, source_temp_elm);

					var editable_elm=temp_elm;
				}
				pklist_dc.add_history(); /* save for undo redo */
				pklist_dc.refresh_editable_selected(editable_elm);

			}
		},
		
		/**
		*	This function will show checkbox for bulk action and action trigger buttons
		*/
		initiate_sidebar_bulk_actions:function(elm)
		{
			pklist_dc.sidebar_accordian_close_all();
			var action=elm.attr('data-action');
			if(typeof action!='undefined')
			{	
				var checkboxes=$('.wt_pklist_dc_sidebar_tabaccord:visible .wt_pklist_dc_sidebar_tabaccord_checkbox[data-'+action+']');
				if(checkboxes.length==0) /* added a visiblity check to exclude other items block */
				{
					alert(wt_pklist_dc_params.labels.bulk_action_no_items_found);
					return false;
				}

				if(checkboxes.is(':visible')) /* if checkboxes are visible then hide it */
				{
					pklist_dc.reset_sidebar_bulk_action_panel();
					return false;
				}

				checkboxes.show().find('input[type="checkbox"]').prop('checked', false);
				
				var action_btn_title='';
				if(action=='removable')
				{
					var action_btn_title=wt_pklist_dc_params.labels.delete;

				}else if(action=='mergable')
				{
					var action_btn_title=wt_pklist_dc_params.labels.merge;
				}

				var html='<div class="wt_pklist_dc_btn wt_pklist_dc_btn_primary wt_pklist_dc_sidebar_bulk_action_trigger_btn" data-action="'+action+'" style="float:right;">'+action_btn_title+'</div>'
						+'<div class="wt_pklist_dc_btn wt_pklist_dc_btn_secondary wt_pklist_dc_sidebar_bulk_action_trigger_btn" data-action="cancel" style="float:right;">'+wt_pklist_dc_params.labels.cancel+'</div>';
				$('.wt_pklist_dc_property_editor_bottom_panel').html(html).show();
			}
		},

		/**
		*	Split items in a merged item
		*	Eg: Merged sub item in address block
		*/
		split_merged_item:function(elm, sidebar_tab_elm)
		{
			var target_elm=pklist_dc.get_target_element(elm);
			var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm);
			var sub_elm_wfte_name=target_elm.attr('data-wfte_name');
			if(pklist_dc.is_merged_group_wfte_name(sub_elm_wfte_name)) 
			{
				var sub_elm_wfte_name_arr=sub_elm_wfte_name.split("=");
				var html='';
				var source_html='';
				$.each(sub_elm_wfte_name_arr, function(indx, sub_elm_wfte_name){
					
					var sub_elm=target_elm.find('[data-wfte_name="'+sub_elm_wfte_name+'"]');
					if(sub_elm.length>0)
					{
						/* for visual element */
						pklist_dc.process_mergeable_item_html(sub_elm, 'split');
						html+=sub_elm[0].outerHTML;

						/* for source element */
						var source_sub_elm=pklist_dc.get_source_elm_from_visual_elm(sub_elm);
						pklist_dc.process_mergeable_item_html(source_sub_elm, 'split');
						source_html+=source_sub_elm[0].outerHTML;						
					}			
				});
				var editable_elm=pklist_dc.get_editable_item(target_elm);
				target_elm.replaceWith(html);
				source_target_elm.replaceWith(source_html); /* for source code */
				pklist_dc.add_history(); /* save for undo redo */
				pklist_dc.refresh_editable_selected(editable_elm);
			}
		},

		/**
		*	Add sub item from available item
		*/
		add_from_available_items:function(elm, sidebar_tab_elm)
		{
			var editor_elm=elm.siblings('.wt_pklist_dc_sidebar_tabaccord');
			var asset_elm=editor_elm.data('target_elm');
			var target_elm=asset_elm.clone(); /* clone of sub item to add */
			var source_target_elm=this.get_source_asset_sub_elm_from_visual_asset_sub_elm(target_elm).clone(); /* source element */
			if(source_target_elm.length==0)
			{
				alert(wt_pklist_dc_params.labels.unable_to_locate_source_elm);
				return false;
			}
			
			var target_parent_elm=sidebar_tab_elm.data('target_elm');
			var source_target_parent_elm=this.get_source_elm_from_visual_elm(target_parent_elm); /* source element */

			/* some elements have value container elements, by default it was the main element itself */
			var target_elm_conainer=target_parent_elm; 
			var source_target_elm_conainer=source_target_parent_elm; /* for source code */

			if(target_parent_elm.find('.wfte_'+pklist_dc.wfte_name+'_val').length>0) /* some elements have value containers. Eg Address fields. */
			{
				target_elm_conainer=target_parent_elm.find('.wfte_'+pklist_dc.wfte_name+'_val');
				source_target_elm_conainer=source_target_parent_elm.find('.wfte_'+pklist_dc.wfte_name+'_val');	 /* for source code */ 		
			}

			if($.inArray('mergable', pklist_dc.editable_item_arr[target_elm.attr('data-wfte_name')])!=-1) /* mergable item, so may be need to clean the item html. */
			{
				pklist_dc.process_mergeable_item_html(target_elm, 'split');
				pklist_dc.process_mergeable_item_html(source_target_elm, 'split'); /* for source code */
			}

			target_elm=pklist_dc.add_dom_element_id(target_elm, source_target_elm); /* add dom ID */

			/* add new element to the design view */
			if(typeof elm.attr('data-prependable')!='undefined')
			{
				/* here we are not using container elements to prepend */
				target_elm=target_elm.prependTo(target_parent_elm);
				source_target_elm=source_target_elm.prependTo(source_target_parent_elm);
				
			}else
			{
				if(target_elm_conainer.prop('tagName').toLowerCase()=='table')
				{
					var tag_name=target_elm.prop('tagName').toLowerCase();
					if(tag_name=='td' || tag_name=='th')
					{
						pklist_dc.add_td_to_table(target_elm_conainer, target_elm, tag_name);

						/* for source code */
						pklist_dc.add_td_to_table(source_target_elm_conainer, source_target_elm, tag_name);


						/* add blank column to other rows */
						var asset_elm_ind=asset_elm.index();
						var asset_elm_parent=asset_elm.parents('table');
						var p=1;

						asset_elm_parent.find('tr').not(':eq(0)').each(function(){
							var td_html='<td></td>';
							if($(this).find('td:eq('+asset_elm_ind+')').length>0)
							{
								td_html=$(this).find('td:eq('+asset_elm_ind+')')[0].outerHTML;
							}
							target_elm_conainer.find('tr:eq('+p+')').append(td_html);
							p++;
						});

					}else
					{
						target_elm.appendTo(target_elm_conainer);
						source_target_elm.appendTo(source_target_elm_conainer); /* for source code */
					}

				}else
				{
					target_elm.appendTo(target_elm_conainer);

					source_target_elm.appendTo(source_target_elm_conainer); /* for source code */					
				}
			}

			pklist_dc.add_history(); /* save for undo redo */
			pklist_dc.refresh_editable_selected(target_parent_elm);

		},

		/**
		 * 	Add TD/TH to table row
		 */
		add_td_to_table:function(parent_table, target_elm, tag_name)
		{
			var first_tr=parent_table.find('tr').eq(0);
			var style_data=first_tr.find('th:last-child').attr('style');
			target_elm.attr('style', style_data);
			
			target_elm.appendTo(first_tr);
			this.update_td_right_column_css_class(parent_table, tag_name);
		},

		update_td_right_column_css_class:function(parent_table, tag_name)
		{
			var first_tr=parent_table.find('tr').eq(0);
			first_tr.find(tag_name).removeClass('wfte_right_column');
			first_tr.find(tag_name+':last-child').addClass('wfte_right_column');
		},

		/**
		*	Delete button action for sidebar sub item
		*/
		remove_block_subitem:function(elm, sidebar_tab_elm)
		{
			var target_elm=pklist_dc.get_target_element(elm);
			var tag_name=target_elm.prop('tagName').toLowerCase();

			/* source element */
			var source_target_elm=this.get_source_elm_from_visual_elm(target_elm);
			if(tag_name=='td' || tag_name=='th') /* taking the parent table before deleting, for updating the rght column css class */
			{
				var source_parent_table=source_target_elm.parents('table');
			}
			source_target_elm.remove();

			var editable_elm=pklist_dc.get_editable_item(target_elm);

			/* removes the item from design view and current editor item */			
			if(tag_name=='td' || tag_name=='th')
			{
				var ind=target_elm.index();
				var parent_table=target_elm.parents('table');
				parent_table.find('tr').each(function(){
					$(this).find('td, th').eq(ind).remove();
				});

				pklist_dc.update_td_right_column_css_class(parent_table, tag_name);
				pklist_dc.update_td_right_column_css_class(source_parent_table, tag_name);

			}else
			{
				target_elm.remove();
			}
			pklist_dc.add_history(); /* save for undo redo */
			pklist_dc.refresh_editable_selected(editable_elm);
		},

		/**
		*	Toggle the visibility of hideable elements
		*	Eg: Company info, Signature
		*/
		hide_show_elements:function(elm, sidebar_tab_elm)
		{
			var target_elm=pklist_dc.get_target_element(elm);
					
			/* a small adjustment for specific image elements */
			var wfte_name=elm.attr('data-target_wfte_name');
			var wfte_name_arr=wfte_name.split('_');
			if(wfte_name_arr[wfte_name_arr.length-1]=='img') /* specific image element */
			{
				var target_parent_elm=target_elm.parents('.wfte_'+wfte_name+'_box');
				if(target_parent_elm.length==1)
				{
					target_elm=target_parent_elm;
				}
			}

			this.source_hide_show(target_elm); /* applying on source code */

			if(target_elm.is(':visible'))
			{
				target_elm.addClass('wfte_hidden');
				elm.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
			}else
			{
				target_elm.removeClass('wfte_hidden').show();
				elm.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
			}
			pklist_dc.add_history(); /* save for undo redo */
		},

		/**
		 * 	File uploader related events
		 */
		file_uploader_events:function()
		{
			$(document).on('click', '.wt_pklist_dc_file_uploader_btn', function(e){
				e.preventDefault();
				if($(this).data('file_frame'))
				{
					
				}else
				{
					/* Create the media frame. */
					var file_frame = wp.media.frames.file_frame = wp.media({
						title: $( this ).data( 'invoice_uploader_title' ),
						button: {
							text: $( this ).data( 'invoice_uploader_button_text' ),
						},
						/* Set to true to allow multiple files to be selected */
						multiple: false
					});
					$(this).data('file_frame', file_frame);
					//var wf_file_target=jQuery(this).attr('wf_file_attacher_target');
					var elm=$(this);

					/* When an image is selected, run a callback. */
					jQuery(this).data('file_frame').on( 'select', function() {
						/* We set multiple to false so only get one image from the uploader */
						var attachment =file_frame.state().get('selection').first().toJSON();
						if(!attachment.url.match(/.(jpg|jpeg|png|gif)$/i))
						{
							wf_notify_msg.error(wt_pklist_dc_params.labels.image_files_only);
						}else
						{
							var target_elm=pklist_dc.get_target_element(elm);
	                        target_elm.attr('src', attachment.url);

	                        var editable_elm=pklist_dc.get_editable_item(target_elm); /* refresh the property editor to apply new changes */
	                        pklist_dc.refresh_editable_selected(editable_elm);
	                        
	                        /* 
	                        *	no need to update this in source code
	                        * 	no need to create new history entry. 
	                        */

	                        /* update in all existing history entries. because this is a global change */
	                        pklist_dc.update_to_all_history_preview(target_elm.attr('data-wfte_name'), 'src', attachment.url);

	                        var option_name=''; /* just hardcode the option names here */
	                        if(pklist_dc.wfte_name=='company_logo')
	                        {
	                        	option_name='woocommerce_wf_packinglist_logo';
	                        }else if(pklist_dc.wfte_name=='signature')
	                        {
	                        	option_name='woocommerce_wf_packinglist_invoice_signature';
	                        }

	                        pklist_dc.update_settings(option_name, attachment.url); /* update new image to the settings */
						}
					});
					/* Finally, open the modal	*/			
				}
				$(this).data('file_frame').open();
			});
		},

		/**
		*	Color picker related events
		*/
		set_color_picker_events:function()
		{
			$(document).on('click', '.wt_pklist_dc_sidebar_tabcontent .wt_pklist_dc_color_picker_input, .wt_pklist_dc_sidebar_tabcontent .wt_pklist_dc_color_preview', function(){
	            var elm=$(this);
	            if(elm.hasClass('wt_pklist_dc_color_preview'))
	            {
	                elm=elm.siblings('.wt_pklist_dc_color_picker_input');
	            }

	            if(elm.siblings('.iris-picker').length>0 && elm.siblings('.iris-picker').is(':visible'))
	            {
	            	elm.iris('hide');
	            	return false;
	            }

	            if(elm.attr('data-color_picker_enabled')!=1)
	            {
	                elm.iris({
	                    change: function(event, ui) {
	                        var elm=$(event.target);
	                        var prev_elm=elm.siblings('.wt_pklist_dc_color_preview');
	                        var color_vl=ui.color.toString();
	                        prev_elm.css({'background': color_vl});

	                        elm.val(color_vl);
	                        pklist_dc.apply_property_to_target_element(elm);
	                    }
	                });
	                elm.attr('data-color_picker_enabled', 1);
	            }

	            $('.wt_pklist_dc_color_picker_input[data-color_picker_enabled="1"]').iris('hide');
	            elm.iris('show');
	            setTimeout(function(){          
	                var picker_box=elm.siblings('.iris-picker');
	                if(picker_box.find('.wt_pklist_dc_color_picker_close').length==0)
	                {
	                    var h=parseInt(picker_box.height());
	                    picker_box.css({'height':h+30}); 
	                    picker_box.find('.iris-picker-inner').append('<div class="wt_pklist_dc_color_picker_close">Close</div>');
	                    picker_box.find('.wt_pklist_dc_color_picker_close').data('target_input', elm);
	                }
	            }, 200);
	            
	        });
	        
	        $(document).on('click', '.wt_pklist_dc_color_picker_close', function(){
	            $(this).data('target_input').iris('hide');
	        });
	        $(document).on('click', '.wt_pklist_dc_sidebar_tabaccord_content, .wt_pklist_dc_sidebar_tabaccord_hd', function(e){
	            e.stopPropagation();
	            if($(e.target).hasClass('wt_pklist_dc_color_picker') || $(e.target).parents('.wt_pklist_dc_color_picker').length>0)
	            {
	            	return false;
	            }
	            $('.iris-picker').hide();
	        });
		},

		/**
		*	On merging the items needs a comma between them and a line break on last element.
		*	On splitting we have to remove the comma and need to add a line break
		*/
		process_mergeable_item_html:function(target_elm, action, last_item)
		{
			if(action=='mergable') /* merging */
			{
				target_elm.find('br').remove();
				var target_elm_html=target_elm.html().trim();
				if(last_item)
				{
					if(target_elm_html.slice(-1)==',')
					{
						target_elm_html=target_elm_html.slice(0, -1);
						target_elm.html(target_elm_html);
					}
				}else
				{
					if(target_elm_html.slice(-1)!=',')
					{
						target_elm.append(', ');
					}
				}
			}else
			{
				var target_elm_html=target_elm.html().trim();
				if(target_elm_html.slice(-1)==',') /* if the item is meregd with another item so may be chance for a comma */
				{
					target_elm_html=target_elm_html.slice(0, -1);
					target_elm.html(target_elm_html);
				}
				if(target_elm.find('br').length==0)
				{
					target_elm.append('<br>');
				}
			}
		},

		/**
		*	Apply changes to the selected element
		*/
		apply_property_to_target_element:function(elm)
		{
			var target_elm=pklist_dc.get_target_element(elm);
			var property_block=elm.parents('.wt_pklist_dc_sidebar_property_block');
			var prop=property_block.attr('data-property');
			var prop_main=prop;
			var wfte_name=target_elm.attr('data-wfte_name');
			var property_slug='';

			var is_label_prop=false;
			var prop_ar=prop.split('-');
			if(prop_ar[0]=='label') /* this property for label element */
			{			
				target_elm=target_elm.find('.wfte_'+wfte_name+'_label');
				prop_ar.shift();
				prop=prop_ar.join('-');	
				is_label_prop=true;			
			}
 
			var source_target_elm=target_elm; /* in some cases we have to input the main target element to source code method. Eg: table, thead etc */

			var tag_name=target_elm.prop('tagName').toLowerCase();
			if(tag_name=='table' || tag_name=='tbody' || tag_name=='thead' || tag_name=='tr')
			{
				target_elm=target_elm.find('td, th');
			}

			/**
			*	If the current property is a four side property then we have to prepare the property name for each side
			*/
			if(elm.hasClass('wt_pklist_dc_four_side_prop'))
			{
				if(elm.hasClass('wt_pklist_dc_four_side_prop_sub'))
				{
					var property_slug=property_block.attr('data-property-slug');
					if(typeof property_slug=='undefined' || property_slug=='') /* some properties need placeholders to replace side name */
					{
						prop=prop+'-%s';
					}else
					{
						prop=property_slug;
					}
					prop=prop.replace('%s', elm.attr('data-side')); /* replace property with current side name */

					var side_values=[];
					/* check all sub inputs have same value */
					property_block.find('.wt_pklist_dc_four_side_prop_sub').each(function(){
						var vl=$(this).val().trim();
						if($.inArray(vl, side_values)==-1)
						{
							side_values.push(vl);
						}
					});

					if(side_values.length==1) /* same values */
					{
						property_block.find('.wt_pklist_dc_four_side_prop_main').val(side_values[0]);
					}else
					{
						property_block.find('.wt_pklist_dc_four_side_prop_main').val(''); /* reset the main input value */
					}
				}else /* main property element */
				{
					/* apply value to all sub inputs */
					property_block.find('.wt_pklist_dc_four_side_prop_sub').val(elm.val());
				}
			}

			var prop_arr=prop.split('-');
			if(prop_arr[0]=='attr') /*  attribute not CSS */
			{
				var elm_vl=elm.val();
				var property_slug=property_block.attr('data-property-slug');
				if(typeof property_slug!=='undefined' && property_slug.trim()!='')
				{
					prop=property_slug.replace('%s', 'data-'+wfte_name);
				}
				
				pklist_dc.apply_value_to_preset_input(property_block, elm_vl);

				/* apply the value to attribute */
				prop=prop.substr(5); /* remove attr- prefix */

				target_elm.attr(prop, elm_vl);
				pklist_dc.source_apply_prop(source_target_elm, prop, new_vl, 'attr');  /* to source code */

				if(prop_main.substr(5)=='date-format') /* need a preview */
				{
					var preview_vl=wt_pklist_dc_php_date(elm_vl);
					if(target_elm.find('.wfte_'+wfte_name+'_val').length>0)
					{
						target_elm.find('.wfte_'+wfte_name+'_val').html(preview_vl);
					}else
					{
						target_elm.html(preview_vl);
					}
				}

			}else
			{	
				if(prop=='html' || prop=='text')
				{
					var new_html=this.nl2br(elm.val());
					if(target_elm.find('.wt_pklist_dc_item_delete').length>0)
					{
						new_html=target_elm.find('.wt_pklist_dc_item_delete')[0].outerHTML+new_html;
					}
					target_elm.html(new_html);
					pklist_dc.source_apply_prop(source_target_elm, prop, new_html, 'html');  /* to source code */

					if(is_label_prop)
					{
						var tab_accord_elm=elm.parents('.wt_pklist_dc_sidebar_tabaccord');
						var item_title=this.trim_item_title(new_html);
						tab_accord_elm.find('.wt_pklist_dc_sidebar_tabaccord_hd_title').text(item_title);
						tab_accord_elm.find('.wt_pklist_dc_sidebar_tabaccord_hd').attr('title', item_title);
					}

				}else 
				{
					var vl=elm.val();
					if(prop=='border-radius')
					{
						target_elm.css({'border-top-left-radius':vl, 'border-top-right-radius':vl, 'border-bottom-left-radius':vl, 'border-bottom-right-radius':vl});
						
					}else if(prop=='border-color')
					{
						target_elm.css({'border-top-color':vl, 'border-right-color':vl, 'border-bottom-color':vl, 'border-left-color':vl});						
					}
					else if(prop=='rotate')
					{
						var transform_vl='';
						if(vl=="")
						{
							vl='0deg';
						}else
						{
							vl+='deg';
						}
						vl='rotate('+vl+')';
						prop='transform';
						target_elm.css({'transform':vl});

					}else
					{
						if(prop=='border-style')
						{
							/* this will prevent unnecessary border applying on border type change if border width is zero */
							$('.wt_pklist_dc_sidebar .wt_pklist_dc_sidebar_property_block[data-property-slug="border-%s-width"] .wt_pklist_dc_four_side_prop_sub').each(function(){
								var side=$(this).attr('data-side');
								var side_width=$(this).val();
								var side_prop='border-'+side+'-width';
								target_elm.css(side_prop, side_width);
								pklist_dc.source_apply_prop(source_target_elm, side_prop, side_width, 'css');  /* to source code */
							});
						}
						target_elm.css(prop, vl);
					}
					pklist_dc.source_apply_prop(source_target_elm, prop, vl, 'css');  /* to source code */
				}
			}

			var editable_elm=this.get_editable_item(target_elm);
			if(editable_elm)
			{
				this.set_elm_delete_btn(editable_elm); /* this is necessary for some properties. Eg: padding, width */
			}

			pklist_dc.add_history(); /* save for undo redo */
		},

		/**
		*	If the current input is custom/preset type
		*	Eg: Date format
		*/
		apply_value_to_preset_input:function(property_block, elm_vl)
		{
			if(property_block.find('.wt_pklist_dc_preset_input').length>0) /* this is a custom/preset type input so check the current value is a preset value */
			{
				if(property_block.find('.wt_pklist_dc_preset_input option[value="'+elm_vl+'"]').length>0)
				{
					property_block.find('.wt_pklist_dc_preset_input').val(elm_vl);
				}else
				{
					property_block.find('.wt_pklist_dc_preset_input').val('');
				}
			}
		},

		/**
		*	Show property editing options on right sidebar
		*/
		set_property_editor:function(elm)
		{
			var sidebar_tab_elm=this.get_sidebar_tab_element("wt-pklist-dc-sidebar-block");
			if(elm.hasClass('wt_pklist_refresh_editor'))
			{
				/* On refresh: no need to open the tab again, and also in internal refresh, need to stay on the existing tab. (Eg: Deleting extra meta from assets) */
			}else
			{
				this.show_sidebar_tab_by_key("wt-pklist-dc-sidebar-block");
			}
			
			sidebar_tab_elm.html('').data('target_elm', null); /* remove all existing elements and reset target element */

			/* identifier for the element */
			var wfte_name=typeof elm.attr('data-wfte_name')!='undefined' ? elm.attr('data-wfte_name') : '';
			
			if(wt_pklist_dc_params.assets_editable_properties.hasOwnProperty(wfte_name)) /* editable properties declared */
			{			
				var assets_title=(wt_pklist_dc_params.assets_titles.hasOwnProperty(wfte_name) ? wt_pklist_dc_params.assets_titles[wfte_name] : '..');

				sidebar_tab_elm.data('target_elm', elm); /* setting current main element as target element */

				/* sidebar top/bottom action panels */
				sidebar_tab_elm.html('<div class="wt_pklist_dc_sidebar_top_panel wt_pklist_dc_property_editor_top_panel"><div class="wt_pklist_dc_property_editor_top_panel_title">'+assets_title+'</div></div><div class="wt_pklist_dc_property_editor_bottom_panel"></div>');

				var editable_item_arr=this.get_assets_editable_properties(wfte_name);				

				var assets_elements_arr=this.get_assets_elements(wfte_name);

				/* store values for future use */
				this.assets_elements_arr=assets_elements_arr;
				this.editable_item_arr=editable_item_arr; 
				this.property_editor_messages=this.get_property_editor_messages(wfte_name); 
				this.wfte_name=wfte_name;
				this.is_editable_tbody_found=false; 

				/* check parent element has editable properties */
				if(editable_item_arr.hasOwnProperty(wfte_name))
				{
					this.prepare_property_editor_blocks(elm, editable_item_arr[wfte_name], assets_elements_arr, wfte_name);
				}

				var is_sortable_items_available=false;
				var bulk_actions=[]; /* bulk actions: merge item, delete item */
				var available_items=[]; /* already added sub items in the block */
				var merged_group_item_arr=[]; /* already added merged item group. In this array we are saving wfte_id of merged group item */

				/**
				*	If editable tbody exists for a table element. Then we need to add it on just below the head section. Not after all head columns. This will give a better sorting experience
				*/
				if(elm.prop('tagName').toLowerCase()=='table')
				{
					var tbody_elm=elm.find('tbody');
					if(tbody_elm.length>0)
					{
						var tbody_wfte_name=typeof tbody_elm.attr('data-wfte_name')!='undefined' ? tbody_elm.attr('data-wfte_name') : '';
						if(tbody_wfte_name!="" && editable_item_arr.hasOwnProperty(tbody_wfte_name)) /* editable tbody exists */
						{
							this.is_editable_tbody_found=true;
						}
					}				
				}

				/* checking on child elements, if exists */
				elm.find('[data-wfte_parent="'+wfte_name+'"]').each(function(){ 
					var is_prepare_property_editor_blocks=true;
					var sub_elm=$(this);

					/**
					* 	In old customizer the items are hided instead of removing. But here we will remove the item 
					*	Checks the item is hidden and not a hideable item
					*/
					if(!sub_elm.is(':visible') && sub_elm.attr('data-wfte_hideable')!=1) 
					{
						var hidden_elm_tagname=sub_elm.prop('tagName').toLowerCase();
						if(hidden_elm_tagname=='th' || hidden_elm_tagname=='td')
						{
							var hidden_elm_index=sub_elm.index();
							sub_elm.parents('table').find('tr td:eq('+hidden_elm_index+'), tr th:eq('+hidden_elm_index+')').remove();
						}
						sub_elm.remove();
						return;
					}

					var sub_elm_wfte_name=typeof sub_elm.attr('data-wfte_name')!='undefined' ? sub_elm.attr('data-wfte_name') : '';

					if(sub_elm_wfte_name!="" && $.inArray(sub_elm_wfte_name, available_items)==-1) /* add an editable section */
					{
						if(!editable_item_arr.hasOwnProperty(sub_elm_wfte_name))
						{
							if(pklist_dc.is_merged_group_wfte_name(sub_elm_wfte_name)) /* check this is a merged group item */
							{
								/* no need to check merged group elements, becuase these elements will be checked by their child elements */
								return;
							}
							if(wt_pklist_dc_params.assets_add_new_item.hasOwnProperty(wfte_name)) /* add new item option available */
							{
								if(wt_pklist_dc_params.assets_add_new_item[wfte_name].hasOwnProperty('editable_property'))
								{
									var sub_editable_item_arr=wt_pklist_dc_params.assets_add_new_item[wfte_name].editable_property; 
								}else
								{
									return;
								}
							}else
							{
								return;	
							}
						}else
						{
							var sub_editable_item_arr=editable_item_arr[sub_elm_wfte_name];
						}
						
						if($.inArray('mergable', sub_editable_item_arr)!=-1) /* mergable item, so check the current item is merged with any other */
						{
							if($.inArray('mergable', bulk_actions)==-1)
							{
								bulk_actions.push('mergable');
							}

							var parent_elm=sub_elm.parent();
							if(parent_elm.hasClass('wfte_'+wfte_name) || parent_elm.hasClass('wfte_'+wfte_name+'_val')) /* second condition is for to include address block value container div */
							{
								//non merged, mergable item
							}else
							{
								//this is a merged item
								var current_wfte_id=parent_elm.attr('data-wfte-id');
								if($.inArray(current_wfte_id, merged_group_item_arr)==-1) /* check current merged item parent is not already added */
								{
									merged_group_item_arr.push(current_wfte_id);
									var sub_elm_wfte_name=pklist_dc.prepare_wfte_name_title(parent_elm, wfte_name, editable_item_arr, assets_elements_arr);
									sub_elm=parent_elm;
								}else
								{
									//merged group parent already added.
									is_prepare_property_editor_blocks=false;
								}
							}

						}

						if(is_prepare_property_editor_blocks)
						{
							/*
							*  Note: We are taking the property list of first item for merged items.
							*/
							pklist_dc.prepare_property_editor_blocks(sub_elm, sub_editable_item_arr, assets_elements_arr, sub_elm_wfte_name);
						}

						/* not already sortable && sortable items available in the current array */
						if(!is_sortable_items_available && $.inArray('sortable', sub_editable_item_arr)!=-1)
						{
							is_sortable_items_available=true;
						}

						/* not already removable && removable items available in the current array */
						if($.inArray('removable', bulk_actions)==-1 && $.inArray('removable', sub_editable_item_arr)!=-1)
						{
							bulk_actions.push('removable');
						}

						if(pklist_dc.is_merged_group_wfte_name(sub_elm_wfte_name)) /* check this is a merged group item */
						{
							var wfte_name_arr=sub_elm_wfte_name.split('=');
							sub_elm_wfte_name=wfte_name_arr[0]; //take first item wfte_name
						}
						available_items.push(sub_elm_wfte_name);
					}
				});

				/* prepare other elements available in the block */
				sidebar_tab_elm.append('<div class="wt_pklist_dc_property_editor_other_available_items"><div class="wt_pklist_dc_property_editor_other_available_items_hd">'+wt_pklist_dc_params.labels.other_available_items+'</div><div class="wt_pklist_dc_property_editor_other_available_items_content"></div></div>');
				var other_items_available=false;
				$.each(editable_item_arr, function(sub_elm_wfte_name, property_arr){
					
					if(sub_elm_wfte_name!=wfte_name && $.inArray(sub_elm_wfte_name, available_items)==-1)
					{
						var sub_elm=$('.wt_pklist_dc_assets_designview_html').find('.wfte_'+wfte_name).find('.wfte_'+sub_elm_wfte_name);
						if(sub_elm.length>0)
						{
							other_items_available=true;
							pklist_dc.prepare_property_editor_blocks(sub_elm, editable_item_arr[sub_elm_wfte_name], assets_elements_arr, sub_elm_wfte_name, true);
						}
					}

				}); 

				if(other_items_available)
				{
					$('.wt_pklist_dc_property_editor_other_available_items').show();
				}

				if(bulk_actions.length>0) /* add bulk action panel */
				{					
					$.each(bulk_actions, function(index, bulk_action){

						if(pklist_dc.sidebar_bulk_action_btns.hasOwnProperty(bulk_action))
						{
							sidebar_tab_elm.find('.wt_pklist_dc_property_editor_top_panel').append(pklist_dc.sidebar_bulk_action_btns[bulk_action]);
						}
					});		
				}

				/* sortable elements available */
				if(is_sortable_items_available)
				{
					pklist_dc.initiate_sidebar_sortable(elm);
				}

				/* current element have add new sub item option */
				if(wt_pklist_dc_params.assets_add_new_item.hasOwnProperty(wfte_name))
				{
					var add_new_options=wt_pklist_dc_params.assets_add_new_item[wfte_name];
					sidebar_tab_elm.find('.wt_pklist_dc_property_editor_top_panel').append(pklist_dc.add_new_subitem_btn);
					sidebar_tab_elm.find('.wt_pklist_dc_property_editor_top_panel .wt_pklist_dc_property_editor_top_panel_add_new_subitem_btn').attr('title', add_new_options.title);
				}

				/* adding HTML editor button */
				sidebar_tab_elm.find('.wt_pklist_dc_property_editor_top_panel').append(pklist_dc.html_editor_btn);

				/* open first accordian editor panel */
				if(elm.hasClass('wt_pklist_refresh_editor')) //no need to open first panel on refresh
				{
					elm.removeClass('wt_pklist_refresh_editor');
				}else
				{	
					if(pklist_dc.open_first_panel)
					{
						sidebar_tab_elm.find('.wt_pklist_dc_sidebar_tabaccord:eq(0) .wt_pklist_dc_sidebar_tabaccord_hd').trigger('click');
					}
				}

			}
		},

		/**
		* this is to get actual css values instead of computed 
		*/
		get_dummy_target_element:function(elm)
		{
			return elm.clone().prependTo('body').wrap('<div style="display: none"></div>');
		},

		remove_dummy_target_element:function(dummy_elm)
		{
			dummy_elm.parent('div').remove();
		},

		/**
		 * 	Prepare page property editor
		 * 
		 */
		set_page_property_editor:function()
		{
			var elm=$('.wt_pklist_dc_visual_editor .wfte_invoice-main');
			var block_title=wt_pklist_dc_params.labels.properties;
			var temp_elm=pklist_dc.prepare_property_editor_block_html(elm, block_title, block_title, false);
			var property_arr=wt_pklist_dc_params.page_editable_properties;
			var wfte_name='page_main';
			elm.attr('data-wfte_name', wfte_name);
			
			var dummy_elm=pklist_dc.get_dummy_target_element(elm);

			$.each(property_arr, function(index, name){

				if(!pklist_dc.prepare_property_fields(dummy_elm, temp_elm, name, wfte_name))
				{
					return;
				}

			});

			pklist_dc.remove_dummy_target_element(dummy_elm);

			var sidebar_tab_elm=this.get_sidebar_tab_element('wt-pklist-dc-sidebar-page');
			sidebar_tab_elm.html('');
			temp_elm.appendTo(sidebar_tab_elm);

			/* opening the accordian */
			sidebar_tab_elm.find('.wt_pklist_dc_sidebar_tabaccord:eq(0) .wt_pklist_dc_sidebar_tabaccord_hd').trigger('click');

			$('.wt_pklist_dc_sidebar_tab_btn[data-tab-target="wt-pklist-dc-sidebar-page"]').trigger('click');
		},

		prepare_property_editor_block_html:function(elm, full_title, item_title, is_title_element)
		{
			var html='<div class="wt_pklist_dc_sidebar_tabaccord">'
					+'<div class="wt_pklist_dc_sidebar_tabaccord_hd noselect" title="'+full_title+'">'
						+'<div class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_accord">'
							+'<span class="dashicons dashicons-arrow-down-alt2"></span>'
						+'</div>'
						+'<span class="wt_pklist_dc_sidebar_tabaccord_hd_title">'+item_title+'</span>'
						+(is_title_element ? '<span class="wt_pklist_dc_sidebar_tabaccord_hd_title_flag">('+wt_pklist_dc_params.labels.title+')</span>' : '')
					+'</div>'
					+'<div class="wt_pklist_dc_sidebar_tabaccord_content">'
					+'</div></div>';

			var temp_elm=$('<div />').html(html);
			temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').data('target_elm', elm);
			return temp_elm;
		},

		/**
		*	Prepare property editor for corresponding elements
		*/
		prepare_property_editor_blocks:function(elm, property_arr, assets_elements_arr, wfte_name, is_extra_item)
		{
			var main_elm=elm; /* this is necessary if label element styles are applicable */
			
			var full_title='';
			var is_title_element=false; /* current element is title element */
			if(pklist_dc.is_merged_group_wfte_name(wfte_name)) /* check this is a merged group item */
			{
				var item_title=this.merged_item_title;
				full_title=item_title;				
				item_title=this.trim_item_title(item_title);

				elm.attr('data-wfte_name', wfte_name); /* by default wfte_name not added for merged group */
			}else
			{
				if(elm.find('.wfte_'+wfte_name+'_label').length>0) /* label element exists, then use label text */
				{
					var item_title=elm.find('.wfte_'+wfte_name+'_label').text();
				}else
				{
					if($.inArray('text', property_arr)!=-1) /* text editable propert exists, so we can use the text as item title */
					{
						var regex = /[^a-zA-Z0-9_-\s]/g;
						var item_title= pklist_dc.extract_element_content(elm, 'text').replace(regex, '').trim(); /* replace non alpha numeric characters */
						is_title_element=true;
					}else if(assets_elements_arr.hasOwnProperty(wfte_name))
					{
						var item_title=assets_elements_arr[wfte_name];
					}else
					{
						var item_title=wfte_name;
					}
				}

				full_title=item_title;
				item_title=this.trim_item_title(item_title);
			}
			
			var temp_elm=pklist_dc.prepare_property_editor_block_html(elm, full_title, item_title, is_title_element);

			var hd_elm=temp_elm.find('.wt_pklist_dc_sidebar_tabaccord_hd');
			var checkbox_added=false; /* checkbox for bulk action like delete, merge */
			var is_prependable=false; /* some items must be added to the starting of the element, eg: Address title */


			if(pklist_dc.property_editor_messages.hasOwnProperty(wfte_name))
			{
				temp_elm.find('.wt_pklist_dc_sidebar_tabaccord_content').prepend('<div class="wt_pklist_dc_sidebar_tabaccord_info_msg">'+pklist_dc.property_editor_messages[wfte_name]+'</div>')
			}

						
			var dummy_elm=pklist_dc.get_dummy_target_element(elm);

			$.each(property_arr, function(index, name){
				
				if(name=='sortable') /* current sub element is sortable */
				{
					temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').addClass('wt_pklist_dc_sidebar_sortable');
					hd_elm.prepend('<div class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_sortable" title="'+wt_pklist_dc_params.labels.drag_to_rearrange+'"></div>');

				}else if(name=='removable') /* current sub element is removable */
				{
					hd_elm.append('<div class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_removable"><i class="material-icons">delete_outline</i></div>');
					checkbox_added=pklist_dc.add_checkbox_html_to_sidebar_accord(hd_elm, wfte_name, checkbox_added, name);

				}else if(name=='mergable') /* current sub element is mergable */
				{
					checkbox_added=pklist_dc.add_checkbox_html_to_sidebar_accord(hd_elm, wfte_name, checkbox_added, name);
				}
				else if(name=='prependable') /* current sub element is prependable */
				{
					is_prependable=true;

				}else if(name=='hideable') /* current sub element is hideable */
				{
					var hideable_icon='visibility';
					if(!elm.is(':visible'))
					{
						hideable_icon='hidden';
					}
					hd_elm.append('<div data-target_wfte_name="'+wfte_name+'" class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_hideable"><span class="dashicons dashicons-'+hideable_icon+'"></span></div>');
				
				}else
				{
					if(!pklist_dc.prepare_property_fields(dummy_elm, temp_elm, name, wfte_name))
					{
						return;
					}
				}
			});

			pklist_dc.remove_dummy_target_element(dummy_elm);

			if(pklist_dc.is_merged_group_wfte_name(wfte_name)) /* check this is a merged group item. Add a split button */
			{
				hd_elm.append('<div class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_splitable" title="'+wt_pklist_dc_params.labels.split_items+'"></div>');
			}			

			if(is_extra_item)
			{
				temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').wrap('<div class="wt_pklist_dc_property_editor_other_available_items_item"></div>');
				temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').hide();
				temp_elm.find('.wt_pklist_dc_property_editor_other_available_items_item').prepend(item_title+'<span class="wt_pklist_dc_property_editor_other_available_items_add_btn" '+(is_prependable ? 'data-prependable="1" ' : '')+'>'+wt_pklist_dc_params.labels.add+'</span>').attr('title', full_title);
				temp_elm.find('.wt_pklist_dc_property_editor_other_available_items_item').appendTo('.wt_pklist_dc_property_editor_other_available_items_content');
			}else
			{
				if(this.is_editable_tbody_found && main_elm.prop('tagName').toLowerCase()=='tbody')
				{
					if($('.wt_pklist_dc_property_editor_top_panel').siblings('.wt_pklist_dc_sidebar_sortable').eq(0).length>0)
					{
						temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').insertBefore($('.wt_pklist_dc_property_editor_top_panel').siblings('.wt_pklist_dc_sidebar_sortable').eq(0));
					}else
					{
						temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').insertBefore($('.wt_pklist_dc_property_editor_bottom_panel'));
					}
				}else
				{
					temp_elm.find('.wt_pklist_dc_sidebar_tabaccord').insertBefore($('.wt_pklist_dc_property_editor_bottom_panel'));
				}
			}			
			temp_elm.remove();
		},


		prepare_property_fields:function(elm, temp_elm, name, wfte_name)
		{
			var label_prop='';
			var prop_ar=name.split('-');
			if(prop_ar[0]=='label') /* this property for label element */
			{
				if(prop_ar.length<=1){ return false; }
				elm=elm.find('.wfte_'+wfte_name+'_label');
				if(elm.length==0){ return false; }
				label_prop=name;
				prop_ar.shift();
				name=prop_ar.join('-');					
			}

			var property_group_block=$('.wt_pklist_dc_property_editor_contents').find('.wt_pklist_dc_sidebar_property_group_block[data-property-group="'+name+'"]');
			if(property_group_block.length>0) /* property group */
			{
				property_group_block=property_group_block.clone();
				property_group_block.find('.wt_pklist_dc_sidebar_property_block').each(function(){
					
					pklist_dc.set_element_property_to_editor(elm, $(this).attr('data-property'), $(this));
					if(label_prop!="")  /* property for label element */
					{
						$(this).attr('data-property', label_prop).find('.wt_pklist_dc_sidebar_property_label').append(' ('+wt_pklist_dc_params.labels.label+')');
					}

				});				
				property_group_block.appendTo(temp_elm.find('.wt_pklist_dc_sidebar_tabaccord_content'));
				
			}else
			{
				var property_block=$('.wt_pklist_dc_property_editor_contents').find('.wt_pklist_dc_sidebar_property_block[data-property="'+name+'"]');
				if(property_block.length>0)
				{
					/**
					*	Check if the container div is already added by other elements in the same group
					*/
					var parent_group_block=property_block.parents('.wt_pklist_dc_sidebar_property_group_block');
					var parent_group_block_slug=parent_group_block.attr('data-property-group');
					if(temp_elm.find('.wt_pklist_dc_sidebar_property_group_block[data-property-group="'+parent_group_block_slug+'"]').length==0)
					{
						parent_group_block=parent_group_block.clone();
						if(parent_group_block.find('.wt_pklist_dc_sidebar_property_container').length==0)
						{
							parent_group_block.html('<div class="wt_pklist_dc_sidebar_property_container"></div>');
						}else
						{
							parent_group_block.find('.wt_pklist_dc_sidebar_property_container').html('');
						}
					}else
					{
						parent_group_block=temp_elm.find('.wt_pklist_dc_sidebar_property_group_block[data-property-group="'+parent_group_block_slug+'"]');
					}

					/**
					*	Add current element to the container element
					*/
					parent_group_block.find('.wt_pklist_dc_sidebar_property_container').append(property_block[0].outerHTML);

					property_block=parent_group_block.find('.wt_pklist_dc_sidebar_property_block[data-property="'+name+'"]');
					
					if(label_prop!="")  /* property for label element */
					{
						property_block.attr('data-property', label_prop).find('.wt_pklist_dc_sidebar_property_label').append(' ('+wt_pklist_dc_params.labels.label+')');
					}

					var assets_elements_arr=pklist_dc.get_assets_elements(pklist_dc.wfte_name);
					var prop_label_html=property_block.find('.wt_pklist_dc_sidebar_property_label').html();
					if(assets_elements_arr.hasOwnProperty(wfte_name))
					{
						prop_label_html=prop_label_html.replace('%s', assets_elements_arr[wfte_name]);
						property_block.find('.wt_pklist_dc_sidebar_property_label').html(prop_label_html);
					}

					pklist_dc.set_element_property_to_editor(elm, name, property_block); /* prepare property editor for current element */	

					parent_group_block.appendTo(temp_elm.find('.wt_pklist_dc_sidebar_tabaccord_content'));
				}
			}

			return true;
		},

		/**
		*	Perform the rearrange action on real elements after doing rearrange in the side panel
		*/
		do_rearrange_based_on_sort:function(elm)
		{
			var prev_elm=null;
			var prev_elm_arr=new Array(); /* for table */
			var i=0;

			/* only pick direct children. There may be same items under `other items block` */
			$('.wt_pklist_dc_sidebar_tabcontent').children('.wt_pklist_dc_sidebar_sortable').each(function(){
				var target_elm=$(this).data('target_elm');
				var tag_name=target_elm.prop('tagName').toLowerCase();
				
				if(tag_name=='th' || tag_name=='td') /* for table, we have to move the entire column instead of the selected item */
				{
					var ind=target_elm.index();
					var p=0;
					var parent_table=target_elm.parents('table');
					parent_table.find('tr').each(function(){
						var crnt_trget_elm=$(this).find('td, th').eq(ind);
						
						if(i>0)
						{
							crnt_trget_elm.insertAfter(prev_elm_arr[p]);
							pklist_dc.source_sort(crnt_trget_elm, prev_elm_arr[p]);
						}

						prev_elm_arr[p]=crnt_trget_elm; 
						p++;
					});

					pklist_dc.update_td_right_column_css_class(parent_table, tag_name);
					pklist_dc.update_td_right_column_css_class(pklist_dc.get_source_elm_from_visual_elm(parent_table), tag_name);
					
				}else
				{
					if(i>0)
					{
						target_elm.insertAfter(prev_elm);
						pklist_dc.source_sort(target_elm, prev_elm);
					}
					prev_elm=target_elm;
				}				
				i++;
			});

			pklist_dc.add_history(); /* save for undo redo */
		},

		/**
		*	Prepare wfte_name for merged group item. This function will prepare wfte_name, item title based on the child element's wfte_name
		*	This function only return wfte_name, The item title will stored into a global variable for use
		*/
		prepare_wfte_name_title:function(group_elm, wfte_name, editable_item_arr, assets_elements_arr)
		{
			var new_wfte_name_arr=[];
			var item_title_arr=[];
			var new_wfte_name='';
			var new_item_title='';
			group_elm.find('[data-wfte_parent="'+wfte_name+'"]').each(function(){
				
				var sub_elm=$(this);
				var sub_elm_wfte_name=typeof sub_elm.attr('data-wfte_name')!='undefined' ? sub_elm.attr('data-wfte_name') : '';
				if(sub_elm_wfte_name!="" && editable_item_arr.hasOwnProperty(sub_elm_wfte_name)) /* add an editable section */
				{
					new_wfte_name_arr.push(sub_elm_wfte_name);
					var item_title=(assets_elements_arr.hasOwnProperty(sub_elm_wfte_name) ? assets_elements_arr[sub_elm_wfte_name] : sub_elm_wfte_name);
					item_title_arr.push(item_title);
				}
				new_wfte_name=new_wfte_name_arr.join('=');
				new_item_title=item_title_arr.join(', ');
			});
			this.merged_item_title=new_item_title;
			return new_wfte_name;
		},

		/**
		*	Extract current property from element and add to editor element
		*/
		set_element_property_to_editor:function(target_elm, prop, property_block)
		{
			var input_elm=property_block.find('.wt_pklist_dc_property_editor_input');
			var wfte_name=target_elm.attr('data-wfte_name');

			if(prop=='html' || prop=='text') 
			{		
				input_elm.val(pklist_dc.extract_element_content(target_elm, prop));

			}
			else if(input_elm.hasClass('wt_pklist_dc_four_side_prop'))
			{
				var sides=['top', 'right', 'bottom', 'left'];
				var property_slug=property_block.attr('data-property-slug');
				if(typeof property_slug=='undefined' || property_slug=='') /* some properties need placeholders to replace side name */
				{
					property_slug=prop;
				}

				var side_values=[];
				var side_input_elm=property_block.find('.wt_pklist_dc_four_side_prop_sub');
				var side_input_main_elm=property_block.find('.wt_pklist_dc_four_side_prop_main');
				$.each(sides, function(index, side){
					var prop_with_side=property_slug.replace('%s', side); /* replace property with current side name */
					var vl=target_elm.css(prop_with_side);
					if($.inArray(vl, side_values)==-1)
					{
						side_values.push(vl);
					}
					side_input_elm.eq(index).attr('data-side', side);
					pklist_dc.set_property_value_to_editor_input(side_input_elm.eq(index), vl);
				});

				var all_sides_checkbox=property_block.find('.wt_pklist_dc_four_side_prop_all_sides');
				if(side_values.length==1) /* all values are same, Then hide the individual value inputs */
				{
					pklist_dc.set_property_value_to_editor_input(side_input_main_elm, side_values[0]);
					all_sides_checkbox.prop('checked', true);
					pklist_dc.toggle_four_side_prop_input(all_sides_checkbox);
				}else
				{
					pklist_dc.set_property_value_to_editor_input(side_input_main_elm, '');
					all_sides_checkbox.prop('checked', false);
					pklist_dc.toggle_four_side_prop_input(all_sides_checkbox);
				}
			}
			else if(prop=='text-style')
			{
				var font_weight=target_elm.css('font-weight');
				var font_weight_elm=property_block.find('.wt_pklist_button_checkbox[data-property="font-weight"]');
				if(font_weight=='bold' || font_weight>=600)
				{
					font_weight_elm.attr('data-value', 'bold').addClass('button_selected');
				}else
				{
					font_weight_elm.attr('data-value', 'normal').removeClass('button_selected');
				}

				var font_style=target_elm.css('font-style');
				var font_style_elm=property_block.find('.wt_pklist_button_checkbox[data-property="font-style"]');
				if(font_style=='italic')
				{
					font_style_elm.attr('data-value', 'italic').addClass('button_selected');
				}else
				{
					font_style_elm.attr('data-value', 'normal').removeClass('button_selected');
				}

				var text_decoration=target_elm.css('text-decoration-line');
				var text_decoration_elm=property_block.find('.wt_pklist_dc_sidebar_property_button_radio_group[data-property="text-decoration"]');
				text_decoration_elm.find('.wt_pklist_button_radio').removeClass('button_selected');
				text_decoration_elm.find('.wt_pklist_button_radio[data-value="'+text_decoration+'"]').addClass('button_selected');
				
			}
			else if(prop=='text-align')
			{
				var text_align=target_elm.css('text-align');
				text_align=(text_align=='start' ? 'left' : text_align);
				text_align=(text_align=='end' ? 'right' : text_align);

				var text_align_elm=property_block.find('.wt_pklist_dc_sidebar_property_button_radio_group[data-property="text-align"]');
				
				text_align_elm.find('.wt_pklist_button_radio').removeClass('button_selected');
				text_align_elm.find('.wt_pklist_button_radio[data-value="'+text_align+'"]').addClass('button_selected');

			}
			else if(prop=='rotate')
			{
				vl=this.getRotationDegrees(target_elm);
				input_elm.val(vl);
			}
			else if(prop=='border-radius')
			{
				vl=target_elm.css('border-top-left-radius');
				input_elm.val(vl);

			}else if(prop=='border-color')
			{
				vl=target_elm.css('border-top-color');
				this.set_property_value_to_editor_input(input_elm, vl);
			}
			else if(prop=='border-style')
			{
				vl=target_elm.css('border-top-style');
				input_elm.val(vl);
			}
			else
			{
				var prop_ar=prop.split('-');
				if(prop_ar[0]=='attr') /*  attribute not CSS */
				{	
					var property_slug=property_block.attr('data-property-slug');
					if(typeof property_slug!=='undefined' || property_slug!=='')
					{
						prop=property_slug.replace('%s', 'data-'+wfte_name);
					}

					prop=prop.substr(5);
					var vl=target_elm.attr(prop);
					if(typeof vl!='undefined')
					{
						input_elm.val(vl);
						pklist_dc.apply_value_to_preset_input(property_block, vl);
					}
				}else
				{
					var vl=target_elm.css(prop);
					if(prop=='opacity')
					{
						vl=parseFloat(vl);
					}

					if(prop=='height' || prop=='width')
					{
						if(target_elm.prop('tagName').toLowerCase()=='img') /* if image is hidden then the computed value will be incorrect */
						{
							var tmp=target_elm.clone().prependTo('body').wrap('<div style="height:0px; overflow:hidden; padding:0px; margin:0px;"></div>').show().css({'visibility':'hidden'});
							tmp.one("load", function(){							  	
							  	input_elm.val(tmp.css(prop));
								target_elm.parent('div').remove();
							}).each(function(){
							  if(this.complete)
							  {
							      $(this).trigger('load');
							  }
							});

							return;
						}
					}

					this.set_property_value_to_editor_input(input_elm, vl);
				}
			}

		},

		/**
		 * 	Hide/Show four side property input fields
		 * 
		 */
		toggle_four_side_prop_input:function(checkbox_elm)
		{
			var four_side_prop_container=checkbox_elm.parents('.wt_pklist_dc_sidebar_property_block').find('.wt_pklist_dc_four_side_prop_container');
			if(checkbox_elm.is(':checked'))
			{
				four_side_prop_container.hide();
			}else
			{
				four_side_prop_container.show();
			}
		},

		/**
		*	Set value to input elements on sidebar editor
		*/
		set_property_value_to_editor_input:function(input_elm, vl)
		{
			if(input_elm.hasClass('wt_pklist_dc_color_picker_input') && typeof vl!='undefined' && vl!="") /* color field */
			{	
				var color_preview_elm=input_elm.siblings('.wt_pklist_dc_color_preview');				
				if(this.get_alpha_value_from_color(vl)==0)
				{
					var color_string='transparent';
					var color_vl='';
					color_preview_elm.addClass('wt_pklist_dc_color_preview_transparent');
				}else
				{
					var color_string=Color(vl).toString();
					var color_vl=color_string;
					color_preview_elm.removeClass('wt_pklist_dc_color_preview_transparent');
				}

				/* set current color preview */
				color_preview_elm.css({'background-color':color_string});
				input_elm.val(color_vl).attr('data-default', color_string);
			}else
			{	
				input_elm.val(vl);
			}
		},

		/**
		*	@author BraadMartin
		*	@link https://github.com/BraadMartin/components/tree/master/alpha-color-picker
		*/
		get_alpha_value_from_color:function(value)
		{
			var alphaVal;

			// Remove all spaces from the passed in value to help our RGBa regex.
			value = value.replace( / /g, '' );

			if ( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ ) ) {
				alphaVal = parseFloat( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ )[1] ).toFixed(2) * 100;
				alphaVal = parseInt( alphaVal );
			} else {
				alphaVal = 100;
			}

			return alphaVal;
		},

		getRotationDegrees:function(obj)
		{
		    var matrix = obj.css("-webkit-transform") ||
		    obj.css("-moz-transform")    ||
		    obj.css("-ms-transform")     ||
		    obj.css("-o-transform")      ||
		    obj.css("transform");
		    if(matrix !== 'none') {
		        var values = matrix.split('(')[1].split(')')[0].split(',');
		        var a = values[0];
		        var b = values[1];
		        var angle = Math.round(Math.atan2(b, a) * (180/Math.PI));
		    } else { var angle = 0; }
		    return angle;
		},

		trim_item_title:function(item_title)
		{
			if(item_title.length>20)
			{
				item_title=item_title.substr(0, 20)+'...';
			}
			return item_title;
		},

		/**
		*	Extract HTML/text of an element, This will remove the element close button HTML of a selected element.
		*/
		extract_element_content:function(target_elm, prop)
		{
			var vl=target_elm.html();
				
			/* Remove item delete button HTML */
			var temp_elm=$('<div />').html(vl);
			temp_elm.find('.wt_pklist_dc_item_delete').remove();
			
			if(prop=='html')
			{
				vl=temp_elm.html();
				vl=typeof vl!='undefined' ? this.br2nl(vl) : vl;
			}else
			{
				vl=temp_elm.text();
			}

			return vl;
		},

		get_assets_elements:function(wfte_name)
		{
			var assets_elements_arr={};
			if(wt_pklist_dc_params.assets_elements.hasOwnProperty(wfte_name))
			{
				assets_elements_arr=wt_pklist_dc_params.assets_elements[wfte_name];
			}
			return assets_elements_arr;
		},

		get_property_editor_messages:function(wfte_name)
		{
			var property_editor_messages={};
			if(wt_pklist_dc_params.property_editor_messages.hasOwnProperty(wfte_name))
			{
				property_editor_messages=wt_pklist_dc_params.property_editor_messages[wfte_name];
			}
			return property_editor_messages;
		}
	}
	return pklist_dc_property_editor;

})( jQuery );