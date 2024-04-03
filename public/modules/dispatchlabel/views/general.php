<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wf-tab-content" data-id="<?php echo $target_id;?>">
	<p><?php _e('Configure the general settings required for dispatch label.','wf-woocommerce-packing-list');?>
	<table class="wf-form-table">
	    <?php
	    $order_meta_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#order-meta';
	    $product_meta_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#product-meta';
	    $product_attr_doc_url = 'https://www.webtoffee.com/add-custom-fields-to-woocommerce-documents/#product-attribute';
	    
		Wf_Woocommerce_Packing_List_Admin::generate_form_field(array(
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
				'type'=>"additional_fields",
				'label'=>__("Order meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_contactno_email',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Select/add additional order information in the dispatch label.%s Learn how to add order meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($order_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_meta",
				'label'=>__("Product meta fields",'wf-woocommerce-packing-list'),
				'option_name'=>'wf_'.$this->module_base.'_product_meta_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product meta will be displayed beneath the respective product in the dispatch label.<br> %s Learn how to add product meta %s','wf-woocommerce-packing-list'),'<a href="'.esc_url($product_meta_doc_url).'" target="_blank">', '</a>'),
			),
			array(
				'type'=>"product_attribute",
				'label'=>__("Product attributes", 'wf-woocommerce-packing-list'),
				'option_name'=>'wt_'.$this->module_base.'_product_attribute_fields',
				'module_base'=>$this->module_base,
				'help_text'=>sprintf(__('Selected product attribute will be displayed beneath the respective product in the dispatch label.<br> %s Learn how to add product attribute %s', 'wf-woocommerce-packing-list'),'<a href="'.esc_url($product_attr_doc_url).'" target="_blank">', '</a>'),
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
			array(
				'type'=>'order_st_multiselect',
				'label'=>__("Enable print dispatch label option for order status",'wf-woocommerce-packing-list'),
				'option_name'=>"woocommerce_wf_attach_".$this->module_base,
				'help_text'=>__("Adds print dispatch label button to the order email for chosen status",'wf-woocommerce-packing-list'),
				'order_statuses'=>$order_statuses,
				'field_vl'=>array_flip($order_statuses),
			),
		),$this->module_id);
		?>
	</table>
	<?php 
    include plugin_dir_path( WF_PKLIST_PLUGIN_FILENAME )."admin/views/admin-settings-save-button.php";
    ?>
</div>