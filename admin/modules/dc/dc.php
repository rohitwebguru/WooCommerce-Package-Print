<?php
/**
 * Template customizer (Dynamic customizer)
 *
 * @link
 * @since 4.0.0
 *
 * @package  Wf_Woocommerce_Packing_List
 */
if (!defined('ABSPATH')) {
    exit;
}
class Wf_Woocommerce_Packing_List_Dc
{
	public $module_id='';
	public static $module_id_static='';
	public $module_base='dc';
	public $enable_code_view=true;
	public $documents=array();
	private static $instance = null;
	public function __construct()
	{
		$this->documents=array(
			'invoice'			=>__('Invoice', 'wf-woocommerce-packing-list'),
			/*'packinglist'		=>__('Packing slip', 'wf-woocommerce-packing-list'),
			'shippinglabel'		=>__('Shipping label', 'wf-woocommerce-packing-list'),
			'deliverynote'		=>__('Delivery note', 'wf-woocommerce-packing-list'),
			'picklist'			=>__('Picklist', 'wf-woocommerce-packing-list'),
			'addresslabel'		=>__('Addresse label', 'wf-woocommerce-packing-list'),
			'creditnote'		=>__('Credit note', 'wf-woocommerce-packing-list'),
			'dispatchlabel'		=>__('Dispatch label', 'wf-woocommerce-packing-list'),
			'proformainvoice'	=>__('Proforma invoice', 'wf-woocommerce-packing-list'), */
		);
		if(Wf_Woocommerce_Packing_List_Dc::$instance!==null)
		{
			return Wf_Woocommerce_Packing_List_Dc::$instance;
		}

		$this->module_id=Wf_Woocommerce_Packing_List::get_module_id($this->module_base);
		self::$module_id_static=$this->module_id;

		add_filter('wt_pklist_intl_alter_html_source', array($this, 'prepare_designview_html'), 10, 3);

		/* ajax main hook to handle all ajax actions */
		add_action('wp_ajax_wt_pklist_dc_ajax', array($this, 'ajax_main'), 1);

		Wf_Woocommerce_Packing_List_Dc::$instance=$this;
	}

	public static function get_instance()
	{
		if(self::$instance==null)
		{
			self::$instance=new Wf_Woocommerce_Packing_List_Dc();
		}

		return self::$instance;
	}

	/**
	 *
	 * 	Initializing customizer under module settings page hook
	 **/
	public function init($base)
	{
		$this->to_customize=$base;
		$this->to_customize_id=Wf_Woocommerce_Packing_List::get_module_id($base);
		add_filter('wt_pklist_module_settings_tabhead', array( __CLASS__, 'settings_tabhead'));
		add_action('wt_pklist_module_out_settings_form', array($this, 'out_settings_form'));
	}

	/**
	 *  =====Module settings page Hook=====
	 * 	Tab head for module settings page
	 **/
	public static function settings_tabhead($arr)
	{
		$added=0;
		$out_arr=array();
		$menu_pos_key=isset($arr['invoice-number']) ? 'invoice-number' : 'general';
		foreach($arr as $k=>$v)
		{
			$out_arr[$k]=$v;
			if($k==$menu_pos_key && $added==0)
			{
				$out_arr[WF_PKLIST_POST_TYPE.'-dc']=__('Customize', 'wf-woocommerce-packing-list');
				$added=1;
			}
		}
		if($added==0){
			$out_arr[WF_PKLIST_POST_TYPE.'-dc']=__('Customize', 'wf-woocommerce-packing-list');
		}
		return $out_arr;
	}

	/**
	 * 	Customizer screen
	 *
	 */
	public function out_settings_form($args)
	{
		/**
		 *	1. Customizer module must be active
		 *	2. Plugin version must be greater than 4.1.9
		 */
		if(!Wf_Woocommerce_Packing_List_Admin::module_exists('customizer') || version_compare(WF_PKLIST_VERSION, '4.2.0')==-1)
		{
			return;
		}

		$template_type=$this->to_customize;

		/**
		*	Customizer object
		*/
		$customizer=Wf_Woocommerce_Packing_List_Customizer::get_instance();

		/**
		 * Enqueue scripts
		 */
		$this->enqueue_scripts();
		$this->enqueue_styles();


		/* ==== Assets Preparing start ===== */

		/**
		*	Extracting data from assets
		* 	1. Preparing HTML for assets section
		* 	2. Taking customizable properties for assets
		*/
		$assets=$this->get_assets($template_type);
		$img_url_placeholders=$this->get_dummy_placeholder_for_images($template_type);
		$assets_codeview_html_arr=array();
		$assets_placeholders_arr=array();
		$assets_editable_properties=array(); /* which editable property to be shown in the right side panel */
		$assets_elements=array(); /* sub elements available under an asset */
		$assets_titles=array(); /* Titles of assets */
		$assets_add_new_item=array(); /* Elements that need add new sub item option. Eg: Product table */
		$property_editor_messages=array(); /* Custom messages to show in property editor section */
		$editable=array(); /* editable elements in the template */
		$draggable=array(); /* draggable elements in the template */

		$this->extract_assets_data($assets_codeview_html_arr, $assets_placeholders_arr, $assets_editable_properties, $assets_elements, $assets_titles, $assets_add_new_item, $property_editor_messages, $assets);

		$this->get_editable_and_draggable($template_type, $assets_elements, $editable, $draggable); /* prepare editable and draggable elements */

		/* preparing placeholders array, If assets have custom values */
		$assets_placeholders_arr=array_filter($assets_placeholders_arr);
		$custom_find_replace_arr=array();
		foreach($assets_placeholders_arr as $value)
		{
			$custom_find_replace_arr=array_merge($custom_find_replace_arr, $value);
		}


		/* prepare code/design view HTML for assets section */
		$assets_codeview_html_arr=array_unique(array_filter($assets_codeview_html_arr));
		$assets_codeview_html=implode('', $assets_codeview_html_arr);

		$assets_html=$customizer->convert_to_design_view_html($assets_codeview_html, $template_type, $custom_find_replace_arr);

		/* replace img src with dummy values to avoid 404 errors */
		$assets_codeview_html=str_replace(array_keys($img_url_placeholders), array_values($img_url_placeholders), $assets_codeview_html);
		/* ==== Assets Preparing end ===== */


		$active_theme_arr=$customizer->get_current_active_theme($template_type);
		$active_template_id=0;
		$template_is_active=0;
		if(!is_null($active_theme_arr) && isset($active_theme_arr->id_wfpklist_template_data))
		{
			$active_template_id=$active_theme_arr->id_wfpklist_template_data;
			$template_is_active=1;
		}

		/**
		 * 	Params for JS section
		 */
		$params=array(
			'nonces' => array(
	            'dc_nonce'=>wp_create_nonce($this->module_id),
	            'main'=>wp_create_nonce($customizer->module_id), /* we are using customizer nonce for ajax */
	        ),
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'labels'=>array(
	        	'error'=>__('Error', 'wf-woocommerce-packing-list'),
	        	'success'=>__('Success','wf-woocommerce-packing-list'),
	        	'no_undo_sure'=>__("You can't undo this action. Are you sure?",'wf-woocommerce-packing-list'),
	        	'sure'=>__("Are you sure?",'wf-woocommerce-packing-list'),
	        	'saving'=>__("Saving",'wf-woocommerce-packing-list'),
	        	'move'=>__("Move",'wf-woocommerce-packing-list'),
	        	'delete'=>__("Delete",'wf-woocommerce-packing-list'),
	        	'merge'=>__("Merge",'wf-woocommerce-packing-list'),
	        	'cancel'=>__("Cancel",'wf-woocommerce-packing-list'),
	        	'merge_desc'=>__("Merge items",'wf-woocommerce-packing-list'),
	        	'insert_after'=>__("Insert below",'wf-woocommerce-packing-list'),
	        	'insert_before'=>__("Insert above",'wf-woocommerce-packing-list'),
	        	'move_up'=>__("Move up",'wf-woocommerce-packing-list'),
	        	'move_down'=>__("Move down",'wf-woocommerce-packing-list'),
	        	'edit_row'=>__("Edit row", 'wf-woocommerce-packing-list'),
	        	'drag_to_rearrange'=>__("Drag to rearrange", 'wf-woocommerce-packing-list'),
	        	'drag_item_tooltip'=>__("Drag item to editor", 'wf-woocommerce-packing-list'),
	        	'split_items'=>__("Click to split the items", 'wf-woocommerce-packing-list'),
	        	'other_available_items'=>__("Other items available in the block", 'wf-woocommerce-packing-list'),
	        	'add'=>__("Add", 'wf-woocommerce-packing-list'),
	        	'please_select_one'=>__("Please select atleast one item.", 'wf-woocommerce-packing-list'),
	        	'label'=>__("Label", 'wf-woocommerce-packing-list'),
	        	'all_fields_mandatory'=>__("All fields are mandatory", 'wf-woocommerce-packing-list'),
	        	'edit_html'=>__("Edit block item HTML", 'wf-woocommerce-packing-list'),
	        	'title'=>__("Title", 'wf-woocommerce-packing-list'),
	        	'bulk_action_no_items_found'=>__("No items found", 'wf-woocommerce-packing-list'),
	        	'choose_a_block_from_editor'=>__("Choose a block from editor.", 'wf-woocommerce-packing-list'),
	        	'properties'=>__('Properties', 'wf-woocommerce-packing-list'),
	        	'please_wait'=>__('Please wait...', 'wf-woocommerce-packing-list'),
	        	'create_new'=>__("Create new template", 'wf-woocommerce-packing-list'),
	        	'change_theme'=>__("Change layout", 'wf-woocommerce-packing-list'),
	        	'no_items_to_drop'=>__("Please select atleast one item from the list.", 'wf-woocommerce-packing-list'),
	        	'meta_delete_warn'=>sprintf(__("Deleting the meta from assets will remove the associated data from the %s. Are you sure?", 'wf-woocommerce-packing-list'), $this->get_doc_title($template_type)),
	        	'deleting'=>__("Deleting", 'wf-woocommerce-packing-list'),
	        	'unable_to_locate_source_elm'=>__("Unable to locate the source element. Please reload the page and try again. Note: Please save the changes before reloading.", 'wf-woocommerce-packing-list'),
	        	'image_files_only'=>__("Please choose an image file.", 'wf-woocommerce-packing-list'),
	        ),
	        'draggable_elements'=>$draggable,
	        'editable_elements'=>$editable,
	        'droppable_elements'=>$this->get_droppable($template_type),
	        'domid_exclude_elements'=>$this->get_domid_exclude_dom_elements(),
	        'img_url_placeholders'=>$img_url_placeholders,
	        'assets_editable_properties'=>$assets_editable_properties,
	        'assets_elements'=>$assets_elements,
	        'assets_titles'=>$assets_titles,
	        'assets_add_new_item'=>$assets_add_new_item,
	        'property_editor_messages'=>$property_editor_messages,
	        'enable_code_view'=>$this->enable_code_view,
	        'page_editable_properties'=>$this->get_page_editable_properties($template_type),
	        'active_template_id'=>$active_template_id,
	        'template_is_active'=>$template_is_active,
	        'template_type'=>$template_type,
	        'extra_field_slug_prefix'=>Wf_Woocommerce_Packing_List_Customizer::$extra_field_slug_prefix,
	        'customizer_ajax_hook'=>'wfpklist_customizer_ajax', /* default customizer ajax hook */
	        'dc_ajax_hook'=>'wt_pklist_dc_ajax', /* dynamic customizer ajax hook */
	        'advanced_fields_ajax_hook'=>'wf_pklist_advanced_fields', /* add custom meta hook */
		);
		wp_localize_script($this->module_id, 'wt_pklist_dc_params', $params);

		$layout_items=$this->get_layouts($template_type); /* row layout options for new row adding popover */
		$editor_panel_data=$this->get_property_editor($template_type); /* editing panel items */


		//default template list
		$def_template_url=$customizer->get_default_template_path($template_type, 'url');
		$def_template_path=$customizer->get_default_template_path($template_type);
		$def_template_arr=array();
		if($def_template_path) //module exists/ template exists
		{
			$def_template_arr=$customizer->load_default_templates($def_template_path, $template_type, '', true);
		}

		/**
		 * 	Params for view file
		 */
		$params=array(
			'assets_codeview_html'=>$assets_codeview_html,
			'assets_html'=>$assets_html,
			'layout_items'=>$layout_items,
			'template_type'=>$template_type,
			'def_template_arr'=>$def_template_arr,
			'assets'=>$assets,
			'assets_add_new_item'=>$assets_add_new_item,
			'def_template_url'=>$def_template_url,
			'editor_panel_data'=>$editor_panel_data,
			'dc'=>self::get_instance(),
		);
		$view_file=plugin_dir_path( __FILE__ ).'views/customize.php';

		Wf_Woocommerce_Packing_List_Admin::envelope_settings_tabcontent(WF_PKLIST_POST_TYPE.'-dc', $view_file, '', $params, 0);

	}

