var pklist_dc_undo_redo=(function( $ ) {

	var pklist_dc_undo_redo=
	{
		undo_btn:null,
		redo_btn:null,
		undo_redo_btn:null,
		visual_editor_div:null,
		code_editor_div:null,
		history_codeview:[],
		history_designview:[],
		current_index:-1,
		max_history:11,
		set_undo_redo:function()
		{
			this.undo_btn=$('.wt_pklist_dc_undo_btn');
			this.redo_btn=$('.wt_pklist_dc_redo_btn');
			this.undo_redo_btn=$('.wt_pklist_dc_undo_btn, .wt_pklist_dc_redo_btn');
			this.visual_editor_div=$('.wt_pklist_dc_visual_editor');
			this.code_editor_div=$('.wt_pklist_dc_code_editor');
			this.reg_undo_redo_events();
		},

		/**
		 * 	This is usefull on logo/signature image update. 
		 * 	This will update img preview on history design view entries
		 */
		update_to_all_history_preview:function(wfte_name, prop, val)
		{
			$.each(this.history_designview, function(indx, history_entry){
				var tmp_elm=$('<div />').html(history_entry);
				tmp_elm.find('.wfte_'+wfte_name).attr(prop, val);
				pklist_dc.history_designview[indx]=tmp_elm.html();
			});
		},

		reg_undo_redo_events:function()
		{
			this.undo_redo_btn.unbind('click').on('click', function(){
				if($(this).hasClass('wt_pklist_dc_undo_btn'))
				{
					pklist_dc.do_undo();
				}else
				{
					pklist_dc.do_redo();
				}
			});
		},

		do_undo:function()
		{
			var new_index=this.current_index-1;
			if(new_index<0)
			{
				this.undo_btn.addClass('wt_pklist_dc_btn_inactive');
				return false;
			}
			this.current_index=new_index;
			this.apply_html_from_history();

			if(new_index==0) /* no steps so disable undo */
			{
				this.undo_btn.addClass('wt_pklist_dc_btn_inactive');
			}
			this.redo_btn.removeClass('wt_pklist_dc_btn_inactive'); /* enable redo */
		},

		do_redo:function()
		{
			var new_index=parseInt(this.current_index)+1;
			if(new_index>=this.history_codeview.length)
			{
				this.redo_btn.addClass('wt_pklist_dc_btn_inactive');
				return false;
			}
			this.current_index=new_index;
			this.apply_html_from_history();

			if(new_index==(this.history_codeview.length-1)) /* no steps so disable redo */
			{
				this.redo_btn.addClass('wt_pklist_dc_btn_inactive');
			}
			this.undo_btn.removeClass('wt_pklist_dc_btn_inactive'); /* enable undo */
		},

		add_history:function()
		{
			if(this.current_index>=(this.max_history-1)) /* max limit reached, so remove an item from first */
			{
				this.history_codeview.shift();
				this.history_designview.shift();
				this.current_index=(this.max_history-1);
			}else
			{
				this.current_index++;
			}

			var design_html=this.visual_editor_div.html();
			var temp_elm=$('<div />').html(design_html);
			
			temp_elm.find('.wt_pklist_dc_item_delete, .wt_pklist_dc_editor_panel, .wt_pklist_dc_empty_editor').remove();
			temp_elm.find('.wt_pklist_dc_editable_selected').removeClass('wt_pklist_dc_editable_selected');
			temp_elm.find('.wt_pklist_dc_draggable_selected').removeClass('wt_pklist_dc_draggable_selected');
			temp_elm.find('.wt_pklist_dc_row_hover').removeClass('wt_pklist_dc_row_hover');
			design_html=temp_elm.html();

			if(typeof this.history_codeview[this.current_index]!='undefined') /* already exists, Then overwrite */
			{
				this.history_codeview[this.current_index]=this.code_editor_div.html();
				this.history_designview[this.current_index]=design_html;
			}else
			{
				this.history_codeview.push(this.code_editor_div.html());
				this.history_designview.push(design_html);
			}
			
			this.undo_btn.removeClass('wt_pklist_dc_btn_inactive');
			this.redo_btn.addClass('wt_pklist_dc_btn_inactive');
		},

		/**
		*	Clear history
		*/
		reset_undo_redo:function()
		{
			this.current_index=-1;
			this.history_codeview=[];
			this.history_designview=[];

			this.add_history(); /* add current html as first item */

			this.undo_redo_btn.addClass('wt_pklist_dc_btn_inactive');
		},

		apply_html_from_history:function()
		{
			this.code_editor_div.html(this.history_codeview[this.current_index]);
			this.visual_editor_div.html(this.history_designview[this.current_index]);
			this.set_page_property_editor();
			this.show_empty_block_editor_msg();
		}

	}
	return pklist_dc_undo_redo;

})( jQuery );