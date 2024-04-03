var pklist_dc_row=(function( $ ) {

	var pklist_dc_row=
	{
		/**
		*	Remove row border and editor panel
		*/
		remove_row_border:function()
		{
			pklist_dc.selected_row=null;
			$('.wt_pklist_dc_layout_preview_box').trigger('blur');
			$('.wfte_row').removeClass('wt_pklist_dc_row_hover');
			$('.wt_pklist_dc_editor_panel').remove();
		},

		/**
		*	Set row border on hover
		*/
		set_row_border:function(elm)
		{
			var row=this.get_row(elm);
			if(row && this.selected_row && row.attr('data-wfte-id')==this.selected_row.attr('data-wfte-id'))
			{
				return false;
			}

			this.remove_row_border();

			if(row)
			{
				this.selected_row=row;
				row.addClass('wt_pklist_dc_row_hover');
				this.set_row_editor_panel(row);
			}
		},

		set_row_editor_panel:function(elm)
		{
			/* add editing panel */
			if(elm.find('.wt_pklist_dc_editor_panel').length==0)
			{
				$('.wt_pklist_dc_editor_panel').remove();
				elm.prepend(pklist_dc.editor_panel);
				//var editor_panel_html='<div class="wt_pklist_dc_editor_panel_title_box">'+wt_pklist_dc_params.labels.edit_row+'</div><div class="wt_pklist_dc_editor_panel_btn_box">';
				var editor_panel_html='<div class="wt_pklist_dc_editor_panel_btn_box">';

				editor_panel_html+='<span class="editor_btn wt_pklist_dc_editor_delete_btn" title="'+wt_pklist_dc_params.labels.delete+'"><i class="material-icons">delete_outline</i></span>';
				editor_panel_html+='<span class="editor_btn wt_pklist_dc_editor_insertafter_btn" title="'+wt_pklist_dc_params.labels.insert_after+'"></span>';
				editor_panel_html+='<span class="editor_btn wt_pklist_dc_editor_insertbefore_btn" title="'+wt_pklist_dc_params.labels.insert_before+'"></span>';						
				editor_panel_html+='<span class="editor_btn wt_pklist_dc_editor_movedown_btn" title="'+wt_pklist_dc_params.labels.move_down+'"><i class="material-icons">keyboard_arrow_down</i></span>';
				editor_panel_html+='<span class="editor_btn wt_pklist_dc_editor_moveup_btn" title="'+wt_pklist_dc_params.labels.move_up+'"><i class="material-icons">keyboard_arrow_up</i></span>';	



				editor_panel_html+='</div>';

				var w=36.5*5; /* width of above 5 buttons */
				var pos=elm.position();
				var posl=pos.left;
				var post=pos.top;
				elm.find('.wt_pklist_dc_editor_panel').html(editor_panel_html).css({'left':posl, 'top':post, 'width':w});
			}
		},

		do_row_moveup:function(row)
		{
			var prev_row=row.prevAll('.wfte_row').first();
			if(prev_row.length>0)
			{
				/* check there is a clearfix div */
				var prev=row.prev();
				row.insertBefore(prev_row);

				if(prev.length>0 && prev.attr('class').trim()=='clearfix')
				{
					prev.insertBefore(prev_row);
				}
			}
		},


		do_row_movedown:function(row)
		{
			var next_row=row.nextAll('.wfte_row').first();
			if(next_row.length>0)
			{
				/* check there is a clearfix div */
				var nxt=row.next();
				row.insertAfter(next_row);
				if(nxt.length>0 && nxt.attr('class').trim()=='clearfix')
				{
					nxt.insertAfter(next_row);
				}
			}
		},

		/**
		*	Popup to show available row options
		*	Show row popup on click
		*/
		show_row_popup:function(row, btn_elm)
		{
			var pos=row.position();
			var posl=pos.left;
			var post=pos.top;
			var row_popup=$('.wt_pklist_dc_layout_preview_box');
			
			if(row.is('.wt_pklist_dc_empty_editor'))
			{
				post=parseInt(post)+parseInt(row.outerHeight());
				posl=parseInt(posl)+parseInt(row.css('margin-left'))+(parseInt(row.outerWidth()/2) - parseInt(row_popup.outerWidth()/2));
			}
					
			row_popup.trigger('blur');			
			row_popup.show().css({'opacity':0, 'left':posl, 'top':post}).stop(true, true).animate({'opacity':1, 'top':post+3}, 200, function(){
				$(this).focus();
			});

			pklist_dc.row_popup_rowelm=row;
			pklist_dc.row_popup_btnelm=btn_elm;	
		},

		/**
		*	Popup to show available row options
		*	Handle row popup related events.
		*	Eg: Insert new row
		*/
		set_row_popup:function()
		{
			var row_popup=$('.wt_pklist_dc_layout_preview_box');
			
			row_popup.on('blur', function(){
			    if(pklist_dc.row_popup_rowelm && pklist_dc.row_popup_rowelm.hasClass('wt_pklist_dc_empty_editor'))
			    {
			    	/* do not close the popup */
			    }else
			    {
			    	$(this).hide();
			    }
			});

			row_popup.unbind('mouseover').on('mouseover', function(){
				if(pklist_dc.row_popup_rowelm)
				{
					pklist_dc.set_row_border(pklist_dc.row_popup_rowelm);
				}
			});

			row_popup.find('.wfte_row').unbind('click').on('click', function(){
				if(!pklist_dc.row_popup_rowelm || !pklist_dc.row_popup_btnelm)
				{
					return false;
				}

				var row=pklist_dc.row_popup_rowelm;
				var btn_elm=pklist_dc.row_popup_btnelm;

				var elm=$(this).clone();
				elm=pklist_dc.add_dom_element_id(elm);

				var clearfix_elm=$('<div class="clearfix"></div>');
				clearfix_elm=pklist_dc.add_dom_element_id(clearfix_elm);

				/* source code */
				var source_elm=elm.clone();
				var source_row=pklist_dc.get_source_elm_from_visual_elm(row);
				var source_clearfix_elm=clearfix_elm.clone();

				elm=pklist_dc.set_droppable_elements(elm);
				if(btn_elm.hasClass('wt_pklist_dc_editor_insertafter_btn'))
				{
					/* source code */
					source_row.after(source_elm);
					source_row.after(source_clearfix_elm);

					row.after(elm);
					row.after(clearfix_elm);
				}else
				{
					/* source code */
					source_row.before(source_elm);
					source_row.before(source_clearfix_elm);

					row.before(elm);
					row.before(clearfix_elm);
				}
				pklist_dc.row_popup_rowelm=null;
				row_popup.trigger('blur');
				if(row.is('.wt_pklist_dc_empty_editor'))
				{
					row.remove();
				}
				pklist_dc.add_history(); /* save for undo redo */
			});
		},

		/**
		*	Register row related action events
		*/
		set_row_editable:function()
		{
			$('body').on('mouseover', function(e){
				if($(e.target).parents('.wt_pklist_dc_visual_editor').length==0 && $(e.target).parents('.wt_pklist_dc_layout_preview_box').length==0 && !$(e.target).hasClass('wt_pklist_dc_layout_preview_box'))
				{
					pklist_dc.remove_row_border();		
				}
			});

			$(document).on('mouseover', '.wt_pklist_dc_visual_editor .wfte_row', function(){
				pklist_dc.set_row_border($(this));
			});

			$(document).on('mouseout', '.wt_pklist_dc_visual_editor .wfte_row', function(e){
				pklist_dc.set_row_border($(e.target));
			});

			$(document).on('mouseover', '.wt_pklist_dc_editor_panel', function(e){ 				
				e.stopPropagation();
				var elm=$(this);
				pklist_dc.set_row_border(elm);
			});

			/* move up/down/delete */
			$(document).on('click', '.wt_pklist_dc_editor_panel_btn_box .editor_btn', function(e){ 				
				e.stopPropagation();
				var elm=$(this);
				var row=pklist_dc.get_row(elm);
				if(row)
				{
					if(elm.hasClass('wt_pklist_dc_editor_moveup_btn'))
					{
						pklist_dc.remove_row_border();
						pklist_dc.do_row_moveup(row);

						/* source code */
						var source_row=pklist_dc.get_source_elm_from_visual_elm(row);
						pklist_dc.do_row_moveup(source_row);
						pklist_dc.add_history(); /* save for undo redo */

					}else if(elm.hasClass('wt_pklist_dc_editor_movedown_btn'))
					{
						pklist_dc.remove_row_border();
						pklist_dc.do_row_movedown(row);

						/* source code */
						var source_row=pklist_dc.get_source_elm_from_visual_elm(row);
						pklist_dc.do_row_movedown(source_row);
						pklist_dc.add_history(); /* save for undo redo */
					}
					else if(elm.hasClass('wt_pklist_dc_editor_delete_btn'))
					{
						if(row.text().trim()!="" || row.find('img').length>0) /* non empty columns found, ask confirmation */
						{
							if(confirm(wt_pklist_dc_params.labels.sure))
							{
								/* source code */
								pklist_dc.source_row_delete(row);

								row.remove();
								pklist_dc.check_and_remove_editable_selected();
							}
						}else
						{
							/* source code */
							pklist_dc.source_row_delete(row);

							row.remove();
							pklist_dc.check_and_remove_editable_selected();							
						}
						pklist_dc.add_no_row_div();
						pklist_dc.add_history(); /* save for undo redo */
					}
					else if(elm.hasClass('wt_pklist_dc_editor_insertafter_btn') || elm.hasClass('wt_pklist_dc_editor_insertbefore_btn'))
					{
						pklist_dc.show_row_popup(row, elm);
					}
					
				}
			});

			$(document).on('click', '.wt_pklist_dc_empty_editor', function(){
				pklist_dc.show_row_popup($(this), $(this));
			});

			this.set_row_popup(); /* handle row_popup actions */
		},

		/**
		 *  If no rows present in the editor, shows an option to create new rows
		 * 
		 */
		add_no_row_div:function()
		{
			if($('.wt_pklist_dc_visual_editor .wfte_row').length==0) /* no row */
			{
				$('.wt_pklist_dc_visual_editor').append('<div class="wt_pklist_dc_empty_editor"></div>'); /* use append. May be other non wfte elemts are there */
			}
		},

		/**
		*	get holding of an element row 
		*/
		get_row:function(elm) 
		{
			var row=null;
			if(!elm.hasClass('wfte_row'))
			{
				var parent_row=elm.parents('.wfte_row');
				if(parent_row.length>0)
				{
					row=parent_row;
				}
			}else
			{
				row=elm;
			}
			return row;
		},
	}
	return pklist_dc_row;

})( jQuery );