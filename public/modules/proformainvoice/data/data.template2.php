<div class="wfte_rtl_main wfte_invoice-main">
  <div class="wfte_invoice-header clearfix">
      <div class="wfte_doc_title wfte_text_right clearfix">__[Proforma Invoice]__</div>
      <div class="wfte_invoice-header_top clearfix">
        <div class="clearfix">
            <div class="wfte_company_logo float_left">
                <div class="wfte_company_logo_img_box">
                    <img src="[wfte_company_logo_url]" class="wfte_company_logo_img">
                </div>
                <div class="wfte_company_name wfte_hidden">[wfte_company_name]</div>
                <div class="wfte_company_logo_extra_details">__[]__</div>
            </div>
            <div class="float_right wfte_text_left" style="width:23%;">
                <div class="wfte_invoice_data">
                    <div class="wfte_vat_number">
                      <span class="wfte_vat_number_label">__[VAT:]__</span>
                      <span>[wfte_vat_number]</span>
                    </div>
                    <div class="wfte_ssn_number">
                      <span class="wfte_ssn_number_label">__[SSN:]__</span>
                      <span>[wfte_ssn_number]</span>
                    </div>
                    <div class="wfte_email">
                      <span class="wfte_email_label">__[Email:]__</span>
                      <span>[wfte_email]</span>
                    </div>
                    <div class="wfte_tel">
                      <span class="wfte_tel_label">__[Tel:]__</span>
                      <span>[wfte_tel]</span>
                    </div>
                    <div class="wfte_shipping_method">
                      <span class="wfte_shipping_method_label">__[Shipping Method:]__</span>
                      <span>[wfte_shipping_method]</span>
                    </div>
                    <div class="wfte_tracking_number">
                      <span class="wfte_tracking_number_label">__[Tracking number:]__</span>
                      <span>[wfte_tracking_number]</span>
                    </div>
                </div>
                <div class="wfte_order_item_meta">[wfte_order_item_meta]</div>
                [wfte_extra_fields]
            </div>
        </div>
        <div class="wfte_addrss_field_main clearfix wfte_text_left">
           <div class="wfte_addrss_fields wfte_from_address float_left">
             <div class="wfte_address-field-header wfte_from_address_label">__[From Address:]__</div>
             <div class="wfte_from_address_val">[wfte_from_address]</div>
           </div>
        </div>
      </div>
      <div class="wfte_addrss_field_main clearfix wfte_text_left">        
            <div class="wfte_addrss_fields wfte_billing_address float_left">
                <div class="wfte_address-field-header wfte_billing_address_label">__[Billing Address:]__</div>
                    [wfte_billing_address]
                </div>
            <div class="wfte_addrss_fields wfte_shipping_address float_left wfte_text_left">
                <div class="wfte_address-field-header wfte_shipping_address_label">__[Shipping Address:]__</div>
                [wfte_shipping_address]
            </div>
            <div class="wfte_invoice_data_grey wfte_addrss_fields float_right">
                <div class="wfte_invoice_data wfte_text_left">
                    <div class="wfte_proforma_invoice_number">
                      <span class="wfte_proforma_invoice_number_label">__[Proforma Invoice no:]__</span> [wfte_proforma_invoice_number]
                    </div>
                    <div class="wfte_proforma_invoice_date" data-proforma_invoice_date-format="m/d/Y">
                      <span class="wfte_proforma_invoice_date_label">__[Proforma Invoice date:]__</span> [wfte_proforma_invoice_date]
                    </div>
                    <div class="wfte_order_number">
                      <span class="wfte_order_number_label">__[Order No:]__</span>[wfte_order_number]
                    </div>
                    <div class="wfte_order_date" data-order_date-format="m/d/Y">
                      <span class="wfte_order_date_label">__[Date:]__</span>[wfte_order_date]
                    </div>
                </div>
            </div>
      </div>
  </div>
  <div class="wfte_invoice-body clearfix">
  <div class="wfte_received_seal wfte_hidden"><span class="wfte_received_seal_text">__[PAID]__</span>[wfte_received_seal_extra_text]</div> 
  [wfte_product_table_start]
    <table class="wfte_product_table">
        <thead class="wfte_product_table_head">
          <tr>
            <th class="wfte_product_table_head_image wfte_table_head_color wfte_product_table_head_bg" col-type="image">__[Image]__</th>
            <th class="wfte_product_table_head_sku wfte_table_head_color wfte_product_table_head_bg" col-type="sku">__[SKU]__</th>
            <th class="wfte_product_table_head_product wfte_table_head_color wfte_product_table_head_bg" col-type="product">__[Product]__</th>
            <th class="wfte_product_table_head_quantity wfte_table_head_color wfte_product_table_head_bg" col-type="quantity">__[Quantity]__</th>
            <th class="wfte_product_table_head_price wfte_table_head_color wfte_product_table_head_bg" col-type="price">__[Price]__</th>
            <th class="wfte_product_table_head_total_price wfte_table_head_color wfte_product_table_head_bg" col-type="total_price">__[Total Price]__</th>
            <th class="wfte_product_table_head_tax_items wfte_table_head_color wfte_product_table_head_bg" col-type="tax_items">[wfte_product_table_tax_item_column_label]</th>
            <th class="wfte_product_table_head_tax wfte_table_head_color wfte_product_table_head_bg" col-type="-tax">__[Total Tax]__</th>     
          </tr>
        </thead>
        <tbody class="wfte_product_table_body wfte_table_body_color">
        </tbody>
    </table>  
  [wfte_product_table_end]   
  <table class="wfte_payment_summary_table wfte_product_table">
    <tbody class="wfte_payment_summary_table_body wfte_table_body_color">
      <tr class="wfte_payment_summary_table_row wfte_product_table_subtotal">
        <td class="wfte_product_table_subtotal_label wfte_text_right">__[Subtotal]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_subtotal]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_shipping">
        <td class="wfte_product_table_shipping_label wfte_text_right">__[Shipping]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_shipping]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_cart_discount">
        <td class="wfte_product_table_cart_discount_label wfte_text_right">__[Cart Discount]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_cart_discount]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_order_discount">
        <td class="wfte_product_table_order_discount_label wfte_text_right">__[Order Discount]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_order_discount]</td>
      </tr>
      <tr data-row-type="wfte_tax_items" class="wfte_payment_summary_table_row wfte_product_table_tax_item">
        <td class="wfte_product_table_tax_item_label wfte_text_right">[wfte_product_table_tax_item_label]</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_tax_item]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_total_tax">
        <td class="wfte_product_table_total_tax_label wfte_text_right">__[Total Tax]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_total_tax]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_fee">
        <td class="wfte_product_table_fee_label wfte_text_right">__[Fee]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_fee]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_coupon">
        <td class="wfte_product_table_coupon_label wfte_text_right">__[Coupon Used]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_coupon]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_payment_method">
        <td class="wfte_product_table_payment_method_label wfte_text_right">__[Payment Method]__</td>
        <td class="wfte_right_column wfte_text_center">[wfte_product_table_payment_method]</td>
      </tr>
      <tr class="wfte_payment_summary_table_row wfte_product_table_payment_total">
        <td class="wfte_product_table_payment_total_label wfte_text_right wfte_product_table_head_bg wfte_table_head_color">__[Total]__</td>
        <td class="wfte_product_table_payment_total_val wfte_right_column wfte_text_center wfte_product_table_head_bg wfte_table_head_color">[wfte_product_table_payment_total]</td>
      </tr>
    </tbody>
  </table>
    <div class="clearfix"></div>
    <div class="wfte_special_notes wfte_text_left clearfix">
      [wfte_special_notes]
    </div>
    <div class="wfte_transport_terms wfte_text_left clearfix">
      [wfte_transport_terms]
    </div>
    <div class="wfte_sale_terms wfte_text_left clearfix">
      [wfte_sale_terms]
    </div>
    <div class="wfte_footer wfte_text_left clearfix">
      [wfte_footer]
    </div>
  </div>