	/**
	 *  Main ajax hook to handle all ajax actions under DC.
	 *
	 */
	public function ajax_main()
	{
		$out=array(
			'status'=>0,
			'msg'=>__("Error", 'wf-woocommerce-packing-list')
		);
    	if(Wf_Woocommerce_Packing_List_Admin::check_write_access($this->module_id)) //no error then proceed
    	{
			$allowed_actions=array('update_settings');
			$action=sanitize_text_field($_REQUEST['wt_pklist_dc_action']);
			include(plugin_dir_path( __FILE__ ).'classes/class-dc-ajax.php');
			$dc_ajax=new Wf_Woocommerce_Packing_List_Dc_Ajax();
			if(method_exists($dc_ajax, $action))
			{
				$out=$dc_ajax->{$action}();
			}
		}
		echo json_encode($out);
		exit();
	}

	public function get_doc_title($template_type)
	{
		return isset($this->documents[$template_type]) ? $this->documents[$template_type] : __('Unknown', 'wf-woocommerce-packing-list');
	}

	/**
	 * 	List of available property editing fields in property editor
	 *
	 */
	public function get_property_editor($template_type)
	{
		$editor=array(
			'content'=>array(
				'title'=>'',
				'id'=>'content',
				'fields'=>array(
					'html'=>array(
						'label'=>__('Content (HTML/Text)', 'wf-woocommerce-packing-list'),
						'field_type'=>'textarea',
						'width'=>'100%',
					),
					'text'=>array(
						'label'=>__('Text', 'wf-woocommerce-packing-list'),
						'width'=>'100%',
					),
					'attr-src'=>array(
						'label'=>__('%s', 'wf-woocommerce-packing-list'),
						'field_type'=>'image-uploader',
						'width'=>'100%',
					),
				)
			),
			'dimension-background'=>array(
				'title'=>__('Dimension/Background', 'wf-woocommerce-packing-list'),
				'id'=>'dimension-background',
				'fields'=>array(
					'width'=>array(
						'label'=>__('Width', 'wf-woocommerce-packing-list'),
						'width'=>'30%',
						'float'=>'left'
					),
					'height'=>array(
						'label'=>__('Height', 'wf-woocommerce-packing-list'),
						'width'=>'30%',
						'float'=>'left'
					),
					'margin'=>array(
						'label'=>__('Margin', 'wf-woocommerce-packing-list'),
						'width'=>'100%',
						'field_type'=>'four_side_text',
						'slug'=>'margin-%s',
					),
					'padding'=>array(
						'label'=>__('Padding', 'wf-woocommerce-packing-list'),
						'width'=>'100%',
						'field_type'=>'four_side_text',
						'slug'=>'padding-%s',
					),
					'background-color'=>array(
						'label'=>__('Background color', 'wf-woocommerce-packing-list'),
						'width'=>'40%',
						'float'=>'left',
						'field_type'=>'color'
					),
				)
			),
			'typograhy'=>array(
				'title'=>__('Typograhy', 'wf-woocommerce-packing-list'),
				'id'=>'typograhy',
				'fields'=>array(
					'font-family'=>array(
						'label'=>__('Font', 'wf-woocommerce-packing-list'),
						'width'=>'70%',
						'float'=>'left',
						'field_type'=>'dropdown',
						'items'=>array( //dropdown items
							'Arial'=>'Arial',
							'Verdana'=>'Verdana',
							'Helvetica'=>'Helvetica',
							'Georgia'=>'Georgia',
							'Courier New'=>'Courier',
						),
					),
					'font-size'=>array(
						'label'=>__('Text size', 'wf-woocommerce-packing-list'),
						'width'=>'30%',
						'float'=>'left',
					),
					'line-height'=>array(
						'label'=>__('Line spacing', 'wf-woocommerce-packing-list'),
						'width'=>'30%',
						'float'=>'left',
					),
					'color'=>array(
						'label'=>__('Text color', 'wf-woocommerce-packing-list'),
						'width'=>'37%',
						'float'=>'left',
						'field_type'=>'color'
					),
					'text-align'=>array(
						'label'=>'&nbsp;',
						'width'=>'40%',
						'float'=>'left',
						'field_type'=>'button_radio',
						'values'=>array('left', 'center', 'right'),
						'icons'=>array('<span class="dashicons dashicons-editor-alignleft"></span>', '<span class="dashicons dashicons-editor-aligncenter"></span>', '<span class="dashicons dashicons-editor-alignright"></span>'),
					),
					'text-style'=>array(
						'field_type'=>'button_group',
						'label'=>'&nbsp;',
						'width'=>'33%',
						'float'=>'left',
						'items'=>array(
							'font-weight'=>array(
								'label'=>'&nbsp;',
								'field_type'=>'button_checkbox',
								'values'=>array('bold', 'normal'),
								'icon'=>'<span class="dashicons dashicons-editor-bold"></span>',
							),
							'font-style'=>array(
								'label'=>'&nbsp;',
								'field_type'=>'button_checkbox',
								'values'=>array('italic', 'normal'),
								'icon'=>'<span class="dashicons dashicons-editor-italic"></span>',
							),
							/*'text-decoration'=>array(
								'label'=>__('Text decoration', 'wf-woocommerce-packing-list'),
								'field_type'=>'button_radio',
								'values'=>array('overline', 'line-through', 'underline'),
								'icons'=>array('<span style="text-decoration:overline;">O</span>', '<span style="text-decoration:line-through;">S</span>', '<span style="text-decoration:underline;">U</span>'),
							), */
						)
					),

				)
			),
			'border'=>array(
				'title'=>__('Border', 'wf-woocommerce-packing-list'),
				'id'=>'border',
				'fields'=>array(
					'border-width'=>array(
						'label'=>__('Border width', 'wf-woocommerce-packing-list'),
						'width'=>'100%',
						'field_type'=>'four_side_text',
						'slug'=>'border-%s-width',
					),
					'border-style'=>array(
						'label'=>__('Border style', 'wf-woocommerce-packing-list'),
						'width'=>'49%',
						'float'=>'left',
						'field_type'=>'dropdown',
						'items'=>array(
							'none'=>__('None', 'wf-woocommerce-packing-list'),
							'solid'=>__('Solid', 'wf-woocommerce-packing-list'),
							'dashed'=>__('Dashed', 'wf-woocommerce-packing-list'),
							'dotted'=>__('Dotted', 'wf-woocommerce-packing-list'),
							'double'=>__('Double', 'wf-woocommerce-packing-list'),
						),
					),
					'border-color'=>array(
						'label'=>__('Border color', 'wf-woocommerce-packing-list'),
						'width'=>'37%',
						'float'=>'left',
						'field_type'=>'four_side_text',
						'field_type'=>'color',
					),
					'border-radius'=>array(
						'label'=>__('Border radius', 'wf-woocommerce-packing-list'),
						'width'=>'49%',
						'float'=>'left',
						'field_type'=>'text'
					)
				)
			),
			'received-seal'=>array(
				'title'=>__('Other properties', 'wf-woocommerce-packing-list'),
				'id'=>'received-seal',
				'fields'=>array(
					'opacity'=>array(
						'label'=>__('Opacity', 'wf-woocommerce-packing-list'),
						'width'=>'49%',
						'float'=>'left'
					),
					'rotate'=>array(
						'label'=>__('Angle', 'wf-woocommerce-packing-list'),
						'width'=>'49%',
						'float'=>'right'
					),
				)
			),
			'date-format'=>array(
				'title'=>__('Format', 'wf-woocommerce-packing-list'),
				'id'=>'date-format',
				'fields'=>array(
					'attr-date-format'=>array(
						'label'=>__('Format', 'wf-woocommerce-packing-list'),
						'width'=>'100%',
						'float'=>'left',
						'slug'=>'attr-%s-format',
						'field_type'=>'custom_preset', /* field with custom and preset option */
						'preset_arr'=>array(
							""=>'--'.__('Select one', 'wf-woocommerce-packing-list').'--',
							"d/m/Y"=>'d/m/Y',
							"d/m/y"=>'d/m/y',
							"d/M/y"=>'d/M/y',
							"d/M/Y"=>'d/M/Y',
							"m/d/Y"=>'m/d/Y',
							"m/d/y"=>'m/d/y',
							"M/d/y"=>'M/d/y',
							"M/d/Y"=>'M/d/Y'
						)
					),
				)
			)
		);

		return apply_filters('wt_pklist_dc_alter_property_editor', $editor, $template_type);
	}

