<?php

/**
 * Address related function for customizer module
 *
 * @link       
 * @since 4.0.0     
 *
 * @package  Wf_Woocommerce_Packing_List  
 */

if (!defined('ABSPATH')) {
    exit;
}
trait Wf_Woocommerce_Packing_List_Customizer_Address
{

	/**
	 * Get shipping address
	 *
	 * @param String $template_type Document type eg:invoice
	 * @param Object $order Order object 
	 * @return String billing address
	 */
	public static function get_shipping_address($template_type, $order=null)
	{
		if(!is_null($order))
        {
			$the_options=Wf_Woocommerce_Packing_List::get_settings();
			$order = ( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
	        $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
	        $shipping_address = array();
	        $countries = new WC_Countries;
	        $shipping_country = get_post_meta($order_id, '_shipping_country', true);
	        $shipping_state = get_post_meta($order_id, '_shipping_state', true);
	        $shipping_state_full = ( $shipping_country && $shipping_state && isset($countries->states[$shipping_country][$shipping_state]) ) ? $countries->states[$shipping_country][$shipping_state] : $shipping_state;
	        $shipping_country_full = ( $shipping_country && isset($countries->countries[$shipping_country]) ) ? $countries->countries[$shipping_country] : $shipping_country;
	        
	        $shipping_address=array(
	        	'name'=>'',
	        	'first_name'=>$order->shipping_first_name,
	        	'last_name'=>$order->shipping_last_name,
	        	'company'=>$order->shipping_company,
	        	'address_1'=>$order->shipping_address_1,
	        	'address_2'=>$order->shipping_address_2,
	        	'city'=>$order->shipping_city,
	        	'state'=>($the_options['woocommerce_wf_state_code_disable']=='yes' ? $shipping_state_full : $shipping_state),
	        	'country'=>$shipping_country_full,
	        	'postcode'=>$order->shipping_postcode,
	        );
	        $shipping_address=apply_filters('wf_pklist_alter_shipping_address',$shipping_address,$template_type,$order);

	        $shipping_address['name']=(isset($shipping_address['first_name']) ? $shipping_address['first_name'] : '').' '.(isset($shipping_address['last_name']) ? $shipping_address['last_name'] : ''); 
	        unset($shipping_address['last_name'], $shipping_address['first_name']);
	        if(trim($shipping_address['name'])==""){ unset($shipping_address['name']); }

	        return $shipping_address;
	    }else
	    {
	    	return array();
	    }
	}
	
	/**
	*
	* @since 4.0.0  Merge City State Zip code to one line
	* @since 4.0.2  Preserves the array key order while merging
	* @param array address
	* @return array merged address
	*/
	public static function merge_city_state_zip($address)
	{
		//return $address; //disabled
		$arr=array();
		$to_merge=array('city','state','postcode');
		foreach($address as $k=>$v)
		{
			if(in_array($k,$to_merge))
			{
				$arr[]=$v;
			}
		}
		unset($address['state']);
		unset($address['postcode']);
		$address['city']=implode(", ", array_filter(array_values($arr)));
		return $address;
	}

	public static function set_shipping_address($find_replace,$template_type, $html, $order=null)
	{
		if(!is_null($order))
        {
			$shipping_address=self::get_shipping_address($template_type, $order);

			if(count(array_filter(array_values($shipping_address)))==0) //no data
			{
				$shipping_address=self::get_billing_address($template_type, $order);
			}

	    	if(strpos($html, '[wfte_shipping_address]') !== false)
	        {
		        $shipping_address=self::merge_city_state_zip($shipping_address);
	        	$shipping_addr_vals=array_filter(array_values($shipping_address));
	        	$find_replace['[wfte_shipping_address]']=implode("<br />",$shipping_addr_vals);
		    }else
		    {
		    	$find_replace=self::set_address_inner_placeholders($find_replace, $shipping_address, 'wfte_shipping_address_');
		    }

	    }else
	    {
	    	$find_replace['[wfte_shipping_address]']='';
	    }
	    return $find_replace;
	}
	
	/**
	 * Get billing address
	 *
	 * @param String $template_type Document type eg:invoice
	 * @param Object $order Order object 
	 * @return String billing address
	 */
	public static function get_billing_address($template_type, $order=null)
	{
		if(!is_null($order))
        {
			$the_options=Wf_Woocommerce_Packing_List::get_settings();
			$order = ( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
			$order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();     
	        $countries = new WC_Countries;  
	        $billing_country = get_post_meta($order_id, '_billing_country', true);   
	        $billing_state = get_post_meta($order_id, '_billing_state', true);
	        $billing_state_full = ( $billing_country && $billing_state && isset($countries->states[$billing_country][$billing_state]) ) ? $countries->states[$billing_country][$billing_state] : $billing_state;
	        $billing_country_full = ( $billing_country && isset($countries->countries[$billing_country]) ) ? $countries->countries[$billing_country] : $billing_country;
	        
	        $billing_address=array(
	        	'name'=>'',
	        	'first_name'=>$order->billing_first_name,
	        	'last_name'=>$order->billing_last_name,
	        	'company'=>$order->billing_company,
	        	'address_1'=>$order->billing_address_1,
	        	'address_2'=>$order->billing_address_2,
	        	'city'=>$order->billing_city,
	        	'state'=>($the_options['woocommerce_wf_state_code_disable']=='yes' ? $billing_state_full : $billing_state),
	        	'country'=>$billing_country_full,
	        	'postcode'=>$order->billing_postcode,
	        );
	        $billing_address=apply_filters('wf_pklist_alter_billing_address',$billing_address,$template_type,$order);

	        $billing_address['name']=(isset($billing_address['first_name']) ? $billing_address['first_name'] : '').' '.(isset($billing_address['last_name']) ? $billing_address['last_name'] : ''); 
	        unset($billing_address['last_name'], $billing_address['first_name']);
	        if(trim($billing_address['name'])==""){ unset($billing_address['name']); }

	        return $billing_address;
	    }else
	    {
	    	return array();
	    }
	}

	public static function set_billing_address($find_replace, $template_type, $html,$order=null)
	{
		if(!is_null($order))
        {
			$billing_address=self::get_billing_address($template_type, $order);

        	if(strpos($html, '[wfte_billing_address]') !== false)
	        {
		        $billing_address=self::merge_city_state_zip($billing_address);
	        	$billing_addr_vals=array_filter(array_values($billing_address));
	        	$find_replace['[wfte_billing_address]']=implode("<br />",$billing_addr_vals);
		    }else
		    {
		    	$find_replace=self::set_address_inner_placeholders($find_replace, $billing_address, 'wfte_billing_address_');
		    }

	    }else
	    {
	    	$find_replace['[wfte_billing_address]']='';
	    }
	    return $find_replace;
	}
	
	public static function set_shipping_from_address($find_replace, $template_type, $html, $order=null)
	{
		$the_options=Wf_Woocommerce_Packing_List::get_settings();
		
        $country_selected=$the_options['wf_country'];
        $country_arr=explode(":",$country_selected);
        $country=isset($country_arr[0]) ? $country_arr[0] : '';
        $state=isset($country_arr[1]) ? $country_arr[1] : '';
        $countries=new WC_Countries; 
        $fromaddress=array(
        	'name'=>$the_options['woocommerce_wf_packinglist_sender_name'],
        	'address_line1'=>$the_options['woocommerce_wf_packinglist_sender_address_line1'],
        	'address_line2'=>$the_options['woocommerce_wf_packinglist_sender_address_line2'],
        	'city'=>$the_options['woocommerce_wf_packinglist_sender_city'],
        	'state'=>$state,
        	'country'=>(isset($countries->countries[$country]) ? $countries->countries[$country] : ''),
        	'postcode'=>$the_options['woocommerce_wf_packinglist_sender_postalcode'],
        	'contact_number'=>$the_options['woocommerce_wf_packinglist_sender_contact_number'],
        	'vat'=>$the_options['woocommerce_wf_packinglist_sender_vat'],
        );
              
        //display state name instead of state code   
        if($the_options['woocommerce_wf_state_code_disable']=='yes')
        {
        	$fromaddress['state']=isset($countries->states[$country]) && isset($countries->states[$country][$state]) ? $countries->states[$country][$state] : '';
        }
        $returnaddress=$fromaddress; //not affect from address filter to return address
        if(!is_null($order))
        {
        	$order=( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
        	$fromaddress=apply_filters('wf_pklist_alter_shipping_from_address',$fromaddress,$template_type,$order);
        	$returnaddress=apply_filters('wf_pklist_alter_shipping_return_address',$returnaddress,$template_type,$order);
        }
        if(strpos($html, '[wfte_from_address]') !== false)
        {
	        $fromaddress=self::merge_city_state_zip($fromaddress);     

	        $from_addr_vals=is_array($fromaddress) ? array_filter(array_values($fromaddress)) : array();
	        $find_replace['[wfte_from_address]']=implode("<br />",$from_addr_vals);
	    }else
	    {
	    	$find_replace=self::set_address_inner_placeholders($find_replace, $fromaddress, 'wfte_from_address_');
	    }
	    if(strpos($html, '[wfte_return_address]') !== false)
        {
	        $returnaddress=self::merge_city_state_zip($returnaddress);      

	        $return_addr_vals=is_array($returnaddress) ? array_filter(array_values($returnaddress)) : array();
	        $find_replace['[wfte_return_address]']=implode("<br />",$return_addr_vals);
	    }else
	    {
	    	$find_replace=self::set_address_inner_placeholders($find_replace, $returnaddress, 'wfte_return_address_');
	    }
		return $find_replace;
	}

	/**
	*	@since 4.1.6
	*	Set values to inner placeholder of address blocks.
	*/
	private static function set_address_inner_placeholders($find_replace, $data_arr, $address_prefix)
	{

		/**
		*	Some fields in adress block is showing in one line. We have to hide the entire row if values of all fields are empty
		*/
		$single_line_fields_arr=array(
			'city_state_postcode_country'=>array('city', 'state', 'postcode', 'country'),
			'address_1_address_2'=>array('address_1', 'address_2'),
			'address_line1_address_line2'=>array('address_line1', 'address_line2'),
		);
		foreach($single_line_fields_arr as $address_key => $single_line_fields)
		{
			$find_replace["[".$address_prefix.$address_key."]"]=''; //empty value to hide
			foreach($single_line_fields as $field)
			{
				if(isset($data_arr[$field]) && trim($data_arr[$field])!="") //atleast one field with value found then prevent hiding by setting a value
				{
					$find_replace["[".$address_prefix.$address_key."]"]=1; //just a value to prevent hiding
					break 1; //break the loop
				}
			}
		}


		foreach ($data_arr as $key => $value)
		{
			if(strpos($key, ' ') === false) /* check no space in key */
			{
				$find_replace["[".$address_prefix.$key."]"]=$value;
			}
		}
		return $find_replace;
	}
}