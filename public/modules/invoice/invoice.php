<?php
/**
 * Invoice section of the plugin
 *
 * @link
 * @since 4.0.0
 *
 * @package  Wf_Woocommerce_Packing_List
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wf_Woocommerce_Packing_List_Invoice
{
	public $module_id='';
	public static $module_id_static='';
	public $module_base='invoice';
    public $customizer=null;
    private $seq_number=null;
    private $payment_link = null;
    public $is_enable_invoice='';
	public function __construct()
	{
		$this->module_id=Wf_Woocommerce_Packing_List::get_module_id($this->module_base);
		self::$module_id_static=$this->module_id;

		add_filter('wf_module_default_settings', array($this, 'default_settings'), 10, 2);
		add_filter('wf_module_customizable_items', array($this, 'get_customizable_items'), 10, 2);
		add_filter('wf_module_non_options_fields', array($this, 'get_non_options_fields'), 10, 2);
		add_filter('wf_module_non_disable_fields', array($this, 'get_non_disable_fields'), 10, 2);

		//hook to add which fiedls to convert
		add_filter('wf_module_convert_to_design_view_html',array($this,'convert_to_design_view_html'),10,3);

		//hook to generate template html
		add_filter('wf_module_generate_template_html',array($this,'generate_template_html'),10,6);

		//initializing customizer
		$this->customizer=Wf_Woocommerce_Packing_List::load_modules('customizer');

		//initializing Sequential Number
		$this->seq_number=Wf_Woocommerce_Packing_List::load_modules('sequential-number');

		//initializing Payment Link
		$this->payment_link=Wf_Woocommerce_Packing_List::load_modules('payment-link');

		/* add admin menu */
		add_filter('wt_admin_menu',array($this,'add_admin_pages'),10,1);

		/* checking `enable invoice` option is checked to add necessary filters */
		$this->is_enable_invoice=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_enable_invoice',$this->module_id);
		if($this->is_enable_invoice=='Yes') /* `print_it` method also have the same checking */
		{
			add_filter('wt_print_docdata_metabox',array($this, 'add_docdata_metabox'),10,3);
			add_filter('wt_print_actions', array($this, 'add_print_actions'), 10, 4);
			add_filter('wt_print_bulk_actions',array($this, 'add_bulk_print_buttons'));
			add_filter('wt_pklist_intl_frontend_order_detail_page_print_actions', array($this, 'add_frontend_order_detail_page_print_buttons'), 10, 3);
			add_filter('wt_pklist_intl_frontend_order_list_page_print_actions', array($this, 'add_frontend_order_list_page_print_buttons'), 10, 3);

			add_filter('wt_pklist_intl_email_print_actions', array($this, 'add_email_print_buttons'), 10, 5);
			add_filter('wt_email_attachments', array($this,'add_email_attachments'),10,4);

			//hook to wc status change for generating invoice number on order status change, (If user set the order status)
			add_filter('wf_pklist_enable_sequential_number_on_status_change',array($this,'generate_invoice_number_on_order_status_change'),10,2);
		}
		add_action('wt_print_doc',array($this,'print_it'),10,2);

		//add fields to customizer panel
		add_filter('wf_pklist_alter_customize_inputs', array($this,'alter_customize_inputs'),10,3);
		add_filter('wf_pklist_alter_customize_info_text', array($this,'alter_customize_info_text'),10,3);

		add_filter('wt_pklist_alter_order_template_html', array($this,'alter_received_seal'),10,3);

		add_action('wt_run_necessary', array($this, 'run_necessary'));

		//invoice column and value
		add_filter('manage_edit-shop_order_columns', array($this,'add_invoice_column'),11); /* Add invoice number column to order page */
		add_action('manage_shop_order_posts_custom_column', array($this,'add_invoice_column_value'),11); /* Add value to invoice number column in order page */

		add_filter('wt_pklist_alter_tooltip_data', array($this,'register_tooltips'), 1);


		/**
		* @since 4.0.5 declaring multi select form fields in settings form
		*/
		add_filter('wt_pklist_intl_alter_multi_select_fields', array($this,'alter_multi_select_fields'), 10, 2);

		/**
		* @since 4.0.5 Declaring validation rule for form fields in settings form
		*/
		add_filter('wt_pklist_intl_alter_validation_rule', array($this,'alter_validation_rule'), 10, 2);

		/**
		* @since 4.0.5 Enable PDF preview option
		*/
		add_filter('wf_pklist_intl_customizer_enable_pdf_preview', array($this,'enable_pdf_preview'), 10, 2);


		/**
		* @since 4.1.4 Add to remote printing
		*/
		add_filter('wt_pklist_add_to_remote_printing', array($this, 'add_to_remote_printing'), 10, 2);

		/**
		* @since 4.1.4 Do remote printing
		*/
		add_filter('wt_pklist_do_remote_printing', array($this, 'do_remote_printing'), 10, 2);
	}

	/**
	*	@since 4.1.4
	*	Add to remote printing, this will enable remote printing settings
	*/
	public function add_to_remote_printing($arr, $remote_print_vendor)
	{
		$arr[$this->module_base]=__('Invoice', 'wf-woocommerce-packing-list');
		return $arr;
	}

	/**
	*	@since 4.1.8
	*	Do remote printing.
	*/
	public function do_remote_printing($module_base_arr, $order_id)
	{
        return Wf_Woocommerce_Packing_List_Admin::do_remote_printing($module_base_arr, $order_id, $this);
    }

	/**
	* 	Enable PDF preview
	*	@since 	4.0.5
	*/
	public function enable_pdf_preview($status, $template_type)
	{
		if($template_type==$this->module_base)
		{
			$status=true;
		}
		return $status;
	}

	/**
	* 	@since 4.0.5
	* 	Declaring validation rule for form fields in settings form
	*/
	public function alter_validation_rule($arr, $base_id)
	{
		if($base_id == $this->module_id)
		{
			$arr=array(
				'wf_'.$this->module_base.'_contactno_email'=>array('type'=>'text_arr'),
	        	'wf_'.$this->module_base.'_product_meta_fields'=>array('type'=>'text_arr'),
	        	'woocommerce_wf_generate_for_orderstatus'=>array('type'=>'text_arr'),
	        	'woocommerce_wf_attach_'.$this->module_base=>array('type'=>'text_arr'),
	        	'woocommerce_wf_packinglist_footer'=>array('type'=>'textarea'),
	        	'wt_'.$this->module_base.'_product_attribute_fields'=>array('type'=>'text_arr'),
			);

			if(Wf_Woocommerce_Packing_List_Admin::module_exists('sequential-number'))
			{
				//sequential number validation rule
				$seq_arr=Wf_Woocommerce_Packing_List_Sequential_Number::get_validation_rule();
				$seq_arr=(!is_array($seq_arr) ? array() : $seq_arr);
				$arr=array_merge($arr, $seq_arr);
			}

			if(Wf_Woocommerce_Packing_List_Admin::module_exists('payment-link'))
			{
				//sequential number validation rule
				$payment_link_arr=Wf_Woocommerce_Packing_List_Payment_Link::get_validation_rule();
				$payment_link_arr=(!is_array($payment_link_arr) ? array() : $payment_link_arr);
				$arr=array_merge($arr, $payment_link_arr);
			}
		}
		return $arr;
	}

	/**
	* 	@since 4.0.5
	* 	Declaring multi select form fields in settings form
	*/
	public function alter_multi_select_fields($arr, $base_id)
	{
		if($base_id==$this->module_id)
		{
			$arr=array(
	        	'wf_'.$this->module_base.'_contactno_email'=>array(),
				'wf_'.$this->module_base.'_product_meta_fields'=>array(),
	        	'woocommerce_wf_generate_for_orderstatus'=>array(),
	        	'woocommerce_wf_attach_'.$this->module_base=>array(),
	        	'wt_'.$this->module_base.'_product_attribute_fields'=>array(),
	        );
		}
		return $arr;
	}

	/**
	* 	@since 4.0.4
	* 	Hook the tooltip data to main tooltip array
	*/
	public function register_tooltips($tooltip_arr)
	{
		include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
		$tooltip_arr[$this->module_id]=$arr;
		return $tooltip_arr;
	}

	/**
	* Adding received seal filters and other options
	*	@since 	4.0.3
	*/
	public function alter_received_seal($html,$template_type,$order)
	{
		if($template_type==$this->module_base)
		{
			$is_enable_received_seal=true;
			$is_enable_received_seal=apply_filters('wf_pklist_toggle_received_seal',$is_enable_received_seal,$template_type,$order);
			if($is_enable_received_seal!==true) //hide it
			{
				$html=Wf_Woocommerce_Packing_List_CustomizerLib::addClass('wfte_received_seal',$html,Wf_Woocommerce_Packing_List_CustomizerLib::TO_HIDE_CSS);
			}
		}
		return $html;
	}

	/**
	* Adding received seal extra text
	*	@since 	4.0.3
	*/
	private static function set_received_seal_extra_text($find_replace,$template_type,$html,$order)
	{
		if(strpos($html,'[wfte_received_seal_extra_text]')!==false) //if extra text placeholder exists then only do the process
        {
        	$extra_text='';
        	$find_replace['[wfte_received_seal_extra_text]']=apply_filters('wf_pklist_received_seal_extra_text',$extra_text,$template_type,$order);
		}
		return $find_replace;
	}

	/**
	* Adding customizer info text for received seal
	*	@since 	4.0.3
	*/
	public function alter_customize_info_text($info_text,$type,$template_type)
	{
		if($template_type==$this->module_base)
		{
			if($type=='received_seal')
			{
				$info_text=sprintf(__('You can control the visibility of the seal according to order status via filters. See filter documentation %s here. %s', 'print-invoices-packing-slip-labels-for-woocommerce'), '<a href="'.admin_url('admin.php?page=wf_woocommerce_packing_list#wf-help#filters').'" target="_blank">', '</a>');
			}
		}
		return $info_text;
	}

	/**
	* Adding received seal customization options to customizer
	*	@since 	4.0.3
	*/
	public function alter_customize_inputs($fields,$type,$template_type)
	{
		if($template_type==$this->module_base)
		{
			if($type=='received_seal')
			{
				$fields=array(
					array(
						'label'=>__('Width','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'width',
						'trgt_elm'=>$type,
						'unit'=>'px',
						'width'=>'49%',
					),
					array(
						'label'=>__('Height','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'height',
						'trgt_elm'=>$type,
						'unit'=>'px',
						'width'=>'49%',
						'float'=>'right',
					),
					array(
						'label'=>__('Text','wf-woocommerce-packing-list'),
						'css_prop'=>'html',
						'trgt_elm'=>$type.'_text',
						'width'=>'49%',
					),
					array(
						'label'=>__('Font size','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'font-size',
						'trgt_elm'=>$type,
						'unit'=>'px',
						'width'=>'49%',
						'float'=>'right',
					),
					array(
						'label'=>__('Border width','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'border-top-width|border-right-width|border-bottom-width|border-left-width',
						'trgt_elm'=>$type,
						'unit'=>'px',
						'width'=>'49%',
					),
					array(
						'label'=>__('Line height','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'line-height',
						'trgt_elm'=>$type,
						'width'=>'49%',
						'float'=>'right',
					),
					array(
						'label'=>__('Opacity','wf-woocommerce-packing-list'),
						'type'=>'select',
						'select_options'=>array(
							'1'=>1,
							'0.9'=>.9,
							'0.8'=>.8,
							'0.7'=>.7,
							'0.6'=>.6,
							'0.5'=>.5,
							'0.4'=>.4,
							'0.3'=>.3,
							'0.2'=>.2,
							'0.1'=>.1,
							'0'=>0,
						),
						'css_prop'=>'opacity',
						'trgt_elm'=>$type,
						'width'=>'49%',
						'event_class'=>'wf_cst_change',
					),
					array(
						'label'=>__('Border radius','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'border-top-left-radius|border-top-right-radius|border-bottom-left-radius|border-bottom-right-radius',
						'trgt_elm'=>$type,
						'width'=>'49%',
						'float'=>'right',
					),
					array(
						'label'=>__('From left','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'margin-left',
						'trgt_elm'=>$type,
						'unit'=>'px',
						'width'=>'49%',
					),
					array(
						'label'=>__('From top','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'margin-top',
						'trgt_elm'=>$type,
						'width'=>'49%',
						'float'=>'right',
					),
					array(
						'label'=>__('Angle','wf-woocommerce-packing-list'),
						'type'=>'text_inputgrp',
						'css_prop'=>'rotate',
						'trgt_elm'=>$type,
						'unit'=>'deg',
					),
					array(
						'label'=>__('Color','wf-woocommerce-packing-list'),
						'type'=>'color',
						'css_prop'=>'border-top-color|border-right-color|border-bottom-color|border-left-color|color',
						'trgt_elm'=>$type,
						'event_class'=>'wf_cst_click',
					)
				);
			}
		}
		return $fields;
	}

	/**
	*	@since 4.0.4	Generate invoice number on order staus change, If user set status to generate invoice number
	*
	*/
	public function generate_invoice_number_on_order_status_change($module_id_arr, $order_id)
	{
		$module_id_arr[$this->module_id]=array(__CLASS__,'generate_invoice_number_for_first');
		return $module_id_arr;
	}

	/**
	 *  Items needed to be converted to design view
	 */
	public function convert_to_design_view_html($find_replace,$html,$template_type)
	{
		if($template_type==$this->module_base)
		{
			// change
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_billing_address($find_replace, $template_type, $html);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_shipping_address($find_replace,$template_type, $html);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_product_table($find_replace,$template_type,$html);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_extra_charge_fields($find_replace,$template_type,$html);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_other_data($find_replace,$template_type,$html);
			$find_replace['[wfte_received_seal_extra_text]']='';
		}
		return $find_replace;
	}

	/**
	 *  Items needed to be converted to HTML for print/download
	 */
	public function generate_template_html($find_replace,$html,$template_type,$order,$box_packing=null,$order_package=null)
	{
		if($template_type==$this->module_base)
		{
			//Generate invoice number while printing invoice
			self::generate_invoice_number($order);

			// change
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_billing_address($find_replace, $template_type, $html,$order);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_shipping_address($find_replace,$template_type, $html,$order);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_product_table($find_replace,$template_type,$html,$order);
			$find_replace=Wf_Woocommerce_Packing_List_CustomizerLib::set_order_data($find_replace,$template_type,$html,$order);
			$find_replace=self::set_received_seal_extra_text($find_replace,$template_type,$html,$order);
		}
		return $find_replace;
	}


	public function run_necessary()
	{
		$this->wf_filter_email_attach_invoice_for_status();
	}


    /**
	* Get invoice date
	* @since  	4.0.2
	* @since  	4.0.4	functionality moved to separate module
	* @return mixed
	*/
    public static function get_invoice_date($order_id, $date_format, $order)
    {
    	if(Wf_Woocommerce_Packing_List_Admin::module_exists('sequential-number'))
	    {
    		return Wf_Woocommerce_Packing_List_Sequential_Number::get_sequential_date($order_id, 'wf_invoice_date', $date_format, $order);
    	}else
    	{
    		return '';
    	}
    }

	/**
	* 	Function to generate invoice number
	* 	@since 4.0.0
	* 	@since 4.0.2	separate date for invoice date functionality added
	*	@since 4.0.4 	functionality moved to separate module
	*	@since 4.1.9	Restrict generating invoice number for free orders if option is NO
	* 	@return mixed
	*/
    public static function generate_invoice_number($order, $force_generate=true,$free_ord="")
    {
    	$order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
	    $wf_invoice_id = get_post_meta($order_id, 'wf_invoice_number', true);

	    if((empty($wf_invoice_id)) && ($free_ord != "set")){
	    	$free_order_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_orders',self::$module_id_static);
			if($free_order_enable == "No"){
				if(\intval($order->get_total()) === 0){
					return '';
				}
			}
	    }

	    if(Wf_Woocommerce_Packing_List_Admin::module_exists('sequential-number'))
	    {
	    	return Wf_Woocommerce_Packing_List_Sequential_Number::generate_sequential_number($order, self::$module_id_static, array('number'=>'wf_invoice_number', 'date'=>'wf_invoice_date', 'enable'=>'woocommerce_wf_enable_invoice'), $force_generate);
	    }else
	    {
	    	return '';
	    }
	}


	/**
	* 	Function to generate invoice number,When doing Checkout
	* 	@since 4.0.0
	* 	@since 4.0.2	separate date for invoice date functionality added
	*	@since 4.0.4 	functionality moved to separate module
	*	@since 4.1.9	Restrict generating invoice number for free orders if option is NO
	* 	@return mixed
	*/
    public static function generate_invoice_number_for_first($order, $force_generate=true)
    {

    	$free_order_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_orders',self::$module_id_static);

		if($free_order_enable == "No"){
			if(\intval($order->get_total()) === 0){
				return '';
			}
		}

	    if(Wf_Woocommerce_Packing_List_Admin::module_exists('sequential-number'))
	    {
	    	return Wf_Woocommerce_Packing_List_Sequential_Number::generate_sequential_number($order, self::$module_id_static, array('number'=>'wf_invoice_number', 'date'=>'wf_invoice_date', 'enable'=>'woocommerce_wf_enable_invoice'), $force_generate);
	    }else
	    {
	    	return '';
	    }
	}

	/**
	 * Function to add "Invoice" column in order listing page
	 *
	 * @since    4.0.0
	 */
	public function add_invoice_column($columns)
	{
		$columns['Invoice']=__('Invoice','wf-woocommerce-packing-list');
        return $columns;
	}

	/**
	 * Function to add value in "Invoice" column
	 *
	 * @since    4.0.0
	 */
	public function add_invoice_column_value($column)
	{
		global $post, $woocommerce, $the_order;
		if($column=='Invoice')
		{
			$order=wc_get_order($post->ID);
			$order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
			$generate_invoice_for =Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_orderstatus',$this->module_id);
			$force_generate=in_array(get_post_status($order_id),$generate_invoice_for) ? true :false;
			echo self::generate_invoice_number($order,$force_generate);
		}
	}

	/**
	 * removing status other than generate invoice status
	 * @since     4.0.0
	 * @since 	  4.0.2 [Bug fix] array intersect issue when order status is empty
	 */
	private function wf_filter_email_attach_invoice_for_status()
	{
		$the_options=Wf_Woocommerce_Packing_List::get_settings($this->module_id);
		$email_attach_invoice_for_status=$the_options['woocommerce_wf_attach_invoice'];
		$generate_for_orderstatus=$the_options['woocommerce_wf_generate_for_orderstatus'];
		$generate_for_orderstatus=!is_array($generate_for_orderstatus) ? array() : $generate_for_orderstatus;
		$email_attach_invoice_for_status=!is_array($email_attach_invoice_for_status) ? array() : $email_attach_invoice_for_status;
		$the_options['woocommerce_wf_attach_invoice']=array_intersect($email_attach_invoice_for_status,$generate_for_orderstatus);
		Wf_Woocommerce_Packing_List::update_settings($the_options,$this->module_id);
	}

	/**
	*	Input for customizer sidebar, which items need to customize.
	*
	*/
	public function get_customizable_items($settings,$base_id)
	{
		if($base_id==$this->module_id)
		{
			//these fields are the classname in template Eg: `company_logo` will point to `wfte_company_logo`
			return array(
				'doc_title'=>__('Document title','wf-woocommerce-packing-list'),
				'company_logo'=>__('Company Logo','wf-woocommerce-packing-list'),
				'invoice_number'=>__('Invoice Number','wf-woocommerce-packing-list'),
				'order_number'=>__('Order Number','wf-woocommerce-packing-list'),
				'invoice_date'=>__('Invoice Date','wf-woocommerce-packing-list'),
				'order_date'=>__('Order Date','wf-woocommerce-packing-list'),
				'received_seal'=>__('Payment received stamp','wf-woocommerce-packing-list'),
				'from_address'=>__('From Address','wf-woocommerce-packing-list'),
				'billing_address'=>__('Billing Address','wf-woocommerce-packing-list'),
				'shipping_address'=>__('Shipping Address','wf-woocommerce-packing-list'),
				'email'=>__('Email Field','wf-woocommerce-packing-list'),
				'tel'=>__('Tel Field','wf-woocommerce-packing-list'),
				'vat_number'=>__('VAT Field','wf-woocommerce-packing-list'),
				'ssn_number'=>__('SSN Field','wf-woocommerce-packing-list'),
				'shipping_method'=>__('Shipping Method','wf-woocommerce-packing-list'),
				'tracking_number'=>__('Tracking Number','wf-woocommerce-packing-list'),
				'product_table'=>__('Product Table','wf-woocommerce-packing-list'),
				'product_table_subtotal'=>__('Subtotal','wf-woocommerce-packing-list'),
				'product_table_shipping'=>__('Shipping','wf-woocommerce-packing-list'),
				'product_table_cart_discount'=>__('Cart Discount','wf-woocommerce-packing-list'),
				'product_table_order_discount'=>__('Order Discount','wf-woocommerce-packing-list'),
				'product_table_total_tax'=>__('Total Tax','wf-woocommerce-packing-list'),
				'product_table_fee'=>__('Fee','wf-woocommerce-packing-list'),
				'product_table_coupon'=>__('Coupon info','wf-woocommerce-packing-list'),
				'product_table_payment_method'=>__('Payment Method','wf-woocommerce-packing-list'),
				'payment_link'=>__('Payment Link','wf-woocommerce-packing-list'),
				'product_table_payment_total'=>__('Total','wf-woocommerce-packing-list'),
				'barcode'=>__('Bar Code','wf-woocommerce-packing-list'),
				'signature'=>__('Signature','wf-woocommerce-packing-list'),
				'footer'=>__('Footer','wf-woocommerce-packing-list'),
				'return_policy'=>__('Return Policy','wf-woocommerce-packing-list'),
				//'product_table_payment_summary'=>__('Extra Charges Fields','wf-woocommerce-packing-list'),
			);
		}
		return $settings;
	}

	/*
	* These are the fields that have no customizable options, Just on/off
	*
	*/
	public function get_non_options_fields($settings,$base_id)
	{
		if($base_id==$this->module_id)
		{
			return array(
				'barcode',
				'footer',
				'return_policy',
			);
		}
		return $settings;
	}

	/*
	* These are the fields that are switchable
	*
	*/
	public function get_non_disable_fields($settings,$base_id)
	{
		if($base_id==$this->module_id)
		{
			return array(
				'product_table_payment_summary'
			);
		}
		return $settings;
	}

	/**
	* 	@since 4.0.0 Retriving default fields
	*	@since 4.1.9 Added new option for free line items and free order
	*/
	public function default_settings($settings,$base_id)
	{
		if($base_id==$this->module_id)
		{
			$settings=array(
				'wf_'.$this->module_base.'_contactno_email'=>array('email','contact_number'),
	        	'wf_'.$this->module_base.'_product_meta_fields'=>array(),
	        	'woocommerce_wf_generate_for_orderstatus'=>array('wc-completed'),
	        	'wf_woocommerce_invoice_free_orders' => 'Yes',
	        	'wf_woocommerce_invoice_free_line_items' => 'Yes', /* Since 4.1.9 , To display the free line items*/
	        	'woocommerce_wf_attach_'.$this->module_base=>array(),
	        	'woocommerce_wf_packinglist_invoice_signature'=>'',
	        	'woocommerce_wf_packinglist_logo'=>'',
	        	'woocommerce_wf_add_'.$this->module_base.'_in_mail'=>'No',
	        	'woocommerce_wf_packinglist_frontend_info'=>'No',
	        	'woocommerce_wf_packinglist_footer'=>'',
	        	'woocommerce_wf_packinglist_variation_data'=>'Yes',
				'woocommerce_wf_enable_invoice'=>"Yes",
				'wt_pklist_show_individual_tax_column'=>"No",
				'wt_pklist_individual_tax_column_display_option'=>"amount", /* Possible values: 1. amount, 2. rate, 3. amount-rate, 4. separate-column (amount and rate in separate columns) */
				'wt_pklist_total_tax_column_display_option'=>"amount", /* Possible values: 1. amount, 2. rate, 3. amount-rate */
				'wf_woocommerce_product_category_wise_splitting'=>'No',
				'wt_'.$this->module_base.'_product_attribute_fields'=>array(),
				'sort_products_by'=>'',
				'bundled_product_display_option'=>'main-sub', /* Possible values: 1.main-sub, 2.main, 3.sub */
				'woocommerce_wf_custom_pdf_name' => '[prefix][order_no]',/* Since 4.1.9 */
				'woocommerce_wf_custom_pdf_name_prefix' => 'Invoice_',/* Since 4.1.9 */
				'woocommerce_wf_use_latest_settings_invoice' => 'Yes', /* Since 4.2.1 */
			);

			if(Wf_Woocommerce_Packing_List_Admin::module_exists('sequential-number'))
			{
				//sequential number settings
				$seq_settings=Wf_Woocommerce_Packing_List_Sequential_Number::get_sequential_field_default_settings();
				$seq_settings=(!is_array($seq_settings) ? array() : $seq_settings);
				$settings=array_merge($settings, $seq_settings);
			}

			if(Wf_Woocommerce_Packing_List_Admin::module_exists('payment-link'))
			{
				//sequential number settings
				$payment_link_settings=Wf_Woocommerce_Packing_List_Payment_Link::get_payment_link_default_settings();
				$payment_link_settings=(!is_array($payment_link_settings) ? array() : $payment_link_settings);
				$settings=array_merge($settings, $payment_link_settings);
			}
			return $settings;
		}else
		{
			return $settings;
		}
	}

	public static function save_paylater_settings(){
		$checkout_paylater	=	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_show_pay_later_in_checkout',self::$module_id_static);
		if(($checkout_paylater === 1) || ($checkout_paylater === '1')){
			$enable_paylater	=	"yes";
		}else{
			$enable_paylater	=	"no";
		}

		$paylater_title	=	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_pay_later_title',self::$module_id_static);
		$paylater_desc	=	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_pay_later_description',self::$module_id_static);
		$paylater_inst	=	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_pay_later_instuction',self::$module_id_static);
		$paylater_arr 	=	array(
			'title'			=>	sanitize_text_field($paylater_title),
			'description'	=>	sanitize_textarea_field($paylater_desc),
			'instructions'	=>	sanitize_textarea_field($paylater_inst),
			'enabled'		=>	$enable_paylater
		);
		$installed_payment_methods = WC()->payment_gateways->payment_gateways();

		if(array_key_exists("wf_pay_later",$installed_payment_methods)){
			if(get_option('woocommerce_gateway_order')){
				$all_gateways =	get_option('woocommerce_gateway_order');
				if(!array_key_exists('wf_pay_later',$all_gateways)){
					$paylater__serial_no = count($all_gateways);
					$all_gateways['wf_pay_later'] = $paylater__serial_no;
					update_option('woocommerce_gateway_order',$all_gateways);
				}
			}
		}
		update_option('woocommerce_wf_pay_later_settings',$paylater_arr);
	}

	public function add_admin_pages($menus)
	{
		$menus[]=array(
			'submenu',
			WF_PKLIST_POST_TYPE,
			__('Invoice','wf-woocommerce-packing-list'),
			__('Invoice','wf-woocommerce-packing-list'),
			'manage_woocommerce',
			$this->module_id,
			array($this,'admin_settings_page')
		);
		return $menus;
	}

	public function add_bulk_print_buttons($actions)
	{
		$actions['print_invoice']=__('Print Invoices','wf-woocommerce-packing-list');
		$actions['download_invoice']=__('Download Invoices','wf-woocommerce-packing-list');
		return $actions;
	}

	/**
	*	Adding print/download options in Order list/detail page
	*	@since 4.0.9 Added new filter to alter button list
	*	@since 4.1.9 Show the prompt for free orders, when no invoice number for the free order
	*/
	public function add_print_actions($item_arr, $order, $order_id, $button_location)
	{
		$invoice_number=self::generate_invoice_number($order,false);
		$generate_invoice_for =Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_orderstatus',$this->module_id);
		$free_order_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_orders',$this->module_id);
		$is_show=0;
		$is_show_prompt=1;

		if(in_array(get_post_status($order_id), $generate_invoice_for) || !empty($invoice_number))
        {
        	$is_show_prompt=0;
        	$is_show=1;
		}else
		{
			if(empty($invoice_number))
			{
				$is_show_prompt=1;
				$is_show=1;
			}
		}

		if(empty($invoice_number))
		{
			if($free_order_enable == "No"){
				if(\intval($order->get_total()) === 0){
					$is_show_prompt=2;
				}
			}
		}

		if($is_show==1)
		{
			//for print button
			$btn_args=array(
				'action'=>'print_invoice',
				'tooltip'=>__('Print Invoice','wf-woocommerce-packing-list'),
				'is_show_prompt'=>$is_show_prompt,
				'button_location'=>$button_location,
			);

			//for download button
			$btn_args_dw=array(
				'action'=>'download_invoice',
				'tooltip'=>__('Download Invoice','wf-woocommerce-packing-list'),
				'is_show_prompt'=>$is_show_prompt,
				'button_location'=>$button_location,
			);

			if($button_location=='detail_page')
			{
				$btn_args['label']=__('Print','wf-woocommerce-packing-list');
				$btn_args_dw['label']=__('Download','wf-woocommerce-packing-list');

				$item_arr[]=array(
					'button_type'=>'aggregate',
					'button_key'=>'invoice_actions', //unique if multiple on same page
					'button_location'=>$button_location,
					'action'=>'',
					'label'=>__('Invoice','wf-woocommerce-packing-list'),
					'tooltip'=>__('Print/Download Invoice','wf-woocommerce-packing-list'),
					'is_show_prompt'=>0, //always 0
					'items'=>array(
						$btn_args,
						$btn_args_dw
					),
				);
			}else
			{
				$btn_args['label']=__('Print Invoice','wf-woocommerce-packing-list');
				$btn_args_dw['label']=__('Download Invoice','wf-woocommerce-packing-list');
				$item_arr[]=$btn_args;
				$item_arr[]=$btn_args_dw;
			}

			/**
			*	@since 4.0.9
			*	Alter button array just after adding buttons.
			*	We are specifying `module_base` as an argument to use common callback when needed
			*/
			$item_arr=apply_filters('wt_pklist_after_'.$this->module_base.'_print_button_list', $item_arr, $order, $button_location, $this->module_base);
		}
		return $item_arr;
	}

	/**
	* 	@since 4.0.4
	*	Print invoice number details
	*/
	public function add_docdata_metabox($data_arr, $order, $order_id)
	{

		$invoice_number=self::generate_invoice_number($order, false);
		if($invoice_number!="")
		{
			$data_arr[]=array(
				'label'=>__('Invoice Number','wf-woocommerce-packing-list'),
				'value'=>$invoice_number,
			);

			$invoice_date=self::get_invoice_date($order_id, get_option( 'date_format' ), $order);
			$data_arr[]=array(
				'label'=>__('Invoice Date', 'wf-woocommerce-packing-list'),
				'value'=>$invoice_date,
			);
		}
		return $data_arr;
	}

	/**
	*	@since 4.0.8 added email class id checking and filter to alter email class id
	*	@since 4.1.9 Avoid to generate invoice attachments for free order, if the option is set to "No"
	*/
	public function add_email_attachments($attachments, $order, $order_id, $email_class_id)
	{
		$free_order_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_orders',$this->module_id);

		if($free_order_enable == "No"){
			if(\intval($order->get_total()) === 0){
				return $attachments;
			}
		}

		if(Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_add_'.$this->module_base.'_in_mail',$this->module_id)== "Yes")
        {
        	/* check order email types */
			$attach_to_mail_for=array('new_order', 'customer_completed_order', 'customer_invoice', 'customer_on_hold_order', 'customer_processing_order');
			$attach_to_mail_for=apply_filters('wf_pklist_alter_'.$this->module_base.'_attachment_mail_type', $attach_to_mail_for, $order_id, $email_class_id, $order);

			/* check order statuses to generate invoice */
			$generate_invoice_for=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_orderstatus', $this->module_id);

			if(in_array('wc-'.$order->get_status(), $generate_invoice_for) && in_array($email_class_id, $attach_to_mail_for))
			{
           		if(!is_null($this->customizer))
		        {
		        	$order_ids=array($order_id);
		        	$pdf_name=$this->customizer->generate_pdf_name($this->module_base,$order_ids);
		        	$this->customizer->template_for_pdf=true;
		        	$html=$this->generate_order_template($order_ids, $pdf_name);
		        	$attachments[]=$this->customizer->generate_template_pdf($html, $this->module_base, $pdf_name, 'attach');
		        }
           	}
        }
        return $attachments;
	}

	public function add_email_print_buttons($wt_actions, $order, $order_id, $email_obj, $sent_to_admin)
	{
		return $this->add_print_button($wt_actions, $order, $order_id, true, $sent_to_admin);
	}

	/**
	*	Print buttons in my account order list page
	*	@since 4.1.0
	*/
	public function add_frontend_order_list_page_print_buttons($wt_actions, $order, $order_id)
	{
		if($this->is_show_frontend_print_button($order))
		{
			$wt_actions[$this->module_base]=array(
				'print'=>__('Print Invoice', 'wf-woocommerce-packing-list'),
			);
		}
		return $wt_actions;
	}


	/**
	*	Print buttons in my account order detail page
	*	@since 4.1.0
	*/
	public function add_frontend_order_detail_page_print_buttons($wt_actions, $order, $order_id)
	{
		return $this->add_print_button($wt_actions, $order, $order_id);
	}

	/**
	*	Generate frontend/email print buttons
	*	@since 4.1.0 Condition checking moved to separate function
	*/
	public function add_print_button($wt_actions, $order, $order_id, $email_button=false, $sent_to_admin=false)
	{
		if($this->is_show_frontend_print_button($order))
		{
			$wt_actions[$this->module_base]=array(
				'print'=>array(
					'title'=>__('Print Invoice', 'wf-woocommerce-packing-list'),
					'email_button'=>$email_button,
					'sent_to_admin'=>$sent_to_admin,
				),
				'download'=>array(
					'title'=>__('Download Invoice', 'wf-woocommerce-packing-list'),
					'email_button'=>$email_button,
					'sent_to_admin'=>$sent_to_admin,
				),
			);
			if($email_button)
			{
				unset($wt_actions[$this->module_base]['download']);
			}
	    }
	    return $wt_actions;
	}

	/**
	*	Is show print button in email/my account
	*	@since 4.1.0
	*	@since 4.1.9 avoid generating the invoice, displaying button when checkout in email and thank you page.
	*/
	public function is_show_frontend_print_button($order)
	{
		$free_order_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_orders',$this->module_id);

		if($free_order_enable == "No"){
			if(\intval($order->get_total()) === 0){
				return false;
			}
		}

		$show_on_frontend=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_frontend_info',$this->module_id);
		if($show_on_frontend=='Yes')
		{
			$show_print_button_for=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_attach_'.$this->module_base, $this->module_id);
	        if(in_array('wc-'.$order->get_status(), $show_print_button_for))
	        {
	         	return true;
	        }
	    }
	    return false;
	}

	/**
	*  Admin settings page
	*
	*/
	public function admin_settings_page()
	{
		$order_statuses = wc_get_order_statuses();
		$wf_generate_invoice_for=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_orderstatus', $this->module_id);
		wp_enqueue_script('wc-enhanced-select');
		wp_enqueue_style('woocommerce_admin_styles',WC()->plugin_url().'/assets/css/admin.css');
		wp_enqueue_media();
		wp_enqueue_script($this->module_id,plugin_dir_url( __FILE__ ).'assets/js/main.js',array('jquery'),WF_PKLIST_VERSION);
		$params=array(
			'nonces' => array(
	            'main'=>wp_create_nonce($this->module_id),
	        ),
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'msgs'=>array(
	        	'error'=>__('Error','wf-woocommerce-packing-list'),
	        )
		);
		wp_localize_script($this->module_id,$this->module_id,$params);
		$the_options=Wf_Woocommerce_Packing_List::get_settings($this->module_id);

		//initializing necessary modules, the argument must be current module name/folder
	    if(!is_null($this->payment_link))
		{
			$this->payment_link->init($this->module_base, __('Payment Link', 'wf-woocommerce-packing-list'));
		}

	    //initializing necessary modules, the argument must be current module name/folder
	    if(!is_null($this->customizer))
		{
			$this->customizer->init($this->module_base);
		}

		//initializing necessary modules, the argument must be current module name/folder
	    if(!is_null($this->seq_number))
		{
			$this->seq_number->init($this->module_base, __('Invoice', 'wf-woocommerce-packing-list'));
		}
		include(plugin_dir_path( __FILE__ ).'views/invoice-admin-settings.php');
	}

	/**
	* 	Print_window for invoice
	* 	@param $orders : order ids
	*	@param $action : (string) download/preview/print
	*	@since 4.0.5 Added compatibilty preview PDF
	*/
    public function print_it($order_ids, $action)
    {
    	if($action=='print_invoice' || $action=='download_invoice' || $action=='preview_invoice')
    	{
    		if($this->is_enable_invoice!='Yes') /* invoice not enabled so only allow preview option */
    		{
    			if($action=='print_invoice' || $action=='download_invoice')
    			{
    				return;
    			}else
    			{
    				if(!Wf_Woocommerce_Packing_List_Admin::check_role_access()) //Check access
	                {
	                	return;
	                }
    			}
    		}
    		if(!is_array($order_ids))
    		{
    			return;
    		}
	        if(!is_null($this->customizer))
	        {
	        	$pdf_name=$this->customizer->generate_pdf_name($this->module_base, $order_ids);
	        	if($action=='download_invoice' || $action=='preview_invoice')
	        	{

	        		$this->customizer->template_for_pdf=true;

	        		if($action=='preview_invoice')
		        	{
		        		$html=$this->customizer->get_preview_pdf_html($this->module_base);
		        		$html=$this->generate_order_template_for_invoice_preview($order_ids, $pdf_name, $html);
		        	}else
		        	{
		        		$html=$this->generate_order_template($order_ids, $pdf_name);
		        	}
		        	$action=str_replace('_'.$this->module_base, '', $action);
	        		$this->customizer->generate_template_pdf($html, $this->module_base, $pdf_name, $action);
	        	}else
	        	{
	        		$html = "";
	        		$html=$this->generate_order_template($order_ids, $pdf_name,$html,$action);
	        		echo $html;
	        	}
	        }else
	        {
	        	_e('Customizer module is not active.', 'wf-woocommerce-packing-list');
	        }
	        exit();
    	}
    }

    /**
    * @since 4.2.1
    * Generate template for order preview in customize tab
    */
    public function generate_order_template_for_invoice_preview($orders, $page_title, $html="")
    {
    	$template_type=$this->module_base;
    	if($html=="")
    	{
    		//taking active template html
    		$html=$this->customizer->get_template_html($template_type);
    	}
    	$style_blocks=$this->customizer->get_style_blocks($html);
    	$html=$this->customizer->remove_style_blocks($html,$style_blocks);
    	$out='';
    	if($html!="")
    	{
    		$number_of_orders=count($orders);
			$order_inc=0;
			foreach($orders as $order_id)
			{
				$order_inc++;
				$order=( WC()->version < '2.7.0' ) ? new WC_Order($order_id) : new wf_order($order_id);
				$out.=$this->customizer->generate_template_html($html,$template_type,$order);
				if($number_of_orders>1 && $order_inc<$number_of_orders)
				{
                	$out.='<p class="pagebreak"></p>';
	            }
			}
			$out=$this->customizer->append_style_blocks($out,$style_blocks);
			$out=$this->customizer->append_header_and_footer_html($out,$template_type,$page_title);
    	}
    	return $out;
    }

    /**
	* 	@since 4.2.1
	* 	Save the invoice template as html file for each order
	*/
    public function generate_order_template($orders,$page_title, $html="",$action=""){
    	$template_type = $this->module_base;
    	$number_of_orders = count($orders);
    	$order_inc = 0;
    	$out = '';

    	foreach($orders as $order_id){
    		$order_inc++;
    		$out .= $this->generate_order_template_for_single_order($order_id,$page_title,$action);

    		if($number_of_orders>1 && $order_inc<$number_of_orders)
			{
            	$out.='<p class="pagebreak"></p>';
            }
    	}
    	return $out;
    }

	public function generate_order_template_for_single_order($order_id,$page_title,$action){
    	$use_latest_settings_invoice = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_use_latest_settings_invoice', $this->module_id);
    	$template_type = $this->module_base;
    	$order=( WC()->version < '2.7.0' ) ? new WC_Order($order_id) : new wf_order($order_id);
		$invoice_html = get_post_meta($order_id, 'wf_invoice_html', true);
		$order_id_arr[] = $order_id;
		$pdf_name=$this->customizer->generate_pdf_name($this->module_base, $order_id_arr);
		$html = "";

		$out = "";
		$upload_loc=Wf_Woocommerce_Packing_List::get_temp_dir();
		$upload_dir=$upload_loc['path'];
        $upload_url=$upload_loc['url'];

		if(!empty($invoice_html)){
	        $file_loc=$upload_dir.'/'.$template_type. '/'.$invoice_html;
	        if(!file_exists($file_loc)){
	        	$new_invoice_html_set = 1;
	        }else{
	        	$new_invoice_html_set = 0;
	        	$html_file=@fopen($file_loc,'r');
  				$html=fread($html_file,filesize($file_loc));
	        }
		}else{
			$new_invoice_html_set = 1;
		}



		if((trim($html)=="") || ($use_latest_settings_invoice == "Yes"))
    	{
    		$html=$this->customizer->get_template_html($template_type);
    		$new_invoice_html_set = 1;

    		$style_blocks=$this->customizer->get_style_blocks($html);
	    	$html=$this->customizer->remove_style_blocks($html,$style_blocks);

	    	$out.=$this->customizer->generate_template_html($html,$template_type,$order);

	        $out=$this->customizer->append_style_blocks($out,$style_blocks);
			$out=$this->customizer->append_header_and_footer_html($out,$template_type,$page_title);
    	}else{
    		$payment_method_slug = (WC()->version< '2.7.0' ? get_post_meta( $order_id, '_payment_method', true ) : $order->get_payment_method());
			$paymethod_title=(WC()->version< '2.7.0' ? $order->payment_method_title : $order->get_payment_method_title());

    		$paymethod_title=__($paymethod_title, 'wf-woocommerce-packing-list');

    		$custom_payment_key = "custom_payment_key";
    		$custom_script_regex = '/<script id="custom_payment_key"[^>]*>[\s\S]*'.$custom_payment_key.'[\s\S]*?<\/script>/';

    		$order_statuses_arr = array('wc-on-hold','wc-pending','wc-failed');
			if(!in_array('wc-'.$order->get_status(), $order_statuses_arr)){
				$updated_script = '<script id="custom_payment_key">var custom_render_block_key = "custom_payment_key";let current = document.querySelector(".wfte_product_table_payment_method_label");let nextSibling = current.nextElementSibling;console.log(nextSibling.innerHTML);nextSibling.innerHTML="'.$paymethod_title.'";let paylink_elm = document.querySelector(".wfte_payment_link");paylink_elm.classList.add("wfte_hidden"); </script>';
			}else{
				$updated_script = '<script id="custom_payment_key">var custom_render_block_key = "custom_payment_key";let current = document.querySelector(".wfte_product_table_payment_method_label");let nextSibling = current.nextElementSibling;console.log(nextSibling.innerHTML);nextSibling.innerHTML="'.$paymethod_title.'"</script>';
			}

    		if(preg_match($custom_script_regex, $html)){
		     	$html = preg_replace($custom_script_regex, $updated_script, $html);
	     	}else{
	     		$html .=$updated_script;
	     	}

	     	$style_regex = '/<style id="template_font_style"[^>]*>[\s\S]*?<\/style>/';
	     	$updated_style = '<style id="template_font_style">*{font-family:"DeJaVu Sans", monospace;}</style>';

	     	if($action === 'print_invoice'){
	     		$updated_style = '<style id="template_font_style">*{/*font-family:"DeJaVu Sans", monospace;*/}</style>';
	     	}

	     	if(preg_match($custom_script_regex, $html)){
     			$html = preg_replace($style_regex, $updated_style, $html);
     		}
    		$out .= $html;

    		$new_invoice_html_set = 1;

    	}

		if($new_invoice_html_set === 1){
	        if(!is_dir($upload_dir))
	        {
	            @mkdir($upload_dir, 0700);
	        }

	        //document type specific subfolder
	        $upload_dir=$upload_dir.'/'.$template_type;
	        $upload_url=$upload_url.'/'.$template_type;
	        if(!is_dir($upload_dir))
	        {
	            @mkdir($upload_dir, 0700);
	        }

	        //if directory successfully created
	        if(is_dir($upload_dir))
	        {
	        	$file_name = $pdf_name.'.html';
	        	$file_path=$upload_dir . '/'.$pdf_name.'.html';
        		$file_url=$upload_url . '/'.$pdf_name.'.html';
	        	//$myfile = fopen($file_path, "w") or die("Unable to open file!");
	        	$fh=@fopen($file_path, "w");
	            if(is_resource($fh))
	            {
	                fwrite($fh,$out);
	                fclose($fh);
	            }
				update_post_meta($order_id,'wf_invoice_html',$file_name);
	        }
		}
		return $out;
    }
}
new Wf_Woocommerce_Packing_List_Invoice();