	/**
	 * 	Prepare `Extra fields` arrays for assets method
	 */
	private function prepare_extra_fields_for_assets(&$extra_fields, &$extra_field_editable_properties, &$extra_field_html, &$extra_field_placeholders, &$extra_field_deletable)
	{
        $extra_fields=Wf_Woocommerce_Packing_List_Customizer::get_all_user_created_fields();

        $extra_field_deletable=Wf_Woocommerce_Packing_List_Customizer::get_user_created_meta_fields();

        $extra_field_slug_prefix=Wf_Woocommerce_Packing_List_Customizer::$extra_field_slug_prefix;
	    $extra_field_editable_properties=array();
	    $extra_field_new=array();
	    $extra_field_deletable_new=array();
	    foreach($extra_fields as $extra_field_key => $value)
	    {
	    	$attr_safe_key=$extra_field_slug_prefix.Wf_Woocommerce_Packing_List_Admin::sanitize_css_class_name($extra_field_key); //remove unwanted characters

	    	/* editable properties */
	    	$extra_field_editable_properties[$attr_safe_key]=array('label-text', 'padding', 'background-color', 'font-size', 'color', 'text-style', 'removable', 'sortable');

	    	/* prepareing new array with attr safe key and translated label */
	    	$extra_field_new[$attr_safe_key]=__($value, 'wf-woocommerce-packing-list').Wf_Woocommerce_Packing_List::get_display_key($extra_field_key);

	    	if(isset($extra_field_deletable[$extra_field_key]))
	    	{
	    		$extra_field_deletable_new[$attr_safe_key]=$extra_field_key; /* original key is required for item deleting, so add it as value.   attr_safe_key is for HTML DOM related actions */
	    	}

	    	/* html */
	    	$css_class='wfte_'.$attr_safe_key;
	    	$placeholder=Wf_Woocommerce_Packing_List_Customizer::prepare_custom_meta_placeholder($extra_field_key);
	    	$extra_field_html.='
		    	<div class="'.$css_class.'">
		        	<span class="'.$css_class.'_label">__['.$value.':]__ </span>
		        	<span class="'.$css_class.'_val">'.$placeholder.'</span>
		        </div>';
		    $extra_field_placeholders[$placeholder]=$extra_field_key;
	    }

	    $extra_fields=$extra_field_new;
	    $extra_field_deletable=$extra_field_deletable_new;
	}

	/**
	 * 	Prepare `Order fields` arrays for assets method
	 */
	private function prepare_order_fields_for_assets(&$order_fields, &$order_field_editable_properties, &$order_field_html)
	{
		$order_fields=array(
			'invoice_data'=>__('Order field block', 'wf-woocommerce-packing-list'),
			'invoice_number'=>__('Invoice number', 'wf-woocommerce-packing-list'),
			'invoice_date'=>__('Invoice date', 'wf-woocommerce-packing-list'),
			'order_number'=>__('Order number', 'wf-woocommerce-packing-list'),
			'order_date'=>__('Order date', 'wf-woocommerce-packing-list'),
			'shipping_method'=>__('Shipping method', 'wf-woocommerce-packing-list'),
			'tracking_number'=>__('Tracking number', 'wf-woocommerce-packing-list'),
			'email'=>__('Email', 'wf-woocommerce-packing-list'),
			'tel'=>__('Phone', 'wf-woocommerce-packing-list'),
			'vat_number'=>__('VAT(vat_number)', 'wf-woocommerce-packing-list'),
			'eu_vat_number'=>__('VAT(eu_vat_number)', 'wf-woocommerce-packing-list'),
			'vat'=>__('VAT(vat)', 'wf-woocommerce-packing-list'),
			'ssn_number'=>__('SSN', 'wf-woocommerce-packing-list'),
			'customer_note'=>__('Customer note', 'wf-woocommerce-packing-list'),
			'product_table_payment_method'=>__('Payment method', 'wf-woocommerce-packing-list'),
		);

		$editable_properties_1=array('label-text', 'padding', 'background-color', 'font-size', 'color', 'text-style', 'removable', 'sortable');
		$editable_properties_2=array('label-text', 'attr-date-format', 'padding', 'background-color', 'font-size', 'color', 'text-style', 'removable', 'sortable');

		$order_field_editable_properties=array(
	    	'invoice_data'=>array('padding', 'background-color', 'font-size', 'line-height', 'color', 'text-align', 'text-style'), //main section
	    	'invoice_number'=>$editable_properties_1,
	    	'invoice_date'=>$editable_properties_2,
	    	'order_number'=>$editable_properties_1,
	    	'order_date'=>$editable_properties_2,
	    	'shipping_method'=>$editable_properties_1,
	    	'tracking_number'=>$editable_properties_1,
	    	'email'=>$editable_properties_1,
			'tel'=>$editable_properties_1,
			'vat_number'=>$editable_properties_1,
			'eu_vat_number'=>$editable_properties_1,
			'vat'=>$editable_properties_1,
			'ssn_number'=>$editable_properties_1,
			'customer_note'=>$editable_properties_1,
			'product_table_payment_method'=>$editable_properties_1,
	    );

	    $order_field_html='
		    <div class="wfte_invoice_number">
	        	<span class="wfte_invoice_number_label">__[INVOICE]__ #</span>
	        	<span class="wfte_invoice_number_val">[wfte_invoice_number] </span>
	        </div>
	        <div class="wfte_invoice_date" data-invoice_date-format="d/M/Y">
	            <span class="wfte_invoice_date_label">__[Invoice Date:]__ </span>
	            <span class="wfte_invoice_date_val">[wfte_invoice_date]</span>
	        </div>
	        <div class="wfte_order_number">
	            <span class="wfte_order_number_label">__[Order No.:]__ </span>
	            <span class="wfte_order_number_val">[wfte_order_number]</span>
	        </div>
	        <div class="wfte_order_date" data-order_date-format="m/d/Y">
	            <span class="wfte_order_date_label">__[Order Date:]__ </span>
	            <span class="wfte_order_date_val">[wfte_order_date]</span>
	        </div>
	        <div class="wfte_shipping_method">
	            <span class="wfte_shipping_method_label">__[Shipping Method:]__ </span>
	            <span class="wfte_shipping_method_val">[wfte_shipping_method]</span>
	        </div>
	        <div class="wfte_tracking_number">
	            <span class="wfte_tracking_number_label">__[Tracking number:]__ </span>
	            <span class="wfte_tracking_number_val">[wfte_tracking_number]</span>
	        </div>
	        <div class="wfte_email">
	            <span class="wfte_email_label">__[Email:]__</span>
	            <span class="wfte_email_val">[wfte_email]</span>
	        </div>
	        <div class="wfte_tel">
	            <span class="wfte_tel_label">__[Phone:]__ </span>
	            <span class="wfte_tel_val">[wfte_tel]</span>
	        </div>
	        <div class="wfte_vat_number">
	            <span class="wfte_vat_number_label">__[VAT:]__ </span>
	            <span>[wfte_vat_number]</span>
	        </div>
	        <div class="wfte_eu_vat_number">
	            <span class="wfte_eu_vat_number_label">__[VAT:]__ </span>
	            <span>[wfte_eu_vat_number]</span>
	        </div>
	        <div class="wfte_vat">
	            <span class="wfte_vat_label">__[VAT:]__ </span>
	            <span>[wfte_vat]</span>
	        </div>
	        <div class="wfte_ssn_number">
	            <span class="wfte_ssn_number_label">__[SSN:]__ </span>
	            <span>[wfte_ssn_number]</span>
	        </div>
	        <div class="wfte_customer_note">
	            <span class="wfte_customer_note_label">__[Customer note:]__ </span>
	            <span>[wfte_customer_note]</span>
	        </div>
	        <div class="wfte_product_table_payment_method">
	            <span class="wfte_product_table_payment_method_label">__[Payment method:]__ </span>
	            <span>[wfte_product_table_payment_method]</span>
	        </div>';
	}

