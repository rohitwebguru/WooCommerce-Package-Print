<?php

/**
 * Product table related function for customizer module
 *
 * @link
 * @since 4.0.0
 *
 * @package  Wf_Woocommerce_Packing_List
 */

if (!defined('ABSPATH')) {
    exit;
}
trait Wf_Woocommerce_Packing_List_Customizer_Product_table
{

	/**
	*	@since 4.0.0 Generating product table
	*	@since 4.0.2 Tax column introduced
	*
	*/
	public static function set_product_table($find_replace,$template_type,$html,$order=null,$box_packing=null,$order_package=null)
	{
		$match=array();
		$default_columns=array('image','sku','product','quantity','price','total_price');
		$columns_list_arr=array();

		//extra column properties like text-align etc are inherited from table head column. We will extract that data to below array
	    $column_list_options=array();

	    $module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);
	    /* checking product table markup exists  */
	    if(preg_match('#<table[^>]*class\s*=\s*["\']([^"\']*)wfte_product_table(.*?[^"\']*)["\'][^>]*>(.*?)</table>#s',$html,$match))
		{
			$product_tb_html=$match[0];
			$thead_match=array();

			$th_html='';
			if(preg_match('/<thead(.*?)>(.*?)<\/thead>/s', $product_tb_html, $thead_match))
			{
				if(isset($thead_match[2]) && $thead_match[2]!="")
				{
					$thead_tr_match=array();
					if(preg_match('/<tr(.*?)>(.*?)<\/tr>/s',$thead_match[2],$thead_tr_match))
					{
						if(isset($thead_tr_match[2]))
						{
							$th_html=$thead_tr_match[2];
						}
					}
				}
			}

			if($th_html!="")
			{
				$th_html_arr=explode('</th>',$th_html);

				$th_html_arr=array_filter($th_html_arr);
				$col_ind=0;
				foreach($th_html_arr as $th_single_html)
				{
					$th_single_html=trim($th_single_html);
					if($th_single_html!="")
					{
						$matchs=array();
						$is_have_col_id=preg_match('/col-type="(.*?)"/',$th_single_html,$matchs);
						$col_ind++;
						$col_key=($is_have_col_id ? $matchs[1] : $col_ind); //column id exists

						//extracting extra column options, like column text align class etc
						$extra_table_col_opt=self::extract_table_col_options($th_single_html);

						if($col_key=='tax' || $col_key=='-tax') //column key is tax then check, tax column options are enabled
						{
			            	//adding column data to arrays
							$columns_list_arr[$col_key]=$th_single_html.'</th>';
							$column_list_options[$col_key]=$extra_table_col_opt;
						}
						elseif($col_key=='tax_items' || $col_key=='-tax_items')
						{
							if(!is_null($order)) //do not show this column in customizer
        					{
								//show individual tax column
				            	$show_individual_tax_column=Wf_Woocommerce_Packing_List::get_option('wt_pklist_show_individual_tax_column',$module_id);
								if($show_individual_tax_column===false) //option not present, then add a filter to control the value
								{
									$show_individual_tax_column=apply_filters('wf_pklist_alter_show_individual_tax_column', $show_individual_tax_column, $template_type, $order);
								}

								if($show_individual_tax_column===true || $show_individual_tax_column==='Yes')
								{

									//individual tax column display options
					            	$individual_tax_column_display_option=Wf_Woocommerce_Packing_List::get_option('wt_pklist_individual_tax_column_display_option', $module_id);
									if($individual_tax_column_display_option===false) //option not present, then add a filter to control the value
									{
										$individual_tax_column_display_option=apply_filters('wf_pklist_alter_individual_tax_column_display_option', $individual_tax_column_display_option, $template_type, $order);
									}

									$show_individual_tax_rate_column_after_amount_column=true; //only applicable on separte column
									if($individual_tax_column_display_option=='separate-column')
									{
										/**
										*	Show rate column after amount column. Default:true
										*
										*/
										$show_individual_tax_rate_column_after_amount_column=apply_filters('wf_pklist_show_individual_tax_rate_column_after_amount_column', $show_individual_tax_rate_column_after_amount_column, $template_type, $order);
									}

									/**
									*	This variable is for filter.
									*/
									$individual_tax_column_config=array(
										'display_option'=>$individual_tax_column_display_option,
										'rate_column_after_amount_column'=>$show_individual_tax_rate_column_after_amount_column,
									);


									$tax_items = $order->get_items('tax');
									$tax_id_prefix=($col_key[0]=='-' ? $col_key[0] : '').'individual_tax_';

									$tax_id_prefix_rate_only='rate_'.$tax_id_prefix;

									$tax_id_prefix=($individual_tax_column_display_option=='separate-column' ? 'amount' : $individual_tax_column_display_option).'_'.$tax_id_prefix;

									foreach($tax_items as $tax_item)
									{
										$tax_id=$tax_item->get_rate_id();
										$tax_id_rate_only=$tax_id_prefix_rate_only.$tax_id;
										$tax_id=$tax_id_prefix.$tax_id;

										$tax_label=$tax_item->get_label();
										$tax_rate_only_column_label=$tax_label.'(%)';

										if($individual_tax_column_display_option=='rate')
										{
											$tax_label=$tax_rate_only_column_label;
										}

										/**
										*	Rate column before amount column
										*/
										if($individual_tax_column_display_option=='separate-column' && !$show_individual_tax_rate_column_after_amount_column)
										{
											self::prepare_tax_item_column_html($tax_id_rate_only, $tax_rate_only_column_label, $th_single_html, $columns_list_arr, $column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order);
										}

										self::prepare_tax_item_column_html($tax_id, $tax_label, $th_single_html, $columns_list_arr, $column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order);

										/**
										*	Rate column after amount column
										*/
										if($individual_tax_column_display_option=='separate-column' && $show_individual_tax_rate_column_after_amount_column)
										{
											self::prepare_tax_item_column_html($tax_id_rate_only, $tax_rate_only_column_label, $th_single_html, $columns_list_arr, $column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order);
										}
									}
								}
							}else
							{
								$columns_list_arr['tax_items']=$th_single_html.'</th>';
								$column_list_options['tax_items']=$extra_table_col_opt;
							}
						}
						else
						{
							//adding column data to arrays
							$columns_list_arr[$col_key]=$th_single_html.'</th>';
							$column_list_options[$col_key]=$extra_table_col_opt;
						}
					}
				}

				if(!is_null($order))
	    		{
	    			//filter to alter table head
					$columns_list_arr=apply_filters('wf_pklist_alter_product_table_head',$columns_list_arr,$template_type,$order);
				}
				$columns_list_arr=(!is_array($columns_list_arr) ? array() : $columns_list_arr);

				//for table head
				$columns_list_arr=apply_filters('wf_pklist_reverse_product_table_columns',$columns_list_arr,$template_type);

				/* update the column options according to $columns_list_arr */
				$column_list_option_modified=array();
				foreach($columns_list_arr as $column_key=>$column_data)
				{
					if(isset($column_list_options[$column_key]))
					{
						$column_list_option_modified[$column_key]=$column_list_options[$column_key];
					}else
					{
						//extracting extra column options, like column text align class etc
						$extra_table_col_opt=self::extract_table_col_options($column_data);
						$column_list_option_modified[$column_key]=$extra_table_col_opt;
					}
				}
				$column_list_options=$column_list_option_modified;

				//replace for table head section
				$find_replace[$th_html]=self::generate_product_table_head_html($columns_list_arr,$template_type);

			}

			//product table body section
			$tbody_tag_match=array();
			$tbody_tag='';
			if(preg_match('/<tbody(.*?)>/s',$product_tb_html,$tbody_tag_match))
			{
				self::$reference_arr['tbody_placholder']=$tbody_tag_match[0];
				if(!is_null($box_packing))
				{
					$find_replace[$tbody_tag_match[0]]=$tbody_tag_match[0].self::generate_package_product_table_product_row_html($column_list_options,$template_type,$order,$box_packing,$order_package);
				}else
				{
					$find_replace[$tbody_tag_match[0]]=$tbody_tag_match[0].self::generate_product_table_product_row_html($column_list_options,$template_type,$order);
				}
			}
		}

