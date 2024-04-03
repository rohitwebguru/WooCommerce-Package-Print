<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
if(is_array($printer_arr) && count($printer_arr)>0)
{
?>
	<div class="wt_pklist_cloud_print_test_msg_box">

	</div>
	<table class="wp-list-table widefat fixed striped wt_pklist_cloud_printers_tb">
		<thead>
			<tr>
				<th><?php _e('Display Name', 'wf-woocommerce-packing-list'); ?></th>
				<th><?php _e('Name', 'wf-woocommerce-packing-list'); ?></th>
				<th><?php _e('ID', 'wf-woocommerce-packing-list'); ?></th>
				<th>
					<?php
					_e('Connection Status', 'wf-woocommerce-packing-list');
					echo $tooltip_html=Wf_Woocommerce_Packing_List_Admin::set_tooltip('connection_status', $this->module_id);
					?>
				</th>
				<th>
					<?php
					_e('Actions', 'wf-woocommerce-packing-list');
					echo $tooltip_html=Wf_Woocommerce_Packing_List_Admin::set_tooltip('actions', $this->module_id);
					?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($printer_arr as $printer)
			{
				?>
				<tr>
					<td class="wt_pklist_cloud_print_printer_display_name"><?php echo $printer['display_name'];?></td>
					<td><?php echo $printer['name'];?></td>
					<td><?php echo $printer['id'];?></td>
					<td>
						<?php
						$status_label=__('Offline', 'wf-woocommerce-packing-list');
						$status_color='#ccc';
						if($printer['connection_status']=='ONLINE')
						{
							$status_label=__('Online', 'wf-woocommerce-packing-list');
							$status_color='#35ac19';
						}
						?>
						<span class="wt_pklist_cloud_status_info" style="background:<?php echo $status_color;?>;"></span><span class="wt_pklist_cloud_status_info_text"><?php echo $status_label;?></span>
					</td>
					<td>
						<a class="wt_pklist_cloud_printer_action_btn" onclick="wt_pklist_cloud_print.test_printer(this, '<?php echo $printer['id'];?>', '<?php echo $printer['connection_status'];?>');" <?php echo ($printer['connection_status']!='ONLINE' ? 'style="opacity:.5; cursor:not-allowed;"' : '');?> title="<?php _e('Test printer by printing a sample document', 'wf-woocommerce-packing-list');?>"><?php _e('Test printer', 'wf-woocommerce-packing-list');?></a>
						 | <a class="wt_pklist_cloud_printer_action_btn wt_pklist_cloud_printer_default_toggle_btn" data-id="<?php echo $printer['id'];?>" onclick="wt_pklist_cloud_print.toggle_default_printer(this, '<?php echo $printer['id'];?>');"></a>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
<?php
}else
{
	?>
	<h4 style="text-align:center; background:#fff;">
		<?php _e('No printers found.', 'wf-woocommerce-packing-list') ?> <br />
		<?php _e('Need help?', 'wf-woocommerce-packing-list') ?>
		<?php echo sprintf(__('Click %shere%s to find how to set up your printer with Google Cloud Print', 'wf-woocommerce-packing-list'), '<a href="https://support.google.com/cloudprint/answer/1686197" target="_blank" rel="nofollow">', '</a>'); ?>
	</h4>
	<?php
}
?>