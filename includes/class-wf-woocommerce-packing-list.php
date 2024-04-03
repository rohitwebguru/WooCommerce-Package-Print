<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.webtoffee.com/
 * @since      4.0.0
 *
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      4.0.0
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/includes
 * @author     WebToffee <info@webtoffee.com>
 */
if(!class_exists('Wf_Woocommerce_Packing_List'))
{


class Wf_Woocommerce_Packing_List {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      Wf_Woocommerce_Packing_List_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;


	private static $stored_options=array();

	public static $no_image="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";

	public static $template_data_tb='wfpklist_template_data';

	public static $default_additional_checkout_data_fields=array(
        'ssn'=>'SSN',
        'vat'=>'VAT',
    );


    public static $default_additional_data_fields=array(
        'contact_number' => 'Contact Number',
        'email' => 'Email',
        'ssn' => 'SSN',
        'vat' => 'VAT',
        'vat_number' => 'VAT',
        'eu_vat_number' => 'VAT',
        'cus_note' => 'Customer Note',
    );

    /**
    *	@since 4.1.1
    * 	default fields without _billing_ prefix
    */
    public static $default_fields_no_prefix=array(
    	'ssn', 'vat', 'vat_number', 'eu_vat_number'
    );

    public static $wf_packinglist_brand_color='080808';
    public static $loaded_modules=array();

    /**
    *	Current running data.
    *
    */
    public static $running_data=array();

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    4.0.0
	 */
	public function __construct()
	{
		if( defined( 'WF_PKLIST_VERSION' ) )
		{
			$this->version = WF_PKLIST_VERSION;
		}else
		{
			$this->version = '4.2.1';
		}
		if(defined('WF_PKLIST_PLUGIN_NAME'))
		{
			$this->plugin_name=WF_PKLIST_PLUGIN_NAME;
		}else
		{
			$this->plugin_name='wf-woocommerce-packing-list';
		}
		$this->load_dependencies();
		$this->set_locale();
		$this->define_common_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wf_Woocommerce_Packing_List_Loader. Orchestrates the hooks of the plugin.
	 * - Wf_Woocommerce_Packing_List_i18n. Defines internationalization functionality.
	 * - Wf_Woocommerce_Packing_List_Admin. Defines all hooks for the admin area.
	 * - Wf_Woocommerce_Packing_List_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wf-woocommerce-packing-list-loader.php';


		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wf-woocommerce-packing-list-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wf-woocommerce-packing-list-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wf-woocommerce-packing-list-public.php';

		/**
		 * The class responsible for pay later payment method to add the pay link in invoice
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wf-woocommerce-packing-list-pay-later-payment.php';

		$this->loader = new Wf_Woocommerce_Packing_List_Loader();
		$this->plugin_admin = new Wf_Woocommerce_Packing_List_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->plugin_public = new Wf_Woocommerce_Packing_List_Public( $this->get_plugin_name(), $this->get_version() );
		$this->plugin_pay_later_payment = new Wf_Woocommerce_Packing_List_Pay_Later_Payment( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wf_Woocommerce_Packing_List_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wf_Woocommerce_Packing_List_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	*	@since 4.0.0 Some necessary functions
	*	@since 4.0.9 Added language swicthing
	*/
	private function define_common_hooks()
	{
		$this->loader->add_action('init', $this, 'run_necessary', 1); //run some necessary function copied from old plugin

		$this->loader->add_filter('locale', $this, 'switch_locale', 1);

	}

	/**
	*	@since 4.0.9 Swicth language on printing screen
	*/
	public function switch_locale($locale)
	{
		if(isset($_GET['print_packinglist']))
        {
        	$lang=(isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '');
            $lang_list=$this->plugin_admin->get_language_list();
            if($lang!="" && isset($lang_list[$lang])) /* valid language code */
            {
            	//$site_langs=get_available_languages();
            	//if(in_array($lang, $site_langs)) /* available site languages */
            	//{
            		$locale=$lang;
            	//}
            }
        }
        return $locale;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		//ajax hook for saving settings, Includes plugin main settings and settings from module
		$this->loader->add_action('wp_ajax_wf_save_settings', $this->plugin_admin, 'save_settings');

		//hook for print checkout field list view in popup
		$this->loader->add_action('wp_ajax_wt_pklist_custom_field_list_view',$this->plugin_admin,'custom_field_list_view');

		//hook for pulling address from Woo address section
		$this->loader->add_action('wp_ajax_wf_pklist_load_address_from_woo', $this->plugin_admin, 'load_address_from_woo');

		//ajax hook for downloading temp files
		$this->loader->add_action('wp_ajax_wt_pklist_download_all_temp', $this->plugin_admin, 'download_all_temp');

		//ajax hook for deleting temp files
		$this->loader->add_action('wp_ajax_wt_pklist_delete_all_temp', $this->plugin_admin, 'delete_all_temp');

		//ajax hook for save instruction
		$this->loader->add_action('wp_ajax_wt_pklist_save_intructions', $this->plugin_admin, 'save_intructions');

		//ajax hook for delete instruction
		$this->loader->add_action('wp_ajax_wt_pklist_delete_instruction', $this->plugin_admin, 'delete_instruction');

		//ajax hook for delete condition
		$this->loader->add_action('wp_ajax_wt_pklist_delete_condition', $this->plugin_admin, 'delete_condition');

		//ajax hook for save condition
		$this->loader->add_action('wp_ajax_wt_pklist_save_condition', $this->plugin_admin, 'save_condition');

		//registering new time interval for temp file deleting cron
		$this->loader->add_filter('cron_schedules', $this->plugin_admin, 'cron_interval_for_temp');//registering new time interval for temp file deleting cron

		//hook for temp files clearing cron
		$this->loader->add_action('wt_pklist_auto_clear_temp_files', $this->plugin_admin, 'delete_temp_files_recrusively');

		//hook for registering cron for temp files clearing
		$this->loader->add_action('init', $this->plugin_admin, 'schedule_temp_file_clearing');

		$this->loader->add_action('admin_menu', $this->plugin_admin, 'admin_menu',11); /* Adding admin menu */
		$this->loader->add_action('add_meta_boxes',$this->plugin_admin, 'add_meta_boxes',11); /* Add print option metabox in order page */

		//saving hook for debug tab
		$this->loader->add_action('admin_init',$this->plugin_admin,'debug_save');

		// Add plugin settings link:
		$this->loader->add_filter('plugin_action_links_'.plugin_basename(WF_PKLIST_PLUGIN_FILENAME),$this->plugin_admin,'plugin_action_links');

		//print action button and dropdown items
		$this->loader->add_filter('woocommerce_admin_order_actions', $this->plugin_admin, 'add_print_action_button', 10, 2); //to add print option in the order list page action column
		$this->loader->add_action('manage_shop_order_posts_custom_column',$this->plugin_admin,'add_print_actions',10); /* Add print action buttons to action column order list page */

		$this->loader->add_filter('bulk_actions-edit-shop_order',$this->plugin_admin,'alter_bulk_action',10); /* Add print buttons to order bulk actions */

		//frontend print action buttons
		$this->loader->add_action('woocommerce_order_details_after_order_table', $this->plugin_admin, 'add_order_detail_page_print_actions', 10); /* Add print action buttons in user dashboard order detail page */
		$this->loader->add_filter('woocommerce_my_account_my_orders_actions', $this->plugin_admin, 'add_order_list_page_print_actions', 10, 2); /* Add print action buttons in user dashboard orders page */

		//email print action buttons
		$this->loader->add_action('woocommerce_email_after_order_table',$this->plugin_admin,'add_email_print_actions', 10, 4); /* Add print action buttons in order */

		//email attachment
		$this->loader->add_filter('woocommerce_email_attachments',$this->plugin_admin,'add_email_attachments',10,3); /* Add pdf attachments to order email */

		$this->loader->add_filter('woocommerce_checkout_fields',$this->plugin_admin,'add_checkout_fields'); /* Add additional checkout fields */

		/**
		* @since 4.0.9 Show additional checkout fields in order detail page
		*/
		$this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $this->plugin_admin, 'additional_checkout_fields_in_order_detail_page');


		$this->loader->add_action('init', $this->plugin_admin, 'print_window', 10); /* to print the invoice and packinglist */

		/**
		* @since 4.0.5 Fields like `Order meta fields`, `Product meta fields` etc have extra popup for saving item.
		*/
		$this->loader->add_action('wp_ajax_wf_pklist_advanced_fields', $this->plugin_admin, 'advanced_settings', 10); /* to print the invoice and packinglist */

		/**
		*	@since 4.0.6 Hook for downloading temp zip file via nonce URL
		*/
		$this->loader->add_action('admin_init', $this->plugin_admin, 'download_temp_zip_file', 11);

		/**
		*	@since 4.1.3 Add admin notices
		*/
		$this->loader->add_action('admin_notices', $this->plugin_admin, 'admin_notices', 11);

		$this->plugin_admin->admin_modules();
		$this->plugin_public->common_modules();

		$this->loader->add_action('plugins_loaded', $this->plugin_admin, 'register_tooltips', 11);

		$this->loader->add_action('admin_enqueue_scripts',$this->plugin_admin, 'enqueue_styles' );
		$this->loader->add_action('admin_enqueue_scripts',$this->plugin_admin, 'enqueue_scripts' );

		/*Compatible function and filter with multicurrency and currency switcher plugin*/
		$this->loader->add_filter('wt_pklist_change_price_format',$this->plugin_admin,'wf_display_price',10,3);
		$this->loader->add_filter('wt_pklist_convert_currency',$this->plugin_admin,'wf_convert_to_user_currency',10,3);

		$this->loader->add_filter('woocommerce_shop_order_search_fields',$this->plugin_admin,'wf_search_order_by_invoice_number',10,1); /* Search the order by invoice number */

		$this->loader->add_action('plugins_loaded',$this->plugin_pay_later_payment,'init');
		$this->loader->add_action('plugins_loaded',$this->plugin_admin,'save_paylater_settings_admin',11);
		$this->loader->add_action('woocommerce_valid_order_statuses_for_payment',$this->plugin_admin,'wf_allow_payment_for_order_status',10,2);
		$this->loader->add_filter('woocommerce_payment_gateways',$this->plugin_admin,'wf_paylater_add_to_gateways');
		$this->loader->add_filter('woocommerce_available_payment_gateways',$this->plugin_admin,'hide_pay_later_payment_in_order_pay_page');
	}

	/**
	* Checking an array is associative or not
	* @since 4.0.1
	* @param array $array input array
	* @return bool
	*/
	public static function is_assoc(array $array)
	{
	    // Keys of the array
	    $keys = array_keys($array);

	    // If the array keys of the keys match the keys, then the array must
	    // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
	    return array_keys($keys) !== $keys;
	}

	/**
	* [Bug fix] In new version added separate fields for key and value. Adding compatibility to old versions
	* @since 4.0.1
	* @param array $array checkout field value unprocessed
	* @return array $array checkout field value processed
	*/
	public static function process_checkout_fields($arr)
	{
		$arr=!is_array($arr) ? array() : $arr;
		/* not associative array, That mean's old version,then convert it */
		if(!self::is_assoc($arr) && count($arr)>0)
		{
			$arr_keys=array_map(function($vl){
			  return Wf_Woocommerce_Packing_List::process_checkout_key($vl);
			},$arr);
			$arr=array_combine($arr_keys,$arr); //creating an array
		}
		return $arr;
	}

	/**
	* Filtering unwanted characters from checkout field meta key
	* @since 4.0.1
	* @param string $meta_key meta key user input
	* @return string $meta_key processed meta key
	*/
	public static function process_checkout_key($meta_key)
	{
		return strtolower(preg_replace("/[^A-Za-z]/",'_', $meta_key));
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$this->loader->add_action( 'wp_enqueue_scripts',$this->plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts',$this->plugin_public, 'enqueue_scripts' );
	}


	/**
	 * Some modules are not start by default. So need to initialize via code
	 *
	 * @since    4.0.0
	 * @since    4.0.4 Added support for module names with multiple words
	 * @since    4.1.5 Added new argument to decide to create new instance
	 */
	public static function load_modules($module_base, $create_new_instance=false)
	{
		if(Wf_Woocommerce_Packing_List_Public::module_exists($module_base) || Wf_Woocommerce_Packing_List_Admin::module_exists($module_base))
		{
			if(!isset(self::$loaded_modules[$module_base]) || $create_new_instance) //already not initiated or force to create new instance
			{
				$class_name=str_replace(' ','_',ucwords(str_replace('-',' ', $module_base)));
				$module_class='Wf_Woocommerce_Packing_List_'.$class_name;
				self::$loaded_modules[$module_base]=new $module_class;
			}
			return self::$loaded_modules[$module_base];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    4.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     4.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     4.0.0
	 * @return    Wf_Woocommerce_Packing_List_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     4.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * run some necessary function copied from old plugin
	 * @since     4.0.0
	 */
	public function run_necessary()
	{
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wf-legacy.php';
		do_action('wt_run_necessary');
	}

	/**
	 * Generate tab head for settings page.
	 * method will translate the string to current language
	 * @since     4.0.0
	 */
	public static function generate_settings_tabhead($title_arr,$type="plugin")
	{
		// print_r($title_arr);
		// echo "<br/>";
		// die;
		$out_arr=apply_filters("wt_pklist_".$type."_settings_tabhead",$title_arr);
		unset($out_arr['wf_woocommerce_packing_list-customize']);
		$out_arr['wf_woocommerce_packing_list-customize'] = "Customize";
		// print_r($out_arr);
		// echo "<br/>";
		// die;
		foreach($out_arr as $k=>$v)
		{
			if(is_array($v))
			{
				$v=(isset($v[2]) ? $v[2] : '').$v[0].' '.(isset($v[1]) ? $v[1] : '');
			}
		?>
			<a class="nav-tab" href="#<?php echo $k;?>"><?php echo $v; ?></a>
		<?php
		}
	}

	public static function wf_encode($data)
	{
        return rtrim(strtr(base64_encode($data),'+/','-_'),'=');
    }

    public static function wf_decode($data)
    {
        return base64_decode(str_pad(strtr($data,'-_','+/'),strlen($data)%4,'=',STR_PAD_RIGHT));
    }

    /**
    *	@since 4.0.0 	Generate print button for email(includes admin email too), order detail page etc
    *	@since 4.0.9	Added new argument and filter to toggle the visibility for admin/user. Only applicable for email print buttons
    *					$action argument changed to $template_type
    *					Added locale argument in print URL
    *	@since 4.1.0	URL generation moved to separate function
    */
    public static function generate_print_button_for_user($order, $order_id, $template_type, $action, $action_data)
    {
    	$show_button=true;

    	/* toggle the visibility for admin/customer */
    	$show_button=apply_filters('wt_pklist_toggle_email_print_buttons', $show_button, $order, $template_type, $action_data['email_button'], $action_data['sent_to_admin']);

    	if($show_button)
    	{
	        $style='';
	        if($action_data['email_button'])
	        {
	        	$style='background:#0085ba; border-color:#0073aa; box-shadow:0 1px 0 #006799; color:#fff; text-decoration:none; padding:10px; border-radius:10px; text-shadow:0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;';
	        	$style=apply_filters('wt_pklist_alter_email_button_style', $style, $order, $template_type, $action_data['sent_to_admin']);
	        }
	        $button = '<a class="button button-primary" style="'.$style.'" target="_blank" href="'.$action_data['url'].'">'.$action_data['title'].'</a><br><br>';
	        echo $button;
    	}
    }

    /**
	 * Generate print URL for customers
	 * @since     4.1.0
	 */
    public static function generate_print_url_for_user($order, $order_id, $template_type, $action, $email_button=false, $sent_to_admin=false)
    {
    	$action=$action.'_'.$template_type;
    	$wc_version=WC()->version;
		$billing_email=($wc_version< '2.7.0' ? $order->billing_email : $order->get_billing_email());
		$order_id_enc=self::wf_encode($order_id);
		$billing_email_enc=self::wf_encode($billing_email);
		$_nonce=wp_create_nonce(WF_PKLIST_PLUGIN_NAME);
		$locale='&lang='.get_locale();
		return esc_url(site_url('?attaching_pdf=1&print_packinglist=true&email='.$billing_email_enc.'&post='.$order_id_enc.'&type='.$action.'&user_print=1&_wpnonce='.$_nonce.$locale));
    }

	/**
	 * Get default settings
	 * @since     4.0.0
	 */
	public static function default_settings($base_id='')
	{
		$settings=array(
			'woocommerce_wf_packinglist_companyname'=>'',
			'woocommerce_wf_packinglist_logo'=>'',
			'woocommerce_wf_tracking_number'=>'_tracking_number',
			'woocommerce_wf_generate_for_orderstatus'=>array("wc-completed"),

			'woocommerce_wf_select_sign'=>"sign_no",

        	'woocommerce_wf_attach_shipping_label'=>array(),

			'woocommerce_wf_packinglist_datamatrix_information'=>'yes',
			'woocommerce_wf_packinglist_add_sku'=>'No',
			'woocommerce_wf_state_code_disable'=>'yes',
			'wf_additional_data_fields'=>array(),
			'wf_product_meta_fields'=>array(),
			'woocommerce_wf_generate_for_taxstatus'=>array('ex_tax'),
			'wf_additional_checkout_data_fields'=>array(),
			'wf_invoice_additional_checkout_data_fields'=>array(),
			'woocommerce_wf_packinglist_footer'=>'',
			'woocommerce_wf_packinglist_special_notes'=>'',
			'woocommerce_wf_packinglist_return_policy'=>'',
			'woocommerce_wf_packinglist_transport_terms'=>'',
			'woocommerce_wf_packinglist_sale_terms'=>'',
			'woocommerce_wf_packinglist_sender_name'=>'',
			'woocommerce_wf_packinglist_sender_address_line1'=>'',
			'woocommerce_wf_packinglist_sender_address_line2'=>'',
			'woocommerce_wf_packinglist_sender_city'=>'',
			'wf_country'=>'',
			'woocommerce_wf_packinglist_sender_postalcode'=>'',
			'woocommerce_wf_packinglist_sender_contact_number'=>'',
			'woocommerce_wf_packinglist_sender_vat'=>'',
			'woocommerce_wf_packinglist_preview'=>'enabled',
			'woocommerce_wf_packinglist_package_type'=>'single_packing',
			'woocommerce_wf_packinglist_boxes'=>array(),
			'woocommerce_wf_add_rtl_support'=>'No',
			'wt_additional_checkout_field_options'=>array(),
			'wf_pklist_auto_temp_clear'=>'No',
			'wf_pklist_auto_temp_clear_interval'=>'1440', //one day
			'active_pdf_library'=>'dompdf',
		);
		if($base_id!='')
		{
			$settings=apply_filters('wf_module_default_settings',$settings,$base_id);
		}
		return $settings;
	}

	/**
	 * Get active PDF libraries
	 * @since     4.0.9
	 */
	public static function get_pdf_libraries()
	{
		/* load available PDF libs */
        $pdf_libs=array(
            'dompdf'=>array(
                'file'=>plugin_dir_path(__FILE__).'class-dompdf.php', //library main file
                'class'=>'Wt_Pklist_Dompdf', //class name
                'title'=>'Dompdf', //This is for settings section
            )
        );

        return apply_filters('wt_pklist_alter_pdf_libraries', $pdf_libs);
	}

	/**
	 * Reset to default settings
	 * @since     4.0.0
	 */
	public static function reset_to_default($option_name,$base_id='')
	{
		$settings=self::default_settings($base_id);
		return (isset($settings[$option_name]) ? $settings[$option_name] : '');
	}

	/**
	 * Get current settings.
	 * @since  4.0.0
	 * @since  4.0.2 Added filter to alter settings values
	 */
	public static function get_settings($base_id='')
	{
		$settings=self::default_settings($base_id);
		$option_name=($base_id=="" ? WF_PKLIST_SETTINGS_FIELD : $base_id);
		$option_id=($base_id=="" ? 'main' : $base_id); //to store in the stored option variable
		self::$stored_options[$option_id]=get_option($option_name);
		if(!empty(self::$stored_options[$option_id]))
		{
			$settings=wp_parse_args(self::$stored_options[$option_id],$settings);
		}
		//stripping escape slashes
		$settings=self::arr_stripslashes($settings);
		$settings=apply_filters('wf_pklist_alter_settings', $settings, $base_id);
		return $settings;
	}

	protected static function arr_stripslashes($arr)
	{
		if(is_array($arr) || is_object($arr))
		{
			foreach($arr as &$arrv)
			{
				$arrv=self::arr_stripslashes($arrv);
			}
			return $arr;
		}else
		{
			return stripslashes($arr);
		}
	}

	/**
	 * Update current settings.
	 * @param $base_id  Module id
	 * @since     4.0.0
	 */
	public static function update_settings($the_options,$base_id='')
	{
		if($base_id!="" && $base_id!='main') //main is reserved so do not allow modules named main
		{
			self::$stored_options[$base_id]=$the_options;
			update_option($base_id,$the_options);
		}
		if($base_id=="")
		{
			self::$stored_options['main']=$the_options;
			update_option(WF_PKLIST_SETTINGS_FIELD, $the_options);
		}
	}

	/**
	 * Update option value,
	 * @since     4.0.0
	 * @return mixed
	 */
	public static function update_option($option_name, $value, $base='')
	{
		$the_options=self::get_settings($base);
		$the_options[$option_name]=$value;
		self::update_settings($the_options,$base);
	}

	/**
	 * Get option value, move the option to common option field if it was individual
	 * @since  4.0.0
	 * @since  4.0.2 Added filter to alter option value
	 * @return mixed
	 */
	public static function get_option($option_name, $base='', $the_options=null)
	{
		if(is_null($the_options))
		{
			$the_options=self::get_settings($base);
		}
		$vl=isset($the_options[$option_name]) ? $the_options[$option_name] : false;
		$vl=apply_filters('wf_pklist_alter_option',$vl,$the_options,$option_name,$base);
		return $vl;
	}

	public static function get_module_id($module_base)
	{
		return WF_PKLIST_POST_TYPE.'_'.$module_base;
	}

	/**
	*	@since 4.1.3
	*	Get module base from module id
	*/
	public static function get_module_base($module_id)
	{
		if(strpos($module_id, WF_PKLIST_POST_TYPE.'_')!==false) //valid module ID
		{
			return str_replace(WF_PKLIST_POST_TYPE.'_', '', $module_id);
		}
		return false;
	}

	/**
	* 	@since 4.0.5
	*	Get upload dir, Path
	*	@return array / string
	*/
	public static function get_temp_dir($out='')
	{
		$upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_url = $upload['baseurl'];
        //plugin subfolder
        $upload_dir = $upload_dir.'/'.WF_PKLIST_PLUGIN_NAME;
        $upload_url = $upload_url.'/'.WF_PKLIST_PLUGIN_NAME;
        if($out=='path')
        {
        	return $upload_dir;
        }elseif($out=='url')
        {
        	return $upload_url;
        }else
        {
        	return array(
        		'path'=>$upload_dir,
        		'url'=>$upload_url,
        	);
        }
	}
    public static function is_from_address_available()
    {
        if((self::get_option('woocommerce_wf_packinglist_sender_address_line1')=='' ||
        	self::get_option('woocommerce_wf_packinglist_sender_city') == '' ||
        	self::get_option('wf_country') == '' ||
        	self::get_option('woocommerce_wf_packinglist_sender_postalcode') == ''))
        {
            return false;
        } else
        {
            return true;
        }
    }

    /**
    *	@since 4.1.1
    *	To display meta key along with meta label. Default fields without _billing_ prefix will be added
    */
    public static function get_display_key($meta_key)
    {
    	/* default fields without _billing_ prefix */
        if(in_array($meta_key, self::$default_fields_no_prefix))
        {
        	$meta_key_display="_billing_".$meta_key;
        }
        elseif($meta_key=='cus_note') /* customer note is not a meta item */
        {
        	$meta_key_display="";
        }
        else
        {
        	$meta_key_display=$meta_key;
        }
        return ($meta_key_display!="" ? "(".$meta_key_display.")" : "");
    }
}
}