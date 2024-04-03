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
trait Wf_Woocommerce_Packing_List_Customizer_Product_Table_Creditnote
{
    /**
    *   @since 4.0.0 Generating product table
    *   @since 4.0.2 Tax column introduced 
    *   
    */
    public static function set_product_table_creditnote($find_replace,$template_type,$html,$order=null,$refund_id=null,$refund_order=null,$box_packing=null,$order_package=null)
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
                                        *   Show rate column after amount column. Default:true
                                        *   
                                        */
                                        $show_individual_tax_rate_column_after_amount_column=apply_filters('wf_pklist_show_individual_tax_rate_column_after_amount_column', $show_individual_tax_rate_column_after_amount_column, $template_type, $order);
                                    }

                                    /**
                                    *   This variable is for filter.
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
                                        *   Rate column before amount column
                                        */
                                        if($individual_tax_column_display_option=='separate-column' && !$show_individual_tax_rate_column_after_amount_column)
                                        {
                                            self::prepare_tax_item_column_html($tax_id_rate_only, $tax_rate_only_column_label, $th_single_html, $columns_list_arr, $column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order);
                                        }

                                        self::prepare_tax_item_column_html($tax_id, $tax_label, $th_single_html, $columns_list_arr, $column_list_options, $extra_table_col_opt, $template_type, $tax_item, $individual_tax_column_config, $order);

                                        /**
                                        *   Rate column after amount column
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
                    $find_replace[$tbody_tag_match[0]]=$tbody_tag_match[0].self::generate_product_table_product_row_html($column_list_options,$template_type,$order,$refund_order,$refund_id);
                }
            }
        }

        $find_replace['[wfte_product_table_start]']='';
        $find_replace['[wfte_product_table_end]']='';
        return $find_replace;
    }

    /**
    *   Set other charges fields in product table
    *   @since  4.0.0
    *   @since  4.0.2 refund amount calculation issue fixed. Total in words integrated. Added filter to alter total
    *   @since  4.1.6 Added new filter to alter tax item amount `wf_pklist_alter_taxitem_amount`
    */
    public static function set_extra_charge_fields_creditnote($find_replace,$template_type,$html,$order=null,$refund_id=null,$refund_order=null)
    {
        //module settings are saved under module id
        $module_id=Wf_Woocommerce_Packing_List::get_module_id($template_type);
        if(!is_null($order))
        {
            $the_options=Wf_Woocommerce_Packing_List::get_settings($module_id);
            $order_items=$order->get_items();
            if(!is_null($refund_order)){
                $order_items=$refund_order->get_items();
            }
            $wc_version=WC()->version;
            $order_id=$wc_version<'2.7.0' ? $order->id : $order->get_id();
            $user_currency=get_post_meta($order_id, '_order_currency', true);
            
            $tax_type=Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_taxstatus');
            $incl_tax=in_array('in_tax', $tax_type);

            //subtotal ==========================
            if(!isset($find_replace['[wfte_product_table_subtotal]'])) /* check already added */
            {
                $incl_tax_text='';
                $sub_total=(float)$refund_order->get_subtotal();
                if($incl_tax)
                {
                    $incl_tax_text=self::get_tax_incl_text($template_type, $order, 'product_price');
                    $incl_tax_text=($incl_tax_text!="" ? ' ('.$incl_tax_text.')' : $incl_tax_text);

                    $total_tax=(float)$refund_order->get_total_tax();
                    $shipping_tax=(float)$refund_order->get_shipping_tax();
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
                        $shipping = abs((float) get_post_meta($refund_id,'_order_shipping',true));
                        //$shipping += abs((float) get_post_meta($refund_id,'_order_shipping_tax',true));
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

            $tax_items = $order->get_tax_totals();

            //tax items ==========================
            if(!isset($find_replace['[wfte_product_table_total_tax]'])) /* check already added */
            {
                if(in_array('ex_tax',$tax_type))
                {
                    //total tax ==========================
                    if(is_array($tax_items) && count($tax_items)>0)
                    {
                        $tax_total = $refund_order->get_total_tax();
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

            //echo $tax_items_row_html;

            if(is_array($tax_items) && count($tax_items)>0)
            {
                foreach($tax_items as $tax_item)
                {
                    if(in_array('ex_tax',$tax_type) && $tax_items_row_html!='')
                    {   
                        $tax_rate_id = $tax_item->rate_id;
                        $tax_label=apply_filters('wf_pklist_alter_taxitem_label', esc_html($tax_item->label), $template_type, $order, $tax_item);
                        $tax_amount = 0;
                        foreach($refund_order->get_items() as $refunded_item_id => $refunded_item){
                            $refund_tax = $refunded_item->get_taxes();
                            $tax_amount += isset( $refund_tax['total'][ $tax_rate_id ] ) ? (float) $refund_tax['total'][ $tax_rate_id ] : 0;
                        }
                        $tax_amount=apply_filters('wf_pklist_alter_taxitem_amount', $tax_amount, $tax_item, $order, $template_type,$tax_rate_id);
                        if($tax_amount == ""){
                            break;
                        }
                        // echo $tax_amount;
                        //print_r(array($tax_label, $tax_amount));
                        $tax_items_html.=str_replace(array('[wfte_product_table_tax_item_label]','[wfte_product_table_tax_item]'), array($tax_label, $tax_amount), $tax_items_row_html);
                        //echo $tax_items_html;
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
                $fee_details=$refund_order->get_items('fee');
                $fee_details_html='';
                $fee_total_amount = 0;
                if(!empty($fee_details)){
                    $fee_ord_arr = array();
                    foreach($fee_details as $fee => $fee_value){
                        $fee_order_id = $fee;
                        if(!in_array($fee_order_id,$fee_ord_arr)){
                            $fee_total_amount += (abs((float)wc_get_order_item_meta($fee_order_id,'_line_total',true))) + (abs((float)wc_get_order_item_meta($fee_order_id,'_line_tax',true)));
                            $fee_ord_arr[] = $fee_order_id;
                        }
                    }
                    $fee_total_amount_formated= Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$fee_total_amount);
                    $find_replace['[wfte_product_table_fee]']=$fee_total_amount_formated;
                }else{
                    $find_replace['[wfte_product_table_fee]']='';
                }
            }

            //total amount ==========================
            if(!isset($find_replace['[wfte_product_table_payment_total]']) || !isset($find_replace['[wfte_total_in_words]'])) /* check already added */
            {
                $total_price_final=($wc_version<'2.7.0' ? $refund_order->order_total : get_post_meta($refund_id,'_order_total',true));
                $refund_amount = abs((float)$total_price_final);
                $total_price_html=Wf_Woocommerce_Packing_List_Admin::wf_display_price($user_currency,$order,$refund_amount);
                $total_price_html = apply_filters('wf_pklist_alter_price_creditnote',$total_price_html,$template_type,$order);
                $find_replace = self::set_total_in_words($total_price_final, $find_replace, $template_type, $html, $order);

                $find_replace['[wfte_product_table_payment_total]']=$total_price_html;
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
}