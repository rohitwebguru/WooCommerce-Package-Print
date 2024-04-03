/**
 * 	This file handles drag and drop related actions
 */
var pklist_dc_drag_drop=(function( $ ) {

	var pklist_dc_drag_drop=
	{
		set_draggable_elements:function(parnt)
		{
			$.each(wt_pklist_dc_params.draggable_elements, function(index, value){
				parnt.find('.wfte_'+value).addClass('wfte_draggable').attr('draggable', true);
			});
			return parnt;
		},

		set_droppable_elements:function(parnt)
		{
			parnt.find('*').filter(function() {
			    var css_class=typeof $(this).attr('class')!='undefined' ? $(this).attr('class') : '';
			    var matched=false;
			    var css_class_arr=css_class.split(" ");
				$.each(css_class_arr, function(css_index, css_value){
					if(!matched)
					{
					    $.each(wt_pklist_dc_params.droppable_elements, function(index, value){

					    	var rgx=new RegExp('^wfte_'+value, 'g');
					    	
					    	if(!matched)
					    	{
					    		matched=css_value.match(rgx);
					    	}else
					    	{
					    		return false; /* break the loop */
					    	}
					    });
					}else{
						return false; /* break the loop */
					}
				});
				
			    return matched;

			}).each(function(){				
				
				$(this).addClass('wfte_droppable');
				pklist_dc.handle_empty_column($(this));

			});

			return parnt;
		},

		/**
		*	This function handles drap and drop related actions
		*
		*/
		set_drag_and_drop:function()
		{		
			$(document).on('dragstart', '.wfte_draggable', function(e){
				var draggable_elm=pklist_dc.get_draggable($(e.target));
				if(!draggable_elm)
				{
					e.preventDefault();
					return false;
				}else
				{
					pklist_dc.on_drag_elm=draggable_elm;
					$('.wt_pklist_dc_dragover').removeClass('wt_pklist_dc_dragover');
					if(!$(pklist_dc.on_drag_elm).hasClass('wt_pklist_dc_asset_item')) /* not from sidebar assets section */
					{
						pklist_dc.set_editable_selected(pklist_dc.on_drag_elm);
					}
				}
			});

			$(document).on('dragover', '.wt_pklist_dc_visual_editor *', function(e){
				e.preventDefault();
				e.stopPropagation();
				var droppable_elm=pklist_dc.get_droppable($(e.target));
				if(droppable_elm)
				{
					droppable_elm.addClass('wt_pklist_dc_dragover');
				}
			});

			$(document).on('dragleave', '.wfte_droppable', function(e){
				e.preventDefault();
				var droppable_elm=pklist_dc.get_droppable($(e.target));
				droppable_elm.removeClass('wt_pklist_dc_dragover');

			});
			
			$('.wt_pklist_dc_visual_editor, .wt_pklist_dc_visual_editor *').on('drop', function(e){
				e.preventDefault();
			});

			$(document).on('drop', '.wfte_droppable', function(e){
				e.preventDefault();
				if(!pklist_dc.on_drag_elm){ return false; }
				var target_elm=$(e.target);

				var drop_elm_parent=pklist_dc.on_drag_elm.parents('.wfte_droppable');
				var droppable_elm=pklist_dc.get_droppable(target_elm);
				
				if($(pklist_dc.on_drag_elm).hasClass('wt_pklist_dc_asset_item')) /* dragged from sidebar */
				{
					var is_element_group_main_item=$(pklist_dc.on_drag_elm).hasClass('wt_pklist_dc_asset_element_group_main'); /* these lines must be above of the below clone method */
					var is_element_group_sub_item=$(pklist_dc.on_drag_elm).hasClass('wt_pklist_dc_asset_element_group_sub'); 
					var tab_content=$(pklist_dc.on_drag_elm).parents('.wt_pklist_dc_sidebar_tabaccord_content');

					if(is_element_group_main_item && tab_content.find('.wt_pklist_dc_asset_element_group_checkbox:checked').length==0)
					{
						droppable_elm.removeClass('wt_pklist_dc_dragover');
						wf_notify_msg.error(wt_pklist_dc_params.labels.no_items_to_drop);
						return false;
					}

					var asset_slug=$(pklist_dc.on_drag_elm).attr('data-slug');
					
					var asset_elm=pklist_dc.get_asset_visual_elm_by_wfte_name(asset_slug);
					var source_asset_elm=pklist_dc.get_asset_source_elm_by_wfte_name(asset_slug);
					pklist_dc.on_drag_elm=asset_elm.clone();
					pklist_dc.source_on_drag_elm=source_asset_elm.clone();

					if(is_element_group_main_item) /* check current asset item is an element group main item. If yes then check which elements are choosed to add */ 
					{
						tab_content.find('.wt_pklist_dc_asset_element_group_checkbox:not(:checked)').each(function(){
							var wfte_name=$(this).attr('data-slug');
							pklist_dc.on_drag_elm.find('[data-wfte_name="'+wfte_name+'"]').remove();
							pklist_dc.source_on_drag_elm.find('[data-wfte_name="'+wfte_name+'"]').remove();
						});

					}else if(is_element_group_sub_item) /* if this is an element group sub item, then it must be wrapped with its parent item */
					{
						var asset_parent_slug=tab_content.find('.wt_pklist_dc_asset_element_group_checkbox_main').attr('data-slug'); /* parent slug */
						
						var asset_parent_elm=pklist_dc.get_asset_visual_elm_by_wfte_name(asset_parent_slug); /* parent asset item */
						var source_asset_parent_elm=pklist_dc.get_asset_source_elm_by_wfte_name(asset_parent_slug); /* source parent asset item */
						
						pklist_dc.on_drag_elm=asset_parent_elm.clone();
						pklist_dc.source_on_drag_elm=source_asset_parent_elm.clone(); /* source element */

						
						/** 
						*	remove items other than current item 
						*/
						pklist_dc.on_drag_elm.children('*').not('[data-wfte_name="'+asset_slug+'"]').remove();
						pklist_dc.on_drag_elm.html(pklist_dc.on_drag_elm.html().trim())
						
						pklist_dc.source_on_drag_elm.children('*').not('[data-wfte_name="'+asset_slug+'"]').remove(); /* source element */
						pklist_dc.source_on_drag_elm.html(pklist_dc.source_on_drag_elm.html().trim()) 
					}
					
					/* preparing the new element for visual editor */
					pklist_dc.add_dom_element_id(pklist_dc.on_drag_elm, pklist_dc.source_on_drag_elm);
					pklist_dc.on_drag_elm.addClass('wfte_draggable wfte_editable').attr('draggable', true);
					
					droppable_elm.append(pklist_dc.on_drag_elm);

					pklist_dc.source_code_drop_asset(droppable_elm, pklist_dc.source_on_drag_elm);

					
				}else
				{
					pklist_dc.source_code_rearrange(droppable_elm, pklist_dc.on_drag_elm);

					droppable_elm.append(pklist_dc.on_drag_elm);
					pklist_dc.handle_empty_column(drop_elm_parent);

					/* adding editable selection to dropped element */
					setTimeout(function(){
						pklist_dc.set_editable_selected(pklist_dc.on_drag_elm);
					}, 200);				
				}			

				droppable_elm.removeClass('wt_pklist_dc_dragover wt_pklist_dc_empty_column wt_pklist_dc_empty_column_height_fix');
				pklist_dc.add_history(); /* save for undo redo */
				pklist_dc.set_row_border(target_elm);

			});	
		},

		/** 
		*	get draggable item, may be parent of current element or the elemnt itself 
		*/
		get_draggable:function(elm) 
		{
			var draggable=null;
			if(!elm.hasClass('wfte_draggable'))
			{
				var parent_draggable=elm.parents('.wfte_draggable');
				if(parent_draggable.length>0)
				{
					draggable=parent_draggable;
				}
			}else
			{
				draggable=elm;
			}
			return draggable;
		},

		/**
		*	get droppable item, may be parent of current element or the elemnt itself 
		*/	
		get_droppable:function(elm) 
		{
			var droppable=null;
			if(!elm.hasClass('wfte_droppable'))
			{
				var parent_droppable=elm.parents('.wfte_droppable');
				/*
				var on_drag_elm_parent=pklist_dc.on_drag_elm.parents('.wfte_droppable');
				 && !parent_droppable.is(on_drag_elm_parent)
				 */
				if(parent_droppable.length>0)
				{
					droppable=parent_droppable;
				}
			}else
			{
				droppable=elm;
			}
			return droppable;
		},
	}
	return pklist_dc_drag_drop;
})( jQuery );