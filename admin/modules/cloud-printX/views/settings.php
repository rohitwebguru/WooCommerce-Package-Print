<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style type="text/css">
.wt_pklist_cloud_setup_printer{ cursor:pointer; }
.wt_pklist_cloud_connect_status .wt_pklist_cloud_setup_printer{ margin-left:15px; margin-right:0px; margin-top:-14px; float:right;}
.wt_pklist_cloud_printers{ display:none; }
.wt_pklist_cloud_printers_tb td{ text-align:left; }
.wt_pklist_cloud_printer_action_btn{ cursor:pointer; }
.wt_pklist_cloud_status_info{ float:left; width:15px; height:15px; border-radius:10px; margin-right:5px; }
.wt_pklist_cloud_status_info_text{ float:left; margin-top:-2px; }
.wt_pklist_cloud_print_test_msg_box{margin-bottom: 15px;}
.wt_pklist_cloud_print_default_printer_info{ color:green; }
.wt_pklist_cloud_print_update_after_connect_infobox{ <?php echo ($connection_status ? '' : 'display:none;');?> }
</style>

<form method="post" class="wf_settings_form">
	
	<div class="wt_warn_box">
	    <?php echo sprintf(__("%s Note %s: Google, after December 31st, 2020, will no longer support Cloud Print. Learn more %s here %s. We have launched an alternative service in tie up with PrintNode, try out the product %s here %s.", 'wf-woocommerce-packing-list'), '<b>', '</b>', '<a href="https://support.google.com/chrome/a/answer/9633006" target="_blank">', '</a>', '<a href="https://www.webtoffee.com/product/remote-print-woocommerce-pdf-invoice-printnode/" target="_blank">', '</a>'); ?>    		
	</div>


	<h3 style="border-bottom:dashed 1px #ccc; padding-bottom:5px;"><?php _e('Google account', 'wf-woocommerce-packing-list'); ?></h3>
	<p>
		<?php echo sprintf(__("To have a Cloud Print, you will need to obtain client ID and client secret from Google developer console. Refer %sGoogle developer documentation%s to know more.", 'wf-woocommerce-packing-list'), '<a href="https://support.google.com/cloud/answer/6158849" target="_blank">','</a>'); ?> <br />
		<?php echo sprintf(__('Click %shere%s to find how to set up your printer with Google Cloud Print', 'wf-woocommerce-packing-list'), '<a href="https://support.google.com/cloudprint/answer/1686197" target="_blank" rel="nofollow">', '</a>'); ?>
	</p>
	<p class="wt_pklist_cloud_print_update_after_connect_infobox">
		<?php _e('If you need to update the credentials, please disconnect from the existing connection then update the credentials and authenticate again.', 'wf-woocommerce-packing-list'); ?>
	</p>
	<table class="wf-form-table">
		<?php
		$readonly_attr='readonly="readonly" style="background:#f6f6f6;"';
		$readonly_on_connected=($connection_status ? $readonly_attr : '');
		self::generate_form_field(array(
			array(
				'label'=>__("Client ID",'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_client_id",
				'help_text'=>__('Specify the Client ID obtained from Google developer console for authentication.', 'wf-woocommerce-packing-list'),
				'attr'=>$readonly_on_connected,
			),
			array(
				'label'=>__("Client secret", 'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_client_secret",
				'help_text'=>__('Specify the Client Secret obtained from Google developer console for authentication.', 'wf-woocommerce-packing-list'),
				'attr'=>$readonly_on_connected,
			),
			array(
				'label'=>__("Redirect URI", 'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_client_redirect_url",
				'attr'=>$readonly_attr,
				'help_text'=>__("Use this redirect URI when setting up Google API credentials."),
			),
		), $module_id);
		?>
		<tr>
			<th style="padding-top:0px;"></th>
			<td style="padding-top:0px;">
				<button type="button" name="wt_pklist_cloud_connect" class="button button-primary" data-action="<?php echo $btn_action;?>" style="float:right; margin-top:-5px;"><?php echo $btn_label; ?></button>
				<span class="wt_pklist_cloud_connect_status" style="float:right; margin-right:15px; margin-top:9px;"><?php echo $connection_info_html; ?></span>
			</td>
			<td></td>
		</tr>
	</table>

	<div class="wt_pklist_cloud_printers" style="display:block">
	</div>

	<h3 style="border-bottom:dashed 1px #ccc; padding-bottom:5px; margin-top:30px;"><?php _e('Settings', 'wf-woocommerce-packing-list'); ?></h3>

	<table class="wf-form-table">
		<?php
		$form_fields=array(
			array(
				'type'=>'radio',
				'label'=>__("Enable manual printing",'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_manual",
				'help_text'=>__("Choose ‘Yes’ to have a ‘Cloud Print’ button in the WooCommerce Orders page.", 'wf-woocommerce-packing-list'),
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No', 'wf-woocommerce-packing-list')
				)
			),
			array(
				'type'=>'radio',
				'label'=>__("Enable automatic printing",'wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_automatic",
				'help_text'=>__("Prints the document automatically whenever an order status is updated. Ensure that a default printer is set up for this to work.", 'wf-woocommerce-packing-list'),
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				),
				'form_toggler'=>array(
					'type'=>'parent',
					'target'=>'wt_pklist_cloud_print_automatic',
				)
			),
			array(
				'type'=>'radio',
				'label'=>__('Notify by email','wf-woocommerce-packing-list'),
				'option_name'=>"wt_pklist_cloud_print_email_notification",
				'help_text'=>__("Failure to print due to any reasons will be notified to the site admin via email upon enabling this option.", 'wf-woocommerce-packing-list'),
				'radio_fields'=>array(
					'Yes'=>__('Yes','wf-woocommerce-packing-list'),
					'No'=>__('No','wf-woocommerce-packing-list')
				),
				'form_toggler'=>array(
					'type'=>'child',
					'val'=>'Yes',
					'id'=>'wt_pklist_cloud_print_automatic',
				)
			)
		);

		if(is_array($documents))
		{
			foreach ($documents as $doc_key => $doc_title)
			{
				$form_fields[]=array(
					'type'=>'order_st_multiselect',
					'label'=>$doc_title,
					'option_name'=>"wt_pklist_cloud_print_automatic_{$doc_key}_statuses",
					'order_statuses'=>$order_statuses,
					'field_vl'=>array_flip($order_statuses),
					'help_text'=>sprintf(__('Choose the order statuses for which the %s has to be printed.', 'wf-woocommerce-packing-list'), $doc_title),
					'form_toggler'=>array(
						'type'=>'child',
						'val'=>'Yes',
						'id'=>'wt_pklist_cloud_print_automatic',
					)
				);
			}
		}
		self::generate_form_field($form_fields, $module_id);
		?>
	</table>

	<input type="hidden" value="<?php echo $module_base;?>" class="wf_settings_base" />
	<?php
	self::add_settings_footer();
	?>
</form>