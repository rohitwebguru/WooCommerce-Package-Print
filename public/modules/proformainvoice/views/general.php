<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wf-tab-content" data-id="<?php echo $target_id;?>">
	<p><?php _e('Configure the general settings required for proforma invoice.','wf-woocommerce-packing-list');?>
	<table class="wf-form-table">
	    <?php
	    $order_meta_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#order-meta';
	    $product_meta_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#product-meta';
	    $product_attr_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#product-attribute';

		Wf_Woocommerce_Packing_List_Admin::generate_form_field(array(
			array(
				'type'=>"radio",
				'label'=>__("Use order date as proforma invoice date",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_orderdate_as_invoicedate",
				'help_text'=>__("If you choose 'No' then the proforma invoice date will be the date on which it is generated.",'wf-woocommerce-packing-list'),
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
				'label'=>__("Enable variation data",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_packinglist_variation_data",
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
				'label'=>__("Generate proforma invoice for order statuses",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_generate_for_orderstatus",
				'help_text'=>__("Order statuses for which an proforma invoice should be generated.",'wf-woocommerce-packing-list'),
				'order_statuses'=>$order_statuses,
				'field_vl'=>array_flip($order_statuses),
				'attr'=>'',
			),
			array(
				'type'=>"radio",
				'label'=>__("Attach proforma invoice PDF in email",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_add_".$this->module_base."_in_mail",
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				),
				'help_text'=>__('PDF version of proforma invoice will be attached with the order email based on the above statuses','wf-woocommerce-packing-list'),		
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
				'label'=>__("Order statuses to show print button",'wf-woocommerce-packing-list'),
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
				'type'=>"additional_fields",
				'label'=>__("Order meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_contactno_email',
				'module_base'=> $this->module_base,
				'help_text'=>sprintf(__('Select/add additional order information in the proforma invoice.%s Learn how to add order meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($order_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_meta",
				'label'=>__("Product meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_product_meta_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product meta will be displayed beneath the respective product in the proforma invoice.<br> %s Learn how to add product meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($product_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_attribute",
				'label'=>__("Product attributes", 'wf-woocommerce-packing-list'),
				'option_name'=>'wt_'.$this->module_base.'_product_attribute_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product attribute will be displayed beneath the respective product in the proforma invoice.<br> %s Learn how to add product attribute %s', 'wf-woocommerce-packing-list'),'<a href="'.esc_url($product_attr_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"textarea",
				'label'=>__("Custom footer for proforma invoice",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_packinglist_footer",
				'help_text'=>__('If left blank, defaulted to footer from General settings.','wf-woocommerce-packing-list'),
			),
			array(
				'type'=>'textarea',
				'label'=>__("Special notes",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_packinglist_special_notes",
			),
			array(
				'type'=>"radio",
				'label'=>__("Show individual tax column in product table",'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_show_individual_tax_column",
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				),
				'help_text'=>__("Your template must support tax columns",'wf-woocommerce-packing-list'),
			),
		),$this->module_id);
		?>
	</table>
	<?php 
    include plugin_dir_path( WF_PKLIST_PLUGIN_FILENAME )."admin/views/admin-settings-save-button.php";
    ?>
</div>