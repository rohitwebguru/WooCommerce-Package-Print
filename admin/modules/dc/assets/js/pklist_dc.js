/**
 * 	Main JS file for DC
 */
var pklist_dc=(function( $ ) {
	'use strict';

	var pklist_dc=
	{
		selected_elm:null,
		last_dom_id:-1,
		editor_panel:'<div class="wt_pklist_dc_editor_panel"></div>',
		col_delete_btn:'<div class="wt_pklist_dc_item_delete">X</div>',
		column_delete_btn:'<div class="wt_pklist_dc_block_remove"></div>',
		add_new_subitem_btn:'<span class="wt_pklist_dc_property_editor_top_panel_btn wt_pklist_dc_property_editor_top_panel_add_new_subitem_btn" title="'+wt_pklist_dc_params.labels.add+'"><span class="dashicons dashicons-plus"></span></span>',
		html_editor_btn:'<span class="wt_pklist_dc_property_editor_top_panel_btn wt_pklist_dc_property_editor_top_panel_html_editor_btn" title="'+wt_pklist_dc_params.labels.edit_html+'"><span class="dashicons dashicons-html"></span></span>',
		sidebar_bulk_action_btns:{
							'removable': '<span class="wt_pklist_dc_property_editor_top_panel_btn wt_pklist_dc_property_editor_top_panel_delete_btn" data-action="removable" title="'+wt_pklist_dc_params.labels.delete+'"><i class="material-icons">delete_outline</i></span>', 
							'mergable': '<span class="wt_pklist_dc_property_editor_top_panel_btn wt_pklist_dc_property_editor_top_panel_merge_btn" data-action="mergable" title="'+wt_pklist_dc_params.labels.merge_desc+'"></span>'
						},
		on_drag_elm:null,
		row_popup_rowelm:null,
		assets_elements_arr:{},  /* current selected item elements array */
		editable_item_arr:{}, /* current selected item/subitem editable properties array */
		property_editor_messages:{}, /* custom messages in the property editor panel */
		wfte_name:'', /* current selected item wfte_name */
		is_removable:{}, /* current selected block have removable items. This flag will populate a remove button on top panel */
		is_mergable:{}, /* current selected block have mergable items. This flag will populate a merge button on top panel */
		merged_item_title:'', /* current merged group item title */
		is_editable_tbody_found:false, /* when selecting table for editing */
		enable_code_view:false,
		code_editor:null,
		selected_row:null, /* current mouseover row */

		active_template_id:0,
		template_type:null,
		template_id:0,
		default_template_id:1,
		customizer_ajax_hook:'',
		dc_ajax_hook:'',
		template_is_active:0,
		template_loaded:0,
		open_first_panel:false, /* open first panel on editable item selected */
		initial_tmr:null,
		Set:function()
		{	
			this.prepare_asset_elements();
			this.sidebar_tab();
			this.sidebar_accordian();
			this.sidebar_asset_element_group_bulk_check();
			this.reg_events(); /* prepare user events */
			this.set_undo_redo();	

			/* js tab view issue */
			if($('div[data-id="wf_woocommerce_packing_list-dc"]').is(':visible'))
			{
				this.template_id=this.active_template_id;
				this.load_template();
			}else
			{
				this.initial_tmr=setInterval(function(){
					if($('div[data-id="wf_woocommerce_packing_list-dc"]').is(':visible'))
					{
						clearInterval(pklist_dc.initial_tmr);
						pklist_dc.template_id=pklist_dc.active_template_id;
						pklist_dc.load_template();
					}
				}, 1000);
			}
		},

		/**
		 * 	Adjust sidebar and content height based on loaded template content
		 */
		prepare_canvas:function()
		{
			setTimeout(function(){ 

				var body_h=$('.wt_pklist_dc_body').outerHeight();
				var sidebar_h=$('.wt_pklist_dc_sidebar').outerHeight();
				if(body_h>sidebar_h)
				{
					$('.wt_pklist_dc_sidebar, .wt_pklist_dc_body').css({'min-height': body_h});
				}else
				{
					$('.wt_pklist_dc_sidebar, .wt_pklist_dc_body').css({'min-height': sidebar_h});
				}

			}, 1000);
						
		},

		/**
		*	Prepare Template DOM elements for Drag/Drop/Edit
		*	Add dummy placeholders in image placeholder URLs
		*/
		prepare_asset_elements:function()
		{			
			var assets_designview_elm=$('.wt_pklist_dc_assets_designview_html');
			this.set_editable_elements(assets_designview_elm);
			this.set_draggable_elements(assets_designview_elm);	

			var assets_codeview_elm=$('.wt_pklist_dc_assets_codeview_html'); /* this is for adding data-wfte_name attribute only */
			this.set_editable_elements(assets_codeview_elm);

			$.each(wt_pklist_dc_params.assets_add_new_item, function(wfte_name, add_new_options){
				if(add_new_options.hasOwnProperty('deletable'))
				{
					$.each(add_new_options['deletable'], function(slug, meta_key){

						var sidebar_asset_elm=$('.wt_pklist_dc_asset_item[data-slug="'+slug+'"][data-parent-slug="'+wfte_name+'"]');
						if(sidebar_asset_elm.length>0)
						{
							pklist_dc.add_asset_delete_btn(sidebar_asset_elm, slug, wfte_name, meta_key);
						}
					});
				}
				$('.wt_pklist_dc_sidebar_assets_add_new[data-slug="'+wfte_name+'"]').attr('title', add_new_options.title);
			});

			/**
			 * Asset delete event for custom added assets
			 */
			$(document).on('click', '.wt_pklist_dc_asset_delete_btn', function(e){
				e.stopPropagation();
				if(confirm(wt_pklist_dc_params.labels.meta_delete_warn))
				{
					pklist_dc.delete_order_meta($(this));
				}
			});

		},

		add_asset_delete_btn:function(sidebar_asset_elm, slug, wfte_name, meta_key)
		{
			sidebar_asset_elm.append('<div class="wt_pklist_dc_sidebar_asset_btn wt_pklist_dc_asset_delete_btn" data-meta-key="'+meta_key+'" data-parent-slug="'+wfte_name+'" data-slug="'+slug+'" title="'+wt_pklist_dc_params.labels.delete+'"><i class="material-icons">delete_outline</i></div>');
		},

		/**
		*	Prepare Template DOM elements for Drag/Drop/Edit
		*
		*/
		prepare_template_elements:function(editor_elm)
		{
			/* on visual editor */
			if(!editor_elm)
			{
				var editor_elm=$('.wt_pklist_dc_visual_editor');
			}
			this.set_editable_elements(editor_elm);
			this.set_draggable_elements(editor_elm);
			this.set_droppable_elements(editor_elm);

		},

		/**
		*	Checks the current template have full compatibility with DC
		*
		*/
		check_template_compatibility:function()
		{
			var editor_elm=$('.wt_pklist_dc_visual_editor');
			var incompatiblity=0;
			if(editor_elm.find('.wfte_editable').length==0)
			{
				incompatiblity++;
			}
			if(editor_elm.find('.wfte_draggable').length==0)
			{
				incompatiblity++;
			}
			if(editor_elm.find('.wfte_droppable').length==0)
			{
				incompatiblity++;
			}
			if(incompatiblity>0)
			{
				$('.wt_pklist_dc_template_compatibility_wrn').show();
			}else{
				$('.wt_pklist_dc_template_compatibility_wrn').hide();
			}
		},

		/**
		*	Register major action events
		*
		*/
		reg_events:function()
		{
			
			this.reg_property_editor_events();

			/**
			*	Add editable selection on click
			*/
			$(document).on('click', '.wt_pklist_dc_visual_editor .wfte_editable, .wt_pklist_dc_visual_editor .wfte_draggable', function(e){				
				e.stopPropagation();
				var elm=$(this);
				pklist_dc.set_editable_selected(elm);
			});

			/**
			*	Add mouseover highlight on editable element/ its containing row
			*/
			$(document).on('mouseover', '.wt_pklist_dc_visual_editor .wfte_editable, .wt_pklist_dc_visual_editor .wfte_draggable', function(e){				
				e.stopPropagation();
				var elm=$(this);
				pklist_dc.set_row_border(elm);
				pklist_dc.hightlight_editable(elm);
			});

			/**
			*	Remove highlight on editable element
			*/
			$(document).on('mouseout', '.wt_pklist_dc_visual_editor .wfte_editable, .wt_pklist_dc_visual_editor .wfte_draggable', function(){
				var elm=$(this);
				elm.removeClass('wt_pklist_dc_editable_hover');
			});


			/**
			*	Register element delete button action
			*/
			$(document).on('click', '.wt_pklist_dc_visual_editor .wt_pklist_dc_item_delete', function(e){ 				
				e.stopPropagation();
				pklist_dc.do_elm_delete($(this));
			});


			$(document).on('keyup', '.wt_pklist_dc_assets_search', function(){
				pklist_dc.do_assets_search($(this));
			});

			/**
			 *	Dropdown menu 
			 */
			$(document).on('click', '.wt_pklist_dc_options_btn', function(){
				var menu_popup=$('.wt_pklist_dc_dropdown_menu');
				var btn_elm=$(this);
				var pos=btn_elm.position();
				var posl=pos.left-parseInt(menu_popup.width())+parseInt(btn_elm.outerWidth());
				var post=pos.top+parseInt(btn_elm.height());
				menu_popup.show().css({'opacity':0, 'left':posl, 'top':post}).stop(true, true).animate({'opacity':1, 'top':post+5}, 200, function(){
					$(this).focus();
				});

			});


			/**
			 *	Dropdown menu  close
			 */
			$('.wt_pklist_dc_dropdown_menu').on('blur', function(){
			    $(this).hide();
			});

			/**
			*	Code editor events
			*/
			this.source_code_editor_events();

			/**
			*	Drag and drop events
			*/
			this.set_drag_and_drop();

			/**
			*	Register row related action events
			*/
			this.set_row_editable();


			this.set_color_picker_events();

			this.file_uploader_events();

			this.form_toggler();

			this.reg_ajax_events();

			this.default_templates_events();
		},

		default_templates_events:function()
		{
			/* default template popup */
			$('.wt_pklist_dc_new_template').click(function(){
				var popup_elm=$('.wt_pklist_dc_default_template_list');
				$('.wt_pklist_dc_dropdown_menu').trigger('blur');
				wf_popup.showPopup(popup_elm);
			});
			

			/* default template choose */
			$('.wt_pklist_dc_default_template_list_item').click(function(){
				$('.wt_pklist_dc_default_template_list_item').find('.wt_pklist_dc_default_template_list_item_inner').css({'box-shadow':'none'});
				$(this).find('.wt_pklist_dc_default_template_list_item_inner').css({'box-shadow':'2px 3px 11px 0px #68b3d7'});
				pklist_dc.default_template_id=$(this).attr('data-id');
				pklist_dc.template_id=0;
				wf_popup.hidePopup();
				pklist_dc.load_template();
			});
		},

		/**
		 *  Do asset search based on user search input
		 * 
		 */
		do_assets_search:function(input_elm)
		{
			var tab_content=$('.wt_pklist_dc_sidebar_tabcontent[data-tab-id="wt-pklist-dc-sidebar-assets"]');
			var vl=input_elm.val().trim().toLowerCase();
			if(vl=="")
			{
				tab_content.find('.wt_pklist_dc_sidebar_tabaccord, .wt_pklist_dc_asset_item').show();
				pklist_dc.sidebar_accordian_close_all();
				return false;
			}
			tab_content.find('.wt_pklist_dc_sidebar_tabaccord, .wt_pklist_dc_asset_item').hide();
			
			tab_content.find('.wt_pklist_dc_asset_item').filter(function(){
				var name=$(this).text();
				name=name.toLowerCase();
				if(name.search(vl)!=-1)
				{
					return true;
				}else
				{
					return false;
				}
			}).show().parents('.wt_pklist_dc_sidebar_tabaccord').show().find('.wt_pklist_dc_sidebar_tabaccord_hd').each(function(){
				pklist_dc.sidebar_accordian_open($(this));
			});
		},

		/**
		*	Get the targeted element from input/button element.
		*	This may be a sub item, or a main item
		*/
		get_target_element:function(input_elm)
		{
			return input_elm.parents('.wt_pklist_dc_sidebar_tabaccord').data('target_elm');
		},

		get_visual_elm_by_wfte_id:function(wfte_id)
		{
			return $('.wt_pklist_dc_visual_editor [data-wfte-id="'+wfte_id+'"]');
		},

		get_source_elm_by_wfte_id:function(wfte_id)
		{
			return $('.wt_pklist_dc_code_editor [data-wfte-id="'+wfte_id+'"]');
		},

		get_asset_visual_elm_by_wfte_name:function(asset_slug)
		{
			return $('.wt_pklist_dc_assets_designview_html').find('.wfte_'+asset_slug).eq(0);
		},

		get_asset_source_elm_by_wfte_name:function(asset_slug)
		{
			return $('.wt_pklist_dc_assets_codeview_html').find('.wfte_'+asset_slug).eq(0);
		},

		get_wfte_id:function(elm)
		{
			return elm.attr('data-wfte-id');
		},

		/**
		* check this is a merged group wfte_name 
		*/
		is_merged_group_wfte_name:function(wfte_name)
		{
			return (wfte_name.indexOf('=')!=-1);
		},	
				

		/**
		*	Add unique ID for DOM elements.
		*	This ID is making connection with code editor and visual editor
		*/
		add_dom_element_id:function(elm, source_elm)
		{
			this.last_dom_id=parseInt($('.wt_pklist_last_dom_id').val());
			this.last_dom_id++;
			elm.attr('data-wfte-id', this.last_dom_id);
			var last_dom_id=this.add_dom_element_id_sub(elm);

			if(source_elm) /* source element */
			{
				source_elm.attr('data-wfte-id', this.last_dom_id);
				var last_dom_id=this.add_dom_element_id_sub(source_elm); 
			}

			pklist_dc.last_dom_id=last_dom_id;

			$('.wt_pklist_last_dom_id').val(pklist_dc.last_dom_id);
			return elm;
		},

		/**
		*	Sub function for add dom ID
		*/
		add_dom_element_id_sub:function(elm)
		{
			var last_dom_id=elm.attr('data-wfte-id');
			elm.find('*').not(wt_pklist_dc_params.domid_exclude_elements.join(",")).each(function(){
				last_dom_id++;
				$(this).attr('data-wfte-id', last_dom_id);
			});
			return last_dom_id;
		},

		br2nl:function(str)
		{
			str=str.replace(/<br>/g, "\r");
			return str.replace(/<br \/>/g, "\r");
		},

		nl2br:function(str)
		{   
		    var breakTag ='<br />';    
		    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
		},

	}

	return pklist_dc;
})( jQuery );

