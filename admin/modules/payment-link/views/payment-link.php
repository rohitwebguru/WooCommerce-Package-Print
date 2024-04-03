<?php
if (!defined('ABSPATH')) {
	exit;
}
$order_statuses = array(
    'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
    'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
    'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
);
?>
<div class="wf-tab-content" data-id="<?php echo $target_id;?>">
<p> 
	<?php echo __('Adds a payment link in your invoice to let customers pay later. Upon clicking on the payment link, your customers will be redirected to your store\'s payment/checkout page.', 'wf-woocommerce-packing-list'); ?>
</p>

<form method="post" class="wf_settings_form">
<?php
    // Set nonce:
    if (function_exists('wp_nonce_field'))
    {
        wp_nonce_field(WF_PKLIST_PLUGIN_NAME);
    }
?>
<input type="hidden" class="wf_settings_base" value="<?php echo $to_module;?>">
<table class="wf-form-table">
	<?php
	Wf_Woocommerce_Packing_List_Admin::generate_form_field(array(
		array(
			'type' => 'checkbox',
			'label' => __('Show payment link on invoice','wf-woocommerce-packing-list'),
			'option_name' => "woocommerce_wf_enable_payment_link_in_invoice",
			'form_toggler'=>array(
				'type'=>'parent',
				'target'=>'wf_paylink_for_order_status',
			),
			'help_text'=> __("Adds a payment link beside the payment method on the invoice. Ensure to choose a template from the ‘Customize’ tab that supports the payment link.","wf-woocommerce-packing-list"),
		),
		array(
			'type'=>'order_st_multiselect',
			'label'=>__("Choose order status",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_payment_link_in_order_status",
			'order_statuses'=>$order_statuses,
			'field_vl'=>array_flip($order_statuses),
			'form_toggler'=>array(
				'type'=>'child',
				'id'=>'wf_paylink_for_order_status',
				'val'=>'1',
			),
			'help_text'=> __("Adds payment link for selected order statuses. Even if nothing is selected, 'Pending payment' status will be considered.","wf-woocommerce-packing-list"),
		),
		array(
			'type' => 'checkbox',
			'label' => __('Show ‘Pay Later’ at the checkout','wf-woocommerce-packing-list'),
			'option_name' => "woocommerce_wf_show_pay_later_in_checkout",
			'help_text'=> __("Enable to show pay later option at the checkout.","wf-woocommerce-packing-list"),
		),
		array(
			'label'=>__("Title",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_pay_later_title",
		),
		array(
			'type' => 'textarea',
			'label'=>__("Description",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_pay_later_description",
		),
		array(
			'type' => 'textarea',
			'label'=>__("Instruction",'wf-woocommerce-packing-list'),
			'option_name'=>"woocommerce_wf_pay_later_instuction",
		),
	),$to_module_id);
	?>
</table>
<?php
$settings_button_title=sprintf(__('Save %s settings', 'wf-woocommerce-packing-list'), $to_module_title);
include plugin_dir_path( WF_PKLIST_PLUGIN_FILENAME )."admin/views/admin-settings-save-button.php";
?>
</form>
</div>