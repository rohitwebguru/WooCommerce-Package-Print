<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt_pklist_custom_field_form" style="display:none;">
	<div class="wt_pklist_checkout_field_tab">
		<div class="wt_pklist_custom_field_tab_head active_tab" data-target="add_new" data-add-title="<?php _e('Add new', 'wf-woocommerce-packing-list');?>" data-edit-title="<?php _e('Edit','wf-woocommerce-packing-list');?>">
			<span class="wt_pklist_custom_field_tab_head_title"> <?php _e('Add new', 'wf-woocommerce-packing-list');?></span>
			<div class="wt_pklist_custom_field_tab_head_patch"></div>
		</div>
		<div class="wt_pklist_custom_field_tab_head" data-target="list_view">
			<?php _e('Previously added', 'wf-woocommerce-packing-list');?>
			<div class="wt_pklist_custom_field_tab_head_patch"></div>		
		</div>
		<div class="wt_pklist_custom_field_tab_head_add_new" title="<?php _e('Add new', 'wf-woocommerce-packing-list');?>">
			<span class="dashicons dashicons-plus"></span>
		</div>
	</div>
	<div class="wt_pklist_custom_field_tab_content active_tab" data-id="add_new">
    	<div class='wt_pklist_custom_field_tab_form_row wt_pklist_custom_field_form_notice'>
    		<?php _e('You can edit an existing item by using its key.', 'wf-woocommerce-packing-list');?>
    	</div>
    	<div class='wt_pklist_custom_field_tab_form_row'>
			<div style='width:48%; float:left;'><?php _e('Field Name', 'wf-woocommerce-packing-list'); ?><i style="color:red;">*</i>: <input type='text' name='wt_pklist_new_custom_field_title' data-required="1" style='width:100%'/></div>
			<div style='width:48%; float:right;'><?php _e('Meta Key', 'wf-woocommerce-packing-list'); ?><i style="color:red;">*</i>: <input type='text' name='wt_pklist_new_custom_field_key' data-required="1" style='width:100%'/> </div>
		</div>
		<div class='wt_pklist_custom_field_tab_form_row' style="height:25px;">

		</div>
	</div>
	<div class="wt_pklist_custom_field_tab_content" data-id="list_view" style="height:155px; overflow:auto;">
		
	</div>
</div>