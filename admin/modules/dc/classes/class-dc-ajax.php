<?php
/**
 * Template customizer ajax
 *
 * @link       
 * @since 4.0.0     
 *
 * @package  Wf_Woocommerce_Packing_List  
 */
if (!defined('ABSPATH')) {
    exit;
}
class Wf_Woocommerce_Packing_List_Dc_Ajax
{
	public $default_template=1;
	private static $instance=null;
	

	public static function get_instance()
	{
		if(self::$instance==null)
		{
			self::$instance=new Wf_Woocommerce_Packing_List_Dc_Ajax();
		}
		return self::$instance;
	}

	public function update_settings()
	{
		$template_type=isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : '';
		$out=array(
			'status'=>0,
			'msg'=>__("Unable to update settings.", 'wf-woocommerce-packing-list'),
		);
		if($template_type!="")
		{
			$dc=Wf_Woocommerce_Packing_List_Dc::get_instance();
			if(isset($dc->documents[$template_type])) /* DC enabled docs only */
			{
				//for security we are limiting the options that can editable by DC
				$allowed_options=array('woocommerce_wf_packinglist_logo', 'woocommerce_wf_packinglist_invoice_signature');

				$option_name=(isset($_POST['option_name']) ? sanitize_text_field($_POST['option_name']) : '');
				$option_value=(isset($_POST['option_value']) ? sanitize_text_field($_POST['option_value']) : '');
				if($option_name!="" && $option_value!="")
				{
					if(in_array($option_name, $allowed_options))
					{
						Wf_Woocommerce_Packing_List::update_option($option_name, $option_value, Wf_Woocommerce_Packing_List::get_module_id($template_type));
						$out['status']=1;
						$out['msg']=__("Success", 'wf-woocommerce-packing-list');
					}
				}
			}
		}
		return $out;
	}

}