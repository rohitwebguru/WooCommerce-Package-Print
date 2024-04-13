<?php
/**
 * Necessary functions for customizer module
 *
 * @link
 * @since 4.0.0
 *
 * @package  Wf_Woocommerce_Packing_List
 */

if (!defined('ABSPATH')) {
    exit;
}
include "class-customizer-product-table.php";
include "class-customizer-address.php";
include "class-customizer-product-table-creditnote.php";
class Wf_Woocommerce_Packing_List_CustomizerLib
{
	use Wf_Woocommerce_Packing_List_Customizer_Product_table; /* product table related functions */
	use Wf_Woocommerce_Packing_List_Customizer_Address; /* address related functions */
	use Wf_Woocommerce_Packing_List_Customizer_Product_Table_Creditnote; /* product table related functions */

	const TO_HIDE_CSS='wfte_hidden';
	public static $reference_arr=array();
	public static $hide_on_empty_fields=array();
	public static function get_order_number($order,$template_type)
	{
		$order_number=$order->get_order_number();
		return apply_filters('wf_pklist_alter_order_number', $order_number, $template_type, $order);
	}

	/**
	* @since 4.0.3
	* get documnted generated date
	*/
	public static function get_printed_on_date($html)
	{
		$printed_on_format=self::get_template_html_attr_vl($html, 'data-printed_on-format', 'm/d/Y');
		return date_i18n($printed_on_format);
	}

	public static function set_order_data($find_replace,$template_type,$html,$order=null)
	{
		if(!is_null($order))
        {
        	$wc_version=WC()->version;
			$order_id=$wc_version<'2.7.0' ? $order->id : $order->get_id();

			$find_replace['[wfte_order_number]']=self::get_order_number($order,$template_type);
			if(Wf_Woocommerce_Packing_List_Public::module_exists('invoice'))
			{
				$find_replace['[wfte_invoice_number]']=Wf_Woocommerce_Packing_List_Invoice::generate_invoice_number($order,false); //do not force generate
			}else
			{
				$find_replace['[wfte_invoice_number]']='';
			}

			//order date
			$order_date_match=array();
			$order_date_format='m/d/Y';
			if(preg_match('/data-order_date-format="(.*?)"/s',$html,$order_date_match))
			{
				$order_date_format=$order_date_match[1];
			}
			$order_date=get_the_date($order_date_format,$order_id);
			$order_date=apply_filters('wf_pklist_alter_order_date', $order_date, $template_type, $order);
			$find_replace['[wfte_order_date]']=$order_date;

			//invoice date
			if(Wf_Woocommerce_Packing_List_Public::module_exists('invoice'))
			{
				$invoice_date_match=array();
				$invoice_date_format='m/d/Y';
				if(preg_match('/data-invoice_date-format="(.*?)"/s',$html,$invoice_date_match))
				{
					$invoice_date_format=$invoice_date_match[1];
				}
				//must call this line after `generate_invoice_number` call
				$invoice_date=Wf_Woocommerce_Packing_List_Invoice::get_invoice_date($order_id,$invoice_date_format,$order);
				$invoice_date=apply_filters('wf_pklist_alter_invoice_date',$invoice_date,$template_type,$order);
				$find_replace['[wfte_invoice_date]']=$invoice_date;
			}else
			{
				$find_replace['[wfte_invoice_date]']='';
			}

			//dispatch date
			$dispatch_date_match=array();
			$dispatch_date_format='m/d/Y';
			if(preg_match('/data-dispatch_date-format="(.*?)"/s',$html,$dispatch_date_match))
			{
				$dispatch_date_format=$dispatch_date_match[1];
			}
			$dispatch_date=get_the_date($dispatch_date_format,$order_id);
			$dispatch_date=apply_filters('wf_pklist_alter_dispatch_date',$dispatch_date,$template_type,$order);
			$find_replace['[wfte_dispatch_date]']=$dispatch_date;
		}
		return $find_replace;
	}


	public static function package_doc_items($find_replace,$template_type,$order,$box_packing,$order_package)
	{
		if(!is_null($box_packing))
        {
			$box_details=$box_packing->wf_packinglist_get_table_content($order,$order_package);

			$box_name=$box_details['name'];
			if(Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_package_type')=='box_packing')
			{
				$box_name=apply_filters('wf_pklist_include_box_name_in_packinglist',$box_name, $box_details, $order);
				$box_name_label=apply_filters('wf_pklist_alter_box_name_label',__('Box name', 'wf-woocommerce-packing-list'),$template_type,$order);
				$find_replace['[wfte_box_name]']=(trim($box_name)!="" ? $box_name_label.': '.$box_name : '');
			}else
			{
				$find_replace['[wfte_box_name]']='';
			}
		}else
		{
			$find_replace['[wfte_box_name]']='';
		}
		return $find_replace;
	}

	/**
	* 	Set extra data like footer, special_notes. Modules can override these type of data
	*	@since 4.0.3
	*/
	private static function set_extra_text_data($find_replace,$data_slug,$template_type,$html,$order=null)
	{
		if(!isset($find_replace['[wfte_'.$data_slug.']'])) /* check already added */
		{
			//module settings are saved under module id
			$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);

			$txt_data=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_'.$data_slug,$module_id);
			if($txt_data===false || $txt_data=='') //custom data from module not present or empty
			{
				//call main data
				$txt_data=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_'.$data_slug);
			}
			if(!is_null($order))
			{
				$txt_data=apply_filters('wf_pklist_alter_'.$data_slug.'_data', $txt_data, $template_type, $order);
			}
			$find_replace['[wfte_'.$data_slug.']']=nl2br($txt_data);
		}
		return $find_replace;
	}

	/**
	* 	Process text data like return policy, sale terms, transport terms.
	*	@since 4.0.3
	*/
	private static function set_text_data($find_replace,$data_slug,$template_type,$html,$order=null)
	{
		if(!isset($find_replace['[wfte_'.$data_slug.']'])) /* check already added */
		{
			$txt_data=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_'.$data_slug);
			if(!is_null($order))
			{
				$txt_data=apply_filters('wf_pklist_alter_'.$data_slug.'_data',$txt_data,$template_type,$order);
			}
			$find_replace['[wfte_'.$data_slug.']']=nl2br($txt_data);
		}
		return $find_replace;
	}

	/**
	* 	Set other data, includes barcode, signature etc
	*	@since 4.0.0
	*	@since 4.0.2	Included total weight function, added $html argument
	*/
	public static function set_other_data($find_replace,$template_type,$html,$order=null)
	{
		//module settings are saved under module id
		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);

