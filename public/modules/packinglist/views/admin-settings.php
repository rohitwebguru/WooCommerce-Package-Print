<?php
if (!defined('ABSPATH')) {
    exit;
}
$tab_items = array(
    "general" => __("General", 'wf-woocommerce-packing-list'),
    "custom_parameter" => __("Custom Parameter", 'wf-woocommerce-packing-list'),
);
// print_r($tab_items );die;
?>
<div class="wrap">
	<h2 class="wp-heading-inline">
	<?php _e('Settings', 'wf-woocommerce-packing-list');?>: <?php _e('Packing slip', 'wf-woocommerce-packing-list');?>
	</h2>
	<div class="nav-tab-wrapper wp-clearfix wf-tab-head">
	<?php Wf_Woocommerce_Packing_List::generate_settings_tabhead($tab_items, 'module');?>
	</div>
	<div class="wf-tab-container">
		<?php
//inside the settings form
$setting_views_a = array(
    'general' => 'general.php',
    'custom_parameter' => 'custom_parameter.php',
);

//outside the settings form
$setting_views_b = array(

);
// $setting_views_c = array(
//     'custom_parameter' => 'custom_parameter.php',
// );

?>
        <form method="post" class="wf_settings_form">
            <input type="hidden" value="packinglist" class="wf_settings_base" />
            <?php
// Set nonce:
if (function_exists('wp_nonce_field')) {
    wp_nonce_field('wf-update-invoice-' . WF_PKLIST_POST_TYPE);
}
foreach ($setting_views_a as $target_id => $value) {
    $settings_view = plugin_dir_path(__FILE__) . $value;
    if (file_exists($settings_view)) {
        include $settings_view;
    }
}
?>
            <?php
//settings form fields
do_action('wt_pklist_module_settings_form');?>
        </form>


        <?php
foreach ($setting_views_b as $target_id => $value) {
    $settings_view = plugin_dir_path(__FILE__) . $value;

    print($settings_view);
    if (file_exists($settings_view)) {
        include $settings_view;
    }
}
?>
        <?php do_action('wt_pklist_module_out_settings_form', array(
    'module_id' => $this->module_base,
));?>
	</div>
</div>