</div>
<style type="text/css">
body, html{margin:0px; padding:0px;}
.clearfix::after {
    display: block;
    clear: both;
  content: "";}
.wfte_invoice-main{ color:#73879C; font-family:"Helvetica Neue", Roboto, Arial, "Droid Sans", sans-serif; font-size:12px; font-weight:400; line-height:14px; box-sizing:border-box; width:100%; margin:0px; padding:0px;}
.wfte_invoice-main *{ box-sizing:border-box;}
.wfte_invoice-header{ background:#ffffff; color:#000; padding:15px 30px; width:100%; }
.wfte_doc_title{ padding:15px 0px; padding-top:5px; width:100%; font-size:24px; font-weight:bold; color:#333;}
.wfte_invoice-header_top{ padding:15px 0px; padding-bottom:25px; border-bottom:solid 1px #ccc; width:100%;}
.wfte_company_logo{ float:left; width:40%; }
.wfte_company_logo_img{ width:150px; max-width:100%; }
.wfte_company_name{ font-size:18px; font-weight:bold; }
.wfte_company_logo_extra_details{ font-size:12px; }
.wfte_order_number, .wfte_order_date, .wfte_proforma_invoice_number, .wfte_proforma_invoice_date{ font-size:12px; }
.wfte_proforma_invoice_number{ font-weight:bold; }
.wfte_invoice_data{ width:100%; }
.wfte_invoice_data_grey{ background-color:#f3f3f3; padding:15px !important;}
.wfte_addrss_field_main{ width:100%; font-size:12px; padding-top:10px; }
.wfte_addrss_fields{ width:30%; padding:0px 1%;}
.wfte_address-field-header{ font-weight:bold; }
.wfte_invoice-body{background:#ffffff; color:#23272c; padding:10px 30px; width:100%;}
.wfte_product_table{ width:100%; border-collapse:collapse; }
.wfte_product_table_head{ color:#ffffff;}
.wfte_product_table_head_bg{background-color:#212529;}
.wfte_product_table .wfte_right_column{ width:17%; }
.wfte_payment_summary_table{ margin-bottom:15px; }
.wfte_payment_summary_table .wfte_left_column{ width:60%; }
.wfte_product_table_head{background-color:#212529;}
.wfte_product_table_head th{height:36px; padding:0px 5px; font-size:.75rem; text-align:center; line-height:10px; font-family:"Helvetica Neue", Roboto, Arial, "Droid Sans", sans-serif;}
.wfte_product_table_body td, .wfte_payment_summary_table_body td{font-size:12px; line-height:10px;}
.wfte_table_head_color{ color:#fff; }
.wfte_product_table_body td{padding:8px 5px; text-align:center;}
.wfte_payment_summary_table_body td{padding:8px 5px;}
.wfte_product_table_payment_total{ font-size:12px; font-weight:bold; height:36px; padding:0px;line-height:10px;}
td.wfte_product_table_payment_total_label{ text-align:right; height:36px; padding:0px 5px; line-height:10px;}
.wfte_product_table_payment_total_val{}
.wfte_footer{ width:100%; height:auto; border-top:solid 1px #dfd5d5; padding:5px 0px; margin-top:5px; font-size:10px;}
.wfte_special_notes{width:100%; height:auto; padding:5px 0px; margin-top:5px;}
.wfte_sale_terms{width:100%; height:auto; padding:5px 0px; margin-top:5px;}
.wfte_transport_terms{width:100%; height:auto; padding:5px 0px; margin-top:5px;}

.wfte_invoice_data td, .wfte_extra_fields td{font-size:12px; padding:0px; font-family:"Helvetica Neue", Roboto, Arial, "Droid Sans", sans-serif;}

.float_left{ float:left; }
.float_right{ float:right; }
</style>