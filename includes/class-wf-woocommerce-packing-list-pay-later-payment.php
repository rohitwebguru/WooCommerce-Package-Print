<?php
/**
 * Pay Later Payment Gateway
 *
 * @link       
 * @since 4.2.1     
 *
 * @package  Wf_Woocommerce_Packing_List  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wf_Woocommerce_Packing_List_Pay_Later_Payment'))
{
	class Wf_Woocommerce_Packing_List_Pay_Later_Payment extends WC_Payment_Gateway {
		
		private $plugin_name;
		private $version;

		public function __construct() {

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
			$this->define_admin_hooks();

			$this->id = 'wf_pay_later';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'Pay Later', 'wf-woocommerce-packing-list' );
			$this->method_description = __( 'Allows a ‘Pay Later’ option at the checkout. Orders will be marked with the status ‘Pending payment’ on using this payment method.', 'wf-woocommerce-packing-list' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
		}

		private function load_dependencies() {
			$this->plugin_admin = new Wf_Woocommerce_Packing_List_Admin( $this->plugin_name, $this->version );
		}

		private function define_admin_hooks() {
			add_action( 'woocommerce_update_options_payment_gateways_wf_pay_later', array( $this->plugin_admin, 'save_paylater_settings_admin' ) );
		}
		public function init() {

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wf_pay_later_payment_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wf-woocommerce-packing-list' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Pay Later Payment', 'wf-woocommerce-packing-list' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wf-woocommerce-packing-list' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wf-woocommerce-packing-list' ),
					'default'     => __( 'Pay Later Payment', 'wf-woocommerce-packing-list' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wf-woocommerce-packing-list' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wf-woocommerce-packing-list' ),
					'default'     => __( 'Please remit payment to Store Name upon pickup or delivery.', 'wf-woocommerce-packing-list' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wf-woocommerce-packing-list' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wf-woocommerce-packing-list' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			) );
		}


		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}


		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}


		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {

			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'pending', __( 'Awaiting offline payment', 'wf-woocommerce-packing-list' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}


	}
}
?>