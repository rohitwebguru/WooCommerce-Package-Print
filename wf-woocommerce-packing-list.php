<?php
/**
 * The plugin bootstrap file
 *
 *
 * @link              https://www.webtoffee.com/
 * @since             4.0.0
 * @package           Wf_Woocommerce_Packing_List
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels (Pro)
 * Plugin URI:        https://www.webtoffee.com/product/woocommerce-pdf-invoices-packing-slips/
 * Description:       Prints Packing List,Invoice,Delivery Note & Shipping Label.
 * Version:           4.2.1
 * Author:            WebToffee
 * Author URI:        https://www.webtoffee.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wf-woocommerce-packing-list
 * Domain Path:       /languages
 * WC tested up to:   5.6
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if(!defined('WF_PKLIST_VERSION')) //check plugin file already included
{
    define('WF_PKLIST_PLUGIN_DEVELOPMENT_MODE', false );
    define('WF_PKLIST_PLUGIN_BASENAME', plugin_basename(__FILE__) );
    define('WF_PKLIST_PLUGIN_PATH', plugin_dir_path(__FILE__) );
    define('WF_PKLIST_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('WF_PKLIST_PLUGIN_FILENAME',__FILE__);
    define('WF_PKLIST_POST_TYPE','wf_woocommerce_packing_list'); /* using in add-on plugins */
    define('WF_PKLIST_ACTIVATION_ID', 'wt_pdfinvoice');
    define('WT_PKLIST_EDD_ACTIVATION_ID', '196728');
    define('WF_PKLIST_DOMAIN','wf-woocommerce-packing-list');
    define('WF_PKLIST_SETTINGS_FIELD','Wf_Woocommerce_Packing_List');
    define('WF_PKLIST_PLUGIN_NAME','wf-woocommerce-packing-list');
    define('WF_PKLIST_PLUGIN_DESCRIPTION','WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels');

    /**
     * Currently plugin version.
     */
    define( 'WF_PKLIST_VERSION', '4.2.1' );
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


$current_plugin_name='WooCommerce PDF Invoices, Packing Slips, Delivery Notes and Shipping Labels (Pro)';
$wt_pklist_no_plugin_conflict=true;


//check if basic version is there
if(is_plugin_active('print-invoices-packing-slip-labels-for-woocommerce/print-invoices-packing-slip-labels-for-woocommerce.php'))
{
    $active_plugin_name='WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels (Basic)';
    $wt_pklist_no_plugin_conflict=false;

}else if(is_plugin_active('shipping-labels-for-woo/wf-woocommerce-packing-list.php'))
{
    $active_plugin_name='WooCommerce Shipping Label (Basic)';
    $wt_pklist_no_plugin_conflict=false;
}
if(!$wt_pklist_no_plugin_conflict)
{
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die(sprintf(__("The plugins %s and %s cannot be active in your store at the same time. Kindly deactivate one of these prior to activating the other.", "wf-woocommerce-packing-list"), $active_plugin_name, $current_plugin_name), "", array('link_url' => admin_url('plugins.php'), 'link_text' => __('Go to plugins page', 'wf-woocommerce-packing-list') ));
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wf-woocommerce-packing-list-activator.php
 */
if(!function_exists('activate_wf_woocommerce_packing_list'))
{
    function activate_wf_woocommerce_packing_list()
    {
    	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wf-woocommerce-packing-list-activator.php';
    	Wf_Woocommerce_Packing_List_Activator::activate();
    }
    register_activation_hook( __FILE__, 'activate_wf_woocommerce_packing_list' );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wf-woocommerce-packing-list-deactivator.php
 */
if(!function_exists('deactivate_wf_woocommerce_packing_list'))
{
    function deactivate_wf_woocommerce_packing_list()
    {
    	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wf-woocommerce-packing-list-deactivator.php';
    	Wf_Woocommerce_Packing_List_Deactivator::deactivate();
    }
    register_deactivation_hook( __FILE__, 'deactivate_wf_woocommerce_packing_list' );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

require plugin_dir_path( __FILE__ ) . 'includes/class-wf-woocommerce-packing-list.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    4.0.0
 */
if(!function_exists('run_wf_woocommerce_packing_list'))
{
    function run_wf_woocommerce_packing_list() {

    	$plugin = new Wf_Woocommerce_Packing_List();
    	$plugin->run();

    }
}
if(!function_exists('woocommerce_packing_list_check_necessary'))
{
    function woocommerce_packing_list_check_necessary()
    {
    	global $wpdb;
    	$search_query = "SHOW TABLES LIKE %s";
    	$tb=Wf_Woocommerce_Packing_List::$template_data_tb;
        $like = '%' . $wpdb->prefix.$tb.'%';
        if(!$wpdb->get_results($wpdb->prepare($search_query, $like),ARRAY_N))
        {
        	return false;
        	//wp_die(_e('Plugin not installed correctly','wf-woocommerce-packing-list'));
        }
        return true;
    }
}

if(function_exists('woocommerce_packing_list_check_necessary') && function_exists('run_wf_woocommerce_packing_list'))
{
    if( woocommerce_packing_list_check_necessary() && (in_array( 'woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins'))) || array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins', array() ) ) )) )
    {
    	run_wf_woocommerce_packing_list();
    }else
    {
        if(!function_exists('WC'))
        {
            add_action('admin_notices', 'wt_pklist_require_wc_admin_notice');
            function wt_pklist_require_wc_admin_notice()
            {
                ?>
                <div class="error">
                    <p><?php echo sprintf(__('%s WooCommerce PDF Invoices, Packing Slips, Delivery Notes and Shipping Labels (Pro) %s is enabled but not effective. It requires %s WooCommerce %s in order to work.', 'wf-woocommerce-packing-list'), '<b>', '</b>', '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">', '</a>'); ?></p>
                </div>
                <?php
            }
        }
    }
}

if(!function_exists('wf_woocommerce_packing_list_update_message'))
{
    function wt_woocommerce_packing_list_update_message( $data, $response )
    {
        if(isset( $data['upgrade_notice']))
        {
            printf(
            '<style type="text/css">
            #wt-woocommerce-packing-list-update .wf-update-message p::before{ content: "";}
            #wt-woocommerce-packing-list-update ul{ list-style:disc; margin-left:30px;}
            </style>
            .update-message
            <div class="update-message wf-update-message">%s</div>',
               $data['upgrade_notice']
            );
        }
    }
    add_action( 'in_plugin_update_message-wt-woocommerce-packing-list/wf-woocommerce-packing-list.php', 'wt_woocommerce_packing_list_update_message', 10, 2 );
}


// modify shipping address : put ZIP Code before city

add_filter('wf_pklist_alter_shipping_address','wf_pklist_alter_address_fn',10,3);
add_filter('wf_pklist_alter_billing_address','wf_pklist_alter_address_fn',10,3);
add_filter('wf_pklist_alter_shipping_from_address','wf_pklist_alter_address_fn',10,3);
function wf_pklist_alter_address_fn($address, $template_type, $order)
{
 $arr=array($address['postcode'], $address['city']);
//  print_r($address);
//  exit();
//  $address['city']=implode($arr, ", ");
//  unset($address['postcode']);
 return $address;
}