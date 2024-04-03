<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}	
?>
<table class="wp-list-table widefat fixed striped wt_pklist_licence_table">
	<thead>
		<tr>
			<th><?php _e('Licence key', 'wf-woocommerce-packing-list'); ?></th>
			<th style="width:150px;"><?php _e('Email', 'wf-woocommerce-packing-list'); ?></th>
			<th style="width:100px;"><?php _e('Status', 'wf-woocommerce-packing-list'); ?></th>
			<th><?php _e('Product', 'wf-woocommerce-packing-list'); ?></th>
			<th style="width:150px;"><?php _e('Actions', 'wf-woocommerce-packing-list'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if(count($licence_data_arr)>0)
		{
			$i=0;
			foreach ($licence_data_arr as $product_slug=>$licence_data)
			{
				$i++;
				?>
				<tr class="licence_tr" data-product="<?php echo $product_slug; ?>">
					<td>
						<?php echo $this->mask_licence_key($licence_data['key']); ?>						
					</td>
					<td><?php echo $licence_data['email']; ?></td>
					<td class="status_td" data-status="<?php echo $licence_data['status'];?>"><?php echo $this->get_status_label($licence_data['status']); ?></td>
					<td>
						<?php
						echo $licence_data['products'];
						?>	
					</td>
					<td class="action_td">
						<?php 
						$button_label=($licence_data['status']=='active' ? 'Deactivate' : 'Delete');
						$button_action=($licence_data['status']=='active' ? 'deactivate' : 'delete');
						?>
						<button type="button" class="button button-secondary wt_pklist_licence_deactivate_btn" data-product="<?php echo $product_slug; ?>" data-action="<?php echo $button_action;?>"><?php _e($button_label, 'wf-woocommerce-packing-list');?></button>				
					</td>
				</tr>
				<?php
			}
		}else
		{
			?>
			<tr>
				<td colspan="5" style="text-align:center;"><?php _e("No Licence details found.", 'wf-woocommerce-packing-list'); ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>