		//return policy, sale terms, transport terms
		$find_replace=self::set_text_data($find_replace,'return_policy',$template_type,$html,$order);
		$find_replace=self::set_text_data($find_replace,'transport_terms',$template_type,$html,$order);
		$find_replace=self::set_text_data($find_replace,'sale_terms',$template_type,$html,$order);

		//footer data
		$find_replace=self::set_extra_text_data($find_replace,'footer',$template_type,$html,$order);

		//special notes
		$find_replace=self::set_extra_text_data($find_replace,'special_notes',$template_type,$html,$order);


		//signature
		$signture_url=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_invoice_signature',$module_id);
		$find_replace['[wfte_signature_url]']=$signture_url;
		$find_replace['[wfte_signature]']=$signture_url;

		//barcode, additional info
		if(!is_null($order))
        {
			if(!isset($find_replace['[wfte_barcode_url]'])) /* check already added */
			{
				$invoice_number=Wf_Woocommerce_Packing_List_Public::module_exists('invoice') ? Wf_Woocommerce_Packing_List_Invoice::generate_invoice_number($order,false) : '';
				$invoice_number=apply_filters('wf_pklist_alter_barcode_data', $invoice_number, $template_type, $order);
				$find_replace['[wfte_barcode_url]']='';
				$find_replace['[wfte_barcode]']='';
				if($invoice_number!="" && strpos($html, '[wfte_barcode_url]') !== false)
				{
					include_once plugin_dir_path(WF_PKLIST_PLUGIN_FILENAME).'includes/class-wf-woocommerce-packing-list-barcode_generator.php';
					$barcode_width_factor=2;
					$barcode_width_factor=apply_filters('wf_pklist_alter_barcode_width_factor',$barcode_width_factor,$template_type,$invoice_number,$order);

					$barcode_file_type='png';
					$barcode_file_type=apply_filters('wf_pklist_alter_barcode_file_type', $barcode_file_type, $template_type, $order);

					$barcode_url=Wf_Woocommerce_Packing_List_Barcode_generator::generate($invoice_number, $barcode_file_type, $barcode_width_factor);
					if($barcode_url)
					{
						$find_replace['[wfte_barcode_url]']=$barcode_url;
						$find_replace['[wfte_barcode]']='1'; //just a value to prevent hiding barcode
					}
				}
			}

			if(!isset($find_replace['[wfte_additional_data]'])) /* check already added */
			{
				$additional_info='';
				$find_replace['[wfte_additional_data]']=apply_filters('wf_pklist_add_additional_info',$additional_info,$template_type,$order);
			}
		}

		//set total weight
		$find_replace=self::set_total_weight($find_replace,$template_type,$html,$order);