		$find_replace['[wfte_product_table_start]']='';
		$find_replace['[wfte_product_table_end]']='';
		return $find_replace;
	}

	private static function prepare_tax_item_column_html($tax_id, $tax_label, $th_single_html, &$columns_list_arr, &$column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order)
	{
		$tax_label=apply_filters('wf_pklist_alter_individual_tax_column_head', $tax_label, $template_type, $tax_item, $individual_tax_column_config, $order);

		$col_html=str_replace('[wfte_product_table_tax_item_column_label]', $tax_label, $th_single_html);

		//adding column data to arrays
		$columns_list_arr[$tax_id]=$col_html.'</th>';
		$column_list_options[$tax_id]=$extra_table_col_opt;
	}


	/**
	* 	Extract table column style classes.
	*	@since 4.0.2
	*/
	public static function extract_table_col_options($th_single_html)
	{
		$matchs=array();
		$is_have_class=preg_match('/class="(.*?)"/',$th_single_html,$matchs);
		$option_classes=array('wfte_text_left','wfte_text_right','wfte_text_center');
		$out=array();
		if($is_have_class)
		{
			$class_arr=explode(" ",$matchs[1]);
			foreach($class_arr as $class)
			{
				if(in_array($class,$option_classes))
				{
					$out[]=$class;
				}
			}
		}
		return implode(" ",$out);
	}

	/**
	*  	Set other charges fields in product table
	*	@since 	4.0.0
	*	@since 	4.0.2 refund amount calculation issue fixed. Total in words integrated. Added filter to alter total
	*	@since 	4.1.6 Added new filter to alter tax item amount `wf_pklist_alter_taxitem_amount`
	*/
	public static function set_extra_charge_fields($find_replace,$template_type,$html,$order=null)
	{
		//module settings are saved under module id
		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);

		if(!is_null($order))
        {
        	$the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);
			$order_items=$order->get_items();
			$wc_version=WC()->version;
			$order_id=$wc_version<'2.7.0' ? $order->id : $order->get_id();
			$user_currency=get_post_meta($order_id, '_order_currency', true);

			$tax_type=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_taxstatus');
			$incl_tax=in_array('in_tax', $tax_type);



			//subtotal ==========================
			if(!isset($find_replace['[wfte_product_table_subtotal]'])) /* check already added */
			{
				$incl_tax_text='';
				$sub_total=(float)$order->get_subtotal();
				if($incl_tax)
				{
					$incl_tax_text=self::get_tax_incl_text($template_type, $order, 'product_price');
					$incl_tax_text=($incl_tax_text!="" ? ' ('.$incl_tax_text.')' : $incl_tax_text);

					$total_tax=(float)$order->get_total_tax();
					$shipping_tax=(float)$order->get_shipping_tax();
					if(!empty($total_tax))
					{
						if(!empty($shipping_tax))
						{
							$total_tax -= $shipping_tax;
						}
						$sub_total += $total_tax;
					}
				}

				$sub_total=apply_filters('wf_pklist_alter_subtotal', $sub_total, $template_type, $order, $incl_tax);
			    $sub_total_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$sub_total).$incl_tax_text;
			    $find_replace['[wfte_product_table_subtotal]']=apply_filters('wf_pklist_alter_subtotal_formated', $sub_total_formated, $template_type, $sub_total, $order, $incl_tax);
			}

		    //shipping method ==========================
		    if(!isset($find_replace['[wfte_product_table_shipping]'])) /* check already added */
			{
			    if(get_option('woocommerce_calc_shipping')==='yes')
			    {
			        $shippingdetails=$order->get_items('shipping');
			        if (!empty($shippingdetails))
			        {
			            $shipping = Wf_Woocommerce_Packing_List_Admin::wf_shipping_formated_price($order);
			            $shipping=apply_filters('wf_pklist_alter_shipping_method', $shipping, $template_type, $order, 'product_table');
			            $find_replace['[wfte_product_table_shipping]']=__($shipping, 'wf-woocommerce-packing-list');
			        }else
			        {
			            $find_replace['[wfte_product_table_shipping]']='';
			        }
			    }else
			    {
			        $find_replace['[wfte_product_table_shipping]']='';
			    }
			}

		    //cart discount ==========================
		    if(!isset($find_replace['[wfte_product_table_cart_discount]'])) /* check already added */
			{
			    $cart_discount=($wc_version<'2.7.0' ? $order->cart_discount : get_post_meta($order_id,'_cart_discount',true));
			    if($cart_discount>0)
			    {
			        $find_replace['[wfte_product_table_cart_discount]']='-'.Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$cart_discount);
				}
				else
				{
			        $find_replace['[wfte_product_table_cart_discount]']='';
				}
			}

			//order discount ==========================
			if(!isset($find_replace['[wfte_product_table_order_discount]'])) /* check already added */
			{
				$order_discount=($wc_version<'2.7.0' ? $order->order_discount : get_post_meta($order_id,'_order_discount',true));
				if ($order_discount>0)
				{
			        $find_replace['[wfte_product_table_order_discount]']='-'.Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$order_discount);
				}
				else
				{
			        $find_replace['[wfte_product_table_order_discount]']='';
				}
			}

			$tax_items = $order->get_tax_totals();

			//tax items ==========================
			if(!isset($find_replace['[wfte_product_table_total_tax]'])) /* check already added */
			{
				if(in_array('ex_tax',$tax_type))
				{
					//total tax ==========================
					if(is_array($tax_items) && count($tax_items)>0)
					{
						$tax_total = Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$order->get_total_tax());
						$tax_total = apply_filters('wf_pklist_alter_total_tax_row',$tax_total,$template_type,$order,$tax_items);
						$find_replace['[wfte_product_table_total_tax]']=$tax_total;
					}else
					{
						$find_replace['[wfte_product_table_total_tax]']='';
					}
				}else
				{
					$find_replace['[wfte_product_table_total_tax]']='';
				}
			}

			$tax_items_match=array();
			$tax_items_row_html=''; //row html
			$tax_items_html='';
			$tax_items_total=0;
			if(preg_match('/<[^>]*data-row-type\s*=\s*"[^"]*\bwfte_tax_items\b[^"]*"[^>]*>(.*?)<\/tr>/s', $html, $tax_items_match))
			{
				$tax_items_row_html=isset($tax_items_match[0]) ? $tax_items_match[0] : '';
			}
			if(is_array($tax_items) && count($tax_items)>0)
			{
				foreach($tax_items as $tax_item)
				{
					if(in_array('ex_tax',$tax_type) && $tax_items_row_html!='')
					{
						$tax_rate_id = $tax_item->rate_id;
	                    $tax_label=apply_filters('wf_pklist_alter_taxitem_label', esc_html($tax_item->label), $template_type, $order, $tax_item);
	                    $tax_amount=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$tax_item->amount);
	                    $tax_amount=apply_filters('wf_pklist_alter_taxitem_amount', $tax_amount, $tax_item, $order, $template_type,$tax_rate_id);
	                    $tax_items_html.=str_replace(array('[wfte_product_table_tax_item_label]','[wfte_product_table_tax_item]'), array($tax_label, $tax_amount), $tax_items_row_html);
	                }
	                else
	                {
	                    $tax_items_total+=(float)$tax_item->amount;
	                }
				}
			}
			if($tax_items_row_html!='' && isset($tax_items_match[0])) //tax items placeholder exists
			{
				$find_replace[$tax_items_match[0]]=$tax_items_html; //replace tax items
			}

			//fee details ==========================
			if(!isset($find_replace['[wfte_product_table_fee]'])) /* check already added */
			{
				$fee_details=$order->get_items('fee');
		        $fee_details_html='';
		        $fee_total_amount = 0;
		        if(!empty($fee_details))
		        {
			        foreach($fee_details as $fee_detail)
			        {
			            $fee_detail_html=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$fee_detail['amount']).' via '.$fee_detail['name'];
			            $fee_detail_html=apply_filters('wf_pklist_alter_fee',$fee_detail_html,$template_type,$fee_detail,$user_currency,$order);
			            $fee_details_html.=($fee_detail_html!="" ? $fee_detail_html.'<br/>' : '');
			            $fee_total_amount+=(float)$fee_detail['amount'];
			        }
			        $fee_total_amount_formated= Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$fee_total_amount);
		        	$fee_total_amount_formated=apply_filters('wf_pklist_alter_total_fee',$fee_total_amount_formated,$template_type,$fee_total_amount,$user_currency,$order);
		        	if($template_type != 'creditnote'){
		        		$find_replace['[wfte_product_table_fee]']=$fee_details_html.($fee_total_amount_formated!="" ? '<br />'.$fee_total_amount_formated : '');
		        	}else{
		        		$find_replace['[wfte_product_table_fee]']=$fee_total_amount_formated;
		        	}
		    	}else
		        {
		        	$find_replace['[wfte_product_table_fee]']='';
		        }
		    }

	        //coupon details ==========================
	        if(!isset($find_replace['[wfte_product_table_coupon]'])) /* check already added */
			{
		        $coupon_details=$order->get_items('coupon');
		        $coupon_info_arr=array();
		        $coupon_info_html='';
		        if(!empty($coupon_details))
		        {
					foreach($coupon_details as $coupon_id=>$coupon_detail)
					{
						$discount=($wc_version<'3.2.0' ? $coupon_detail['discount_amount'] : $coupon_detail->get_discount());
						$discount_tax=($wc_version<'3.0.0' ? $coupon_detail['discount_amount_tax'] : $coupon_detail->get_discount_tax());
						$coupon_name=($wc_version<'3.0.0' ? $coupon_detail['name'] : $coupon_detail->get_name());
						$discount_total=(float)$discount+(float)$discount_tax;
						$coupon_info_arr[$coupon_name]=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$discount_total);
					}
					$coupon_code_arr=array_keys($coupon_info_arr);
					$coupon_info_html=implode(", ",$coupon_code_arr);
					$find_replace['[wfte_product_table_coupon]']=$coupon_info_html;
				}else
				{
					$find_replace['[wfte_product_table_coupon]']='';
				}
			}

			//payment info ==========================
			if(!isset($find_replace['[wfte_product_table_payment_method]'])) /* check already added */
			{
				$payment_method_slug = ($wc_version< '2.7.0' ? get_post_meta( $order_id, '_payment_method', true ) : $order->get_payment_method());
				$paymethod_title=($wc_version< '2.7.0' ? $order->payment_method_title : $order->get_payment_method_title());
        		$paymethod_title=__($paymethod_title, 'wf-woocommerce-packing-list');
        		$find_replace['[wfte_product_table_payment_method]']=$paymethod_title;
        	}


        	//total amount ==========================
	        if(!isset($find_replace['[wfte_product_table_payment_total]']) || !isset($find_replace['[wfte_total_in_words]'])) /* check already added */
			{
	        	$total_price_final=($wc_version<'2.7.0' ? $order->order_total : get_post_meta($order_id,'_order_total',true));
				$total_price=(float)$total_price_final; //taking value for future use
				$refund_amount=0;
				if($total_price_final)
				{
					$refund_data_arr=$order->get_refunds();
					if(!empty($refund_data_arr))
					{
						foreach($refund_data_arr as $refund_data)
						{
							$refund_id=($wc_version< '2.7.0' ? $refund_data->id : $refund_data->get_id());
							$cr_refund_amount=(float) get_post_meta($refund_id,'_order_total',true);
							$total_price_final+=$cr_refund_amount;
							$refund_amount-=$cr_refund_amount;
						}
					}
				}

	      		$incl_tax_text=self::get_tax_incl_text($template_type, $order, 'total_price');

	        	//inclusive tax data
	        	$tax_data=((in_array('in_tax', $tax_type) && !empty($tax_items_total)) ? ' ('.$incl_tax_text." ".Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$tax_items_total).')' : '');

	        	/**
	        	*	@since 4.0.9 New filter to customize tax info
	        	*/
	        	if($tax_data!="")
	        	{
	        		$tax_data=apply_filters('wf_pklist_alter_tax_info_text', $tax_data, $tax_type, $tax_items_total, $user_currency, $template_type, $order);
	        	}

	        	if(!empty($refund_amount) && $refund_amount!=0) /* having refund */
				{
					$total_price_final=apply_filters('wf_pklist_alter_total_price', $total_price_final, $template_type, $order);

					$total_price_final_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$total_price_final);

					/* price before refund */
					$total_price_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$total_price);

					$refund_formated='<br /> ('.__('Refund','wf-woocommerce-packing-list').' -'.Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$refund_amount).')';
					$refund_formated=apply_filters('wf_pklist_alter_refund_html', $refund_formated, $template_type, $refund_amount, $order);

					$total_price_html='<strike>'.$total_price_formated.'</strike> '.$total_price_final_formated.$tax_data.$refund_formated;
					if($template_type == "creditnote"){
						$total_price_html=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$refund_amount);
						$total_price_html = apply_filters('wf_pklist_alter_price_creditnote',$total_price_html,$template_type,$order);
					}
				}else
				{
					$total_price_final=apply_filters('wf_pklist_alter_total_price',$total_price,$template_type,$order);

					$total_price_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$total_price_final);

					$total_price_html=$total_price_formated.$tax_data;
				}

				/* total price in words */
				$find_replace = self::set_total_in_words($total_price_final, $find_replace, $template_type, $html, $order);
				$find_replace['[wfte_product_table_payment_total]']=$total_price_html;
			}

			//custom order meta row ========
			$custom_order_meta_datas=array();
			if(self::get_summary_table_custom_order_meta_placeholders($html, $custom_order_meta_datas))
			{
				foreach($custom_order_meta_datas as $custom_order_meta_item)
				{
					$order_meta_value = get_post_meta($order_id, $custom_order_meta_item[1], true);
					$find_replace[$custom_order_meta_item[0]] = self::process_meta_value($order_meta_value);
				}
			}

		}else
		{
			/**
			 *  for customizer
			 */

			//custom order meta row ========
			$custom_order_meta_datas=array();
			if(self::get_summary_table_custom_order_meta_placeholders($html, $custom_order_meta_datas))
			{
				foreach($custom_order_meta_datas as $custom_order_meta_item)
				{
					$find_replace[$custom_order_meta_item[0]] = $custom_order_meta_item[1];
				}
			}
		}
		return $find_replace;
	}


	private static function get_summary_table_custom_order_meta_placeholders($html, &$custom_order_meta_datas)
	{
		return preg_match_all('/\[wfte_payment_summary_table_custom_order_meta_([a-zA-Z0-9-_\s]*)\]/m', $html, $custom_order_meta_datas, PREG_SET_ORDER, 0);
	}


	/**
	*	@since 4.0.0 Render product table column data for package type documents
	* 	@since 4.1.0 Added Product attribute option in product name column
	*/
	public static function generate_package_product_table_product_column_html($wc_version,$the_options,$order,$template_type,$_product,$item,$columns_list_arr)
	{
		$html='';
		$product_row_columns=array(); //for html generation
        $product_id=($wc_version< '2.7.0' ? $_product->id : $_product->get_id());

        $variation_id=(int) ($item['variation_id']!='' ? $item['variation_id'] : 0);

        $parent_id=wp_get_post_parent_id($variation_id);
        $order_item_id=$item['order_item_id'];
        $dimension_unit=get_option('woocommerce_dimension_unit');
        $weight_unit = get_option('woocommerce_weight_unit');

        $order_id=$wc_version<'2.7.0' ? $order->id : $order->get_id();
		$user_currency=get_post_meta($order_id,'_order_currency',true);

        foreach($columns_list_arr as $columns_key=>$columns_value)
        {
        	$columns_key_real=$columns_key; /* backup */
			if(strpos($columns_key, 'default_column_')===0) /* if the current column added by customer, and its a default column */
			{
				$columns_key=str_replace('default_column_', '', $columns_key);
			}

            if($columns_key==='image' || $columns_key==='-image')
            {
            	$column_data=self::generate_product_image_column_data($product_id,$variation_id,$parent_id);
            }
            elseif($columns_key==='sku' || $columns_key==='-sku')
            {
            	$column_data=$_product->get_sku();
            }
            elseif($columns_key==='product' || $columns_key==='-product')
            {
            	$product_name=apply_filters('wf_pklist_alter_package_product_name',$item['name'],$template_type,$_product,$item,$order);

            	//variation data======
            	$variation='';
            	if(isset($the_options['woocommerce_wf_packinglist_variation_data']) && $the_options['woocommerce_wf_packinglist_variation_data']=='Yes')
            	{
	            	$variation=$item['variation_data'];
			        $item_meta=$item['extra_meta_details'];
			        $variation_data=apply_filters('wf_pklist_add_package_product_variation',$item_meta,$template_type,$_product,$item,$order);
			        if(!empty($variation_data) && !is_array($variation_data))
			        {
			            $variation.='<br>'.$variation_data;
			        }
			        if(!empty($variation))
			        {
			        	$variation='<small style="word-break: break-word;">'.$variation.'</small>';
			        }
		    	}

		        //additional product meta
		        $addional_product_meta = '';
		        if(isset($the_options['wf_'.$template_type.'_product_meta_fields']) && is_array($the_options['wf_'.$template_type.'_product_meta_fields']) && count($the_options['wf_'.$template_type.'_product_meta_fields'])>0)
		        {
		            $selected_product_meta_arr=$the_options['wf_'.$template_type.'_product_meta_fields'];
		            $product_meta_arr=Wf_Woocommerce_Packing_List::get_option('wf_product_meta_fields');
		            foreach($selected_product_meta_arr as $value)
		            {
			            if(isset($product_meta_arr[$value])) //meta exists in the added list
			            {
			                $meta_data=get_post_meta($product_id, $value, true);
			                if($meta_data=='' && $variation_id>0)
			                {
			                	$meta_data=get_post_meta($parent_id, $value, true);
			                }
			                if(is_array($meta_data))
		                    {
		                        $output_data=(self::wf_is_multi($meta_data) ? '' : implode(', ',$meta_data));
		                    }else
		                    {
		                        $output_data=$meta_data;
		                    }
		                    if($output_data!="")
						    {
			                    $meta_info_arr=array('key'=>$value, 'title'=>__($product_meta_arr[$value], 'wf-woocommerce-packing-list'), 'value'=>__($output_data,'wf-woocommerce-packing-list'));
			                    $meta_info_arr=apply_filters('wf_pklist_alter_package_product_meta', $meta_info_arr, $template_type, $_product, $item, $order);
		                    	if(is_array($meta_info_arr) && isset($meta_info_arr['title']) && isset($meta_info_arr['value']) && $meta_info_arr['value']!="")
			                    {
			                    	$item_html='<small>'.$meta_info_arr['title'].': '.$meta_info_arr['value'].'</small><br>';

			                    	/**
							        *	@since 4.1.0 to alter the prepared meta item HTML
							        */
			                    	$item_html=apply_filters('wf_pklist_alter_package_product_meta_item_html', $item_html, $meta_info_arr, $template_type, $_product, $item, $order);
			                    	$addional_product_meta.=(is_string($item_html) ? $item_html : '');
			                    }
			                }
		                }
	                }
		        }
		        $addional_product_meta=apply_filters('wf_pklist_add_package_product_meta', $addional_product_meta, $template_type, $_product, $item, $order);


		        /**
		        *	@since 4.1.0
		        *	Product attribute
		       	*/
		        $product_attr= '';
		        if(isset($the_options['wt_'.$template_type.'_product_attribute_fields']) && is_array($the_options['wt_'.$template_type.'_product_attribute_fields']) && count($the_options['wt_'.$template_type.'_product_attribute_fields'])>0)
		        {
		            $selected_product_attr_arr=$the_options['wt_'.$template_type.'_product_attribute_fields'];
		            $product_attr_arr=Wf_Woocommerce_Packing_List::get_option('wt_product_attribute_fields');
		            foreach($selected_product_attr_arr as $attr)
		            {
		                $attr_data=$_product->get_attribute($attr);
		                $label=wc_attribute_label($attr, $_product);

	                    $attr_info_arr=array('key'=>$attr, 'title'=>$label, 'value'=>$attr_data);
	                    $attr_info_arr=apply_filters('wf_pklist_alter_package_product_attr_item', $attr_info_arr, $template_type, $_product, $item, $order);

                    	if(is_array($attr_info_arr) && isset($attr_info_arr['title']) && isset($attr_info_arr['value']) && $attr_info_arr['value']!="")
	                    {
	                    	$item_html='<small>'.$attr_info_arr['title'].': '.$attr_info_arr['value'].'</small><br>';
	                    	$item_html=apply_filters('wf_pklist_alter_package_product_attr_item_html', $item_html, $attr_info_arr, $template_type, $_product, $item, $order);
	                    	$product_attr.=(is_string($item_html) ? $item_html : '');
	                    }
	                }
		        }
		        $product_attr=apply_filters('wf_pklist_alter_package_product_attr_data', $product_attr, $template_type, $_product, $item, $order);

		        $product_column_data_arr=array(
		        	'product_name'=>'<b>'.$product_name.'</b>',
		        	'variation'=>$variation,
		        	'product_meta'=>$addional_product_meta,
		        	'product_attr'=>$product_attr,
		        );

		        /**
		        *	@since 4.1.0 to alter the data items in product column
		        */
		        $product_column_data_arr=apply_filters('wf_pklist_alter_package_product_column_data_arr', $product_column_data_arr, $template_type, $_product, $item, $order);

		        $column_data='';
		        if(is_array($product_column_data_arr))
		        {
		        	$product_column_data_arr=array_filter(array_values($product_column_data_arr));
		        	$column_data=implode('<br> ', $product_column_data_arr);
		        }else
		        {
		        	$column_data=$product_column_data_arr;
		        }

            }
            elseif($columns_key==='quantity' || $columns_key==='-quantity')
            {
            	$column_data=apply_filters('wf_pklist_alter_package_item_quantiy',$item['quantity'],$template_type,$_product,$item,$order);
            }
            elseif($columns_key==='total_weight' || $columns_key==='-total_weight')
            {
            	$item_weight=($item['weight']!= '') ? $item['weight']*$item['quantity'].' '.$weight_unit : __('n/a','wf-woocommerce-packing-list');
            	$column_data=apply_filters('wf_pklist_alter_package_item_total_weight', $item_weight, $template_type, $_product, $item, $order);
            }
            elseif($columns_key==='total_price' || $columns_key==='-total_price')
            {
            	$item_price = Wf_Woocommerce_Packing_List_Admin::wf_convert_to_user_currency($item['price'],$user_currency,$order);

            	$product_total=(int) $item['quantity'] * (float) $item_price;

				$total_price=apply_filters('wf_pklist_alter_package_item_total',$product_total,$template_type,$_product,$item,$order);
            	$product_total_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$total_price);
            	$column_data=apply_filters('wf_pklist_alter_package_item_total_formated', $product_total_formated, $template_type, $product_total, $_product, $item, $order);

            }elseif($columns_key==='category' || $columns_key==='-category')
			{
	        	$column_data=self::set_category_col($product_id, $template_type, $order);
			}
			else //custom column by user
			{
				$column_data='';
				if(!self::set_custom_meta_table_col_data($column_data, $columns_key_real, $product_id, $order_item_id))
				{
				    $column_data = apply_filters('wf_pklist_package_product_table_additional_column_val', $column_data, $template_type, $columns_key_real, $_product, $item, $order);
				}
			}
            $product_row_columns[$columns_key_real]=$column_data;
        }
        $product_row_columns=apply_filters('wf_pklist_alter_package_product_table_columns', $product_row_columns, $template_type, $_product, $item, $order);
        $html=self::build_product_row_html($product_row_columns, $columns_list_arr, $order_item_id);
        return $html;
	}

	/**
	* @since 4.0.0 Render product table row HTML for package type documents
	* @since 4.0.3 Added group by order for Picklist, Compatibility for variable subscription product
	* @since 4.1.3 Order item sorting added
	*/
	public static function generate_package_product_table_product_row_html($columns_list_arr,$template_type,$order=null,$box_packing=null,$order_package=null)
	{
		$html='';
		if(!is_null($order))
        {
        	//module settings are saved under module id
			$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);
			$the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);

			/**
			*	@since 4.0.9
			*	This filter will: Want to check variation data too when grouping products
			*/
			$compare_with_variation_data=false;
			if($template_type=='picklist')
			{
				$compare_with_variation_data=apply_filters('wt_pklist_compare_variation_data_to_group_in_picklist', $compare_with_variation_data);
			}


        	/**
			*	@since 4.1.3
			*	Sort order items
			*/
			if(isset($the_options['sort_products_by']) && $the_options['sort_products_by']!="")
			{
				$sort_config_arr=explode("_", $the_options['sort_products_by']);
				$sort_by=$sort_config_arr[0];
				$sort_order=(isset($sort_config_arr[1]) ? $sort_config_arr[1] : "");
				$order_package=self::sort_items($order_package, $template_type, $order, "package", $sort_by, $sort_order);
			}else
			{
				/* the sort function also handles bundle product compatibilty, here no sorting enabled so it will handle bundle products only */
				$order_package=self::sort_items($order_package, $template_type, $order, "package");
			}

        	$order_package=apply_filters('wf_pklist_alter_package_order_items', $order_package, $template_type, $order);


			$wc_version=WC()->version;

        	$package_type =Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_package_type');
            $category_wise_split =Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_product_category_wise_splitting',$module_id);

            /* @since 4.0.3 only for picklist   */
            $order_wise_split =Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_product_order_wise_splitting',$module_id);
            if($package_type == 'single_packing' && ($category_wise_split == 'Yes'|| $order_wise_split=='Yes'))
           	{
           		/* if both are enabled we need to decide which is outer */
           		$is_category_under_order=1;
           		if($order_wise_split=='Yes' && $category_wise_split=='Yes')
	            {
	            	$is_category_under_order=apply_filters('wf_pklist_alter_groupby_is_category_under_order', $is_category_under_order, $template_type);
	            }
           		$item_arr=array();
	            foreach ($order_package as $id => $item)
	            {
	                $_product = wc_get_product($item['id']);
	                if(!$_product){ continue; }
	                if($item['variation_id'] !='')
	                {
	                   $parent_id=wp_get_post_parent_id($item['variation_id']);
	                   $item['id']=$parent_id;
	                }
	                $item_obj=$_product;
	                $item_obj->qty = $item['quantity'];
                    $item_obj->weight = $item['weight'];
                    $item_obj->price = $item['price'];
                    $item_obj->variation_data = $item['variation_data'];
                    $item_obj->variation_id = $item['variation_id'];
                    $item_obj->item_id = $item['id'];
                    $item_obj->name = $item['name'];
                    $item_obj->sku = $item['sku'];
                    $item_obj->order_item_id = $item['order_item_id'];
                    $item_obj->item= $item;

	                if($category_wise_split=='Yes')
	                {
	                	$terms=get_the_terms($item['id'], 'product_cat');
		                $term_name_arr=array();
		                if($terms)
		                {
		                	$term_name_arr=self::get_term_data($item['id'], $term_name_arr, $template_type, $order);

		                }else /* compatibility for variable subscription products */
						{
							if(isset($item['extra_meta_details']) && isset($item['extra_meta_details']['_product_id'])) //extra meta details available
							{
								if(is_array($item['extra_meta_details']['_product_id']))
								{
									foreach($item['extra_meta_details']['_product_id'] as $p_id)
									{
										$term_name_arr=self::get_term_data($p_id, $term_name_arr, $template_type, $order);
									}
								}else
								{
									$p_id=(int) $item['extra_meta_details']['_product_id'];
									if($p_id>0)
									{
										$term_name_arr=self::get_term_data($p_id, $term_name_arr, $template_type, $order);
									}
								}
							}
						}

						//adding empty value if no term found
						$term_name_arr=(count($term_name_arr)==0 ? array('--') : $term_name_arr);

	                	$term_name=implode(", ",$term_name_arr);
	                	if($order_wise_split=='Yes')
	                	{
	                		$order_text=self::order_text_for_product_table_grouping_row($item, $template_type);
	                		if($is_category_under_order==1)
	                		{
	                			if(!isset($item_arr[$order_text]))
								{
									$item_arr[$order_text]=array();
								}
								if(!isset($item_arr[$order_text][$term_name]))
								{
									$item_arr[$order_text][$term_name]=array();
								}

	                			$item_arr[$order_text][$term_name][]=$item_obj;
	                		}else
	                		{
	                			if(!isset($item_arr[$term_name]))
								{
									$item_arr[$term_name]=array();
								}
								if(!isset($item_arr[$term_name][$order_text]))
								{
									$item_arr[$term_name][$order_text]=array();
								}

	                			$item_arr[$term_name][$order_text][]=$item_obj;
	                		}
	                	}else
	                	{

	                		if(!isset($item_arr[$term_name]))
							{
								$item_arr[$term_name]=array();
							}

	                		//avoiding duplicate row of products (Picklist)
	                		if($template_type=='picklist') //not need a checking, but for perfomance and security
	                		{
	                			$variation_id=(isset($item['extra_meta_details']['_variation_id']) && !is_array($item['extra_meta_details']['_variation_id']) && trim($item['extra_meta_details']['_variation_id'])!='' ? $item['extra_meta_details']['_variation_id'] : $item['variation_id']);
	                			$product_id=($variation_id!='' ? $variation_id : $item['id']);

	                			if(isset($item_arr[$term_name][$product_id])) //already added then increment the quantity
	                			{
	                				$cr_item=$item_arr[$term_name][$product_id];
	                				$increase_quantity=true;
	                				if($compare_with_variation_data) /* compare with variation data too */
	                				{
	                					$cr_item_variation_data=$cr_item->item['variation_data'];
	                					$item_variation_data=$item_obj->item['variation_data'];
	                					if($cr_item_variation_data!=$item_variation_data) /* variation data doesn't matches so add as different item */
	                					{
	                						$increase_quantity=false;
	                						$product_id=$product_id.'_'.$item_obj->item['order_item_id'];
	                					}
	                				}

	                				if($increase_quantity)
	                				{
	                					$new_quantity=((int) $cr_item->qty) + ((int) $item_obj->qty);
		                				$cr_item->qty=$new_quantity;
		                				$cr_item->item['quantity']=$new_quantity;
		                				$item_obj=$cr_item;
	                				}
	                			}
	                			$item_arr[$term_name][$product_id]=$item_obj;
	                		}else
	                		{
                        		$item_arr[$term_name][]=$item_obj;
                        	}
                    	}
	                }else
	                {
	                	$order_text=self::order_text_for_product_table_grouping_row($item,$template_type);
	                	$item_arr[$order_text][]=$item_obj;
	                }
	            }

	            $item_arr=apply_filters('wf_pklist_alter_package_grouped_order_items', $item_arr, array('order'=>$order_wise_split, 'category'=>$category_wise_split), $order_package, $template_type, $order);

	            $total_column=self::get_total_table_columms_enabled($columns_list_arr);
	            if($order_wise_split=='Yes' && $category_wise_split=='Yes')
	            {
	            	foreach($item_arr as $key=>$val_arr)
            		{
		            	$html.=self::get_product_table_grouping_row($is_category_under_order, 1, $key, $total_column, $template_type);
            			foreach($val_arr as $val_key=>$val)
            			{
            				$html.=self::get_product_table_grouping_row($is_category_under_order, 2, $val_key, $total_column, $template_type);
			            	foreach($val as $cat_ind=>$cat_data)
			            	{
			            		// get the product; if this variation or product has been deleted, this will return null...
					    		$_product=$cat_data;
					    		$item=$cat_data->item;
					    		if($_product)
					    		{
					    			$html.=self::generate_package_product_table_product_column_html($wc_version,$the_options,$order,$template_type,$_product,$item,$columns_list_arr);
					    		}
			            	}
            			}
            		}
	            }else
	            {
	            	foreach($item_arr as $val_key=>$val)
        			{
        				$is_group_by_cat=($category_wise_split=='Yes' ? 1 : 0);
        				$html.=self::get_product_table_grouping_row($is_group_by_cat, 2, $val_key, $total_column, $template_type);
		            	foreach($val as $cat_ind=>$cat_data)
		            	{
		            		// get the product; if this variation or product has been deleted, this will return null...
				    		$_product=$cat_data;
				    		$item=$cat_data->item;
				    		if($_product)
				    		{
				    			$html.=self::generate_package_product_table_product_column_html($wc_version,$the_options,$order,$template_type,$_product,$item,$columns_list_arr);
				    		}
		            	}
        			}
	            }
           	}else
           	{
           		if($package_type == 'single_packing' && $template_type=='picklist') /* remove the duplicates and increase the quantity. not need a template type checking, but for perfomance and security */
           		{
           			$item_arr=array();
           			foreach ($order_package as $id => $item)
					{
						$product_id=($item['variation_id'] !='' ? $item['variation_id'] : $item['id']);
						if(isset($item_arr[$product_id])) //already added then increment the quantity
						{
							$cr_item=$item_arr[$product_id];
							$item_arr[$product_id]['quantity']=((int) $cr_item['quantity']) + ((int) $item['quantity']);
						}else
						{
							$item_arr[$product_id]=$item;
						}
	            	}
	            	$order_package=$item_arr;
           		}

           		foreach($order_package as $id => $item)
	            {
	            	$_product = wc_get_product($item['id']);
	                if($item['variation_id'] !='')
	                {
	                   $parent_id=wp_get_post_parent_id($item['variation_id']);
	                   $item['id']=$parent_id;
	                }
	                if($_product)
				    {
	            		$html.=self::generate_package_product_table_product_column_html($wc_version,$the_options,$order,$template_type,$_product,$item,$columns_list_arr);
	            	}
	            }
           	}
           	$html=apply_filters('wf_pklist_package_product_tbody_html', $html, $columns_list_arr, $template_type, $order, $box_packing, $order_package);
        }else
        {
			$html=self::dummy_product_row($columns_list_arr);
        }
        return $html;
	}

	/**
	*	@since 4.0.3 Prepare grouping row for package product table Eg: Order wise(Only for picklist), Category wise
	*	@since 4.0.5 Added new filter to alter grouping row content
	*/
	public static function get_product_table_grouping_row($is_category_under_order, $loop, $key, $total_column, $template_type)
	{
		$row_type='category';
		if(($is_category_under_order==1 && $loop==1) || ($is_category_under_order!=1 && $loop==2))
		{
			$row_type='order';
		}
		$key=apply_filters('wf_pklist_alter_grouping_row_data', $key, $row_type, $template_type);
		if($row_type=='category')
		{
			$category_tr_html='<tr class="wfte_product_table_category_row"><td colspan="'.$total_column.'">'.$key.'</td></tr>';
			return apply_filters('wf_pklist_alter_category_row_html', $category_tr_html, $key, $total_column, $template_type);
		}else
		{
			$order_tr_html='<tr class="wfte_product_table_order_row"><td colspan="'.$total_column.'">'.$key.'</td></tr>';
    		return apply_filters('wf_pklist_alter_order_row_html', $order_tr_html, $key, $total_column, $template_type);
		}
	}

		/**
	*
	* Render image column for product table
	* @since 4.0.0
	* @since 4.0.2 Default image option added, CSS class option added
	* @since 4.0.9 Added filter to alter image URL
	*/
	public static function generate_product_image_column_data($product_id, $variation_id, $parent_id)
	{
		$img_url=plugin_dir_url(plugin_dir_path(__FILE__)).'assets/images/thumbnail-preview.png';
		if($product_id>0)
		{
			$image_id=get_post_thumbnail_id($product_id);
	        $attachment=wp_get_attachment_image_src($image_id);
	        if(empty($attachment[0]) && $variation_id>0) //attachment is empty and variation is available
	        {
	            $var_image_id=get_post_thumbnail_id($variation_id);
	            $image_id=(($var_image_id=='' || $var_image_id==0) ? get_post_thumbnail_id($parent_id) : $var_image_id);
	            $attachment=wp_get_attachment_image_src($image_id);
	        }
	        $img_url=(!empty($attachment[0]) ? $attachment[0] : $img_url);
    	}

    	$img_url=apply_filters('wt_pklist_alter_product_image_url', $img_url, $product_id, $variation_id, $parent_id);

        return '<img src="'.$img_url.'" style="max-width:30px; max-height:30px; border-radius:25%;" class="wfte_product_image_thumb"/>';
	}

	/**
	*	@since 4.0.3 Prepare order grouping row text for package product table
	*	@since 4.1.6 Added new filter(wf_pklist_alter_order_grouping_row_text_glue) to alter order info text glue.
	*/
	public static function order_text_for_product_table_grouping_row($item, $template_type)
	{
		$order_text=__('Unknown','wf-woocommerce-packing-list');
		if(isset($item['order']) && !is_null($item['order']) && is_object($item['order']) && is_a($item['order'],'WC_Order'))
		{
			$order_info_arr=array();
			$order_info_arr[]=__('Order number','wf-woocommerce-packing-list').': '.self::get_order_number($item['order'],$template_type);
			if(Wf_Woocommerce_Packing_List_Public::module_exists('invoice'))
			{
				$order_info_arr[]=__('Invoice number','wf-woocommerce-packing-list').': '.Wf_Woocommerce_Packing_List_Invoice::generate_invoice_number($item['order'],false); //do not force generate
			}
			$order_info_arr=apply_filters('wf_pklist_alter_order_grouping_row_text', $order_info_arr, $item['order'], $template_type);

			$order_info_glue=" ";
			$order_info_glue=apply_filters('wf_pklist_alter_order_grouping_row_text_glue', $order_info_glue, $item['order'], $template_type);

			$order_text=implode($order_info_glue, $order_info_arr);
		}
		return $order_text;
	}

	public static function generate_product_table_head_html($columns_list_arr,$template_type)
	{
		$is_rtl_for_pdf=false;
		$is_rtl_for_pdf=apply_filters('wf_pklist_is_rtl_for_pdf',$is_rtl_for_pdf,$template_type);

		$first_visible_td_key='';
		$last_visible_td_key='';

		foreach ($columns_list_arr as $columns_key=>$columns_value)
		{
			$is_hidden=($columns_key[0]=='-' ? 1 : 0); //column not enabled

			if(strip_tags($columns_value)==$columns_value) //column entry without th HTML so we need to add
			{
				$coumn_key_real=($is_hidden==1 ? substr($columns_key,1) : $columns_key);
				$columns_value='<th class="wfte_product_table_head_'.$coumn_key_real.' wfte_product_table_head_bg wfte_table_head_color" col-type="'.$columns_key.'">'.$columns_value.'</th>';
			}

			if($is_hidden==1)
			{
				$columns_value_updated=self::addClass('', $columns_value, self::TO_HIDE_CSS);
				if($columns_value_updated==$columns_value) //no class attribute in some cases
				{
					$columns_value_updated=str_replace('<th>','<th class="'.self::TO_HIDE_CSS.'">',$columns_value);
				}
			}else
			{
				$columns_value_updated=self::removeClass('',$columns_value,self::TO_HIDE_CSS);

				if($first_visible_td_key=='')
				{
					$first_visible_td_key=$columns_key;
				}
				$last_visible_td_key=$columns_key;
			}
			//remove last column CSS class
			$columns_value_updated=str_replace('wfte_right_column','',$columns_value_updated);
			$columns_list_arr[$columns_key]=$columns_value_updated;
		}

		//add end th CSS class
		$end_td_key=($is_rtl_for_pdf===false ? $last_visible_td_key : $first_visible_td_key);
		if($end_td_key!="")
		{
			$columns_class_added=self::addClass('', $columns_list_arr[$end_td_key], 'wfte_right_column');
			if($columns_class_added==$columns_list_arr[$end_td_key]) //no class attribute in some cases, so add it
			{
				$columns_class_added=str_replace('<th>','<th class="wfte_right_column">',$columns_list_arr[$end_td_key]);
			}
			$columns_list_arr[$end_td_key]=$columns_class_added;
		}
		$columns_list_val_arr=array_values($columns_list_arr);
		return implode('',$columns_list_val_arr);
	}

	public static function build_product_row_html($product_row_columns, $columns_list_arr, $order_item_id)
	{
		$html='';
		if(is_array($product_row_columns))
        {
        	/**
        	*	@since 4.1.6 Added CSS class for Bundled products
        	*/
        	$is_bundle=false;
        	self::is_bundle_type($is_bundle, $order_item_id);

        	$tr_class="";
        	if($is_bundle!==false)
        	{
        		$tr_class.=($is_bundle==1 ? ' wfte_product_row_bundle_parent' : ' wfte_product_row_bundle_child');
        	}

        	$html.='<tr class="'.$tr_class.'">';
        	foreach($product_row_columns as $columns_key=>$columns_value)
        	{
        		$hide_it=($columns_key[0]=='-' ? self::TO_HIDE_CSS : ''); //column not enabled
        		$extra_col_options=$columns_list_arr[$columns_key];
        		$td_class=$columns_key.'_td';
        		$html.='<td class="'.$hide_it.' '.$td_class.' '.$extra_col_options.'">';
        		$html.=$columns_value;
        		$html.='</td>';
        	}
        	$html.='</tr>';
        }
        return $html;
	}

	/**
	*	@since 4.1.6
	*	The current order item is bundle type. Currently compatible with YITH and WC
	*/
	public static function is_bundle_type(&$is_bundle, $order_item_id)
	{
		/**
			Woocommerce Product Bundles
				_bundled_by -  child
				_bundled_items -  parent
				_bundle_cart_key -  bundle item id
		*/

		/**
			YITH WooCommerce Product Bundles
				_cartstamp - Parent
				_bundled_by - Child
				_yith_bundle_cart_key -  bundle item id(Parent) - (Pro only)
		*/


		$bundle_info_arr=array(
			'_bundled_by'=>'', //for child
			'_bundle_cart_key'=>'', //for parent
		);

		$meta_data_arr=wc_get_order_item_meta($order_item_id, '', false);

		/* check, it is a product bundle main product */
		if(isset($meta_data_arr['_bundled_items']) || isset($meta_data_arr['_cartstamp']))
		{
			$is_bundle=1; //parent
			if(isset($meta_data_arr['_bundle_cart_key'])) //WC
			{
				$bundle_info_arr['_bundle_cart_key']=$meta_data_arr['_bundle_cart_key'];

			}elseif(isset($meta_data_arr['_yith_bundle_cart_key'])) //Yith pro
			{
				$bundle_info_arr['_bundle_cart_key']=$meta_data_arr['_yith_bundle_cart_key'];
			}
			$bundle_info_arr=self::_bundle_product_process_assoc_array($bundle_info_arr, '_bundle_cart_key');

		}elseif(isset($meta_data_arr['_bundled_by']))
		{
			$is_bundle=2; //child
			$bundle_info_arr['_bundled_by']=(isset($meta_data_arr['_bundled_by']) ? $meta_data_arr['_bundled_by'] : '');
			$bundle_info_arr=self::_bundle_product_process_assoc_array($bundle_info_arr, '_bundled_by');
		}

		return $bundle_info_arr;
	}

	/**
	*	@since 4.1.6
	*	In some cases the bundle meta data is saved as array
	*/
	private static function _bundle_product_process_assoc_array($bundle_info_arr, $key)
	{
		if(isset($bundle_info_arr[$key]) && is_array($bundle_info_arr[$key]))
		{
			if(isset($bundle_info_arr[$key][0]) && is_string($bundle_info_arr[$key][0]))
			{
				$bundle_info_arr[$key]=$bundle_info_arr[$key][0];
			}else
			{
				$bundle_info_arr[$key]='';
			}
		}
		return $bundle_info_arr;
	}

	/**
	*	Sort order items, By Name SKU etc
	*	@since 4.1.3
	*	@since 4.1.4 [Bug fix] Items missing in Picklist while bulk printing when sorting is enabled.
	*	@since 4.1.5 [Bug fix] Product not exist error on non package documents
	* 				 [Bug fix] Wrong quantity/Item missing on Picklist when packaging is Pack items individually
	*	@since 4.1.6 Added compatibility with bundled products. (WC, YITH)
	*/
	public static function sort_items($items, $template_type, $order, $doc_type="non-package", $sort_by="", $sort_order="asc")
	{
		$out=array();
		$sort_key_arr=array(); //this is for package documents only

		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);
		$bundle_display_option=Wf_Woocommerce_Packing_List_Customizer::get_bundle_display_option($template_type, $order);

		/* we have to disable bundle display option when category wise display is enabled */
		$category_wise_split=Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_product_category_wise_splitting', $module_id);

		$previous_sort_key='';

		$bundle_child_arr=array();

		foreach($items as $k=>$item)
		{
			$sort_key=$k;
			if($sort_by!="")
			{
				if($doc_type=="non-package") //invoice, proforma etc
				{
					$product=$item->get_product();

					if($product instanceof WC_Product)
					{
		                $sort_key_suffix='_'.$k;

						if($sort_by=="name")
						{
							$sort_key=$product->get_name().$sort_key_suffix;
						}
						elseif($sort_by=="sku")
						{
							$sort_key=$product->get_sku().$sort_key_suffix;
						}
					}

				}else
				{
					$sort_key_suffix='_'.$item['order_item_id'];

					if($sort_by=="name")
					{
						$sort_key=$item['name'].$sort_key_suffix;
					}
					elseif($sort_by=="sku")
					{
						$sort_key=$item['sku'].$sort_key_suffix;
					}

					/**
					* 	@since 4.1.5 [Bug fix] Item missing/Wrong quantity on Picklist when packaging is Pack items individually
					*/
					if(isset($sort_key_arr[$sort_key]))
					{
						$sort_key_arr[$sort_key]=$sort_key_arr[$sort_key]+1;
						$sort_key.='-'.$sort_key_arr[$sort_key];
					}else
					{
						$sort_key_arr[$sort_key]=1;
					}
				}
			}

			/* filter to alter sort key */
			$sort_key=apply_filters('wt_pklist_alter_product_sort_by', $sort_key, $item, $template_type, $sort_by, $order);

			$remove_current_item=false;
			$bundle_info_arr=array();

			if($category_wise_split!='Yes') /* Check category wise grouping not enabled */
			{
				$is_bundle=false;
				$order_item_id=($doc_type=="non-package" ? $k : (isset($item['order_item_id']) ? $item['order_item_id'] : 0));
				$bundle_info_arr=self::is_bundle_type($is_bundle, $order_item_id);

				if($is_bundle==1) //parent
				{
					if($bundle_display_option=='sub')
					{
						$remove_current_item=true;
					}

				}elseif($is_bundle==2) //child
				{
					if($bundle_display_option=='main')
					{
						$remove_current_item=true;

					}elseif($bundle_display_option=='main-sub')
					{
						/* check this as a bundle child, only if there is a bundle_by attribute, Otherwise treat as a normal order item */
						if(isset($bundle_info_arr['_bundled_by']) && $bundle_info_arr['_bundled_by']!="")
						{
							/* check this is the first child of the bundle parent. (Not items already added) */
							if(!isset($bundle_child_arr[$bundle_info_arr['_bundled_by']]))
							{
								if($previous_sort_key!="" && isset($out[$previous_sort_key])) //previous item exists(Parent item) in the loop, Otherwise treat as a normal order item
								{
									if($out[$previous_sort_key][2]['_bundle_cart_key']=="") /* bundle cart key is empty then fill it with bundle child `_bundled_by`. for Yith basic users. */
									{
										$out[$previous_sort_key][2]['_bundle_cart_key']=$bundle_info_arr['_bundled_by'];
									}

									$remove_current_item=true; //remove the item from main sorting array and add it to separate array

									/* add child item to separate children array under its parent's _bundle_cart_id */
									$bundle_child_arr[$bundle_info_arr['_bundled_by']]=array($sort_key=>array($item, $k, $bundle_info_arr));

								}
							}else
							{
								$remove_current_item=true; //remove the item from main sorting array and add it to separate array
								$bundle_child_arr[$bundle_info_arr['_bundled_by']][$sort_key]=array($item, $k, $bundle_info_arr);
							}
						}
					}
				}
			}

			if(!$remove_current_item)
			{
				$out[$sort_key]=array($item, $k, $bundle_info_arr);
			}
			$previous_sort_key=$sort_key;
		}

		$sort_order=apply_filters('wt_pklist_alter_product_sort_order', $sort_order, $template_type, $order);

		if($sort_order=='asc')
		{
			ksort($out);
		}else
		{
			krsort($out);
		}


		$items=array();
		foreach($out as $v)
		{
			$items[$v[1]]=$v[0];

			if($category_wise_split!='Yes') /* Check category wise grouping not enabled */
			{
				if(isset($v[2]) && isset($v[2]['_bundle_cart_key'])
					&& $v[2]['_bundle_cart_key']!=""
					&& isset($bundle_child_arr[$v[2]['_bundle_cart_key']])) /*  confirming its a bundle parent  */
				{

					$current_bundle_child_arr=$bundle_child_arr[$v[2]['_bundle_cart_key']];
					/* sort child items */
					if($sort_order=='asc')
					{
						ksort($current_bundle_child_arr);
					}else
					{
						krsort($current_bundle_child_arr);
					}
					foreach ($current_bundle_child_arr as $child_arr)
					{
						$items[$child_arr[1]]=$child_arr[0]; //add child items to main array
					}
				}
			}
		}
		return $items;
	}

	/**
	* 	Render product table row HTML for non package type documents
	* 	@since 4.0.8 Group by category option added
	* 	@since 4.1.3 Order item sorting added
	*/
	public static function generate_product_table_product_row_html($columns_list_arr, $template_type, $order=null,$refund_order = null, $refund_id = null)
	{
		$html='';
		//module settings are saved under module id
		$module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);
		$free_line_items_enable = Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_invoice_free_line_items',$module_id);

		if(!is_null($order))
        {
			$wc_version=WC()->version;
			$order_id=$wc_version<'2.7.0' ? $order->id : $order->get_id();
			$user_currency=get_post_meta($order_id,'_order_currency', true);

			$incl_tax_text='';
			$incl_tax=false;
			$tax_type=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_taxstatus');
			if(in_array('in_tax', $tax_type)) /* including tax */
			{
				$incl_tax_text=self::get_tax_incl_text($template_type, $order, 'product_price');
				$incl_tax_text=($incl_tax_text!="" ? ' ('.$incl_tax_text.')' : $incl_tax_text);
				$incl_tax=true;
			}
			$the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);

			$order_items=$order->get_items();
			if(!is_null($refund_order)){
				$order_items=$refund_order->get_items();
			}

			// print_r($refund_order);

			/**
			*	@since 4.1.3
			*	Sort order items
			*/
			if(isset($the_options['sort_products_by']) && $the_options['sort_products_by']!="")
			{
				$sort_config_arr=explode("_", $the_options['sort_products_by']);
				$sort_by=$sort_config_arr[0];
				$sort_order=(isset($sort_config_arr[1]) ? $sort_config_arr[1] : "");
				$order_items=self::sort_items($order_items, $template_type, $order, "non-package", $sort_by, $sort_order);
			}else
			{
				/* the sort function also handles bundle product compatibilty, here no sorting enabled so it will handle bundle products only */
				$order_items=self::sort_items($order_items, $template_type, $order, "non-package");
			}


			$order_items=apply_filters('wf_pklist_alter_order_items', $order_items, $template_type, $order);
			if($wc_version<'2.7.0')
			{
	            $order_prices_include_tax=$order->prices_include_tax;
	            $order_display_cart_ex_tax=$order->display_cart_ex_tax;
	        } else {
	            $order_prices_include_tax=$order->get_prices_include_tax();
	            $order_display_cart_ex_tax=get_post_meta($order_id, '_display_cart_ex_tax', true);
	        }

	        /**
	        *	Check grouping enabled
	        */
	        $category_wise_split =Wf_Woocommerce_Packing_List::get_option('wf_woocommerce_product_category_wise_splitting',$module_id);
	        $item_arr=array();
	        $total_column=self::get_total_table_columms_enabled($columns_list_arr);
	        if($category_wise_split=='Yes')
	        {
	        	foreach ($order_items as $order_item_id=>$order_item)
				{
					$product_id=$order_item->get_product_id();

	        		$term_name_arr=array();
	        		$term_name_arr=self::get_term_data($product_id, $term_name_arr, $template_type, $order);

	        		//adding empty value if no term found
					$term_name_arr=(count($term_name_arr)==0 ? array('--') : $term_name_arr);
                	$term_name=implode(", ",$term_name_arr);
                	if(!isset($item_arr[$term_name]))
                	{
                		$item_arr[$term_name]=array();
                	}
                	$item_arr[$term_name][$order_item_id]=$order_item;
	        	}
	        }else /* prepare same array structure as in the grouping */
	        {
	        	$item_arr[]=$order_items;
	        }


	        /**
	        *	Total tax display option
	        */
        	$total_tax_column_display_option=Wf_Woocommerce_Packing_List::get_option('wt_pklist_total_tax_column_display_option', $module_id);
			if($total_tax_column_display_option===false) //option not present, then add a filter to control the value
			{
				$total_tax_column_display_option=apply_filters('wf_pklist_alter_total_tax_column_display_option', $total_tax_column_display_option, $template_type, $order);
			}

			$tax_items_arr=$order->get_items('tax');
			$tax_data_arr=array();
			foreach ($tax_items_arr as $tax_item)
			{
				$tax_data_arr[$tax_item->get_rate_id()]=$tax_item->get_rate_percent();
			}


	        foreach($item_arr as $item_key=>$items)
	        {

        		if($category_wise_split=='Yes')
	        	{
        			$html.=self::get_product_table_grouping_row(1, 2, $item_key, $total_column, $template_type);
        		}

        		/* since 4.2.0 - Get the refunded items id to show only the refunded items in credit note */
        		$refunded_line_items = array();
        		$order_refunds = $order->get_refunds();

        		if(!empty($order_refunds)){
        			foreach( $order_refunds as $refund ){
						$refunded_items = $refund->get_items();
						foreach($refunded_items as $refund_item){
							$refunded_line_items[] = $refund_item->get_meta('_refunded_item_id');
						}
					}
        		}

				foreach ($items as $order_item_id=>$order_item)
				{
					/*if($template_type == "creditnote"){
						if(!in_array($order_item_id,$refunded_line_items)){
	                    	continue;
	                	}
					}*/

					//Since 4.1.9 - Free line items display in invoice
					$product_total_free_order=($wc_version< '2.7.0' ? $order->get_item_meta($order_item_id,'_line_total',true) : $order->get_line_subtotal($order_item, $incl_tax, true));

					if(\intval($order->get_total()) === 0){
						if($free_line_items_enable === "No"){
							if (((float) $order_item['line_total'] === 0.0) && ((float)$product_total_free_order === 0.0) ) {
		                    	continue;
		                	}
						}
					}else{
						if($free_line_items_enable === "No"){
							if (((float) $order_item['line_total'] === 0.0) || ((float)$product_total_free_order === 0.0) ) {
		                    	continue;
		                	}
						}
					}

				    // get the product; if this variation or product has been deleted, this will return null...
				    $_product=$order_item->get_product();
				    if($_product)
				    {
				        $product_row_columns=array(); //for html generation
				        $product_id=($wc_version< '2.7.0' ? $_product->id : $_product->get_id());

				        $variation_id=($order_item['variation_id']!='' ? $order_item['variation_id']*1 : 0);
				        $parent_id=wp_get_post_parent_id($variation_id);
				        $item_taxes=$order_item->get_taxes();
				        $item_tax_subtotal=(isset($item_taxes['subtotal']) ? $item_taxes['subtotal'] : array());
				        foreach($columns_list_arr as $columns_key=>$columns_value)
				        {
				        	$columns_key_real=$columns_key; /* backup */
				        	if(strpos($columns_key, 'default_column_')===0) /* if the current column added by customer, and its a default column */
				        	{
				        		$columns_key=str_replace('default_column_', '', $columns_key);
				        	}

				            if($columns_key==='image' || $columns_key==='-image')
				            {
				            	$column_data=self::generate_product_image_column_data($product_id,$variation_id,$parent_id);
				            }
				            elseif($columns_key==='sku' || $columns_key==='-sku')
				            {
				            	$column_data=$_product->get_sku();
				            }
				            elseif($columns_key==='product' || $columns_key==='-product')
				            {
				            	$product_name=(isset($order_item['name']) ? $order_item['name'] : '');
				            	$product_name=apply_filters('wf_pklist_alter_product_name', $product_name, $template_type, $_product, $order_item, $order);

				            	//variation data======
				            	$variation='';
				            	if(isset($the_options['woocommerce_wf_packinglist_variation_data']) && $the_options['woocommerce_wf_packinglist_variation_data']=='Yes')
				            	{
				            		// get variation data, meta data
					            	$variation=Wf_Woocommerce_Packing_List_Customizer::get_order_line_item_variation_data($order_item, $order_item_id, $_product, $order, $template_type);

							        $item_meta=function_exists('wc_get_order_item_meta') ? wc_get_order_item_meta($order_item_id, '', false) : $order->get_item_meta($order_item_id);
							        $variation_data=apply_filters('wf_pklist_add_product_variation', $item_meta, $template_type, $_product, $order_item, $order);
							        if(!empty($variation_data) && !is_array($variation_data))
							        {
							        	$variation.='<br>'.$variation_data;
							        }
							        if(!empty($variation))
							        {
							        	$variation='<small style="word-break: break-word;">'.$variation.'</small>';
							        }
						    	}

						        //additional product meta
						        $addional_product_meta = '';
						        $meta_data_formated_arr=array();
						        if(isset($the_options['wf_'.$template_type.'_product_meta_fields']) && count($the_options['wf_'.$template_type.'_product_meta_fields'])>0)
						        {
						            $selected_product_meta_arr=$the_options['wf_'.$template_type.'_product_meta_fields'];
						            $product_meta_arr=Wf_Woocommerce_Packing_List::get_option('wf_product_meta_fields');
						            foreach($selected_product_meta_arr as $value)
						            {
							            if(isset($product_meta_arr[$value])) //meta exists in the added list
							            {
							                $meta_data=get_post_meta($product_id, $value, true);
							                if($meta_data=='' && $variation_id>0)
							                {
							                	$meta_data=get_post_meta($parent_id,$value,true);
							                }
							                if(is_array($meta_data))
						                    {
						                        $output_data=(self::wf_is_multi($meta_data) ? '' : implode(', ',$meta_data));
						                    }else
						                    {
						                        $output_data=$meta_data;
						                    }

						                    if($output_data!="")
						                    {
							                    $meta_info_arr=array('key'=>$value,'title'=>__($product_meta_arr[$value],'wf-woocommerce-packing-list'),'value'=>__($output_data, 'wf-woocommerce-packing-list'));
							                    $meta_info_arr=apply_filters('wf_pklist_alter_product_meta', $meta_info_arr, $template_type, $_product, $order_item, $order);
						                    	if(is_array($meta_info_arr) && isset($meta_info_arr['title']) && isset($meta_info_arr['value']) && $meta_info_arr['value']!="")
							                    {
							                    	$meta_data_formated_arr[]='<span class="wt_pklist_product_meta_item" data-meta-id="'.esc_attr($value).'"><label>'.$meta_info_arr['title'].'</label> : '.$meta_info_arr['value'].'</span>';
							                    }
							                }
							            }
					                }
						        }


						        /**
						    	*	@since 4.2.0 Compatible with Extra product option (theme complete)
						    	*/
						    	$enable_tmcart_data = "Yes";
						    	$tmcart_item_id = $order_item->get_id();
						    	$epo_tc_meta_data=wc_get_order_item_meta($tmcart_item_id, '_tmcartepo_data',true);
						    	$enable_tmcart_data = apply_filters('wf_pklist_alter_tmcart_data_enable',$enable_tmcart_data,$order_item,$order,$template_type);

						    	if($enable_tmcart_data == "Yes"){
						    		if(!empty($epo_tc_meta_data)){
										foreach ($epo_tc_meta_data as $key => $epo)
										{
											if ($epo && is_array($epo))
											{
												$tmcart_option_name = $epo['name'];
												$tmcart_option_value = $epo['value'];
												$tmcart_option_price = Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$epo['price']);
												$tmcart_option_qty = $epo['quantity'];

                                             	$meta_data_formated_arr[] ='<small style="line-height:18px;">'.'<span class="wfte_epo_'.$tmcart_option_name.'">'.$tmcart_option_name .':'.' <span style="white-space: pre-wrap;">'. $tmcart_option_value.'</span><br></span>'. '<span class="wfte_epo_cost"> COST: ' . $tmcart_option_price .'<br></span> <span class="wfte_epo_qty">Qty: '. $tmcart_option_qty .'</span></small>';
											}
										}
									}
						    	}
						        /**
						    	*	@since 4.1.1 The string glue to combine meta data items
						    	*/
								$string_glue='<br>';
						    	$string_glue = apply_filters('wt_pklist_product_meta_string_glue', $string_glue, $order, $template_type);
						    	$addional_product_meta=implode($string_glue, $meta_data_formated_arr);


						        $addional_product_meta=apply_filters('wf_pklist_add_product_meta', $addional_product_meta,$template_type,$_product,$order_item,$order);

						        /**
						        *	@since 4.1.0
						        *	Product attribute
						       	*/
						        $product_attr= '';
						        $product_attr_formated_arr=array();
						        if(isset($the_options['wt_'.$template_type.'_product_attribute_fields']) && is_array($the_options['wt_'.$template_type.'_product_attribute_fields']) && count($the_options['wt_'.$template_type.'_product_attribute_fields'])>0)
						        {
						            $selected_product_attr_arr=$the_options['wt_'.$template_type.'_product_attribute_fields'];
						            $product_attr_arr=Wf_Woocommerce_Packing_List::get_option('wt_product_attribute_fields');
						            foreach($selected_product_attr_arr as $attr)
						            {
						                $attr_data=$_product->get_attribute($attr);
						                $label=wc_attribute_label($attr, $_product);

					                    $attr_info_arr=array('key'=>$attr, 'title'=>$label, 'value'=>$attr_data);
					                    $attr_info_arr=apply_filters('wf_pklist_alter_product_attr_item', $attr_info_arr, $template_type, $_product, $order_item, $order);

				                    	if(is_array($attr_info_arr) && isset($attr_info_arr['title']) && isset($attr_info_arr['value']) && $attr_info_arr['value']!="")
					                    {
					                    	$item_html='<span class="wt_pklist_product_attr_item" data-attr-id="'.esc_attr($attr).'"><label>'.$attr_info_arr['title'].'</label>: '.$attr_info_arr['value'].'</span>';
					                    	$item_html=apply_filters('wf_pklist_alter_product_attr_item_html', $item_html, $attr_info_arr, $template_type, $_product, $order_item, $order);
					                    	if(is_string($item_html) && $item_html!="")
					                    	{
					                    		$product_attr_formated_arr[]=$item_html;
					                    	}
					                    }
					                }
						        }


						        /**
						    	*	@since 4.1.1 The string glue to combine product attr items
						    	*/
								$string_glue='<br>';
						    	$string_glue = apply_filters('wt_pklist_product_attr_string_glue', $string_glue, $order, $template_type);
						    	$product_attr=implode($string_glue, $product_attr_formated_arr);

						        $product_attr=apply_filters('wf_pklist_alter_product_attr_data', $product_attr, $template_type, $_product, $order_item, $order);


						        $product_column_data_arr=array(
						        	'product_name'=>'<b>'.$product_name.'</b>',
						        	'variation'=>$variation,
						        	'product_meta'=>$addional_product_meta,
						        	'product_attr'=>$product_attr,
						        );

						        /**
						        *	@since 4.1.0 to alter the data items in product column
						        */
						        $product_column_data_arr=apply_filters('wf_pklist_alter_product_column_data_arr', $product_column_data_arr, $template_type, $_product, $order_item, $order);

						        $column_data='';
						        if(is_array($product_column_data_arr))
						        {
						        	$product_column_data_arr=array_filter(array_values($product_column_data_arr));
						        	$column_data=implode('<br>', $product_column_data_arr);
						        }else
						        {
						        	$column_data=$product_column_data_arr;
						        }

				            }
				            elseif($columns_key==='quantity' || $columns_key==='-quantity')
				            {
				            	$column_data=apply_filters('wf_pklist_alter_item_quantiy',$order_item['qty'],$template_type,$_product,$order_item,$order);
				            }
				            elseif($columns_key==='price' || $columns_key==='-price')
				            {
				            	$item_price=$order->get_item_subtotal($order_item, $incl_tax, true);
	                    		$item_price=apply_filters('wf_pklist_alter_item_price',$item_price,$template_type,$_product,$order_item,$order);
	                    		$item_price_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$item_price);
	                    		//$item_price_formated = wc_price($item_price,array('currency'=>$user_currency));
	                    		//echo $item_price_formated;

	                    		$column_data=apply_filters('wf_pklist_alter_item_price_formated',$item_price_formated,$template_type,$item_price,$_product,$order_item,$order);
				            }
				            elseif(strpos($columns_key,'individual_tax_')!==false)
				            {
				            	/**
								*	Individual tax column key format
								*	[display type]_individual_tax_[rate_id]
								*/

				            	$tax_info_arr=explode("individual_tax_",$columns_key);
				            	$tax_display_type=str_replace('_', '', $tax_info_arr[0]);

				            	$tax_id=end($tax_info_arr);
				            	$tax_rate=(isset($tax_data_arr[$tax_id]) ? (float) $tax_data_arr[$tax_id] : 0);

				            	$tax_val='';
				            	if($tax_display_type==='amount' || $tax_display_type==='amount-rate')
				            	{
				            		$tax_val=(isset($item_tax_subtotal[$tax_id]) ? $item_tax_subtotal[$tax_id] : 0);
				            		$tax_val=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$tax_val);
				            		$tax_val=apply_filters('wf_pklist_alter_item_individual_tax', $tax_val, $template_type, $tax_id, $order_item, $order);
				            	}

				            	$tax_rate_display='';
				            	if($tax_display_type==='rate' || $tax_display_type==='amount-rate')
				            	{
				            		$tax_rate_display=$tax_display_type=='amount-rate' ? ' ('.$tax_rate.'%)' : $tax_rate.'%';
				            		$tax_rate_display=apply_filters('wf_pklist_alter_individual_tax_rate', $tax_rate_display, $tax_rate, $template_type, $tax_id, $order_item, $order);
				            	}

				            	$column_data=$tax_val.$tax_rate_display;
				            }
				            elseif($columns_key==='tax' || $columns_key==='-tax')
				            {
				            	$tax_rate_display='';
				            	$tax_rate=0;
				            	if($total_tax_column_display_option==='rate' || $total_tax_column_display_option==='amount-rate')
				            	{
				            		foreach($item_tax_subtotal as $tax_id => $tax_val)
				            		{
				            			$tax_rate+=(isset($tax_data_arr[$tax_id]) ? (float) $tax_data_arr[$tax_id] : 0);
				            		}
				            		$tax_rate_display=$total_tax_column_display_option=='amount-rate' ? ' ('.$tax_rate.'%)' : $tax_rate.'%';
				            		$tax_rate_display=apply_filters('wf_pklist_alter_total_tax_rate', $tax_rate_display, $tax_rate, $tax_data_arr, $template_type, $order_item, $order);
				            	}

				            	$item_tax_formated='';
				            	if($total_tax_column_display_option==='amount' || $total_tax_column_display_option==='amount-rate')
				            	{
				            		$product_total=(float) ($wc_version< '2.7.0' ? $order->get_item_meta($order_item_id,'_line_total',true) : $order->get_line_subtotal($order_item, $incl_tax, true));

				            		if($tax_rate > 0){
				            			$item_tax = $product_total * ($tax_rate/100);
				            		}else{
				            			$item_tax=$order->get_line_tax($order_item);
				            		}

									$item_tax=apply_filters('wf_pklist_alter_item_tax', $item_tax, $template_type, $_product, $order_item, $order);
									$item_tax_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$item_tax);
									$item_tax_formated=apply_filters('wf_pklist_alter_item_tax_formated',$item_tax_formated,$template_type,$item_tax,$_product,$order_item,$order);
				            	}

	                    		$column_data=$item_tax_formated.$tax_rate_display;

				            }
				            elseif($columns_key==='total_price' || $columns_key==='-total_price')
				            {
		                        $product_total=($wc_version< '2.7.0' ? $order->get_item_meta($order_item_id,'_line_total',true) : $order->get_line_subtotal($order_item, $incl_tax, true));

		                        $product_total=apply_filters('wf_pklist_alter_item_total', $product_total, $template_type, $_product, $order_item, $order, $incl_tax);

		                        $product_total_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$product_total);

		                        $column_data=apply_filters('wf_pklist_alter_item_total_formated', $product_total_formated, $template_type, $product_total, $_product, $order_item, $order, $incl_tax);

				            }elseif($columns_key==='category' || $columns_key==='-category')
				            {
	        					$column_data=self::set_category_col($product_id, $template_type, $order);
				            }
				            else //custom column by user
				            {
				            	$column_data='';
				            	if(!self::set_custom_meta_table_col_data($column_data, $columns_key_real, $product_id, $order_item_id))
				            	{
				            		$column_data = apply_filters('wf_pklist_product_table_additional_column_val', $column_data, $template_type, $columns_key_real, $_product, $order_item, $order);
				            	}
				            }
				            $product_row_columns[$columns_key_real]=$column_data;
				        }
				        $product_row_columns=apply_filters('wf_pklist_alter_product_table_columns',$product_row_columns,$template_type,$_product,$order_item,$order);
				        $html.=self::build_product_row_html($product_row_columns, $columns_list_arr, $order_item_id);
				    }else{
				    	$product_row_columns=array(); //for html generation
				    	$item_taxes=$order_item->get_taxes();
				        $item_tax_subtotal=(isset($item_taxes['subtotal']) ? $item_taxes['subtotal'] : array());
						foreach($columns_list_arr as $columns_key=>$columns_value)
						{
						    $columns_key_real=$columns_key; /* backup */
						    if(strpos($columns_key, 'default_column_')===0) /* if the current column added by customer, and its a default column */
						    {
						        $columns_key=str_replace('default_column_', '', $columns_key);
						    }

						    if($columns_key==='image' || $columns_key==='-image')
						    {
						        $img_url=plugin_dir_url(plugin_dir_path(__FILE__)).'assets/images/thumbnail-preview.png';
						        $column_data = '<img src="'.$img_url.'" style="max-width:30px; max-height:30px; border-radius:25%;" class="wfte_product_image_thumb"/>';
						    }
						    elseif($columns_key==='product' || $columns_key==='-product')
						    {
						        $product_name=(isset($order_item['name']) ? $order_item['name'] : '');
						        $column_data = $product_name;
						    }
						    elseif($columns_key==='quantity' || $columns_key==='-quantity')
						    {
						        $column_data=apply_filters('wf_pklist_alter_item_quantiy_deleted_product',$order_item['qty'],$template_type,$order_item,$order);
						    }
						    elseif($columns_key==='price' || $columns_key==='-price')
						    {
						        $item_price=$order->get_item_subtotal($order_item, $incl_tax, true);
						        $item_price_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$item_price);
						        $column_data=$item_price_formated;
						    }
						    elseif(strpos($columns_key,'individual_tax_')!==false)
						    {
						        /**
						        *   Individual tax column key format
						        *   [display type]_individual_tax_[rate_id]
						        */

						        $tax_info_arr=explode("individual_tax_",$columns_key);
						        $tax_display_type=str_replace('_', '', $tax_info_arr[0]);

						        $tax_id=end($tax_info_arr);
						        $tax_rate=(isset($tax_data_arr[$tax_id]) ? (float) $tax_data_arr[$tax_id] : 0);

						        $tax_val='';
						        if($tax_display_type==='amount' || $tax_display_type==='amount-rate')
						        {
						            $tax_val=(isset($item_tax_subtotal[$tax_id]) ? $item_tax_subtotal[$tax_id] : 0);
						            $tax_val=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$tax_val);
						            $tax_val=apply_filters('wf_pklist_alter_item_individual_tax', $tax_val, $template_type, $tax_id, $order_item, $order);
						        }

						        $tax_rate_display='';
						        if($tax_display_type==='rate' || $tax_display_type==='amount-rate')
						        {
						            $tax_rate_display=$tax_display_type=='amount-rate' ? ' ('.$tax_rate.'%)' : $tax_rate.'%';
						            $tax_rate_display=apply_filters('wf_pklist_alter_individual_tax_rate', $tax_rate_display, $tax_rate, $template_type, $tax_id, $order_item, $order);
						        }

						        $column_data=$tax_val.$tax_rate_display;
						    }
						    elseif($columns_key==='tax' || $columns_key==='-tax')
						    {
						        $tax_rate_display='';
						        $tax_rate=0;
						        if($total_tax_column_display_option==='rate' || $total_tax_column_display_option==='amount-rate')
						        {
						            foreach($item_tax_subtotal as $tax_id => $tax_val)
						            {
						                $tax_rate+=(isset($tax_data_arr[$tax_id]) ? (float) $tax_data_arr[$tax_id] : 0);
						            }
						            $tax_rate_display=$total_tax_column_display_option=='amount-rate' ? ' ('.$tax_rate.'%)' : $tax_rate.'%';
						            $tax_rate_display=apply_filters('wf_pklist_alter_total_tax_rate', $tax_rate_display, $tax_rate, $tax_data_arr, $template_type, $order_item, $order);
						        }

						        $item_tax_formated='';
						        if($total_tax_column_display_option==='amount' || $total_tax_column_display_option==='amount-rate')
						        {
						            $product_total=(float) ($wc_version< '2.7.0' ? $order->get_item_meta($order_item_id,'_line_total',true) : $order->get_line_subtotal($order_item, $incl_tax, true));

						            if($tax_rate > 0){
						                $item_tax = $product_total * ($tax_rate/100);
						            }else{
						                $item_tax=$order->get_line_tax($order_item);
						            }
						            $item_tax_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$item_tax);
						            $item_tax_formated=apply_filters('wf_pklist_alter_item_tax_formated_deleted_product',$item_tax_formated,$template_type,$item_tax,$order_item,$order);
						        }

						        $column_data=$item_tax_formated.$tax_rate_display;

						    }
						    elseif($columns_key==='total_price' || $columns_key==='-total_price')
						    {
						        $product_total=($wc_version< '2.7.0' ? $order->get_item_meta($order_item_id,'_line_total',true) : $order->get_line_subtotal($order_item, $incl_tax, true));

					         	$product_total=apply_filters('wf_pklist_alter_item_total_deleted_product', $product_total, $template_type, $order_item, $order, $incl_tax);

						        $product_total_formated=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$product_total);

						        $column_data=apply_filters('wf_pklist_alter_item_total_formated_deleted_product', $product_total_formated, $template_type, $product_total, $order_item, $order, $incl_tax);
						    }else{
						        $column_data='';
						    }
						    $product_row_columns[$columns_key_real]=$column_data;
						}
						$html.=self::build_product_row_html($product_row_columns, $columns_list_arr, $order_item_id);
				    }
				}
			}
			$html=apply_filters('wf_pklist_product_tbody_html', $html, $columns_list_arr, $template_type, $order);

		}else //dummy value for preview section (No order data available)
		{
			$html=self::dummy_product_row($columns_list_arr);
		}
		return $html;
	}

	public static function set_category_col($product_id, $template_type, $order)
	{
		$term_name_arr=array();
		$term_name_arr=self::get_term_data($product_id, $term_name_arr, $template_type, $order);
		return implode(", ", $term_name_arr);
	}

	public static function set_custom_meta_table_col_data(&$column_data, $columns_key, $product_id, $order_item_id)
	{
		if(strpos($columns_key, 'custom_product_meta_') === 0)
		{
			$product_meta_key 	= str_replace('custom_product_meta_', '', $columns_key);
			$product_meta_value = get_post_meta($product_id, $product_meta_key, true);
			$column_data = self::process_meta_value($product_meta_value);
			return true;

		}elseif(strpos($columns_key, 'custom_order_item_meta_') === 0)
		{
			$order_item_meta_key = str_replace('custom_order_item_meta_', '', $columns_key);
			$order_item_meta_value = wc_get_order_item_meta($order_item_id, $order_item_meta_key, true);
			$column_data = self::process_meta_value($order_item_meta_value);
			return true;
		}
		return false;
	}

	    /**
    * @since 4.0.8
    * Get total count of enabled table columns
    */
    public static function get_total_table_columms_enabled($columns_list_arr)
    {
    	$total=0;
    	foreach ($columns_list_arr as $key => $value)
    	{
    		if(substr($key, 0, 1)!='-')
    		{
    			$total++;
    		}
    	}
    	return $total;
    }

    /**
	* @since 4.0.3
	* Grouping terms
	*/
	public static function get_term_data($id, $term_name_arr, $template_type, $order)
	{
		$terms=get_the_terms($id,'product_cat');
		if($terms)
		{
			foreach($terms as $term)
			{
				$term_name_arr[]=$term->name;
			}
		}
		$term_name_arr=apply_filters('wf_pklist_alter_grouping_term_names', $term_name_arr, $id, $template_type, $order);
		return $term_name_arr;
	}
}