jQuery(document).ready(function(){
	
	/**
	 * 	Extend the `pklist_dc` object by merging the supporting objects
	 */
	pklist_dc=jQuery.extend(pklist_dc, pklist_dc_sidebar, pklist_dc_editable, pklist_dc_drag_drop, pklist_dc_row, pklist_dc_property_editor, pklist_dc_source_code, pklist_dc_ajax, pklist_dc_undo_redo);

	if(wt_pklist_dc_params.enable_code_view) /* initiate the code editor */
	{
		var mixedMode = ({
		        name: "htmlmixed",
		      },
		      { 
		      	name:'css'
		      });

		var pklist_dc_code_editor=CodeMirror.fromTextArea(document.getElementById("pklist_dc_code_editor"), {
		    lineNumbers:true,
		    mode:mixedMode,
		    lineWrapping:true,
		    indentUnit:4,
		    smartIndent:true, 
		});
		pklist_dc.enable_code_view=true;
		pklist_dc.code_editor=pklist_dc_code_editor;
	}

	/* prepare config vals */
	pklist_dc.active_template_id=wt_pklist_dc_params.active_template_id;
	pklist_dc.template_type=wt_pklist_dc_params.template_type;
	pklist_dc.customizer_ajax_hook=wt_pklist_dc_params.customizer_ajax_hook;
	pklist_dc.dc_ajax_hook=wt_pklist_dc_params.dc_ajax_hook;

	pklist_dc.Set();
});