		if(!isset($find_replace['[wfte_printed_on]'])) /* check already added */
		{
			//prints the current date with the given format
			$find_replace['[wfte_printed_on]']=self::get_printed_on_date($html);
		}
		return $find_replace;
	}

	/**
	* Total price in words
	*	@since 4.0.2
	*/
	public static function set_total_in_words($total,$find_replace,$template_type,$html,$order=null)
	{
		if(strpos($html,'[wfte_total_in_words]')!==false) //if total in words placeholder exists then only do the process
        {
        	$total_in_words=self::convert_number_to_words($total);
        	$total_in_words=apply_filters('wf_pklist_alter_total_price_in_words',$total_in_words,$template_type,$order);
        	$find_replace['[wfte_total_in_words]']=$total_in_words;
        }
        return $find_replace;
	}

	/**
	*	Get the total weight of an order.
	*	@since 4.0.2
	*	@param array $find_replace find and replace data
	* 	@param string $template_type document type Eg: invoice
	*	@param string $html template HTML
	* 	@param object $order order object
	*
	*	@return array $find_replace
	*/
	public static function set_total_weight($find_replace, $template_type, $html, $order=null)
	{
		$total_weight=0;

		if(strpos($html,'[wfte_weight]')!==false && !isset($find_replace['[wfte_weight]'])) //if total weight placeholder exists then only do the process, If already added then skip
        {
			if(!is_null($order))
			{
				$order_items=$order->get_items();
				$find_replace['[wfte_weight]']=__('n/a','wf-woocommerce-packing-list');
				if($order_items)
				{
					foreach($order_items as $item)
					{
						$quantity=(int) $item->get_quantity(); // get quantity
				        $product=$item->get_product(); // get the WC_Product object
				        $weight=0;
				        if($product)
				        {
				        	$weight=(float) $product->get_weight(); // get the product weight
				        }
				        $total_weight+=floatval($weight*$quantity);
					}
					$weight_data=$total_weight.' '.get_option('woocommerce_weight_unit');
					$weight_data=apply_filters('wf_pklist_alter_weight', $weight_data, $total_weight, $order);

					/* the below line is for adding compatibility for existing users */
					$weight_data=apply_filters('wf_pklist_alter_packinglist_weight',$weight_data,$total_weight,$order);
					$find_replace['[wfte_weight]']=$weight_data;
				}
			}else
			{
				$find_replace['[wfte_weight]']=$total_weight.' '.get_option('woocommerce_weight_unit');
			}
		}
		return $find_replace;
	}
	public static function set_extra_fields($find_replace,$template_type,$html,$order=null)
	{

		$extra_fields=array();
		//module settings are saved under module id
		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);

		if(!is_null($order))
        {
        	$wc_version=(WC()->version<'2.7.0') ? 0 : 1;
        	$order=($wc_version==0 ? new WC_Order($order) : new wf_order($order));
        	$order_id=($wc_version==0 ? $order->id : $order->get_id());

        	//Payment Link
        	if(!isset($find_replace['[wfte_payment_link]'])) /* check already added */
			{
				$paymethod_title=($wc_version< '2.7.0' ? get_post_meta( $order_id, '_payment_method', true ) : $order->get_payment_method());
				$enable_payment_link = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_enable_payment_link_in_invoice',$module_id);
				$enable_payment_link_for_order_status = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_payment_link_in_order_status',$module_id);

				if(($enable_payment_link == 1) || ($enable_payment_link == "1")){
					if((in_array('wc-'.$order->get_status(), $enable_payment_link_for_order_status)) || (trim($paymethod_title) == "wf_pay_later")){
						$order_payment_link = $order->get_checkout_payment_url();
			        	if($order_payment_link != "")
			        	{
			        		$find_replace['[wfte_payment_link]']=$order_payment_link;
			        	}else
			        	{
			        		$find_replace['[wfte_payment_link]']='';
			        	}
					}else
		        	{
		        		$find_replace['[wfte_payment_link]']='';
		        	}
				}
				else
	        	{
	        		$find_replace['[wfte_payment_link]']='';
	        	}
	        }
        	//shipping method
        	if(!isset($find_replace['[wfte_shipping_method]'])) /* check already added */
			{
	        	$order_shipping =($wc_version==0 ? $order->shipping_method : $order->get_shipping_method());
	        	if(get_post_meta($order_id, '_tracking_provider', true) || $order_shipping)
	        	{
	        		$find_replace['[wfte_shipping_method]']=apply_filters('wf_pklist_alter_shipping_method', $order_shipping, $template_type, $order, 'order_data');
	        	}else
	        	{
	        		$find_replace['[wfte_shipping_method]']='';
	        	}
	        }

        	//tracking number
        	if(!isset($find_replace['[wfte_tracking_number]'])) /* check already added */
			{
	        	$tracking_key=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_tracking_number');
	        	$tracking_data=apply_filters('wf_pklist_tracking_data_key', $tracking_key, $template_type, $order);
	        	$tracking_details=get_post_meta($order_id, ($tracking_key!='' ? $tracking_data : '_tracking_number'), true);
	        	if($tracking_details)
	        	{
	        		$find_replace['[wfte_tracking_number]']=apply_filters('wf_pklist_alter_tracking_details', $tracking_details, $template_type, $order);
	        	}else
	        	{
	        		$find_replace['[wfte_tracking_number]']='';
	        	}
	        }

	        if(!isset($find_replace['[wfte_extra_fields]'])) /* check already added */
			{
		        $the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);
	        	$default_options=Wf_Woocommerce_Packing_List::default_settings($module_id);
	        	$default_fields=array_keys(Wf_Woocommerce_Packing_List::$default_additional_data_fields);
	        	$user_selected_fields=(isset($the_options['wf_'.$template_type.'_contactno_email']) && is_array($the_options['wf_'.$template_type.'_contactno_email']) ? $the_options['wf_'.$template_type.'_contactno_email'] : array());
	        	$extra_fields=array();

	        	/* loop through default fields */
	        	foreach($default_fields as $default_field)
	        	{
	        		$placeholder='[wfte_'.$default_field.']';
        			if(strpos($html, $placeholder)!==false) // placeholder exists
        			{
        				$find_replace[$placeholder]=self::get_default_order_fields($default_field, $order, $order_id, $wc_version);

        			}elseif(in_array($default_field, $user_selected_fields)) //found on user selected fields
        			{
        				$extra_fields[$default_field]=self::get_default_order_fields($default_field, $order, $order_id, $wc_version);
        			}else
        			{
        				//no need to take the value.
        			}
	        	}


	        	/* add empty values to extra field placeholders. Real values will be assigned to the available items by below code block */
	        	$extra_field_placeholder_prefix='wfte_'.Wf_Woocommerce_Packing_List_Customizer::$extra_field_slug_prefix;
	        	if(preg_match_all('/\['.$extra_field_placeholder_prefix.'([a-zA-Z0-9-_\s]*)\]/m', $html, $extra_field_placeholder_datas, PREG_SET_ORDER, 0))
	        	{
	        		foreach($extra_field_placeholder_datas as $extra_field_placeholder_item)
	        		{
	        			if(!isset($find_replace[$extra_field_placeholder_item[0]]))
	        			{
	        				$find_replace[$extra_field_placeholder_item[0]]='';
	        			}
	        		}
	        	}

		       	$user_created_fields=Wf_Woocommerce_Packing_List_Customizer::get_all_user_created_fields();

		        foreach($user_created_fields as $user_created_field_key=>$user_created_field_val)
        		{
        			$placeholder=Wf_Woocommerce_Packing_List_Customizer::prepare_custom_meta_placeholder($user_created_field_key);
        			if(strpos($html, $placeholder)!==false) /* placeholder exists */
    				{
    					$meta_value=self::get_order_meta_value_by_key($order_id, $user_created_field_key);
    					if($meta_value!="")
    					{
    						$find_replace[$placeholder]=$meta_value;
    					}else
    					{
    						self::$hide_on_empty_fields[]=trim($placeholder, '[]'); /* hide the div on empty */
    					}
    				}elseif(in_array($user_created_field_key, $user_selected_fields)) //found on user selected fields
        			{
        				$extra_fields[$user_created_field_val]=self::get_order_meta_value_by_key($order_id, $user_created_field_key);
        			}else
        			{
        				//no need to take the value.
        			}
        		}

	        	//filter to alter extra fields
	        	$extra_fields=apply_filters('wf_pklist_alter_additional_fields',$extra_fields,$template_type,$order);

	        	self::set_extra_fields_html($extra_fields, $default_fields, $html, $find_replace);
	        }

	        if(!isset($find_replace['[wfte_order_item_meta]'])) /* check already added */
			{
	        	$order_item_meta_data='';
	        	$order_item_meta_data=apply_filters('wf_pklist_order_additional_item_meta', $order_item_meta_data, $template_type, $order);
	        	$find_replace['[wfte_order_item_meta]']=$order_item_meta_data;
	        }
		}
		return $find_replace;
	}

	// change
	public static function preparation_instruction($find_replace, $template_type, $html, $order=null) {

		if (is_null($order)) {
			$title = "Preparation Instruction";

			$lists = array("Parameter 1", "Parameter 2", "Parameter 3", "Parameter 4");

			$html_output ='<ul style="padding: 0px 0px 0px 13px">';
			foreach ($lists as $list_item) {
				$html_output .= '<li>' . $list_item . '</li>';
			}
			$html_output .= '</ul>';


			// return $html_output;

			// $find_replace['[wfte_preparation_instruction]'] = $html_output;
			return $html_output;
		} else {

			$lists = self::getInstructionsListing('preparation_instruction', $order);
			
			$title = "Preparation Instruction";

			$html_output ='<ul style="padding: 0px 0px 0px 13px">';
			foreach ($lists as $list_item) {
				$html_output .= '<li>' . $list_item . '</li>';
			}
			$html_output .= '</ul>';

			$find_replace['[wfte_preparation_instruction]'] = $html_output;
			return $find_replace;
		}
	}

	public static function getInstructionsListing($type, $order=null){
		// change start
		$lists = [];
		if($type && $order){
			
			$total_weight = 0;
			$total_quantity = 0;
			$unique_items = array();
			$shipping_method_title = '';
	
			foreach( $order->get_items() as $item_id => $product_item ){
				$quantity = $product_item->get_quantity();
				$product = $product_item->get_product();
				// echo"<pre>"; print_r($product); die;
				if($product){
					$product_weight = $product->get_weight();
					if($product_weight){
						$total_weight += floatval( $product_weight * $quantity );
					}			
				}

				if($quantity){
					$total_quantity += $quantity;
				}
	
				$product_id = $product_item->get_product_id();
				if (!in_array($product_id, $unique_items)) {
					$unique_items[] = $product_id;
				}
			}
	
			$total_unique_items = count($unique_items);
	
			// Get shipping methods
			$shipping_methods = $order->get_shipping_methods();
			if (!empty($shipping_methods)) {
	
				$first_shipping_method = reset($shipping_methods);
				$shipping_method_title = $first_shipping_method->get_name();
			} else {
				echo "No shipping method found for this order.";
			}
	
			$orderTotalAmount = $order->get_total();
			$orderTotalWeight = $total_weight;
			$orderTotalNumberOfItems = $total_quantity;
			$orderTotalNumberOfDifferentItems = $total_unique_items;
			$orderShippingMethod = $shipping_method_title;
			// echo "<pre>"; print_r($orderShippingMethod); die;

			// $shipping_methods = WC()->shipping->get_shipping_methods();
			// $shipping_method_names = array();			
			// foreach ($shipping_methods as $shipping_method) {
			// 	$method_title = $shipping_method->get_method_title();
			// 	if (!in_array($method_title, $shipping_method_names)) {
			// 		$shipping_method_names[] = $method_title;
			// 	}
			// }


			// $shipping_methods = $order->get_shipping_methods();
			// $shipping_method_names = array();
			// foreach ($shipping_methods as $shipping_method) {
			// 	$method_title = $shipping_method->get_method_title();
			// 	$shipping_method_names[] = $method_title;
			// }
			// echo "<pre>"; print_r($order->get_shipping_methods()); die;

			$lists = [];
			// Queries
			// query for packing instruction
			global $wpdb;
			$packing_instructions = $wpdb->prefix . 'packing_instructions';
			$packing_conditions = $wpdb->prefix . 'packing_conditions';
			// $instructions = $wpdb->get_results("SELECT * FROM $packing_instructions WHERE instruction_type = 'preparation_instruction'", ARRAY_A);
			$instructions = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM $packing_instructions WHERE instruction_type = %s", $type), ARRAY_A
			);
			
			if($instructions){
				foreach($instructions as $instruction){
					// echo "<pre>"; print_r($instruction); die;
					$conditions = $wpdb->get_results(
						$wpdb->prepare("SELECT * FROM $packing_conditions WHERE instruction_id = %d", $instruction['id']),
						ARRAY_A
					);
					
					if($conditions){
						foreach($conditions as $key => $condition){
	
							switch ($condition['p_parameter2']) {
								case 'quantity_in_the_product': {
									$product_quantity = 0;
									foreach ($order->get_items() as $item_id => $item) {
										$product = wc_get_product($item->get_product_id());
										// echo"<pre>"; print_r($product); die;
										if($product && $product->get_sku()==$condition['p_parameter3']){
											$product_quantity += $item->get_quantity();
										}
									}
									// echo"<pre>"; print_r($product_quantity); die;
									if (self::checkOperator($product_quantity, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'quantity_in_each_product_of_category': {
									$category_slug = $condition['p_parameter3'];
									$category_quantity = 0;
									foreach ($order->get_items() as $item_id => $item) {
										$product_id = $item->get_product_id();
										$product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
										
										if (in_array($category_slug, $product_categories)) {
											$category_quantity += $item->get_quantity();
										}
									}
									if (self::checkOperator($category_quantity, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'order_total_amount': {
									if (self::checkOperator($orderTotalAmount, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'order_total_weight': {
									if (self::checkOperator($orderTotalWeight, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'order_total_number_of_items': {
									if (self::checkOperator($orderTotalNumberOfItems, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'order_total_number_of_total_different_items': {
									if (self::checkOperator($orderTotalNumberOfDifferentItems, $condition['p_parameter4'], $condition['p_parameter5'])) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								case 'order_shipping_method': {
									if (self::checkOperator(strtolower($orderShippingMethod), $condition['p_parameter4'], strtolower($condition['p_parameter5']))) {
										$result = 1;
									} else {
										$result = 0;
									}
									break;
								}
								default: {
									// Block scoped to default case
									$result = 0;
									break;
								}
							}
							
							// $result is accessible here, but its value depends on the case that was matched
							$conditions[$key]['result'] = $result;
						}
					}
					
					$lists = self::evaluateConditions($conditions, $instruction, $lists);
				}
			}
		}
		// echo "<pre>"; print_r($lists); die;
		return $lists;
		// change end
	}

	public static function checkOperator($value1, $operator, $value2)
	{
		switch ($operator) {
			case '=': // Equal
				return $value1 == $value2;
			case '<=': // Less than or equal to
				return $value1 <= $value2;
			case '>=': // Greater than or equal to
				return $value1 >= $value2;
			case '<>': // Not equal
				return $value1 != $value2;  
			default:
				return FALSE;
		}
	}

	public static function evaluateConditions($conditions, $instruction, $lists) {

		$pairArray = [];

		for ($i = 0; $i < count($conditions); $i += 2) {
			$pair = [$conditions[$i]];
			if ($i + 1 < count($conditions)) {
				$pair[] = $conditions[$i + 1];
			}
			$pairArray[] = $pair;
		}

		$conditionStr = [];

		if($pairArray){
			foreach($pairArray as $key => $pairArr){
				
				if(sizeof($pairArr)==2){
					if($key > 0){
						array_push($conditionStr,$pairArr[0]['p_parameter1']);	
					}
					array_push($conditionStr,self::pairedArrEvaluate($pairArr));
				}else{
					if($key > 0){
						array_push($conditionStr,$pairArr[0]['p_parameter1']);	
					}
					array_push($conditionStr,$pairArr[0]['result']);
				}
			}
		}

		$array = $conditionStr;

		$conditionString = '';
		for ($i = 0; $i < count($array); $i += 2) {
			if ($i !== 0) {
				$conditionString .= " {$array[$i - 1]} ";
			}
			$conditionString .= $array[$i];
		}

		$result = eval("return $conditionString;");

		if($result){
			if($instruction['text_instruction']){
				array_push($lists, $instruction['text_instruction']);
			}else if($instruction['file_instruction']){
				array_push($lists, '<img src="' . $instruction['file_instruction'] . '" alt="Instruction Image" width="100px">');
			}
		}
		
		return $lists;
	}

	public static function pairedArrEvaluate($pairArr){
		
		switch ($pairArr[1]['p_parameter1']) {
			case "AND": {
				
				if($pairArr[0]['result'] && $pairArr[1]['result']){
					$result = 1; 
				}else{
					$result = 0;
				}
				return $result;
			}
			case 'OR': {
				
				if($pairArr[0]['result'] || $pairArr[1]['result']){
					$result = 1; 
				}else{
					$result = 0;
				}
				return $result;
			}
			
		}
	}

	public static function packing_instruction($find_replace, $template_type, $html, $order=null) {

		if (is_null($order)) {
			$title = "Packing Instruction";

			$lists = array("Parameter 1", "Parameter 2", "Parameter 3", "Parameter 4");

			$html_output ='<ul style="padding: 0px 0px 0px 13px">';
			foreach ($lists as $list_item) {
				$html_output .= '<li>' . $list_item . '</li>';
			}
			$html_output .= '</ul>';

			// $find_replace['[wfte_packing_instruction]'] = $html_output;
			return $html_output;
		} else {
			$title = "Packing Instruction";

			$lists = array("Parameter 1", "Parameter 2", "Parameter 3", "Parameter 4");
			$lists = self::getInstructionsListing('packing_instruction', $order);

			$html_output ='<ul style="padding: 0px 0px 0px 13px">';
			foreach ($lists as $list_item) {
				$html_output .= '<li>' . $list_item . '</li>';
			}
			$html_output .= '</ul>';

			$find_replace['[wfte_packing_instruction]'] = $html_output;
			return $find_replace;
		}
	}

	// change end
	private static function get_default_order_fields($key, $order, $order_id, $wc_version)
	{
		$meta_vl='';
		if($key=='email')
		{
			$meta_vl=($wc_version==0 ? $order->billing_email : $order->get_billing_email());
		}elseif($key=='contact_number')
		{
			$meta_vl=($wc_version==0 ? $order->billing_phone : $order->get_billing_phone());
		}elseif($key=='vat')
		{
			$meta_vl=($wc_version==0 ? $order->billing_vat : get_post_meta($order_id,'_billing_vat',true));

		}elseif($key=='ssn')
		{
			$meta_vl=($wc_version==0 ? $order->billing_ssn : get_post_meta($order_id,'_billing_ssn',true));

		}elseif($key=='cus_note')
		{
			$meta_vl=($wc_version==0 ? $order->customer_note : $order->get_customer_note());
		}else
		{
			$meta_vl=get_post_meta($order_id, '_billing_'.$key, true);
		}
		return $meta_vl;
	}
	private static function get_order_meta_value_by_key($order_id, $meta_key)
	{
		$meta_value=get_post_meta($order_id, '_billing_'.$meta_key, true);
		if(!$meta_value)
		{
			$meta_value=get_post_meta($order_id, $meta_key, true);
			if(!$meta_value)
			{
				$meta_value=get_post_meta($order_id, '_'.$meta_key, true);
				if(!$meta_value)
				{
					$meta_value='';
				}
			}
		}

		/**
		* Some plugins storing meta data as array
		*
		*/
		$meta_value=self::process_meta_value($meta_value);

		return $meta_value;
	}
	/**
	 * 	Some plugins storing meta data as array, So take the first value in the array
	 *
	 */
	public static function process_meta_value($meta_value)
	{
		if(is_array($meta_value))
		{
			if(isset($meta_value[0]) && is_string($meta_value[0]))
			{
				$meta_value=$meta_value[0];
			}else
			{
				$meta_value='';
			}
		}
		return $meta_value;
	}
	public static function set_extra_fields_html($extra_fields, $default_fields, $html, &$find_replace)
	{
		$default_fields_label=Wf_Woocommerce_Packing_List::$default_additional_data_fields;

		$default_fields_placeholder=array(
    		'vat'=>'vat_number',
    		'ssn'=>'ssn_number',
    		'contact_number'=>'tel',
    		'cus_note'=>'customer_note',
    	);

		//extra fields
    	$ex_html='';
    	if(is_array($extra_fields))
    	{
        	foreach($extra_fields as $ex_key=>$ex_vl)
        	{
        		if(!in_array($ex_key, $default_fields)) //not default fields like vat,ssn
    			{
    				if(is_string($ex_vl) && trim($ex_vl)!="")
    				{
    					$ex_html.='<div class="wfte_extra_fields">
				            <span>'.__(ucfirst($ex_key), 'wf-woocommerce-packing-list').':</span>
				            <span>'.__($ex_vl,'wf-woocommerce-packing-list').'</span>
				          </div>';
    				}
        		}else
        		{
        			$placeholder_key=isset($default_fields_placeholder[$ex_key]) ? $default_fields_placeholder[$ex_key] : $ex_key;
        			$placeholder='[wfte_'.$placeholder_key.']';
        			if(strpos($html,$placeholder)===false) //default fields that have no placeholder
        			{
        				if(trim($ex_vl)!="")
        				{
        					$ex_html.='<div class="wfte_extra_fields">
					            <span>'.__($default_fields_label[$ex_key], 'wf-woocommerce-packing-list').':</span>
					            <span>'.__($ex_vl,'wf-woocommerce-packing-list').'</span>
					          </div>';
        				}
        			}else
        			{
        				$find_replace[$placeholder]=__($ex_vl, 'wf-woocommerce-packing-list');
        			}
        		}
        	}
    	}
    	$find_replace['[wfte_extra_fields]']=$ex_html;
	}
	public static function set_logo($find_replace, $template_type)
	{
		//module settings are saved under module id
		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);

		$the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);
		$the_options_main=Wf_Woocommerce_Packing_List::get_settings();
		$find_replace['[wfte_company_logo_url]']='';
		$find_replace['[wfte_company_logo_img_box]']='';
		if(isset($the_options['woocommerce_wf_packinglist_logo']) && $the_options['woocommerce_wf_packinglist_logo']!="")
		{
			$find_replace['[wfte_company_logo_url]']=$the_options['woocommerce_wf_packinglist_logo'];
			$find_replace['[wfte_company_logo_img_box]']=1; //just a value to prevent hiding logo
		}else
		{
			if($the_options_main['woocommerce_wf_packinglist_logo']!="")
			{
				$find_replace['[wfte_company_logo_url]']=$the_options_main['woocommerce_wf_packinglist_logo'];
				$find_replace['[wfte_company_logo_img_box]']=1; //just a value to prevent hiding logo
			}
		}
		$find_replace['[wfte_company_name]']=$the_options_main['woocommerce_wf_packinglist_companyname'];
		return $find_replace;
	}

    private static function wf_is_multi($array)
    {
	    $multi_check = array_filter($array,'is_array');
	    if(count($multi_check)>0) return true;
	    return false;
    }

    /**
    *	Convert number to words
    *	@author hunkriyaz <Github>
    *	@since 4.0.2
    *
    */
    public static function convert_number_to_words($number)
    {
	    $hyphen      = '-';
	    $conjunction = ' and ';
	    $separator   = ', ';
	    $negative    = 'negative ';
	    $decimal     = ' point ';
	    $dictionary  = array(
	        0                   => 'zero',
	        1                   => 'one',
	        2                   => 'two',
	        3                   => 'three',
	        4                   => 'four',
	        5                   => 'five',
	        6                   => 'six',
	        7                   => 'seven',
	        8                   => 'eight',
	        9                   => 'nine',
	        10                  => 'ten',
	        11                  => 'eleven',
	        12                  => 'twelve',
	        13                  => 'thirteen',
	        14                  => 'fourteen',
	        15                  => 'fifteen',
	        16                  => 'sixteen',
	        17                  => 'seventeen',
	        18                  => 'eighteen',
	        19                  => 'nineteen',
	        20                  => 'twenty',
	        30                  => 'thirty',
	        40                  => 'fourty',
	        50                  => 'fifty',
	        60                  => 'sixty',
	        70                  => 'seventy',
	        80                  => 'eighty',
	        90                  => 'ninety',
	        100                 => 'hundred',
	        1000                => 'thousand',
	        1000000             => 'million',
	        1000000000          => 'billion',
	        1000000000000       => 'trillion',
	        1000000000000000    => 'quadrillion',
	        1000000000000000000 => 'quintillion'
	    );
	    if (!is_numeric($number)) {
	        return false;
	    }
	    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
	        // overflow
	        /*
	        trigger_error(
	            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
	            E_USER_WARNING
	        ); */
	        return false;
	    }
	    if ($number < 0) {
	        return $negative . self::convert_number_to_words(abs($number));
	    }
	    $string = $fraction = null;
	    if (strpos($number, '.') !== false) {
	        list($number, $fraction) = explode('.', $number);
	    }
	    switch (true) {
	        case $number < 21:
	            $string = $dictionary[$number];
	            break;
	        case $number < 100:
	            $tens   = ((int) ($number / 10)) * 10;
	            $units  = $number % 10;
	            $string = $dictionary[$tens];
	            if ($units) {
	                $string .= $hyphen . $dictionary[$units];
	            }
	            break;
	        case $number < 1000:
	            $hundreds  = $number / 100;
	            $remainder = $number % 100;
	            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
	            if ($remainder) {
	                $string .= $conjunction . self::convert_number_to_words($remainder);
	            }
	            break;
	        default:
	            $baseUnit = pow(1000, floor(log($number, 1000)));
	            $numBaseUnits = (int) ($number / $baseUnit);
	            $remainder = $number % $baseUnit;
	            $string = self::convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
	            if ($remainder) {
	                $string .= $remainder < 100 ? $conjunction : $separator;
	                $string .= self::convert_number_to_words($remainder);
	            }
	            break;
	    }
	    if (null !== $fraction && is_numeric($fraction)) {
	        $string .= $decimal;
	        $words = array();
	        foreach (str_split((string) $fraction) as $number) {
	            $words[] = $dictionary[$number];
	        }
	        $string .= implode(' ', $words);
	    }
	    return $string;
	}

	/**
	*	@since 4.0.9
	*	Add values to placeholders that are not available in the doc type module
	*/
	public static function add_missing_placeholders($find_replace, $template_type, $html, $order)
	{
		/**
		*	Handle all product table price section shortcodes
		*/
		if($template_type != "creditnote"){
			$find_replace=self::set_extra_charge_fields($find_replace, $template_type, $html, $order);
		}

		/**
		*	Handle all other infos, Including footer, return policy, total weight, printed on etc
		*/
		$find_replace=self::set_other_data($find_replace, $template_type, $html, $order);


		/**
		*	Handle order datas, Order meta, Shipping method, Tracking number etc
		*/
		$find_replace=self::set_extra_fields($find_replace, $template_type, $html, $order);

		return $find_replace;
	}

	/**
	*	@since 4.0.9
	*	Get tax inclusive text.
	*/
	public static function get_tax_incl_text($template_type, $order, $text_for='total_price')
	{
		$incl_tax_text=__('incl. tax', 'wf-woocommerce-packing-list');
	    return apply_filters('wf_pklist_alter_tax_inclusive_text', $incl_tax_text, $template_type, $order, $text_for);
	}

    /**
    *	Hide the empty placeholders in the template HTML
    *	@since 4.0.0
    *	@since 4.0.2	added wfte_weight in defult hide list
    */
    public static function hide_empty_elements($find_replace,$html,$template_type)
    {
    	$hide_on_empty_fields=array('wfte_vat_number','wfte_ssn_number','wfte_email','wfte_tel','wfte_shipping_method','wfte_tracking_number','wfte_footer','wfte_return_policy',
    		'wfte_product_table_coupon',
			'wfte_product_table_fee',
			'wfte_product_table_total_tax',
			'wfte_product_table_order_discount',
			'wfte_product_table_cart_discount',
			'wfte_product_table_shipping',
			'wfte_order_item_meta',
			'wfte_weight',
			'wfte_total_in_words',
			'wfte_signature',
			'wfte_company_logo_img_box',
			'wfte_barcode',
			'wfte_product_table_payment_method',
			'wfte_payment_link',
			'wfte_customer_note',

			'wfte_from_address_name',
			'wfte_from_address_address_line1',
			'wfte_from_address_address_line2',
			'wfte_from_address_city',
			'wfte_from_address_state',
			'wfte_from_address_postcode',
			'wfte_from_address_country',
			'wfte_from_address_contact_number',
			'wfte_from_address_vat',

			'wfte_return_address_name',
			'wfte_return_address_address_line1',
			'wfte_return_address_address_line2',
			'wfte_return_address_city',
			'wfte_return_address_state',
			'wfte_return_address_postcode',
			'wfte_return_address_country',
			'wfte_return_address_contact_number',
			'wfte_return_address_vat',

			'wfte_billing_address_name',
			'wfte_billing_address_company',
			'wfte_billing_address_address_1',
			'wfte_billing_address_address_2',
			'wfte_billing_address_city',
			'wfte_billing_address_state',
			'wfte_billing_address_postcode',
			'wfte_billing_address_country',

			'wfte_shipping_address_name',
			'wfte_shipping_address_company',
			'wfte_shipping_address_address_1',
			'wfte_shipping_address_address_2',
			'wfte_shipping_address_city',
			'wfte_shipping_address_state',
			'wfte_shipping_address_postcode',
			'wfte_shipping_address_country',



		);
    	$hide_on_empty_fields=array_merge(self::$hide_on_empty_fields, $hide_on_empty_fields);

		$hide_on_empty_fields=apply_filters('wf_pklist_alter_hide_empty',$hide_on_empty_fields,$template_type);
    	foreach ($hide_on_empty_fields as $key => $value)
    	{
    		$css_class=Wf_Woocommerce_Packing_List_Admin::sanitize_css_class_name($value);
    		if(isset($find_replace['['.$value.']']))
	    	{
	    		if($find_replace['['.$value.']']=="")
	    		{
	    			$html=self::addClass($css_class, $html, self::TO_HIDE_CSS);
	    		}
	    	}else
	    	{
	    		$find_replace['['.$value.']']='';
	    		$html=self::addClass($css_class, $html, self::TO_HIDE_CSS);
	    	}
    	}

    	$html=apply_filters('wf_pklist_alter_html_after_hide_empty', $html, $hide_on_empty_fields, $template_type);

    	return $html;
    }
    public static function getElmByClass($elm_class,$html)
    {
    	$matches=array();
    	$re = '/<[^>]*class\s*=\s*["\']([^"\']*)'.$elm_class.'(.*?[^"\']*)["\'][^>]*>/m';
		if(preg_match($re,$html,$matches))
		{
		  return $matches;
		}else
		{
			return false;
		}
    }
    private static function filterCssClasses($class)
    {
    	$class_arr=explode(" ",$class);
    	return array_unique(array_filter($class_arr));
    }
	private static function removeClass($elm_class,$html,$remove_class)
    {
    	$match=self::getElmByClass($elm_class,$html);
    	if($match) //found
    	{
    		$elm_class=$match[1].$elm_class.$match[2];
    		$new_class_arr=self::filterCssClasses($elm_class);
			foreach(array_keys($new_class_arr,$remove_class) as $key) {
			    unset($new_class_arr[$key]);
			}
			$new_class=implode(" ",$new_class_arr);
    		return str_replace($elm_class,$new_class,$html);
    	}
    	return $html;
    }

    /**
    *	Add class to element
    *	@since 4.0.0
    *	@param	string $elm_class CSS class to select
    *	@param  string $html HTML to search
    *	@param 	string $new_class new CSS class to add
    */
    public static function addClass($elm_class,$html,$new_class)
    {
    	$match=self::getElmByClass($elm_class,$html);
    	if($match) //found
    	{
    		$elm_class=$match[1].$elm_class.$match[2];
    		$new_class_arr=self::filterCssClasses($elm_class.' '.$new_class);
			$new_class=implode(" ",$new_class_arr);
    		return str_replace($elm_class,$new_class,$html);
    	}
    	return $html;
    }

    public static function get_template_html_attr_vl($html,$attr,$default='')
	{
		$match_arr=array();
		$out=$default;
		if(preg_match('/'.$attr.'="(.*?)"/s',$html,$match_arr))
		{
			$out=$match_arr[1];
			$out=($out=='' ? $default : $out);
		}
		return $out;
	}


	private static function dummy_product_row($columns_list_arr)
	{
		$html='';
		$dummy_vals=array(
			'image'=>self::generate_product_image_column_data(0,0,0),
			'product'=>'Jumbing LED Light Wall Ball',
			'sku'=>'A1234',
			'quantity'=>'1',
			'price'=>'$20.00',
			'tax'=>'$2.00',
			'total_price'=>'$100.00',
			'total_weight'=>'2 kg',
			'tax_items'=>'2% GST',
		);
		$html='<tr>';
		foreach($columns_list_arr as $columns_key=>$columns_value)
		{
			$is_hidden=($columns_key[0]=='-' ? 1 : 0); //column not enabled
			$column_id=($is_hidden==1 ? substr($columns_key,1) : $columns_key);
			$hide_it=($is_hidden==1 ? self::TO_HIDE_CSS : ''); //column not enabled
			$extra_col_options=$columns_list_arr[$columns_key];
			$td_class=$columns_key.'_td';

			$column_val=(isset($dummy_vals[$column_id]) ? $dummy_vals[$column_id] : ucfirst($columns_key));
			if(strpos($columns_key, 'default_column_')===0) /* if the current column added by customer, and its a default column */
        	{
        		$column_val=ucfirst(str_replace('default_column_', '', $columns_key));

        	}elseif(strpos($columns_key, 'custom_product_meta_') === 0)
			{
				$column_val=ucfirst(str_replace('custom_product_meta_', '', $columns_key));

			}elseif(strpos($columns_key, 'custom_order_item_meta_') === 0)
			{
				$column_val=ucfirst(str_replace('custom_order_item_meta_', '', $columns_key));
			}

			$html.='<td class="'.$hide_it.' '.$td_class.' '.$extra_col_options.'">';
			$html.=$column_val;
			$html.='</td>';
		}
		$html.='</tr>';
		return $html;
	}

	/*
	* Add dummy data for customizer design view
	* @return array
	*/
	public static function dummy_data_for_customize($find_replace, $template_type, $html)
	{

    	self::set_extra_fields_for_customize($find_replace, $template_type, $html);


		$find_replace['[wfte_invoice_number]']=123456;
		$find_replace['[wfte_order_number]']=123456;
		$find_replace['[wfte_customer_note]']='Mauris dignissim neque ut sapien vulputate, eu semper tellus porttitor. Cras porta lectus id augue interdum egestas.';

		$order_date_format=self::get_template_html_attr_vl($html,'data-order_date-format','m/d/Y');
		$find_replace['[wfte_order_date]']=date($order_date_format);

		$invoice_date_format=self::get_template_html_attr_vl($html,'data-invoice_date-format','m/d/Y');
		$find_replace['[wfte_invoice_date]']=date($invoice_date_format);

		$dispatch_date_format=self::get_template_html_attr_vl($html,'data-dispatch_date-format','m/d/Y');
		$find_replace['[wfte_dispatch_date]']=date($dispatch_date_format);

		$creditnote_date_format=self::get_template_html_attr_vl($html,'data-creditnote_date-format','m/d/Y');
		$find_replace['[wfte_creditnote_date]']=date($creditnote_date_format);

		//Dummy billing addresss
		$find_replace['[wfte_billing_address]']='Webtoffee <br>20 Maple Avenue <br>San Pedro, California, 90731 <br>United States (US) <br>';

		/* for template with sub placeholders */
		$find_replace['[wfte_billing_address_name]']='Mark';
		$find_replace['[wfte_billing_address_company]']='Webtoffee';
		$find_replace['[wfte_billing_address_address_1]']='20 Maple Avenue';
		$find_replace['[wfte_billing_address_address_2]']='';
		$find_replace['[wfte_billing_address_city]']='San Pedro';
		$find_replace['[wfte_billing_address_state]']='California';
		$find_replace['[wfte_billing_address_postcode]']='90731';
		$find_replace['[wfte_billing_address_country]']='United States (US)';


		//Dummy shipping addresss
		$find_replace['[wfte_shipping_address]']='Webtoffee <br>20 Maple Avenue <br>San Pedro, California, 90731 <br>United States (US) <br>';

		/* for template with sub placeholders */
		$find_replace['[wfte_shipping_address_name]']='Mark';
		$find_replace['[wfte_shipping_address_company]']='Webtoffee';
		$find_replace['[wfte_shipping_address_address_1]']='20 Maple Avenue';
		$find_replace['[wfte_shipping_address_address_2]']='';
		$find_replace['[wfte_shipping_address_city]']='San Pedro';
		$find_replace['[wfte_shipping_address_state]']='California';
		$find_replace['[wfte_shipping_address_postcode]']='90731';
		$find_replace['[wfte_shipping_address_country]']='United States (US)';

		//Dummy shipping addresss
		$find_replace['[wfte_return_address]']='Webtoffee <br>20 Maple Avenue <br>San Pedro, California, 90731 <br>United States (US) <br>';

		/* for template with sub placeholders */
		$find_replace['[wfte_return_address_name]']='Mark';
		$find_replace['[wfte_return_address_company]']='Webtoffee';
		$find_replace['[wfte_return_address_address_1]']='20 Maple Avenue';
		$find_replace['[wfte_return_address_address_2]']='';
		$find_replace['[wfte_return_address_city]']='San Pedro';
		$find_replace['[wfte_return_address_state]']='California';
		$find_replace['[wfte_return_address_postcode]']='90731';
		$find_replace['[wfte_return_address_country]']='United States (US)';

		$find_replace['[wfte_vat_number]']='123456';
		$find_replace['[wfte_eu_vat_number]']='123456';
		$find_replace['[wfte_vat]']='123456';
    	$find_replace['[wfte_ssn_number]']='SSN123456';
    	$find_replace['[wfte_email]']='info@example.com';
    	$find_replace['[wfte_tel]']='+1 123 456';
    	$find_replace['[wfte_shipping_method]']='DHL';
    	$find_replace['[wfte_tracking_number]']='123456';
    	$find_replace['[wfte_order_item_meta]']='';
    	$find_replace['[wfte_extra_fields]']='';
    	$find_replace['[wfte_product_table_tax_item_column_label]']='<span style="color:#aaa; font-style:italic;">'.__('Tax items', 'wf-woocommerce-packing-list').'</span>';
		$find_replace['[wfte_product_table_subtotal]']='$100.00';
		$find_replace['[wfte_product_table_shipping]']='$0.00';
		$find_replace['[wfte_product_table_cart_discount]']='$0.00';
		$find_replace['[wfte_product_table_order_discount]']='$0.00';
		$find_replace['[wfte_product_table_total_tax]']='$0.00';
		$find_replace['[wfte_product_table_fee]']='$0.00';
		$find_replace['[wfte_product_table_payment_method]']='PayPal';
		$find_replace['[wfte_product_table_payment_total]']='$100.00';
		$find_replace['[wfte_product_table_coupon]']='{ABCD100}';
		$find_replace['[wfte_product_table_tax_item]']='$1.00';
		$find_replace['[wfte_product_table_tax_item_label]']=__('Tax items', 'wf-woocommerce-packing-list');

		$find_replace['[wfte_barcode_url]']='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAAAeAQMAAACrPfpdAAAABlBMVEX///8AAABVwtN+AAAAAXRSTlMAQObYZgAAABdJREFUGJVj+MzDfPg8P/NnG4ZRFgEWAHrncvdCJcw9AAAAAElFTkSuQmCC';

		$find_replace['[wfte_return_policy]']='Mauris dignissim neque ut sapien vulputate, eu semper tellus porttitor. Cras porta lectus id augue interdum egestas. Suspendisse potenti. Phasellus mollis porttitor enim sit amet fringilla. Nulla sed ligula venenatis, rutrum lectus vel';
		$find_replace['[wfte_footer]']='Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc nec vehicula purus. Mauris tempor nec ipsum ac tempus. Aenean vehicula porttitor tortor, et interdum tellus fermentum at. Fusce pellentesque justo rhoncus';
		$find_replace['[wfte_special_notes]']='Special notes: consectetur adipiscing elit. Nunc nec vehicula purus. ';
		$find_replace['[wfte_transport_terms]']='Transport Terms: Nunc nec vehicula purus. Mauris tempor nec ipsum ac tempus.';
		$find_replace['[wfte_sale_terms]']='Sale terms: et interdum tellus fermentum at. Fusce pellentesque justo rhoncus';
		//on package type documents
		$find_replace['[wfte_box_name]']='';
		$find_replace['[wfte_qr_code]']='';
		$find_replace['[wfte_total_in_words]']=self::convert_number_to_words(100);
		$find_replace['[wfte_printed_on]']=self::get_printed_on_date($html);

		$find_replace['[wfte_payment_link]'] = '#';


		// change start
		$find_replace['[wfte_preparation_instruction]']=self::preparation_instruction($find_replace,$template_type,$html,$order);
		$find_replace['[wfte_packing_instruction]']=self::packing_instruction($find_replace,$template_type,$html,$order);
		// change end

		$find_replace=apply_filters('wf_pklist_alter_dummy_data_for_customize',$find_replace,$template_type,$html);

		$tax_items_match=array();
		if(preg_match('/<[^>]*data-row-type\s*=\s*"[^"]*\bwfte_tax_items\b[^"]*"[^>]*>(.*?)<\/tr>/s',$html,$tax_items_match))
		{
			$find_replace[$tax_items_match[0]]='';
		}
		return $find_replace;
	}

	private static function set_extra_fields_for_customize(&$find_replace, $template_type, $html)
	{
        $extra_fields=Wf_Woocommerce_Packing_List_Customizer::get_all_user_created_fields();

        foreach($extra_fields as $extra_field_key => $value)
    	{
    		$placeholder=Wf_Woocommerce_Packing_List_Customizer::prepare_custom_meta_placeholder($extra_field_key);
    		$find_replace[$placeholder]=$extra_field_key;
    	}
	}
}
