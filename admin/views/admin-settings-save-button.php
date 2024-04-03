<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
$settings_button_title=isset($settings_button_title) && $settings_button_title!="" ? $settings_button_title : __('Update Settings', 'wf-woocommerce-packing-list');

/** 
* 	@since 4.1.4
*	left and right HTML for settings footer 
*/
$settings_footer_left=isset($settings_footer_left) ? $settings_footer_left : '';
$settings_footer_right=isset($settings_footer_right) ? $settings_footer_right : '';
?>
<div style="clear: both;"></div>
<div class="wf-plugin-toolbar bottom">
    <div class="left">
    	<?php echo $settings_footer_left;?>
    </div>
    <div class="right">
        <input type="submit" name="update_admin_settings_form" value="<?php echo $settings_button_title; ?>" class="button button-primary" style="float:right;"/>
        <?php echo $settings_footer_right;?>
        <span class="spinner" style="margin-top:11px;"></span>
    </div>
</div>