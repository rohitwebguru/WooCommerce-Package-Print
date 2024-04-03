var pklist_dc_source_code=(function( $ ) {

	var pklist_dc_source_code=
	{
		source_on_drag_elm:null,
		full_code_editor:false,
		/**
		*	Update source code when dragging and re-arranging elements inside the visual editor
		*
		*/
		source_code_rearrange:function(droppable_elm, on_drag_elm)
		{
			var droppable_elm=this.get_source_elm_from_visual_elm(droppable_elm);
			var on_drag_elm=this.get_source_elm_from_visual_elm(on_drag_elm);
			droppable_elm.append(on_drag_elm);
		},

		/**
		*	Update source code when dragging elements from assets block
		*
		*/
		source_code_drop_asset:function(droppable_elm, on_drag_elm)
		{
			var droppable_elm=this.get_source_elm_from_visual_elm(droppable_elm);
			droppable_elm.append(on_drag_elm);
		},

		source_do_elm_delete:function(elm)
		{
			var source_elm=this.get_source_elm_from_visual_elm(elm);
			source_elm.remove();
		},

		source_apply_prop:function(elm, prop, val, type)
		{
			var source_elm=this.get_source_elm_from_visual_elm(elm);
			var tag_name=source_elm.prop('tagName').toLowerCase();
			if(tag_name=='table' || tag_name=='thead' || tag_name=='tr') /* exclude tbody */
			{
				source_elm=source_elm.find('td, th');
			}

			if(type=='css')
			{
				source_elm.css(prop, val);

			}else if(type=='attr')
			{
				source_elm.attr(prop, val);

			}else if(type=='html')
			{
				source_elm.html('__['+val+']__'); /* add as translation compatible string */
			}
		},

		source_hide_show:function(elm)
		{
			var source_elm=this.get_source_elm_from_visual_elm(elm);
			if(elm.is(':visible'))
			{
				source_elm.addClass('wfte_hidden');
			}else
			{
				source_elm.removeClass('wfte_hidden').show();
			}
		},

		source_sort:function(elm, prev_elm)
		{
			var elm=this.get_source_elm_from_visual_elm(elm);
			var prev_elm=this.get_source_elm_from_visual_elm(prev_elm);
			if(elm && prev_elm)
			{
				elm.insertAfter(prev_elm);
			}
		},

		source_row_delete(row)
		{
			var source_row=this.get_source_elm_from_visual_elm(row);
			source_row.remove();
		},

		get_source_elm_from_visual_elm:function(visual_elm)
		{
			return this.get_source_elm_by_wfte_id(this.get_wfte_id(visual_elm));
		},

		get_source_asset_sub_elm_from_visual_asset_sub_elm:function(visual_asset_sub_elm)
		{
			var wfte_name=visual_asset_sub_elm.attr('data-wfte_parent');
			var sub_elm_wfte_name=visual_asset_sub_elm.attr('data-wfte_name');

			return $('.wt_pklist_dc_assets_codeview_html').find('.wfte_'+wfte_name).find('.wfte_'+sub_elm_wfte_name);
		},

		apply_dummy_value_to_img_urls:function(html)
		{
			var dummy_placeholders=wt_pklist_dc_params.img_url_placeholders;
			$.each(dummy_placeholders, function(key, val){
				html=html.replace(key, val);
			});
			return html;
		},

		get_source_html:function()
		{
			var temp_elm=$('<div />').html($('.wt_pklist_dc_code_editor').html());
			temp_elm.remove();
			temp_elm.find('*').removeAttr('data-wfte-id');
			temp_elm.find('.wt_pklist_last_dom_id, .wt_pklist_dc_empty_editor, .wt_pklist_last_dom_id').remove();

			var html=temp_elm.html();
			temp_elm.remove();

			var dummy_placeholders=wt_pklist_dc_params.img_url_placeholders;
			$.each(dummy_placeholders,function(key, val){
				html=html.replace(val, key);
			});

			return html;
		},

		source_code_editor_events:function()
		{
			/**
			*	Code editor popup
			*/
			$(document).on('click', '.wt_pklist_dc_property_editor_top_panel_html_editor_btn, .wt_pklist_dc_full_code_editor', function(){
				var popup_elm=$('.wt_pklist_dc_html_editor');
				popup_elm.find('.wt_pklist_dc_html_editor_popup_title').html($(this).attr('title'));
				if($(this).hasClass('wt_pklist_dc_full_code_editor')) /* full code editor */
				{
					pklist_dc.full_code_editor=true;
					var target_elm=$('.wt_pklist_dc_code_editor');
				}else
				{
					pklist_dc.full_code_editor=false;
					var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
					var target_elm=sidebar_tab_elm.data('target_elm');
				}
				if(target_elm)
				{
					/* adjust dimension of editor */
					var Ww=$('#wpbody').width();
					var Wh=$(window).height();
					$('.wt_pklist_dc_html_editor').css({'max-width':(Ww-100), 'height':(Wh-100)});
					$('.wt_pklist_dc_html_editor_popup_content').css({'height':(Wh-200)});
					pklist_dc.code_editor.setSize((Ww-150), (Wh-200));

					if(pklist_dc.full_code_editor) /* full code editor */
					{
						pklist_dc.code_editor.getDoc().setValue(target_elm.html());
					}else
					{
						var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm);
						pklist_dc.code_editor.getDoc().setValue(source_target_elm[0].outerHTML);
					}
				}
				wf_popup.showPopup(popup_elm);
				pklist_dc.code_editor.refresh();
			});

			/**
			*	Code editor save event
			*/
			$(document).on('click', '.wt_pklist_dc_html_editor_submit_btn', function(){
				var sidebar_tab_elm=pklist_dc.get_sidebar_tab_element('wt-pklist-dc-sidebar-block');
				var new_html=pklist_dc.code_editor.getDoc().getValue();

				if(pklist_dc.full_code_editor)
				{
					var current_html=$('.wt_pklist_dc_code_editor').html();
				}else
				{
					var target_elm=sidebar_tab_elm.data('target_elm');
					var source_target_elm=pklist_dc.get_source_elm_from_visual_elm(target_elm);
					var current_html=source_target_elm[0].outerHTML;
				}

				if(current_html!=new_html)
				{
					var source_html_backup=$('.wt_pklist_dc_code_editor').html(); /* to handle ajax error when updating HTML */

					if(pklist_dc.full_code_editor)
					{
						$('.wt_pklist_dc_code_editor').html(new_html);
					}else
					{
						var temp_elm=$('<div />').html(new_html);
						temp_elm.children().addClass('wt_pklist_dc_on_current_code_editor wt_pklist_dc_editable_selected'); /* this class is to reselect the item for edit, after getting update from server */
						source_target_elm.replaceWith(temp_elm.html());
					}
					pklist_dc.update_on_codeview_change(source_html_backup);
				}else
				{
					wf_popup.hidePopup();
				}

			});

		},
	}
	return pklist_dc_source_code;
})( jQuery );