<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wf-tab-content" data-id="<?php echo $target_id;?>">
	<p>
		<?php _e('The below settings can be used to configure additional information with respect to order/product meta, product attribute.','wf-woocommerce-packing-list');?>
	</p>
	<table class="wf-form-table">
	    <?php
	    $order_meta_doc_url = 'https://www.webtoffee.com/adding-additional-fields-pdf-invoices-woocommerce/#add-order-meta';
	    $product_meta_doc_url = 'https://www.webtoffee.com/adding-additional-fields-pdf-invoices-woocommerce/#add-product-meta';
	    $product_attr_doc_url = 'https://www.webtoffee.com/adding-additional-fields-pdf-invoices-woocommerce/#add-product-attribute';
	    
		Wf_Woocommerce_Packing_List_Admin::generate_form_field(array(
			array(
				'type'=>"additional_fields",
				'label'=>__("Order meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_contactno_email',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Select/add the additional order information in the invoice.%s Learn how to add order meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($order_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_meta",
				'label'=>__("Product meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_product_meta_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product meta will be displayed beneath the respective product in the invoice. <br> %s Learn how to add product meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($product_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_attribute",
				'label'=>__("Product attributes", 'wf-woocommerce-packing-list'),
				'option_name'=>'wt_'.$this->module_base.'_product_attribute_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product attribute will be displayed beneath the respective product in the invoice.%s Learn how to add product attribute %s', 'wf-woocommerce-packing-list'),'<a href="'.esc_url($product_attr_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"textarea",
				'label'=>__("Custom footer for invoice",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_packinglist_footer",
				'help_text'=>__('If left blank, defaulted to footer from General settings.','wf-woocommerce-packing-list'),
			),
			array(
				'type'=>"select",
				'label'=>__("Bundled product display options",'wf-woocommerce-packing-list'),
				'option_name'=>"bundled_product_display_option",
				'help_text'=>sprintf(__('Choose how to display bundled products in the invoice. Applicable only if you are using %s Woocommerce Product Bundles %s / %s YITH WooCommerce Product Bundle add-on %s. It may not work along with %sGroup by Category%s option.','wf-woocommerce-packing-list'), '<b>', '</b>', '<b>', '</b>', '<b>', '</b>' ),
				'select_fields'=>array(
					'main-sub'=>__('Main product with bundle items', 'wf-woocommerce-packing-list'),
					'main'=>__('Main product only', 'wf-woocommerce-packing-list'),
					'sub'=>__('Bundle items only', 'wf-woocommerce-packing-list'),
				),
				'help_text_conditional'=>array(
	                array(
	                    'help_text'=>'<img src="'.WF_PKLIST_PLUGIN_URL.'assets/images/bundle-both-items.png"/>',
	                    'condition'=>array(
	                        array('field'=>'bundled_product_display_option', 'value'=>'main-sub')
	                    )
	                ),
	                array(
	                    'help_text'=>'<img src="'.WF_PKLIST_PLUGIN_URL.'assets/images/bundle-parent-only.png"/>',
	                    'condition'=>array(
	                        array('field'=>'bundled_product_display_option', 'value'=>'main')
	                    )
	                ),
	                array(
	                    'help_text'=>'<img src="'.WF_PKLIST_PLUGIN_URL.'assets/images/bundle-child-only.png"/>',
	                    'condition'=>array(
	                        array('field'=>'bundled_product_display_option', 'value'=>'sub')
	                    )
	                ),
	            ),
			),
			array(
				'type'=>"radio",
				'label'=>__("Generate invoice for free orders",'wf-woocommerce-packing-list'),
				'option_name'=>"wf_woocommerce_invoice_free_orders",
				'help_text'=>__("Choose ‘No’ to not generate invoice for free orders.",'wf-woocommerce-packing-list'),
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				)
			),
			array(
				'type'=>"radio",
				'label'=>__("Display free line items in the invoice",'wf-woocommerce-packing-list'),
				'option_name'=>"wf_woocommerce_invoice_free_line_items",
				'help_text'=>__("Choose ‘No’ to not display free line items in the invoice.",'wf-woocommerce-packing-list'),
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				)
			),
			array(
				'type' => "pdf_name_select",
				'label' => __("PDF name format", 'wf-woocommerce-packing-list'),
				'option_name' => 'woocommerce_wf_custom_pdf_name',
				'help_text'=>__("Select a name format for PDF invoice that includes invoice/order number.",'wf-woocommerce-packing-list'),
			),
			array(
				'type' => "pdf_name_prefix",
				'label' => __("Custom PDF Name Prefix", 'wf-woocommerce-packing-list'),
				'option_name' => 'woocommerce_wf_custom_pdf_name_prefix',
				'pdf_name_prefix_label' => 'yes',
				'help_text'=>__("Input a custom prefix for ‘PDF name format’ that will appear at the beginning of the name. Defaulted to ‘Invoice_’.",'wf-woocommerce-packing-list'),
			),
		), $this->module_id);
		?>
	</table>
	<?php 
    include plugin_dir_path( WF_PKLIST_PLUGIN_FILENAME )."admin/views/admin-settings-save-button.php";
    ?>
</div>