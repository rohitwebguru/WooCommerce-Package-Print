<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wf-tab-content" data-id="<?php echo $target_id;?>">
<p><?php _e('Configure the general settings required for the invoice.','wf-woocommerce-packing-list');?></p>
<table class="wf-form-table">
	<?php
	$tax_doc_url = 'https://www.webtoffee.com/add-tax-column-in-woocommerce-invoice/#tax-display-formats';
	$individual_tax_doc_url = 'https://www.webtoffee.com/add-tax-column-in-woocommerce-invoice/#customize-tax';
	Wf_Woocommerce_Packing_List_Admin::generate_form_field(array(
		array(
			'type'=>"radio",
			'label'=>__("Enable invoice",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_enable_invoice",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
		),
		array(
			'type'=>"radio",
			'label'=>__("Group by category",'wf-woocommerce-packing-list'),
			'option_name'=>"wf_woocommerce_product_category_wise_splitting",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			)
		),
		array(
			'type'=>"radio",
			'label'=>__("Use order date as invoice date",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_orderdate_as_invoicedate",
			'help_text'=>__("If you choose 'No' then the invoice date will be the date on which it is generated.",'wf-woocommerce-packing-list'),
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
		),
		array(
			'type'=>"product_sort_by",
			'label'=>__("Sort products by", 'wf-woocommerce-packing-list'),
			'option_name'=>"sort_products_by",
			'help_text'=>'',
		),
		array(
			'type'=>'order_st_multiselect',
			'label'=>__("Generate invoice for order statuses",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_generate_for_orderstatus",
			'help_text'=>__("Order statuses for which an invoice should be generated.",'wf-woocommerce-packing-list'),
			'order_statuses'=>$order_statuses,
			'field_vl'=>array_flip($order_statuses),
			'attr'=>'',
		),
		array(
			'type'=>"radio",
			'label'=>__("Attach invoice PDF in order email",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_add_".$this->module_base."_in_mail",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
			'help_text'=>__('PDF version of invoice will be attached with the order email based on the above statuses','wf-woocommerce-packing-list'),		
		),
		array(
			'type'=>"radio",
			'label'=>__("Enable print option for customers",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_packinglist_frontend_info",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
			'help_text'=>__("Displays print button in the order email, order listing page and in the order summary.",'wf-woocommerce-packing-list'),
			'form_toggler'=>array(
				'type'=>'parent',
				'target'=>'wf_enable_print_button',
			)
		),
		array(
			'type'=>'order_st_multiselect',
			'label'=>__("Show print button only for statuses",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_attach_".$this->module_base,
			'order_statuses'=>$order_statuses,
			'field_vl'=>$wf_generate_invoice_for,
			'form_toggler'=>array(
				'type'=>'child',
				'id'=>'wf_enable_print_button',
				'val'=>'Yes',
			)
		),
		array(
			'type'=>"radio",
			'label'=>__("Enable variation data",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_packinglist_variation_data",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
		),
		array(
			'type'=>"select",
			'label'=>__("Total tax column display options", 'wf-woocommerce-packing-list'),
			'option_name'=>"wt_pklist_total_tax_column_display_option",
			'select_fields'=>array(
				'amount'=>__('Amount', 'wf-woocommerce-packing-list'),
				'rate'=>__('Rate (%)', 'wf-woocommerce-packing-list'),
				'amount-rate'=>__('Rate (%) with Amount', 'wf-woocommerce-packing-list'),
			),
			'help_text'=>sprintf(__("Choose %s how to display total tax column %s in the invoice", 'wf-woocommerce-packing-list'),'<a href="'.esc_url($tax_doc_url).'" target="_blank">', '</a>')
		),
		array(
			'type'=>"radio",
			'label'=>__("Show individual tax column in product table",'wf-woocommerce-packing-list'),
			'option_name'=>"wt_pklist_show_individual_tax_column",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
			'help_text'=>sprintf(__("From 'Customize' tab choose a template that supports the individual tax column. %s Learn more. %s ",'wf-woocommerce-packing-list'),'<a href="'.esc_url($individual_tax_doc_url).'" target="_blank">', '</a>'),
			'form_toggler'=>array(
				'type'=>'parent',
				'target'=>'individual_tax_column',
			)
		),
		array(
			'type'=>"select",
			'label'=>__("Tax column display options", 'wf-woocommerce-packing-list'),
			'option_name'=>"wt_pklist_individual_tax_column_display_option",
			'select_fields'=>array(
				'amount'=>__('Amount', 'wf-woocommerce-packing-list'),
				'rate'=>__('Rate (%)', 'wf-woocommerce-packing-list'),
				'amount-rate'=>__('Rate (%) with Amount', 'wf-woocommerce-packing-list'),
				'separate-column'=>__('Separate columns for Rate (%) and Amount', 'wf-woocommerce-packing-list'),
			),
			'help_text'=>__("Choose how to display tax column in the invoice", 'wf-woocommerce-packing-list'),
			'form_toggler'=>array(
				'type'=>'child',
				'id'=>'individual_tax_column',
				'val'=>'Yes',
			)
		),
		array(
			'type'=>"uploader",
			'label'=>__("Upload signature",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_packinglist_invoice_signature",
		),
		array(
			'type'=>"uploader",
			'label'=>__("Custom logo for invoice",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_packinglist_logo",
			'help_text'=>__('If left blank, defaulted to logo from General settings.','wf-woocommerce-packing-list'),
		),
		array(
			'type'=>"radio",
			'label'=>__("Use latest settings for invoice",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_use_latest_settings_invoice",
			'radio_fields'=>array(
				'Yes'=>__('Yes','wf-woocommerce-packing-list'),
				'No'=>__('No','wf-woocommerce-packing-list')
			),
			'help_text_conditional'=>array(
	                array(
	                	'help_text'=>__("Choose ‘Yes’  to apply the most recent settings to previous order invoices. This will match the previous invoices with the upcoming invoices. <br>Caution: Changing the company address, name or any other settings in the future may overwrite previously created invoices with the most up-to-date information.",'wf-woocommerce-packing-list'),
	                    'condition'=>array(
	                        array('field'=>'woocommerce_wf_use_latest_settings_invoice', 'value'=>'Yes')
	                    )
	                ),
	                array(
	                	'help_text'=>__("If you choose ‘No,' the previous invoices will not be updated to the latest settings.",'wf-woocommerce-packing-list'),
	                    'condition'=>array(
	                        array('field'=>'woocommerce_wf_use_latest_settings_invoice', 'value'=>'No')
	                    )
	                )
	            ),
		),
	),$this->module_id);
	?>
</table>
<?php 
include plugin_dir_path( WF_PKLIST_PLUGIN_FILENAME )."admin/views/admin-settings-save-button.php";
?>
</div>