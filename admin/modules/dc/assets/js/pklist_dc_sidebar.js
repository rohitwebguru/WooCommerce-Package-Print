var pklist_dc_sidebar=(function( $ ) {

	var pklist_dc_sidebar=
	{
		sidebar_tab:function()
		{
			$(document).on('click', '.wt_pklist_dc_sidebar_tab_btn', function(){
				
				$('.wt_pklist_dc_sidebar_tab_btn').removeClass('wt_pklist_active_tab');
				var target=$(this).attr('data-tab-target');
				$(this).addClass('wt_pklist_active_tab');
				$('.wt_pklist_dc_sidebar_tabcontent').hide();
				$('.wt_pklist_dc_sidebar_tabcontent[data-tab-id="'+target+'"]').show();
				
				if(target=='wt-pklist-dc-sidebar-assets')
				{
					pklist_dc.do_assets_search($('.wt_pklist_dc_assets_search'));

				}else if(target=='wt-pklist-dc-sidebar-page')
				{
					/* open propery tab */
					pklist_dc.sidebar_accordian_open($('.wt_pklist_dc_sidebar_tabcontent[data-tab-id="'+target+'"]').find('.wt_pklist_dc_sidebar_tabaccord:eq(0) .wt_pklist_dc_sidebar_tabaccord_hd'));
				}
			});

			this.show_empty_block_editor_msg();
		},
		get_sidebar_tab_element:function(tab_key)
		{
			return $('.wt_pklist_dc_sidebar_tabcontent[data-tab-id="'+tab_key+'"]');
		},
		show_sidebar_tab_by_key:function(tab_key)
		{
			$('.wt_pklist_dc_sidebar_tab_btn[data-tab-target="'+tab_key+'"]').trigger('click');
		},
		sidebar_accordian:function()
		{
			$(document).on('click', '.wt_pklist_dc_sidebar_tabaccord_hd, .wt_pklist_dc_sidebar_tabaccord_accord', function(e){

				if($(e.target).hasClass('wt_pklist_dc_sidebar_tabaccord_checkbox'))
				{
					return false;
				}

				var hd_elm=$(this);
				if($(this).hasClass('wt_pklist_dc_sidebar_tabaccord_accord'))
				{
					hd_elm=$(this).parents('.wt_pklist_dc_sidebar_tabaccord_hd');
				}
				
				var content_elm=hd_elm.siblings('.wt_pklist_dc_sidebar_tabaccord_content');
				if(content_elm.length==0){ return false; }

				if(content_elm.is(':visible'))
				{
					pklist_dc.sidebar_accordian_close_all();
				}else
				{
					pklist_dc.sidebar_accordian_close_all();
					pklist_dc.sidebar_accordian_open(hd_elm);					
				}	
			});
		},
		sidebar_accordian_open:function(hd_elm)
		{
			hd_elm.siblings('.wt_pklist_dc_sidebar_tabaccord_content').show();
			hd_elm.addClass('wt_pklist_dc_sidebar_tabaccord_open');
			hd_elm.find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
		},
		sidebar_accordian_close_all:function()
		{
			$('.wt_pklist_dc_sidebar_tabaccord_content').hide();
			$('.wt_pklist_dc_sidebar_tabaccord_hd').removeClass('wt_pklist_dc_sidebar_tabaccord_open');
			$('.wt_pklist_dc_sidebar_tabaccord_hd .dashicons').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
		},
		sidebar_asset_element_group_bulk_check:function()
		{
			$(document).on('click', '.wt_pklist_dc_asset_element_group_checkbox', function(){
				var tab_content=$(this).parents('.wt_pklist_dc_sidebar_tabaccord_content');
				if(tab_content.find('.wt_pklist_dc_asset_element_group_checkbox:checked').length>0)
				{
					tab_content.find('.wt_pklist_dc_asset_element_group_checkbox_main').prop('checked', true);
				}else
				{
					tab_content.find('.wt_pklist_dc_asset_element_group_checkbox_main').prop('checked', false);
				}
			});

			$(document).on('click', '.wt_pklist_dc_asset_element_group_checkbox_main', function(){
				var tab_content=$(this).parents('.wt_pklist_dc_sidebar_tabaccord_content');
				if($(this).is(':checked'))
				{
					tab_content.find('.wt_pklist_dc_asset_element_group_checkbox').prop('checked', true);
				}else
				{
					tab_content.find('.wt_pklist_dc_asset_element_group_checkbox').prop('checked', false);
				}
			});

			/* prevent opening of accordian while clicking checkbox */
			$(document).on('click', '.wt_pklist_dc_sidebar_tabaccord_checkbox input[type="checkbox"]', function(e){
				e.stopPropagation();
			});
		},

		/**
		*	Reset the bulk action panel
		*	This will hide the bottom panel and checkboxes
		*/
		reset_sidebar_bulk_action_panel:function()
		{
			$('.wt_pklist_dc_property_editor_bottom_panel').html('').hide();
			$('.wt_pklist_dc_sidebar_tabaccord_checkbox').hide().find('input[type="checkbox"]').prop('checked', false);
		},

		/**
		*	Re-initiate the sortable. This method will call after: merging, add items from other elements
		*/
		reload_sidebar_sortable:function(elm)
		{
			$('.wt_pklist_dc_sidebar_tabcontent').sortable("destroy");
			this.initiate_sidebar_sortable(elm);
		},

		initiate_sidebar_sortable:function(elm)
		{
			$('.wt_pklist_dc_sidebar_tabcontent').sortable({
				items:$('.wt_pklist_dc_sidebar_tabcontent').children('.wt_pklist_dc_sidebar_sortable'), /* only pick direct children. There may be same items under `other items block` */
				handle:'.wt_pklist_dc_sidebar_tabaccord_sortable',
				axis: "y",
				delay: 150,
				start:function(event, ui){  pklist_dc.sidebar_accordian_close_all();  },
				stop:function(event, ui){  pklist_dc.do_rearrange_based_on_sort(elm);  },
			});
		},

		/**
		*	Add bulk action checkbox to sidebar property editor accordian
		*
		*/
		add_checkbox_html_to_sidebar_accord:function(hd_elm, wfte_name, checkbox_added, action_name)
		{
			if(!checkbox_added)
			{	
				var checkbox_html='<div class="wt_pklist_dc_sidebar_tabaccord_checkbox"><input type="checkbox" data-target_wfte_name="'+wfte_name+'"/></div>';
				if(hd_elm.find('.wt_pklist_dc_sidebar_tabaccord_sortable').length>0) /* sortable handle already added, then add checkbox after that */
				{
					hd_elm.find('.wt_pklist_dc_sidebar_tabaccord_sortable').after(checkbox_html);
				}else
				{
					hd_elm.prepend(checkbox_html);
				}
				checkbox_added=true;
			}

			/* add the current action as attribute to the checkbox. */
			hd_elm.find('.wt_pklist_dc_sidebar_tabaccord_checkbox').attr('data-'+action_name, true);

			return checkbox_added;
		},

		form_toggler:function()
		{
			$(document).on('change', '.wt_pklist_dc_form_toggler', function(){
				var toggle_id=$(this).attr('data-form-toggle-id');
				var toggle_val=$(this).val();
				
				var child_elements=$('.wt_pklist_dc_form_toggler_child_block[data-form-toggle-target="'+toggle_id+'"]');
				
				child_elements.hide(); /* hide all child elements */
				
				child_elements.filter('[data-form-toggle-value="'+toggle_val+'"]').show(); /* show the current value elements */
			});
		},
	}
	return pklist_dc_sidebar;
})( jQuery );