	/**
	 * 	Get available assets list.
	 * 	Filter available. If any modules need to alter the assets list
	 */
	public function get_assets($template_type)
	{

		/**
		*	Preparing extra fields
		*/
	    $extra_fields=array();
	    $extra_field_editable_properties=array();
	    $extra_field_html='';
	    $extra_field_placeholders=array();
	    $extra_field_deletable=array();
	    $this->prepare_extra_fields_for_assets($extra_fields, $extra_field_editable_properties, $extra_field_html, $extra_field_placeholders, $extra_field_deletable);


		/**
		*	Preparing order fields
		*/
		$order_fields=array();
		$order_field_editable_properties=array();
		$order_field_html='';
		$this->prepare_order_fields_for_assets($order_fields, $order_field_editable_properties, $order_field_html);


		/* merging order field data with extra field data */
		$order_fields=array_merge($order_fields, $extra_fields);
		$order_field_editable_properties=array_merge($order_field_editable_properties, $extra_field_editable_properties);
		$order_field_html.=$extra_field_html;


	    $address_label_editable_properties=array('padding', 'text', 'background-color', 'font-size', 'color', 'text-style', 'text-align', 'prependable', 'removable');
		$address_content_editable_properties=array('padding', 'background-color', 'font-size', 'color', 'text-style', 'text-align');
		$address_item_editable_properties=array('padding', 'background-color', 'font-size', 'color', 'text-style', 'sortable', 'mergable', 'removable');

	    $table_head_editable_properties=array('text', 'removable', 'sortable');
	    $table_row_editable_properties=array('label-text', 'padding', 'background-color', 'font-size', 'color', 'text-style', 'removable', 'sortable');

		$assets=array(
			array(
				'type'=>'group',
				'slug'=>'company_logo',
				'title'=>__('Company info', 'wf-woocommerce-packing-list'),
				'sub_items'=>array(
					array(
						'type'	=> 'element',
						'slug'	=> 'company_logo',
						'title'	=> __('Company logo/Name', 'wf-woocommerce-packing-list'),
						'html'=>'<div class="wfte_company_logo">
				                <div class="wfte_company_logo_img_box wfte_hidden">
				                    <img src="[wfte_company_logo_url]" class="wfte_company_logo_img">
				                </div>
				                <div class="wfte_company_name"> [wfte_company_name]</div>
				                <div class="wfte_company_logo_extra_details">__[]__</div>
				            </div>',
					    'editable_properties'=>array(
					    	'company_logo_img'=>array('attr-src', 'width', 'height', 'margin', 'hideable'),
					    	'company_name'=>array('margin', 'padding', 'background-color', 'typograhy', 'hideable'),
					    	'company_logo_extra_details'=>array('margin', 'padding', 'background-color', 'html', 'typograhy', 'hideable'),
					    ),
					    'elements'=>array(
					    	'company_logo_img'=>__('Logo image', 'wf-woocommerce-packing-list'),
					    	'company_name'=>__('Company name', 'wf-woocommerce-packing-list'),
					    	'company_logo_extra_details'=>__('Extra company info', 'wf-woocommerce-packing-list')
					    ),
					    'placeholders'=>array(),
					    'property_editor_messages'=>array(
					    	'company_logo_img'=>__('Logo image will update in all templates.', 'wf-woocommerce-packing-list'),
					    )
					),
				),
			),
			array(
				'type'=>'element_group',
				'slug'=>'invoice_data',
				'title'=>__('Order fields', 'wf-woocommerce-packing-list'),
				'elements'=>$order_fields,
				'editable_properties'=>$order_field_editable_properties,
				'html'=>'<div class="wfte_invoice_data">'.$order_field_html.'</div>',
			    'placeholders'=>$extra_field_placeholders,
			    'add_new_element'=>array(
			    	'title'=>__("Add new order meta", 'wf-woocommerce-packing-list'),
			    	'add_to_assets'=>true,
			    	'deletable'=>$extra_field_deletable,
			    	'editable_property'=>current($extra_field_editable_properties), //any item in the array
			    	'sample_html'=>'<div class="{pklist_dc_custom_meta_css}">
			        	<span class="{pklist_dc_custom_meta_css}_label"></span>
			        	<span class="{pklist_dc_custom_meta_css}_val"></span>
			        </div>',
			    	'html'=>'
			    	<div class="wt_pklist_dc_add_new_sub_item_block">
			    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Title', 'wf-woocommerce-packing-list').'</label>
			    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_title" />
			    	</div>
			    	<div class="wt_pklist_dc_add_new_sub_item_block">
			    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Meta key', 'wf-woocommerce-packing-list').'</label>
			    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value" />
			    	</div>
			    	',
			    ),
			),
			array(
				'type'=>'group',
				'title'=>__('Addresses', 'wf-woocommerce-packing-list'),
				'sub_items'=>array(
					array(
						'type'	=> 'element',
						'slug'	=> 'from_address',
						'title'	=> __('From Address', 'wf-woocommerce-packing-list'),
						'html'	=> '<div class="wfte_from_address">
									<div class="wfte_address-field-header wfte_from_address_label">__[From Address:]__</div>
					                <div class="wfte_from_address_val">
					                    <span class="wfte_from_address_name">[wfte_from_address_name]<br /></span>
					                    <span class="wfte_from_address_address_line1_address_line2"><span class="wfte_from_address_address_line1">[wfte_from_address_address_line1]</span>, <span class="wfte_from_address_address_line2">[wfte_from_address_address_line2]</span> <br /></span>
					                    <span class="wfte_from_address_city_state_postcode_country"><span class="wfte_from_address_city">[wfte_from_address_city],</span> <span class="wfte_from_address_state">[wfte_from_address_state],</span> <span class="wfte_from_address_postcode">[wfte_from_address_postcode],</span> <span class="wfte_from_address_country">[wfte_from_address_country]</span> <br /></span>
					                    <span class="wfte_from_address_contact_number">[wfte_from_address_contact_number]<br /></span>
					                    <span class="wfte_from_address_vat">__[VAT]__: [wfte_from_address_vat]<br /></span>
					                </div>
					            </div>',
					    'placeholders'=>array(),
					    'editable_properties'=>array(
					    	'from_address_label'=>$address_label_editable_properties,
					    	'from_address_val'=>$address_content_editable_properties,
					    	'from_address_name'=>$address_item_editable_properties,
					    	'from_address_address_line1'=>$address_item_editable_properties,
					    	'from_address_address_line2'=>$address_item_editable_properties,
					    	'from_address_city'=>$address_item_editable_properties,
					    	'from_address_state'=>$address_item_editable_properties,
					    	'from_address_postcode'=>$address_item_editable_properties,
					    	'from_address_country'=>$address_item_editable_properties,
					    	'from_address_contact_number'=>$address_item_editable_properties,
					    	'from_address_vat'=>$address_item_editable_properties,
					    ),
					    'elements'=>array(
					    	'from_address_label'=>__('From address title', 'wf-woocommerce-packing-list'),
					    	'from_address_val'=>__('From address content', 'wf-woocommerce-packing-list'),
					    	'from_address_name'=>__('Name', 'wf-woocommerce-packing-list'),
					    	'from_address_address_line1'=>__('Address line 1', 'wf-woocommerce-packing-list'),
					    	'from_address_address_line2'=>__('Address line 2', 'wf-woocommerce-packing-list'),
					    	'from_address_city'=>__('City', 'wf-woocommerce-packing-list'),
					    	'from_address_state'=>__('State', 'wf-woocommerce-packing-list'),
					    	'from_address_postcode'=>__('Postcode', 'wf-woocommerce-packing-list'),
					    	'from_address_country'=>__('Country', 'wf-woocommerce-packing-list'),
					    	'from_address_contact_number'=>__('Contact number', 'wf-woocommerce-packing-list'),
					    	'from_address_vat'=>__('VAT', 'wf-woocommerce-packing-list'),
					    )
					),
					array(
						'type'	=> 'element',
						'slug'	=> 'billing_address',
						'title'	=> __('Billing Address', 'wf-woocommerce-packing-list'),
						'html'	=> '<div class="wfte_billing_address">
						                <div class="wfte_address-field-header wfte_billing_address_label">__[Billing Address:]__</div>
						                <div class="wfte_billing_address_val">
						                    <span class="wfte_billing_address_name">[wfte_billing_address_name]<br /></span>
						                    <span class="wfte_billing_address_company">[wfte_billing_address_company]<br /></span>
						                    <span class="wfte_billing_address_address_1_address_2"><span class="wfte_billing_address_address_1">[wfte_billing_address_address_1]</span>, <span class="wfte_billing_address_address_2">[wfte_billing_address_address_2]</span> <br /></span>
						                    <span class="wfte_billing_address_city_state_postcode_country"><span class="wfte_billing_address_city">[wfte_billing_address_city],</span> <span class="wfte_billing_address_state">[wfte_billing_address_state],</span> <span class="wfte_billing_address_postcode">[wfte_billing_address_postcode],</span> <span class="wfte_billing_address_country">[wfte_billing_address_country]</span> <br /></span>
						                </div>
						            </div>',
						'placeholders'=>array(),
						'editable_properties'=>array(
					    	'billing_address_label'=>$address_label_editable_properties,
					    	'billing_address_val'=>$address_content_editable_properties,
					    	'billing_address_name'=>$address_item_editable_properties,
					    	'billing_address_company'=>$address_item_editable_properties,
					    	'billing_address_address_1'=>$address_item_editable_properties,
					    	'billing_address_address_2'=>$address_item_editable_properties,
					    	'billing_address_city'=>$address_item_editable_properties,
					    	'billing_address_state'=>$address_item_editable_properties,
					    	'billing_address_postcode'=>$address_item_editable_properties,
					    	'billing_address_country'=>$address_item_editable_properties,
					    ),
					    'elements'=>array(
					    	'billing_address_label'=>__('Billing address title', 'wf-woocommerce-packing-list'),
					    	'billing_address_val'=>__('Billing address content', 'wf-woocommerce-packing-list'),
					    	'billing_address_name'=>__('Name', 'wf-woocommerce-packing-list'),
					    	'billing_address_company'=>__('Company', 'wf-woocommerce-packing-list'),
					    	'billing_address_address_1'=>__('Address line 1', 'wf-woocommerce-packing-list'),
					    	'billing_address_address_2'=>__('Address line 2', 'wf-woocommerce-packing-list'),
					    	'billing_address_city'=>__('City', 'wf-woocommerce-packing-list'),
					    	'billing_address_state'=>__('State', 'wf-woocommerce-packing-list'),
					    	'billing_address_postcode'=>__('Postcode', 'wf-woocommerce-packing-list'),
					    	'billing_address_country'=>__('Country', 'wf-woocommerce-packing-list'),
					    )
					),
					array(
						'type'	=> 'element',
						'slug'	=> 'shipping_address',
						'title'	=> __('Shipping Address', 'wf-woocommerce-packing-list'),
						'html'	=> '<div class="wfte_shipping_address">
						                <div class="wfte_address-field-header wfte_shipping_address_label">__[Shipping Address:]__</div>
						                <div class="wfte_shipping_address_val">
						                    <span class="wfte_shipping_address_name">[wfte_shipping_address_name]<br /></span>
						                    <span class="wfte_shipping_address_company">[wfte_shipping_address_company]<br /></span>
						                    <span class="wfte_shipping_address_address_1_address_2"><span class="wfte_shipping_address_address_1">[wfte_shipping_address_address_1]</span>, <span class="wfte_shipping_address_address_2">[wfte_shipping_address_address_2]</span> <br /></span>
						                    <span class="wfte_shipping_address_city_state_postcode_country"><span class="wfte_shipping_address_city">[wfte_shipping_address_city],</span> <span class="wfte_shipping_address_state">[wfte_shipping_address_state],</span> <span class="wfte_shipping_address_postcode">[wfte_shipping_address_postcode],</span> <span class="wfte_shipping_address_country">[wfte_shipping_address_country]</span> <br /></span>
						                </div>
						            </div>',
						'placeholders'=>array(),
						'editable_properties'=>array(
					    	'shipping_address_label'=>$address_label_editable_properties,
					    	'shipping_address_val'=>$address_content_editable_properties,
					    	'shipping_address_name'=>$address_item_editable_properties,
					    	'shipping_address_company'=>$address_item_editable_properties,
					    	'shipping_address_address_1'=>$address_item_editable_properties,
					    	'shipping_address_address_2'=>$address_item_editable_properties,
					    	'shipping_address_city'=>$address_item_editable_properties,
					    	'shipping_address_state'=>$address_item_editable_properties,
					    	'shipping_address_postcode'=>$address_item_editable_properties,
					    	'shipping_address_country'=>$address_item_editable_properties,
					    ),
					    'elements'=>array(
					    	'shipping_address_label'=>__('Shipping address title', 'wf-woocommerce-packing-list'),
					    	'shipping_address_val'=>__('Shipping address content', 'wf-woocommerce-packing-list'),
					    	'shipping_address_name'=>__('Name', 'wf-woocommerce-packing-list'),
					    	'shipping_address_company'=>__('Company', 'wf-woocommerce-packing-list'),
					    	'shipping_address_address_1'=>__('Address line 1', 'wf-woocommerce-packing-list'),
					    	'shipping_address_address_2'=>__('Address line 2', 'wf-woocommerce-packing-list'),
					    	'shipping_address_city'=>__('City', 'wf-woocommerce-packing-list'),
					    	'shipping_address_state'=>__('State', 'wf-woocommerce-packing-list'),
					    	'shipping_address_postcode'=>__('Postcode', 'wf-woocommerce-packing-list'),
					    	'shipping_address_country'=>__('Country', 'wf-woocommerce-packing-list'),
					    )
					),
					array(
						'type'	=>'element',
						'slug'	=>'return_address',
						'title'	=>__('Return Address', 'wf-woocommerce-packing-list'),
						'html'	=> '<div class="wfte_return_address">
						                <div class="wfte_address-field-header wfte_return_address_label">__[Return Address:]__</div>
						                <div class="wfte_return_address_val">
						                    <span class="wfte_return_address_name">[wfte_return_address_name]<br /></span>
						                    <span class="wfte_return_address_company">[wfte_return_address_company]<br /></span>
						                    <span class="wfte_return_address_address_1_address_2"><span class="wfte_return_address_address_1">[wfte_return_address_address_1]</span>, <span class="wfte_return_address_address_2">[wfte_return_address_address_2]</span> <br /></span>
						                    <span class="wfte_return_address_city_state_postcode_country"><span class="wfte_return_address_city">[wfte_return_address_city],</span> <span class="wfte_return_address_state">[wfte_return_address_state],</span> <span class="wfte_return_address_postcode">[wfte_return_address_postcode],</span> <span class="wfte_return_address_country">[wfte_return_address_country]</span> <br /></span>
						                </div>
						            </div>',
						'placeholders'=>array(),
						'editable_properties'=>array(
					    	'return_address_label'=>$address_label_editable_properties,
					    	'return_address_val'=>$address_content_editable_properties,
					    	'return_address_name'=>$address_item_editable_properties,
					    	'return_address_company'=>$address_item_editable_properties,
					    	'return_address_address_1'=>$address_item_editable_properties,
					    	'return_address_address_2'=>$address_item_editable_properties,
					    	'return_address_city'=>$address_item_editable_properties,
					    	'return_address_state'=>$address_item_editable_properties,
					    	'return_address_postcode'=>$address_item_editable_properties,
					    	'return_address_country'=>$address_item_editable_properties,
					    ),
					    'elements'=>array(
					    	'return_address_label'=>__('Return address title', 'wf-woocommerce-packing-list'),
					    	'return_address_val'=>__('Return address content', 'wf-woocommerce-packing-list'),
					    	'return_address_name'=>__('Name', 'wf-woocommerce-packing-list'),
					    	'return_address_company'=>__('Company', 'wf-woocommerce-packing-list'),
					    	'return_address_address_1'=>__('Address line 1', 'wf-woocommerce-packing-list'),
					    	'return_address_address_2'=>__('Address line 2', 'wf-woocommerce-packing-list'),
					    	'return_address_city'=>__('City', 'wf-woocommerce-packing-list'),
					    	'return_address_state'=>__('State', 'wf-woocommerce-packing-list'),
					    	'return_address_postcode'=>__('Postcode', 'wf-woocommerce-packing-list'),
					    	'return_address_country'=>__('Country', 'wf-woocommerce-packing-list'),
					    )
					)
				),
			),
			array(
				'type'	=> 'group',
				'title'	=> __('Product table', 'wf-woocommerce-packing-list'),
				'sub_items'=>array(
					array(
						'type'	=> 'element',
						'slug'	=> 'product_table',
						'title'	=> __('Product table', 'wf-woocommerce-packing-list'),
						'add_new_element'=>array(
					    	'title'=>__("Add new column", 'wf-woocommerce-packing-list'),
					    	'editable_property'=>array('text', 'width', 'height', 'background-color', 'typograhy', 'removable', 'sortable'),
					    	'sample_html'=>'<th class="wfte_product_table_head_bg wfte_table_head_color" col-type=""></th>',
					    	'html'=>'
					    	<div class="wt_pklist_dc_add_new_sub_item_block">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Title', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_title" />
					    	</div>
					    	<div class="wt_pklist_dc_add_new_sub_item_block">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Value', 'wf-woocommerce-packing-list').'</label>
					    		<select class="wt_pklist_dc_form_toggler wt_pklist_dc_property_editor_add_new_item_value" data-form-toggle-id="product_table_add_new">
					    			<option value="">--'.__('Select one', 'wf-woocommerce-packing-list').'--</option>
					    			<optgroup label="'.__('Available values', 'wf-woocommerce-packing-list').'">
						    			<option value="category">'.__('Category', 'wf-woocommerce-packing-list').'</option>
						    		</optgroup>
					    			<optgroup label="'.__('Other', 'wf-woocommerce-packing-list').'">
					    				<option value="custom_product_meta">'.__('Product meta', 'wf-woocommerce-packing-list').'</option>
					    				<option value="custom_order_item_meta">'.__('Order item meta', 'wf-woocommerce-packing-list').'</option>
					    				<option value="custom_filter">'.__('Filter', 'wf-woocommerce-packing-list').'</option>
					    			</optgroup>
					    		</select>
					    	</div>
					    	<div class="wt_pklist_dc_add_new_sub_item_block wt_pklist_dc_form_toggler_child_block" data-form-toggle-target="product_table_add_new" data-form-toggle-value="custom_product_meta">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Please enter product meta key', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value_data" />
					    	</div>
					    	<div class="wt_pklist_dc_add_new_sub_item_block wt_pklist_dc_form_toggler_child_block" data-form-toggle-target="product_table_add_new" data-form-toggle-value="custom_order_item_meta">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Please enter order item meta key', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value_data" />
					    	</div>

					    	<div class="wt_pklist_dc_add_new_sub_item_block wt_pklist_dc_form_toggler_child_block" data-form-toggle-target="product_table_add_new" data-form-toggle-value="custom_filter">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Column key', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value_data" />
					    		<i>'.__('Please enter a unique alphanumeric key to identify the column on filter.', 'wf-woocommerce-packing-list').'</i>
					    	</div>
					    	',
					    ),
						'html'	=> '[wfte_product_table_start]
					                <table class="wfte_product_table wfte_side_padding_table">
					                    <thead class="wfte_product_table_head wfte_table_head_color wfte_product_table_head_bg">
					                        <tr>
					                            <th class="wfte_product_table_head_image wfte_product_table_head_bg wfte_table_head_color" col-type="image">__[Image]__</th>
					                            <th class="wfte_product_table_head_sku wfte_product_table_head_bg wfte_table_head_color" col-type="sku">__[SKU]__</th>
					                            <th class="wfte_product_table_head_product wfte_product_table_head_bg wfte_table_head_color" col-type="product">__[Product]__</th>
					                            <th class="wfte_product_table_head_quantity wfte_product_table_head_bg wfte_table_head_color" col-type="quantity">__[Quantity]__</th>
					                            <th class="wfte_product_table_head_price wfte_product_table_head_bg wfte_table_head_color" col-type="price">__[Price]__</th>
					                            <th class="wfte_product_table_head_total_price wfte_product_table_head_bg wfte_table_head_color" col-type="total_price">__[Total Price]__</th>
					                            <th class="wfte_product_table_head_tax_items wfte_product_table_head_bg wfte_table_head_color" col-type="tax_items">[wfte_product_table_tax_item_column_label]</th>
					                            <th class="wfte_product_table_head_tax wfte_product_table_head_bg wfte_table_head_color" col-type="tax">__[Total Tax]__</th>
					                        </tr>
					                    </thead>
					                    <tbody class="wfte_product_table_body wfte_table_body_color">
					                    </tbody>
					                </table>
					                [wfte_product_table_end]',
					    'placeholders'=>array(),
					    'editable_properties'=>array(
					    	'product_table_head'=>array('padding', 'background-color', 'font-size', 'text-style', 'color'),
					    	'product_table_body'=>array('padding', 'background-color', 'font-size', 'text-style', 'color'),
					    	'product_table_head_image'=>$table_head_editable_properties,
					    	'product_table_head_sku'=>$table_head_editable_properties,
					    	'product_table_head_product'=>$table_head_editable_properties,
					    	'product_table_head_quantity'=>$table_head_editable_properties,
					    	'product_table_head_price'=>$table_head_editable_properties,
					    	'product_table_head_total_price'=>$table_head_editable_properties,
					    	'product_table_head_tax_items'=>array('removable', 'sortable'),
					    	'product_table_head_tax'=>$table_head_editable_properties,
					    ),
					    'elements'=>array(
					    	'product_table_head'=>__('Table head', 'wf-woocommerce-packing-list'),
					    	'product_table_head_image'=>__('Product image column', 'wf-woocommerce-packing-list'),
					    	'product_table_head_sku'=>__('SKU column', 'wf-woocommerce-packing-list'),
					    	'product_table_head_product'=>__('Product title column', 'wf-woocommerce-packing-list'),
					    	'product_table_head_quantity'=>__('Quantity', 'wf-woocommerce-packing-list'),
					    	'product_table_head_price'=>__('Price', 'wf-woocommerce-packing-list'),
					    	'product_table_head_total_price'=>__('Total price', 'wf-woocommerce-packing-list'),
					    	'product_table_head_tax_items'=>__('Tax items', 'wf-woocommerce-packing-list'),
					    	'product_table_head_tax'=>__('Tax', 'wf-woocommerce-packing-list'),
					    	'product_table_body'=>__('Table body', 'wf-woocommerce-packing-list'),
					    ),
					    'property_editor_messages'=>array(
					    	'product_table_head_tax_items'=>__("'Tax items' displays taxes with respective tax name as column header. Appears in single/multiple columns based on the settings.", 'wf-woocommerce-packing-list')
					    )
					),
					array(
						'type'	=> 'element',
						'slug'	=> 'payment_summary_table',
						'title'	=> __('Summary table', 'wf-woocommerce-packing-list'),
						'html'	=> '<table class="wfte_payment_summary_table wfte_product_table wfte_side_padding_table">
					                    <tbody class="wfte_payment_summary_table_body wfte_table_body_color">
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_subtotal">
					                            <td colspan="2" class="wfte_product_table_subtotal_label wfte_text_right">__[Subtotal]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_subtotal]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_shipping">
					                            <td colspan="2" class="wfte_product_table_shipping_label wfte_text_right">__[Shipping]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_shipping]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_cart_discount">
					                            <td colspan="2" class="wfte_product_table_cart_discount_label wfte_text_right">__[Cart Discount]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_cart_discount]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_order_discount">
					                            <td colspan="2" class="wfte_product_table_order_discount_label wfte_text_right">__[Order Discount]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_order_discount]</td>
					                        </tr>
					                        <tr data-row-type="wfte_tax_items" class="wfte_payment_summary_table_row wfte_product_table_tax_item">
					                            <td colspan="2" class="wfte_product_table_tax_item_label wfte_text_right">[wfte_product_table_tax_item_label]</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_tax_item]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_total_tax">
					                            <td colspan="2" class="wfte_product_table_total_tax_label wfte_text_right">__[Total Tax]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_total_tax]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_fee">
					                            <td colspan="2" class="wfte_product_table_fee_label wfte_text_right">__[Fee]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_fee]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_coupon">
					                            <td colspan="2" class="wfte_product_table_coupon_label wfte_text_right">__[Coupon Used]__</td>
					                            <td class="wfte_right_column wfte_text_left">[wfte_product_table_coupon]</td>
					                        </tr>
					                        <tr class="wfte_payment_summary_table_row wfte_product_table_payment_total">
					                            <td class="wfte_left_column"></td>
					                            <td class="wfte_product_table_payment_total_label wfte_text_right">__[Total]__</td>
					                            <td class="wfte_product_table_payment_total_val wfte_right_column wfte_text_left">[wfte_product_table_payment_total]</td>
					                        </tr>
					                    </tbody>
					                </table>',
					    'placeholders'=>array(),
					    'editable_properties'=>array(
					    	'product_table_subtotal'=>$table_row_editable_properties,
					    	'product_table_shipping'=>$table_row_editable_properties,
					    	'product_table_order_discount'=>$table_row_editable_properties,
					    	'product_table_cart_discount'=>$table_row_editable_properties,
					    	'product_table_tax_item'=>array('removable', 'sortable'),
					    	'product_table_total_tax'=>$table_row_editable_properties,
					    	'product_table_fee'=>$table_row_editable_properties,
					    	'product_table_coupon'=>$table_row_editable_properties,
					    	'product_table_payment_total'=>$table_row_editable_properties,
					    ),
					    'elements'=>array(
					    	'product_table_subtotal'=>__('Subtotal', 'wf-woocommerce-packing-list'),
					    	'product_table_shipping'=>__('Shipping', 'wf-woocommerce-packing-list'),
					    	'product_table_order_discount'=>__('Order discount', 'wf-woocommerce-packing-list'),
					    	'product_table_cart_discount'=>__('Cart discount', 'wf-woocommerce-packing-list'),
					    	'product_table_tax_item'=>__('Tax items', 'wf-woocommerce-packing-list'),
					    	'product_table_total_tax'=>__('Total tax', 'wf-woocommerce-packing-list'),
					    	'product_table_fee'=>__('Fee', 'wf-woocommerce-packing-list'),
					    	'product_table_coupon'=>__('Coupon', 'wf-woocommerce-packing-list'),
					    	'product_table_payment_total'=>__('Payment total', 'wf-woocommerce-packing-list'),
					    ),
					    'add_new_element'=>array(
					    	'title'=>__("Add new row", 'wf-woocommerce-packing-list'),
					    	'editable_property'=>array('label-text', 'height', 'background-color', 'typograhy', 'removable', 'sortable'),
					    	'sample_html'=>'
						    	<tr class="wfte_payment_summary_table_row">
		                            <td colspan="2" class="wfte_text_right"></td>
		                            <td class="wfte_right_column wfte_text_left"></td>
		                        </tr>',
					    	'html'=>'
					    	<div class="wt_pklist_dc_property_editor_add_new_item_block">
					    		<label>'.__('Title', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_title" />
					    	</div>
					    	<div class="wt_pklist_dc_property_editor_add_new_item_block">
					    		<label>'.__('Value', 'wf-woocommerce-packing-list').'</label>
					    		<select class="wt_pklist_dc_form_toggler wt_pklist_dc_property_editor_add_new_item_value" data-form-toggle-id="product_table_add_new">
					    			<option value="">--'.__('Select one', 'wf-woocommerce-packing-list').'--</option>
					    			<option value="custom_order_meta">'.__('Order meta', 'wf-woocommerce-packing-list').'</option>
					    			<option value="custom_filter">'.__('Filter', 'wf-woocommerce-packing-list').'</option>
					    		</select>
					    	</div>
					    	<div class="wt_pklist_dc_add_new_sub_item_block wt_pklist_dc_form_toggler_child_block" data-form-toggle-target="product_table_add_new" data-form-toggle-value="custom_order_meta">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Please enter meta key', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value_data" />
					    	</div>
					    	<div class="wt_pklist_dc_add_new_sub_item_block wt_pklist_dc_form_toggler_child_block" data-form-toggle-target="product_table_add_new" data-form-toggle-value="custom_filter">
					    		<label class="wt_pklist_dc_add_new_sub_item_label">'.__('Placeholder', 'wf-woocommerce-packing-list').'</label>
					    		<input type="text" class="wt_pklist_dc_property_editor_add_new_item_value_data" />
					    	</div>
					    	',
					    ),
					    'property_editor_messages'=>array(
					    	'product_table_tax_item'=>__("'Tax items' displays taxes with respective tax name in single/multiple rows based on the settings.", 'wf-woocommerce-packing-list')
					    )
					)
				)
			),
			array(
				'type'		=>'group',
				'title'		=>__('Other', 'wf-woocommerce-packing-list'),
				'sub_items'	=>array(
					array(
						'type'	=>	'element',
						'slug'	=>	'signature',
						'title'	=>	__('Signature', 'wf-woocommerce-packing-list'),
						'html'	=>	'<div class="wfte_signature wfte_text_right">
					                	<img src="[wfte_signature_url]" class="wfte_image_signature" style="margin-bottom:15px;">
					                	<div class="wfte_manual_signature wfte_hidden" style="height:60px; width:150px;"></div>
					                	<div class="wfte_signature_label">__[Signature]__</div>
					            	</div>',
					    'editable_properties'=>array(
					    	'image_signature'=>array('attr-src', 'width', 'height', 'margin', 'hideable'),
					    	'manual_signature'=>array('width', 'height', 'margin', 'background-color', 'hideable'),
					    	'signature_label'=>array('html', 'margin', 'padding', 'background-color', 'font-size', 'text-style', 'text-align', 'color', 'hideable'),
					    ),
					    'elements'=>array(
					    	'image_signature'=>__('Image signature', 'wf-woocommerce-packing-list'),
					    	'manual_signature'=>__('Manual signature', 'wf-woocommerce-packing-list'),
					    	'signature_label'=>__('Signature label', 'wf-woocommerce-packing-list')
					    ),
					    'placeholders'=>array(),
					    'property_editor_messages'=>array(
					    	'image_signature'=>__('Signature image will update in all templates.', 'wf-woocommerce-packing-list'),
					    )
					),
					array(
						'type'			=>	'element',
						'slug'			=>	'return_policy',
						'title'			=>	__('Return policy', 'wf-woocommerce-packing-list'),
						'html' 			=> 	'<div class="wfte_return_policy wfte_text_left clearfix">
						               			[wfte_return_policy]
						           			</div>',
						'placeholders'	=>array(),
						'editable_properties'=>array(
					    	'return_policy'=>array('html', 'margin', 'padding', 'background-color', 'font-size', 'line-height', 'color', 'text-style', 'text-align', 'border'),
					    ),
					    'elements'=>array(
					    	'return_policy'=>__('Properties', 'wf-woocommerce-packing-list'),
					    ),
					),
					array(
						'type'			=>	'element',
						'slug'			=>	'footer',
						'title'			=>	__('Footer', 'wf-woocommerce-packing-list'),
						'html'			=>	'<div class="wfte_footer wfte_text_left clearfix">
						                		[wfte_footer]
						            		</div>',
						'placeholders'	=>array(),
						'editable_properties'=>array(
					    	'footer'=>array('html', 'margin', 'padding', 'background-color', 'font-size', 'line-height', 'color', 'text-style', 'text-align', 'border'),
					    ),
					    'elements'=>array(
					    	'footer'=>__('Properties', 'wf-woocommerce-packing-list'),
					    ),
					),
					array(
						'type'			=>	'element',
						'slug'			=>	'barcode',
						'title'			=>	__('Barcode', 'wf-woocommerce-packing-list'),
						'html'			=>	'<div class="wfte_barcode float_right wfte_text_right">
						                		<img src="[wfte_barcode_url]" style="">
						            		</div>',
						'placeholders'	=>array(),
						'editable_properties'=>array(
					    	'barcode'=>array('margin', 'background-color'),
					    ),
					    'elements'=>array(
					    	'barcode'=>__('Properties', 'wf-woocommerce-packing-list'),
					    ),
					),
					array(
						'type'			=>	'element',
						'slug'			=>	'received_seal',
						'title'			=>	__('Received seal', 'wf-woocommerce-packing-list'),
						'html'			=> '<div class="wfte_received_seal"><span class="wfte_received_seal_text">__[RECEIVED]__</span>[wfte_received_seal_extra_text]</div>',
						'placeholders'	=>	array(),
						'editable_properties'=>array(
					    	'received_seal'=>array('text', 'width', 'height', 'margin', 'typograhy', 'border', 'received-seal'),
					    ),
					    'elements'=>array(
					    	'received_seal'=>__('Properties', 'wf-woocommerce-packing-list'),
					    ),
					    'property_editor_messages'=>array(
					    	'received_seal'=>sprintf(__('You can control the visibility of the seal according to order status via filters. See filter documentation %s here. %s', 'wf-woocommerce-packing-list'), '<a href="'.admin_url('admin.php?page=wf_woocommerce_packing_list#wf-help#filters').'" target="_blank">', '</a>'),
					    )
					),
					array(
						'type'			=>	'element',
						'slug'			=>	'doc_title',
						'title'			=>	__('Document title', 'wf-woocommerce-packing-list'),
						'html'			=> '<div class="wfte_doc_title">__['.$this->get_doc_title($template_type).']__</div>',
						'placeholders'	=>	array(),
						'editable_properties'=>array(
					    	'doc_title'=>array('text', 'margin', 'background-color', 'font-size', 'text-style', 'text-align', 'color'),
					    ),
					    'elements'=>array(
					    	'doc_title'=>__('Properties', 'wf-woocommerce-packing-list'),
					    ),
					),
				),
			),

		);

		/**
		*	Alter sidebar assets.
		*	Format:
			array(
				'type' 						=> '',  // type
				'slug' 						=> '',	// slug/class name
				'title' 					=> '',	// Title
				'html' 						=> '',	//	Codeview HTML
				'editable_properties'  		=> array(),	//	Properties to be editable via side panel, Properties other than default must be declared via `wt_pklist_dc_alter_property_editor`
				'placeholders' 				=> array(),	//	Place holders and values for design view HTML. Only needed if the placeholder needs custom value or its a custom placeholder
				'elements' 					=> array(),	//	Elements title
				'property_editor_messages' 	=> array(),	//	Custom messages in property editor
			)
		*/
		$assets=apply_filters('wt_pklist_dc_alter_assets', $assets, $template_type);

		return $assets;
	}

	/**
	 * 	Editable properties for main document
	 */
	public function get_page_editable_properties($template_type)
	{
		return array('width', 'background-color', 'padding', 'font-family', 'font-size', 'line-height', 'color', 'border');
	}


	/**
	*	Get editable and draggable elements
	*
	*/
	public function get_editable_and_draggable($template_type, $assets_elements, &$editable, &$draggable)
	{
		$dom_elements=array_keys($assets_elements);

		$editable=apply_filters('wt_pklist_dc_alter_editable', $dom_elements, $template_type);

		$draggable=apply_filters('wt_pklist_dc_alter_draggable', $dom_elements, $template_type);
	}


	/**
	*	Get droppable elements
	*
	*/
	public function get_droppable($template_type)
	{
		$dom_elements=array(
			'col-+\d+',
		);

		return apply_filters('wt_pklist_dc_alter_droppable', $dom_elements, $template_type);
	}

	/**
	*	Get layout elements
	*
	*/
	public function get_layouts($template_type)
	{
		$layout_items=array(
			'100'=>array(1),
			'50/50'=>array(2,2),
			'30/70'=>array(6,7),
			'70/30'=>array(7,6),
			'33/33/33'=>array(3,3,3),
			'25/50/25'=>array(4,2,4),
		);

		return $layout_items;
	}


	public function get_domid_exclude_dom_elements()
	{
		return array('style');
	}

	/**
	 * adding DOM ID and storing last dom ID in a hidden field
	 *
	 */
	public function prepare_designview_html($html, $template_type, $for_customizer)
	{
		if(!$for_customizer)
		{
			return $html;
		}
		$i=0;
		if(trim($html)!="")
		{
			include_once plugin_dir_path( __FILE__ )."libraries/simple_html_dom.php"; /* include simple HTML dom library */
			$html_dom=Wt_Pklist_Dc\str_get_html($html, true, true, DEFAULT_TARGET_CHARSET, false);
			$exclude_elements=$this->get_domid_exclude_dom_elements();
			foreach($html_dom->find('*') as $element)
			{
				if(!in_array($element->tag, $exclude_elements))
				{
					$element->{'data-wfte-id'}=$i;
					$i++;
				}
			}
			$html=$html_dom->outertext;
			$html_dom->clear();
		}else
		{
			$i++;
		}

		$html.='<input type="hidden" value="'.($i-1).'" class="wt_pklist_last_dom_id" />';
		return $html;
	}

	public function extract_assets_data(&$assets_codeview_html_arr, &$assets_placeholders_arr, &$assets_editable_properties, &$assets_elements, &$assets_titles, &$assets_add_new_item, &$property_editor_messages, $assets)
	{
		foreach($assets as $value)
		{
			if(isset($value['html']))
			{
				$assets_codeview_html_arr[]=$value['html'];
			}

			if(isset($value['placeholders']) && is_array($value['placeholders']) && count($value['placeholders'])>0)
			{
				$assets_placeholders_arr[]=$value['placeholders'];
			}

			$slug=(isset($value['slug']) && is_string($value['slug']) ? $value['slug'] : '');
			$title=(isset($value['title']) && is_string($value['title']) ? $value['title'] : '');
			if(isset($value['editable_properties']) && is_array($value['editable_properties']))
			{
				$assets_editable_properties[$slug]=$value['editable_properties'];
			}
			if(isset($value['elements']) && is_array($value['elements']))
			{
				$assets_elements[$slug]=$value['elements'];

			}else //no multiple elements. so take the main element
			{
				if($slug!="")
				{
					$assets_elements[$slug]=array(
						$slug=>$title,
					);
				}
			}

			if(isset($value['add_new_element']))
			{
				$assets_add_new_item[$slug]=$value['add_new_element'];
			}

			if(isset($value['property_editor_messages']))
			{
				$property_editor_messages[$slug]=$value['property_editor_messages'];
			}

			/* saving title */
			if($slug!="")
			{
				$assets_titles[$slug]=$title;
			}

			if(isset($value['sub_items']) && is_array($value['sub_items']) && count($value['sub_items'])>0)
			{
				/* recurrsively fetch data */
				$this->extract_assets_data($assets_codeview_html_arr, $assets_placeholders_arr, $assets_editable_properties, $assets_elements, $assets_titles, $assets_add_new_item, $property_editor_messages, $value['sub_items']);
			}
		}
	}

	/**
	 * 	To prevent 404 error on console.
	 *  We are storing the code view HTML on a hidden div. The src attribute of images in code view HTML are placeholders so it will generate a 404 error on console.
	 */
	private function get_dummy_placeholder_for_images($template_type)
	{
		$images_path=plugin_dir_url( __FILE__ ).'assets/images/';
		$img_url_placeholders=array(
			'[wfte_company_logo_url]'=>$images_path.'logo_dummy.png',
			'[wfte_barcode_url]'=>$images_path.'barcode_dummy.png',
			'[wfte_signature_url]'=>$images_path.'signature_dummy.png',
		);
		return $img_url_placeholders=apply_filters('wt_pklist_alter_img_url_placeholder_list', $img_url_placeholders, $template_type);
	}

	private function enqueue_styles()
	{
		wp_enqueue_style($this->module_id.'-inter-font', plugin_dir_url( __FILE__ ).'assets/css/main.css', array(), WF_PKLIST_VERSION, 'all' );
	}

	private function enqueue_scripts()
	{
		//code editor
		if($this->enable_code_view)
		{
			wp_enqueue_script($this->module_id.'-code_editor-js',plugin_dir_url( __FILE__ ).'libraries/code_editor/lib/codemirror.js',array('jquery'),WF_PKLIST_VERSION);
			//wp_enqueue_script($this->module_id.'-code_editor-mode-xml',plugin_dir_url( __FILE__ ).'libraries/code_editor/mode/xml/xml.js',array('jquery'),WF_PKLIST_VERSION);
			wp_enqueue_script($this->module_id.'-code_editor-mode-htmlmixed',plugin_dir_url( __FILE__ ).'libraries/code_editor/mode/htmlmixed/htmlmixed.js',array('jquery'),WF_PKLIST_VERSION);
			wp_enqueue_script($this->module_id.'-code_editor-mode-css',plugin_dir_url( __FILE__ ).'libraries/code_editor/mode/css/css.js',array('jquery'),WF_PKLIST_VERSION);

			wp_enqueue_style($this->module_id.'-code_editor-css', plugin_dir_url( __FILE__ ).'libraries/code_editor/lib/codemirror.css', array(),WF_PKLIST_VERSION,'all');
			//wp_enqueue_style($this->module_id.'-code_editor-theme-css', plugin_dir_url( __FILE__ ).'libraries/code_editor/theme/night.css', array(),WF_PKLIST_VERSION,'all');
			//wp_enqueue_style($this->module_id.'-code_editor-doc-css', plugin_dir_url( __FILE__ ).'libraries/code_editor/doc/docs.css', array(),WF_PKLIST_VERSION,'all');
			//wp_enqueue_style($this->module_id.'-code_editor-display-css', plugin_dir_url( __FILE__ ).'libraries/code_editor/addon/display/fullscreen.css', array(),WF_PKLIST_VERSION,'all');
		}

		/**
		*	PHP date function equivalent JS function. This will help us to give preview for date format
		*	@author Locutus
		*	@link https://github.com/locutusjs/locutus/blob/master/src/php/datetime/date.js
		*/
		wp_enqueue_script($this->module_id.'-color-picker-alpha-js', plugin_dir_url( __FILE__ ).'assets/js/php_date.js', array(), WF_PKLIST_VERSION);


		wp_enqueue_script($this->module_id.'-sidebar', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_sidebar.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-editable', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_editable.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-drag_drop', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_drag_drop.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-row', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_row.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-property_editor', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_property_editor.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-source_code', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_source_code.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-ajax', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_ajax.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id.'-undo_redo', plugin_dir_url( __FILE__ ).'assets/js/pklist_dc_undo_redo.js', array(), WF_PKLIST_VERSION);
		wp_enqueue_script($this->module_id, plugin_dir_url( __FILE__ ).'assets/js/pklist_dc.js', array('jquery', 'wp-color-picker'), WF_PKLIST_VERSION);

		wp_enqueue_script('jquery-ui-sortable');
	}

	/**
	*	Color picker HTML
	*
	*/
	public function color_picker($css_property, $css_class='')
	{
		?>
		<div class="wt_pklist_dc_color_picker">
	    	<div class="wt_pklist_dc_color_preview"></div>
	    	<input type="text" class="wt_pklist_dc_property_editor_input wt_pklist_dc_color_picker_input <?php echo $css_class;?>" value="#bada55" data-css-property="<?php echo $css_property;?>"/>
	    </div>
		<?php
	}

	/**
	 * 	Prepare four side property editor inputs
	 *
	 */
	public function prepare_four_side_input($field_sub_type, $field_key, $css_class, $dropdown_items=array())
	{
		if($field_sub_type=='color')
		{
			$this->color_picker($field_key, $css_class);

		}elseif($field_sub_type=='dropdown')
		{
			$dropdown_items=(isset($field_value['items']) ? $field_value['items'] : array());
			?>
			<select class="wt_pklist_dc_property_editor_input wt_pklist_dc_change <?php echo $css_class;?>">
			<?php
			foreach ($dropdown_items as $val=> $label)
			{
				?>
				<option value="<?php echo $val;?>"><?php echo $label;?></option>
				<?php
			}
			?>
			</select>
			<?php
		}else
		{
		?>
			<input type="text" class="wt_pklist_dc_property_editor_input wt_pklist_dc_keyup <?php echo $css_class;?>" value="" style="width:95%;">
		<?php
		}
	}

	/**
	 * 	Prepare button radio type property editor inputs
	 *
	 */
	public function button_radio($field_key, $item_field_value)
	{
		$item_field_values=(isset($item_field_value['values']) && is_array($item_field_value['values']) ? $item_field_value['values'] : array());
		$item_field_icons=(isset($item_field_value['icons']) && is_array($item_field_value['icons']) ? $item_field_value['icons'] : array());
		if(count($item_field_values)>0)
		{
			?>
			<div class="wt_pklist_dc_sidebar_property_button_radio_group" data-property="<?php echo $field_key;?>">
				<?php
				foreach($item_field_values as $btn_item_inc=>$btn_item_value)
				{
					$icon=(isset($item_field_icons[$btn_item_inc]) ? $item_field_icons[$btn_item_inc] : ucfirst($btn_item_value));
					?>
					<span class="wt_pklist_dc_sidebar_property_button_group_btn wt_pklist_button_radio" data-value="<?php echo $btn_item_value;?>" style="height:28px; line-height:28px;"><?php echo $icon;?></span>
					<?php
				}
				?>
			</div>
			<?php
		}
	}
}