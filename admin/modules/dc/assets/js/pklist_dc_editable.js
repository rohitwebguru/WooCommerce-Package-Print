var pklist_dc_editable=(function( $ ) {

	var pklist_dc_editable=
	{
		/**
		*	Alter dom elements to editable. Adding wfte_name, editable css class etc
		*/
		set_editable_elements:function(parnt)
		{
			$.each(wt_pklist_dc_params.editable_elements, function(index, wfte_name){
				var elm=parnt.find('.wfte_'+wfte_name)
				if(elm.length>0)
				{
					elm.addClass('wfte_editable').attr('data-wfte_name', wfte_name);
					var assets_elements_arr=pklist_dc.get_assets_elements(wfte_name);
					var editable_item_arr=pklist_dc.get_assets_editable_properties(wfte_name);
					if(!$.isEmptyObject(assets_elements_arr)) /* sub elements exists */
					{
						$.each(assets_elements_arr, function(sub_wfte_name, element_value)
						{
							if(sub_wfte_name!=wfte_name) /* not parent */
							{
								var sub_elm=elm.find('.wfte_'+sub_wfte_name);
								if(sub_elm.length>0) /* element exists */
								{
									pklist_dc.set_editable_sub_elements(sub_elm, wfte_name, sub_wfte_name, editable_item_arr);
								}
							}
						});
					}
				}

			});
			return parnt;
		},

		/**
		*	Adding edit attribute to sub elements 
		*/
		set_editable_sub_elements:function(sub_elm, parent_wfte_name, sub_wfte_name, editable_item_arr)
		{
			sub_elm.attr({'data-wfte_parent': parent_wfte_name, 'data-wfte_name':sub_wfte_name}); /* setting sub item parent, element identifier */

			var editable_properties=this.get_sub_asset_editable_properties(sub_wfte_name, editable_item_arr);
			if($.inArray('hideable', editable_properties)!=-1)
			{
				sub_elm.attr({'data-wfte_hideable': 1}); /* this attribute will prevent this element from deleting if it was hidden */
			}
		},

		/**
		*	Add selection on editable elements on click/drag 
		*/
		set_editable_selected:function(elm)
		{
			if(elm.hasClass('wt_pklist_dc_editable_selected')) /* already on selection so skip it */
			{
				pklist_dc.set_elm_delete_btn(elm); /* sometimes close button rearrange is needed */
				$('.wt_pklist_dc_sidebar_tab_btn[data-tab-target="wt-pklist-dc-sidebar-block"]').trigger('click');
				return false;
			}

			$('.wt_pklist_dc_editable_selected').removeClass('wt_pklist_dc_editable_selected wt_pklist_dc_draggable_selected');
			elm.addClass('wt_pklist_dc_editable_selected');
			elm.removeClass('wt_pklist_dc_editable_hover');

			pklist_dc.set_elm_delete_btn(elm);
			pklist_dc.set_property_editor(elm);
			
			if(elm.hasClass('wfte_draggable'))
			{
				elm.addClass('wt_pklist_dc_draggable_selected');
			}
		},

		/**
		*	Re initiate the editable panel
		*/
		refresh_editable_selected:function(elm)
		{
			var editable_elm=pklist_dc.get_editable_item(elm);
			editable_elm.removeClass('wt_pklist_dc_editable_selected').addClass('wt_pklist_refresh_editor'); /* remove selected state, because  we want force reselect */
			editable_elm.trigger('click');
		},

		/**
		*	Check the item exits if not remove the editor panel. This method will call when deleting item, or deleting row.
		*/
		check_and_remove_editable_selected:function()
		{
			var sidebar_tab_elm=this.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
			if(sidebar_tab_elm.data('target_elm'))
			{
				var target_elm=this.get_visual_elm_by_wfte_id(sidebar_tab_elm.data('target_elm').attr('data-wfte-id'));
				if(target_elm.length==0)
				{
					sidebar_tab_elm.html('').data('target_elm', null);
					this.show_empty_block_editor_msg();
					$('.wt_pklist_dc_sidebar_tab_btn[data-tab-target="wt-pklist-dc-sidebar-page"]').trigger('click');
				}
			}
		},

		/**
		*	Shows when no blocks selected for editing
		*/
		show_empty_block_editor_msg:function()
		{
			var sidebar_tab_elm=this.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
			sidebar_tab_elm.html('<div class="wt_pklist_dc_empty_block_editor">'+wt_pklist_dc_params.labels.choose_a_block_from_editor+'</div>');
		},

		/**
		*	Hightlighting the editable elements on mouseover. Skip the current selected element
		*
		*/
		hightlight_editable:function(elm)
		{
			/* avoid adding dotted border for selected elements */
			if(!elm.hasClass('wt_pklist_dc_draggable_selected') && !elm.hasClass('wt_pklist_dc_editable_selected'))
			{
				elm.addClass('wt_pklist_dc_editable_hover');
			}					
		},

		/**
		* 	Get editable item (The main item) in design view, may be parent of current element or the element itself 
		*/
		get_editable_item:function(elm) 
		{
			var item=null;
			if(!elm.hasClass('wt_pklist_dc_editable_selected'))
			{			
				var parent_item=elm.parents('.wt_pklist_dc_editable_selected');
				if(parent_item.length>0)
				{
					item=parent_item;
				}
			}else
			{
				item=elm;
			}
			return item;
		},

		/**
		*	This function is to add delete button on an editable element
		*/
		set_elm_delete_btn:function(elm)
		{
			
			$('.wt_pklist_dc_item_delete').remove(); /* removing other remove buttons of any other element */

			if(elm.prop('tagName').toLowerCase()=='table')
			{
				elm.before(pklist_dc.col_delete_btn);
			}else
			{
				elm.prepend(pklist_dc.col_delete_btn);
			}
			
			var elm_w=elm.width()+parseInt(elm.css('padding-right'));
			var padd_top=parseInt(elm.css('padding-top'))*-1;
			$('.wt_pklist_dc_item_delete').css({'margin-left':(elm_w-32), 'margin-top':(padd_top+2)}).data('target_elm', elm);
		},

		do_elm_delete:function(btn_elm)
		{
			var item=btn_elm.data('target_elm');
			if(item)
			{
				if(confirm(wt_pklist_dc_params.labels.sure))
				{
					btn_elm.remove(); /* in table elements, the button is outside the target element */
					this.source_do_elm_delete(item); /* delete item from source code */
					var delete_elm_parent=item.parents('.wfte_droppable');
					item.remove();
					pklist_dc.handle_empty_column(delete_elm_parent);
					pklist_dc.check_and_remove_editable_selected();
					pklist_dc.add_history(); /* save for undo redo */
				}
			}
		},

		get_sub_asset_editable_properties:function(sub_wfte_name, editable_properties)
		{
			var assets_editable_properties=[];
			if(editable_properties.hasOwnProperty(sub_wfte_name))
			{
				assets_editable_properties=editable_properties[sub_wfte_name];
			}
			return assets_editable_properties;
		},

		get_assets_editable_properties:function(wfte_name)
		{
			var assets_editable_properties={};
			if(wt_pklist_dc_params.assets_editable_properties.hasOwnProperty(wfte_name))
			{
				assets_editable_properties=wt_pklist_dc_params.assets_editable_properties[wfte_name];
			}
			return assets_editable_properties;
		},

		/**
		* check a column is empty or not. If empty add an empty column placeholder 
		*/
		handle_empty_column:function(drop_elm_parent) 
		{
			if(drop_elm_parent.html().trim()=="") /* add a placeholder on empty column */ 
			{
				drop_elm_parent.addClass('wt_pklist_dc_empty_column wt_pklist_dc_empty_column_height_fix');
			}else
			{
				if(drop_elm_parent.outerHeight()<5) /* not clearly visible height then add a little bit height */
				{
					drop_elm_parent.addClass('wt_pklist_dc_empty_column_height_fix');
				}
			}
		},
	}
	return pklist_dc_editable;
})( jQuery );