<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.webtoffee.com/
 * @since      4.0.0
 *
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/admin
 * @author     WebToffee <info@webtoffee.com>
 */
class Wf_Woocommerce_Packing_List_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    4.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    4.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /*
     * module list, Module folder and main file must be same as that of module name
     * Please check the `register_modules` method for more details
     */
    public static $modules = array(
        'payment-link',
        'customizer',
        'sequential-number',
        'cloud-print',
        'licence_manager',
    );

    public static $existing_modules = array();

    public $bulk_actions = array();

    public static $tooltip_arr = array();

    /**
     *    To store the RTL needed or not status
     *    @since 4.0.9
     */
    public static $is_enable_rtl = null;

    /**
     * Initialize the class and set its properties.
     *
     * @since    4.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    4.0.0
     */
    public function enqueue_styles()
    {

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wf-woocommerce-packing-list-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    4.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wf-woocommerce-packing-list-admin.js', array('jquery', 'wp-color-picker', 'jquery-tiptip'), $this->version, false);
        //order list page bulk action filter
        $this->bulk_actions = apply_filters('wt_print_bulk_actions', $this->bulk_actions);

        $params = array(
            'nonces' => array(
                'wf_packlist' => wp_create_nonce(WF_PKLIST_PLUGIN_NAME),
            ),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'no_image' => Wf_Woocommerce_Packing_List::$no_image,
            'bulk_actions' => array_keys($this->bulk_actions),
            'print_action_url' => admin_url('?print_packinglist=true'),
            'msgs' => array(
                'settings_success' => __('Settings updated.', 'wf-woocommerce-packing-list'),
                'all_fields_mandatory' => __('All fields are mandatory', 'wf-woocommerce-packing-list'),
                'enter_mandatory_fields' => __('Please enter mandatory fields', 'wf-woocommerce-packing-list'),
                'settings_error' => sprintf(__('Unable to update settings due to an internal error. %s To troubleshoot please click %s here. %s', 'wf-woocommerce-packing-list'), '<br />', '<a href="https://www.webtoffee.com/how-to-fix-the-unable-to-save-settings-issue/" target="_blank">', '</a>'),
                'select_orders_first' => __('You have to select order(s) first!', 'wf-woocommerce-packing-list'),
                'invoice_not_gen_bulk' => __('One or more order do not have invoice generated. Generate manually?', 'wf-woocommerce-packing-list'),
                'error' => __('Sorry, something went wrong.', 'wf-woocommerce-packing-list'),
                'please_wait' => __('Please wait', 'wf-woocommerce-packing-list'),
                'sure' => __("You can't undo this action. Are you sure?", 'wf-woocommerce-packing-list'),
                'is_required' => __("is required", 'wf-woocommerce-packing-list'),
                'close' => __("Close", 'wf-woocommerce-packing-list'),
                'save' => __("Save", 'wf-woocommerce-packing-list'),
                'default' => __("Default", 'wf-woocommerce-packing-list'),
                'invoice_title_prompt' => __("Invoice", 'wf-woocommerce-packing-list'),
                'invoice_number_prompt' => __("number has not been generated yet. Do you want to manually generate one ?", 'wf-woocommerce-packing-list'),
                'invoice_number_prompt_free_order' => __("‘Generate invoice for free orders’ is disabled in Invoice settings > Advanced. You are attempting to generate invoice for this free order. Proceed?", 'wf-woocommerce-packing-list'),
                'payment_link_prompt' => __('Please check the `Show payment link on invoice` option, otherwise you will not receive the payment link', 'wf-woocommerce-packing-list'),
            ),
        );
        wp_localize_script($this->plugin_name, 'wf_pklist_params', $params);

    }

    /**
     * Function to add Items to Orders Bulk action dropdown
     *
     * @since    4.0.0
     */
    public function alter_bulk_action($actions)
    {
        return array_merge($actions, $this->bulk_actions);
    }

    /**
     * To show the values of custom checkout fields in order detail page
     *
     * @since    4.0.9
     */
    public function additional_checkout_fields_in_order_detail_page($order)
    {

        $checkout_fields_arr = $this->add_checkout_fields(
            array(
                'billing' => array(),
            )
        );
        $hide_empty_fields = true;
        $hide_empty_fields = apply_filters('wt_pklist_custom_checkout_hide_empty_fields', $hide_empty_fields, $order);
        if ($checkout_fields_arr && is_array($checkout_fields_arr) && isset($checkout_fields_arr['billing']) && is_array($checkout_fields_arr['billing'])) {
            $order_id = $order->get_id();
            foreach ($checkout_fields_arr['billing'] as $field_key => $field_vl) {
                $val = get_post_meta($order_id, '_' . $field_key, true);
                if ($hide_empty_fields) {
                    if ($val != "") {
                        echo '<p><strong>' . $field_vl['label'] . ':</strong> ' . $val . '</p>';
                    }
                } else {
                    echo '<p><strong>' . $field_vl['label'] . ':</strong> ' . $val . '</p>';
                }
            }
        }
    }

    /**
     * Function to add custom fields in checkout page
     *
     * @since    4.0.0
     * @since    4.0.3 is_required and placeholder options added
     * @since    4.0.9 this method is also used to display the fields in order detail page.
     */
    public function add_checkout_fields($fields)
    {
        //user selected fields to show
        $user_selected_data_flds = Wf_Woocommerce_Packing_List::get_option('wf_invoice_additional_checkout_data_fields');
        if (is_array($user_selected_data_flds) && count(array_filter($user_selected_data_flds)) > 0) {
            $data_flds = self::get_checkout_field_list();

            $priority_inc = 110; //110 is the last item(billing email priority so our fields will be after that.)
            $additional_checkout_field_options = Wf_Woocommerce_Packing_List::get_option('wt_additional_checkout_field_options');
            foreach ($user_selected_data_flds as $value) {
                $priority_inc++;
                if (isset($data_flds[$value])) //field exists in the user created/default field list
                {
                    $add_data = isset($additional_checkout_field_options[$value]) ? $additional_checkout_field_options[$value] : array();
                    $is_required = (int) (isset($add_data['is_required']) ? $add_data['is_required'] : 0);
                    $placeholder = (isset($add_data['placeholder']) ? $add_data['placeholder'] : 'Enter ' . $data_flds[$value]);
                    $title = (isset($add_data['title']) && trim($add_data['title']) != "" ? $add_data['title'] : $data_flds[$value]);

                    $fields['billing']['billing_' . $value] = array(
                        'type' => 'text',
                        'label' => __($title, 'woocommerce'),
                        'placeholder' => _x($placeholder, 'placeholder', 'woocommerce'),
                        'required' => $is_required,
                        'class' => array('form-row-wide', 'align-left'),
                        'clear' => true,
                        'priority' => $priority_inc,
                    );
                }
            }
        }
        return $fields;
    }

    /**
     * Function to add print button in order list page action column
     *
     * @since    4.0.0
     */
    public function add_print_action_button($actions, $order)
    {
        $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
        $wf_pklist_print_options = array(
            array(
                'name' => '',
                'action' => 'wf_pklist_print_document',
                'url' => sprintf('#%s', $order_id),
            ),
        );
        return array_merge($actions, $wf_pklist_print_options);
    }

    public function wf_paylater_add_to_gateways($gateways)
    {
        $gateways[] = 'Wf_Woocommerce_Packing_List_Pay_Later_Payment';
        return $gateways;
    }

    /**
     * Function to add email attachments to order email
     *
     * @since    4.0.0
     * @since    4.0.1 added compatibility admin created orders `is_a` checking added
     */
    public function add_email_attachments($attachments, $status = null, $order = null)
    {
        if (is_object($order) && is_a($order, 'WC_Order') && isset($status)) {
            $order = (WC()->version < '2.7.0') ? new WC_Order($order) : new wf_order($order);
            $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
            $attachments = apply_filters('wt_email_attachments', $attachments, $order, $order_id, $status);
        }
        return $attachments;
    }

    /**
     * Function to add action buttons in order email
     *
     *  @since    4.0.0
     *    @since       4.0.8     [Bug fix] Print button missing in email
     *    @since       4.0.9     New argument $sent_to_admin added to `wt_email_print_actions` filter
     */
    public function add_email_print_actions($order, $sent_to_admin, $plain_text, $email)
    {
        if (is_object($order) && is_a($order, 'WC_Order')) {
            $order = (WC()->version < '2.7.0') ? new WC_Order($order) : new wf_order($order);
            $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
            $wt_actions = array();
            $wt_actions = apply_filters('wt_pklist_intl_email_print_actions', $wt_actions, $order, $order_id, $email, $sent_to_admin);
            if (is_array($wt_actions) && count($wt_actions) > 0) {
                foreach ($wt_actions as $template_type => $action_arr) {
                    if (is_array($action_arr)) {
                        foreach ($action_arr as $action => $action_data) {
                            $action_info = array(
                                'url' => Wf_Woocommerce_Packing_List::generate_print_url_for_user($order, $order_id, $template_type, $action),
                                'title' => $action_data['title'],
                            );
                            $action_info = apply_filters('wt_pklist_email_print_action', $action_info, $template_type, $action, $order, $order_id);

                            if (is_array($action_info)) {
                                $action_data = array_merge($action_data, $action_info);
                            }

                            //generate button
                            Wf_Woocommerce_Packing_List::generate_print_button_for_user($order, $order_id, $template_type, $action, $action_data);
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to add action buttons in user dashboard order detail page
     * @since    4.1.0
     */
    public function add_order_detail_page_print_actions($order)
    {
        $order = (WC()->version < '2.7.0') ? new WC_Order($order) : new wf_order($order);
        $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
        $wt_actions = array();
        $wt_actions = apply_filters('wt_pklist_intl_frontend_order_detail_page_print_actions', $wt_actions, $order, $order_id);
        if (is_array($wt_actions) && count($wt_actions) > 0) {
            foreach ($wt_actions as $template_type => $action_arr) {
                if (is_array($action_arr)) {
                    foreach ($action_arr as $action => $action_data) {
                        $show_button = true;
                        $show_button = apply_filters('wt_pklist_is_frontend_order_detail_page_print_action', $show_button, $template_type, $action);
                        if ($show_button) {
                            $action_info = array(
                                'url' => Wf_Woocommerce_Packing_List::generate_print_url_for_user($order, $order_id, $template_type, $action),
                                'title' => $action_data['title'],
                            );
                            $action_info = apply_filters('wt_pklist_frontend_order_detail_page_print_action', $action_info, $template_type, $action, $order, $order_id);

                            if (is_array($action_info)) {
                                $action_data = array_merge($action_data, $action_info);
                            }

                            //generate button
                            Wf_Woocommerce_Packing_List::generate_print_button_for_user($order, $order_id, $template_type, $action, $action_data);

                        }
                    }
                }
            }
        }
    }

    /**
     * Function to add action buttons in user dashboard orders page
     * @since    4.1.0
     */
    public function add_order_list_page_print_actions($actions, $order)
    {
        $order = (WC()->version < '2.7.0') ? new WC_Order($order) : new wf_order($order);
        $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();

        $wt_actions = array();
        $wt_actions = apply_filters('wt_pklist_intl_frontend_order_list_page_print_actions', $wt_actions, $order, $order_id);
        if (is_array($wt_actions) && count($wt_actions) > 0) {
            foreach ($wt_actions as $template_type => $action_arr) {
                if (is_array($action_arr)) {
                    foreach ($action_arr as $action => $title) {
                        $show_button = true;
                        $show_button = apply_filters('wt_pklist_is_frontend_order_list_page_print_action', $show_button, $template_type, $action);
                        if ($show_button) {
                            /** button info to WC hook */
                            $action_data = array(
                                'url' => Wf_Woocommerce_Packing_List::generate_print_url_for_user($order, $order_id, $template_type, $action),
                                'name' => $title,
                            );
                            $actions['wt_pklist_' . $template_type . '_' . $action] = apply_filters('wt_pklist_frontend_order_list_page_print_action', $action_data, $template_type, $action, $order, $order_id);
                        }
                    }
                }
            }
        }

        return $actions;
    }

    public static function get_print_url($order_id, $action)
    {
        $url = wp_nonce_url(admin_url('?print_packinglist=true&post=' . ($order_id) . '&type=' . $action), WF_PKLIST_PLUGIN_NAME);
        $url = (isset($_GET['debug']) ? $url . '&debug' : $url);
        return $url;
    }

    public static function generate_print_button_html($btn_arr, $order, $order_id, $button_location)
    {
        /* filter for customers to alter buttons */
        $btn_arr = apply_filters('wt_pklist_alter_print_actions', $btn_arr, $order, $order_id, $button_location);

        foreach ($btn_arr as $btn_key => $args) {
            $action = $args['action'];
            $css_class = (isset($args['css_class']) && is_string($args['css_class']) ? $args['css_class'] : ''); /* button custom css */
            $custom_attr = (isset($args['custom_attr']) && is_string($args['custom_attr']) ? $args['custom_attr'] : ''); /* button custom attribute */

            $label = $args['label'];
            $is_show_prompt = $args['is_show_prompt'];
            $tooltip = (isset($args['tooltip']) ? $args['tooltip'] : $label);
            $button_location = (isset($args['button_location']) ? $args['button_location'] : 'detail_page');

            $url = self::get_print_url($order_id, $action);

            $href_attr = '';
            $onclick = '';
            $confirmation_clss = '';
            if ($is_show_prompt !== 0) //$is_show_prompt variable is a string then it will set as warning msg title
            {
                $confirmation_clss = 'wf_pklist_confirm_' . $action;
                $onclick = 'onclick=" return wf_Confirm_Notice_for_Manually_Creating_Invoicenumbers(\'' . $url . '\',\'' . $is_show_prompt . '\');"';
            } else {
                $href_attr = ' href="' . $url . '"';
            }
            if ($button_location == "detail_page") {
                $button_type = (isset($args['button_type']) ? $args['button_type'] : 'normal');
                $button_key = (isset($args['button_key']) ? $args['button_key'] : 'button_key_' . $btn_key);
                ?>
				<tr>
					<td class="wt_pklist_dash_btn_row">
						<?php
if ($button_type == 'aggregate' || $button_type == 'dropdown') {
                    if ($button_type == 'aggregate') /* reverse the order of buttons */
                    {
                        $args['items'] = array_reverse($args['items']);
                    }
                    ?>
							<div class="wt_pklist_<?php echo $button_type; ?> <?php echo $css_class; ?>" <?php echo $custom_attr; ?> >
								<div class="wt_pklist_btn_text"><?php echo $label; ?></div>
								<div class="wt_pklist_<?php echo $button_type; ?>_content">
									<?php
foreach ($args['items'] as $btnkk => $btnvv) {
                        $action = $btnvv['action'];
                        $label = $btnvv['label'];

                        $icon = (isset($btnvv['icon']) && $btnvv['icon'] != "" ? $btnvv['icon'] : ''); //dashicon
                        $icon_url = (isset($btnvv['icon_url']) && $btnvv['icon_url'] != "" ? $btnvv['icon_url'] : ''); //image icon

                        if ($button_type == 'aggregate') /* only icon, No label */
                        {
                            if ($icon == "" && $icon_url == "") {
                                global $wp_version;
                                if (version_compare($wp_version, '5.5.3') >= 0) {
                                    $fallback_icon = 'tag';
                                    if (strpos($action, 'download_') !== false) {
                                        $fallback_icon = 'download';

                                    } elseif (strpos($action, 'print_') !== false) {
                                        $fallback_icon = 'printer';
                                    }
                                    $btn_label = '<span class="dashicons dashicons-' . $fallback_icon . '"></span>';

                                } else {
                                    $fallback_icon_url = 'tag-icon.png';
                                    if (strpos($action, 'download_') !== false) {
                                        $fallback_icon_url = 'download-icon.png';

                                    } elseif (strpos($action, 'print_') !== false) {
                                        $fallback_icon_url = 'print-icon.png';
                                    }
                                    $btn_label = '<span class="dashicons" style="line-height:17px;"><img src="' . WF_PKLIST_PLUGIN_URL . 'admin/images/' . $fallback_icon_url . '" style="width:16px; height:16px; display:inline;"></span>';
                                }
                            } else {
                                if ($icon != "") {
                                    $btn_label = '<span class="dashicons dashicons-' . $icon . '"></span>';
                                } else {
                                    $btn_label = '<span class="dashicons" style="line-height:17px;"><img src="' . $icon_url . '" style="width:16px; height:16px; display:inline;"></span>';
                                }
                            }
                        } else {
                            $btn_label = $label;
                        }

                        $tooltip = (isset($btnvv['tooltip']) ? $btnvv['tooltip'] : $label);
                        $is_show_prompt = $btnvv['is_show_prompt'];
                        $item_css_class = (isset($btnvv['css_class']) && is_string($btnvv['css_class']) ? $btnvv['css_class'] : ''); /* dropdown item custom css */
                        $item_custom_attr = (isset($btnvv['custom_attr']) && is_string($btnvv['custom_attr']) ? $btnvv['custom_attr'] : ''); /* dropdown item custom attribute */

                        $url = self::get_print_url($order_id, $action);

                        $href_attr = '';
                        $onclick = '';
                        $confirmation_clss = '';
                        if ($is_show_prompt !== 0) //$is_show_prompt variable is a string then it will set as warning msg title
                        {
                            $confirmation_clss = 'wf_pklist_confirm_' . $action;
                            $onclick = 'onclick=" return wf_Confirm_Notice_for_Manually_Creating_Invoicenumbers(\'' . $url . '\',\'' . $is_show_prompt . '\');"';
                        } else {
                            $href_attr = ' href="' . $url . '"';
                        }
                        ?>
										<a <?php echo $onclick; ?> <?php echo $href_attr; ?> target="_blank" data-id="<?php echo $order_id; ?>" class="<?php echo $item_css_class; ?>" <?php echo $item_custom_attr; ?> title="<?php echo $tooltip; ?>"> <?php echo $btn_label; ?></a>
										<?php
}
                    ?>
								</div>
							</div>
							<?php
} else {
                    ?>
							<a class="button tips wf-packing-list-link <?php echo $css_class; ?>" <?php echo $onclick; ?> <?php echo $href_attr; ?> target="_blank" data-tip="<?php echo esc_attr($tooltip); ?>" data-id="<?php echo $order_id; ?>" <?php echo $custom_attr; ?> >
								<?php echo $label; ?>
							</a>
						<?php
}
                ?>
					</td>
				</tr>
			<?php
} elseif ($button_location == "list_page") {
                ?>
				<li>
					<a class="<?php echo $confirmation_clss; ?> <?php echo $css_class; ?>" data-id="<?php echo $order_id; ?>" <?php echo $onclick; ?> <?php echo $href_attr; ?> target="_blank" title="<?php echo esc_attr($tooltip); ?>" <?php echo $custom_attr; ?> ><?php echo $label; ?></a>
				</li>
			<?php
}
        }
    }

    /**
     * Function to add action buttons in order list page
     *
     * @since    4.0.0
     */
    public function add_print_actions($column)
    {
        global $post, $woocommerce, $the_order;
        if ($column == 'order_actions' || $column == 'wc_actions') {
            $order = (WC()->version < '2.7.0') ? new WC_Order($post->ID) : new wf_order($post->ID);
            $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
            $html = '';
            ?>
			<div id="wf_pklist_print_document-<?php echo $order_id; ?>" class="wf-pklist-print-tooltip-order-actions">
				<div class="wf-pklist-print-tooltip-content">
                    <ul>
                    <?php
$btn_arr = array();
            $btn_arr = apply_filters('wt_print_actions', $btn_arr, $order, $order_id, 'list_page');
            self::generate_print_button_html($btn_arr, $order, $order_id, 'list_page'); //generate buttons
            ?>
					</ul>
                </div>
                <div class="wf_arrow"></div>
			</div>
			<?php
}
        return $column;
    }

    /**
     * Registers meta box and printing options
     *
     * @since    4.0.0
     */
    public function add_meta_boxes()
    {
        add_meta_box('woocommerce-packinglist-box', __('Invoice/Packing', 'wf-woocommerce-packing-list'), array($this, 'create_metabox_content'), 'shop_order', 'side', 'default');
    }

    /**
     * Add plugin action links
     *
     * @param array $links links array
     */
    public function plugin_action_links($links)
    {
        $links[] = '<a href="' . admin_url('admin.php?page=' . WF_PKLIST_POST_TYPE) . '">' . __('Settings', 'wf-woocommerce-packing-list') . '</a>';
        $links[] = '<a href="https://www.webtoffee.com/category/documentation/print-invoices-packing-list-labels-for-woocommerce/" target="_blank">' . __('Documentation', 'wf-woocommerce-packing-list') . '</a>';
        $links[] = '<a href="https://www.webtoffee.com/support/" target="_blank">' . __('Support', 'wf-woocommerce-packing-list') . '</a>';
        return $links;
    }

    /**
     *    @since  4.0.0  create content for metabox
     *    @since  4.0.4  added separate section for document details and print actions
     *
     */
    public function create_metabox_content()
    {
        global $post;
        $order = (WC()->version < '2.7.0') ? new WC_Order($post->ID) : new wf_order($post->ID);
        $order_id = (WC()->version < '2.7.0') ? $order->id : $order->get_id();
        ?>
		<table class="wf_invoice_metabox" style="width:100%;">
			<?php
$data_arr = array();
        $data_arr = apply_filters('wt_print_docdata_metabox', $data_arr, $order, $order_id);
        if (count($data_arr) > 0) {
            ?>
			<tr>
				<td style="font-weight:bold;">
					<h4 style="margin:0px; padding-top:5px; padding-bottom:3px; border-bottom:dashed 1px #ccc;"><?php _e('Document details', 'wf-woocommerce-packing-list');?></h4>
				</td>
			</tr>
			<tr>
				<td style="padding-bottom:10px;">
					<?php

            foreach ($data_arr as $datav) {
                echo '<span style="font-weight:500;">';
                echo ($datav['label'] != "" ? $datav['label'] . ': ' : '');
                echo '</span>';
                echo $datav['value'] . '<br />';
            }
            ?>
				</td>
			</tr>
			<?php
}
        ?>
			<tr>
				<td>
					<h4 style="margin:0px; padding-top:5px; padding-bottom:3px; border-bottom:dashed 1px #ccc;"><?php _e('Print/Download', 'wf-woocommerce-packing-list');?></h4>
				</td>
			</tr>
			<tr>
				<td style="height:3px; font-size:0px; line-height:0px;"></td>
			</tr>
			<?php
$btn_arr = array();
        $btn_arr = apply_filters('wt_print_actions', $btn_arr, $order, $order_id, 'detail_page');
        self::generate_print_button_html($btn_arr, $order, $order_id, 'detail_page'); //generate buttons
        ?>
		</table>
		<?php
}

    /**
     * Registers menu options
     * Hooked into admin_menu
     *
     * @since    1.0.0
     */
    public function admin_menu()
    {
        $menus = array(
            array(
                'menu',
                __('General Settings', 'wf-woocommerce-packing-list'),
                __('Invoice/Packing', 'wf-woocommerce-packing-list'),
                'manage_woocommerce',
                WF_PKLIST_POST_TYPE,
                array($this, 'admin_settings_page'),
                'dashicons-media-text',
                56,
            ),
        );
        $menus = apply_filters('wt_admin_menu', $menus);
        if (count($menus) > 0) {
            add_submenu_page(WF_PKLIST_POST_TYPE, __('General Settings', 'wf-woocommerce-packing-list'), __('General Settings', 'wf-woocommerce-packing-list'), "manage_woocommerce", WF_PKLIST_POST_TYPE, array($this, 'admin_settings_page'));
            foreach ($menus as $menu) {
                if ($menu[0] == 'submenu') {
                    add_submenu_page($menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6]);
                } else {
                    add_menu_page($menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6], $menu[7]);
                }
            }
        }

        if (function_exists('remove_submenu_page')) {
            //remove_submenu_page(WF_PKLIST_POST_TYPE,WF_PKLIST_POST_TYPE);
        }
    }

    /**
     * @since 4.0.5
     * Is user allowed
     */
    public static function check_write_access($nonce_id = '')
    {
        $er = true;
        //checkes user is logged in
        if (!is_user_logged_in()) {
            $er = false;
        }

        if ($er === true) //no error then proceed
        {
            $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
            $nonce = (is_array($nonce) ? $nonce[0] : $nonce);
            $nonce_id = ($nonce_id == "" ? WF_PKLIST_PLUGIN_NAME : $nonce_id);
            if (!(wp_verify_nonce($nonce, $nonce_id))) {
                $er = false;
            } else {
                if (!Wf_Woocommerce_Packing_List_Admin::check_role_access()) //Check access
                {
                    $er = false;
                }
            }
        }
        return $er;
    }

    /**
     * @since 4.0.5
     * Is user allowed
     */
    public static function check_role_access()
    {
        $admin_print_role_access = array('manage_options', 'manage_woocommerce');
        $admin_print_role_access = apply_filters('wf_pklist_alter_admin_print_role_access', $admin_print_role_access);
        $admin_print_role_access = (!is_array($admin_print_role_access) ? array() : $admin_print_role_access);
        $is_allowed = false;
        foreach ($admin_print_role_access as $role) //checking access
        {
            if (current_user_can($role)) //any of the role is okay then allow to print
            {
                $is_allowed = true;
                break;
            }
        }
        return $is_allowed;
    }

    /**
     *     @since 4.0.0     function to render printing window
     *    @since 4.0.9    added language parameter checking
     */
    public function print_window()
    {
        $attachments = array();
        if (isset($_GET['print_packinglist'])) {
            //checkes user is logged in
            if (!is_user_logged_in()) {
                auth_redirect();
            }
            $not_allowed_msg = __('You are not allowed to view this page.', 'wf-woocommerce-packing-list');
            $not_allowed_title = __('Access denied !!!.', 'wf-woocommerce-packing-list');

            $client = false;
            //    to check current user has rights to get invoice and packing list
            if (!isset($_GET['attaching_pdf'])) {
                $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
                if (!(wp_verify_nonce($nonce, WF_PKLIST_PLUGIN_NAME))) {
                    wp_die($not_allowed_msg, $not_allowed_title);
                } else {
                    if (!self::check_role_access()) //Check access
                    {
                        wp_die($not_allowed_msg, $not_allowed_title);
                    }
                    $orders = explode(',', $_GET['post']);
                }
            } else {
                // to get the orders number
                if (isset($_GET['email']) && isset($_GET['post']) && isset($_GET['user_print'])) {
                    $email_data_get = Wf_Woocommerce_Packing_List::wf_decode($_GET['email']);
                    $order_data_get = Wf_Woocommerce_Packing_List::wf_decode($_GET['post']);
                    $order_data = wc_get_order($order_data_get);
                    if (!$order_data) {
                        wp_die($not_allowed_msg, $not_allowed_title);
                    }
                    $logged_in_userid = get_current_user_id();
                    $order_user_id = ((WC()->version < '2.7.0') ? $order_data->user_id : $order_data->get_user_id());
                    if ($logged_in_userid != $order_user_id) //the current order not belongs to the current logged in user
                    {
                        if (!self::check_role_access()) //Check access
                        {
                            wp_die($not_allowed_msg, $not_allowed_title);
                        }
                    }

                    //checks the email parameters belongs to the given order
                    if ($email_data_get === ((WC()->version < '2.7.0') ? $order_data->billing_email : $order_data->get_billing_email())) {
                        $orders = explode(",", $order_data_get); //must be an array
                    } else {
                        wp_die($not_allowed_msg, $not_allowed_title);
                    }
                } else {
                    wp_die($not_allowed_msg, $not_allowed_title);
                }
            }

            $orders = array_values(array_filter($orders));
            $orders = $this->verify_order_ids($orders);
            if (count($orders) > 0) {
                remove_action('wp_footer', 'wp_admin_bar_render', 1000);
                $action = (isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '');

                //action for modules to hook print function
                do_action('wt_print_doc', $orders, $action);
            }
            exit();
        }
    }

    /**
     * Check for valid order ids
     * @since 4.0.2
     * @since 4.0.3 Added compatiblity for `Sequential Order Numbers for WooCommerce`
     */
    public static function verify_order_ids($order_ids)
    {
        $out = array();
        foreach ($order_ids as $order_id) {
            if (wc_get_order($order_id) === false) {
                /* compatibility for sequential order number */
                $order_data = wc_get_orders(
                    array(
                        'limit' => 1,
                        'return' => 'ids',
                        'meta_query' => array(
                            'key' => '_order_number',
                            'value' => $order_id,
                        ),
                    ));
                if ($order_data != false && is_array($order_data) && count($order_data) == 1) {
                    $order_id = (int) $order_data[0];
                    if ($order_id > 0 && wc_get_order($order_id) != false) {
                        $out[] = $order_id;
                    }
                }
            } else {
                $out[] = $order_id;
            }
        }
        return $out;
    }

    /**
     * Ajax hook to load address from woo
     * @since 4.0.2
     */
    public function load_address_from_woo()
    {
        if (!self::check_write_access()) {
            exit();
        }
        $out = array(
            'status' => 1,
            'address_line1' => get_option('woocommerce_store_address'),
            'address_line2' => get_option('woocommerce_store_address_2'),
            'city' => get_option('woocommerce_store_city'),
            'country' => get_option('woocommerce_default_country'),
            'postalcode' => get_option('woocommerce_store_postcode'),
        );
        echo json_encode($out);
        exit();
    }

    /**
     * Get all templates.
     * @since 4.0.2
     */
    private function get_all_templates()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . Wf_Woocommerce_Packing_List::$template_data_tb;
        $qry = "SELECT * FROM $table_name";
        return $wpdb->get_results($qry, ARRAY_A);
    }

    /**
     * Form action for debug settings tab
     * @since 4.0.2
     */
    public function debug_save()
    {
        if (isset($_POST['wt_pklist_export_settings_btn'])) {
            if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
                return;
            }

            //module state
            $module_status = get_option('wt_pklist_common_modules');
            $settings = array();

            //enabling all modules otherwise settings export will not work properly
            $module_list = array(
                'invoice' => 1,
                'packinglist' => 1,
                'shippinglabel' => 1,
                'deliverynote' => 1,
                'dispathlabel' => 1,
                'addresslabel' => 1,
                'creditnote' => 1,
                'picklist' => 1,
                'proformainvoice' => 1,
            );
            update_option('wt_pklist_common_modules', $module_list);
            //=======================================

            foreach ($module_list as $key => $value) {
                $module_id = Wf_Woocommerce_Packing_List::get_module_id($key);
                $settings[$key] = Wf_Woocommerce_Packing_List::get_settings($module_id);
            }
            //general settings
            $settings['main'] = Wf_Woocommerce_Packing_List::get_settings();

            //restoring module state
            update_option('wt_pklist_common_modules', $module_status);

            $out = array(
                'plugin_version' => WF_PKLIST_VERSION,
                'settings' => $settings,
                'module_status' => $module_status,
                'template_data' => $this->get_all_templates(),
            );

            header('Content-Type: application/json');
            header('Content-disposition: attachment; filename="wt_pklist_settings.json"');
            echo json_encode($out);
            exit();
        }

        if (isset($_POST['wt_pklist_import_settings_btn'])) {
            if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
                return;
            }

            if (!empty($_FILES['wt_pklist_import_settings_json']['tmp_name'])) {
                $filename = $_FILES['wt_pklist_import_settings_json']['tmp_name'];
                $json_file = @fopen($filename, 'r');
                $json_data = fread($json_file, filesize($filename));
                $json_data_arr = json_decode($json_data, true);

                //module state
                $module_status = get_option('wt_pklist_common_modules');
                //enabling all modules otherwise settings import will not work properly
                $module_list = array(
                    'invoice' => 1,
                    'packinglist' => 1,
                    'shippinglabel' => 1,
                    'deliverynote' => 1,
                    'dispathlabel' => 1,
                    'addresslabel' => 1,
                    'creditnote' => 1,
                    'picklist' => 1,
                    'proformainvoice' => 1,
                );
                update_option('wt_pklist_common_modules', $module_list);
                if (isset($json_data_arr['settings'])) {
                    $settings = $json_data_arr['settings'];
                    foreach ($module_list as $key => $value) {
                        if (isset($settings[$key])) {
                            $module_id = Wf_Woocommerce_Packing_List::get_module_id($key);
                            Wf_Woocommerce_Packing_List::update_settings($settings[$key], $module_id);
                        }
                    }
                    //general settings
                    if (isset($settings['main'])) {
                        Wf_Woocommerce_Packing_List::update_settings($settings['main']);
                    }
                }

                //module status
                if (isset($json_data_arr['module_status'])) {
                    update_option('wt_pklist_common_modules', $json_data_arr['module_status']);
                } else {
                    //restoring module state
                    update_option('wt_pklist_common_modules', $module_status);
                }

                //template data
                if (isset($json_data_arr['template_data'])) {
                    if (is_array($json_data_arr['template_data'])) {
                        global $wpdb;
                        $db_vl = array();
                        foreach ($json_data_arr['template_data'] as $td) {
                            $db_vl[] = $wpdb->prepare("(%s,%s,%d,%d,%s,%d,%d)",
                                $td['template_name'],
                                $td['template_html'],
                                $td['template_from'],
                                $td['is_active'],
                                $td['template_type'],
                                $td['created_at'],
                                $td['updated_at']);
                        }
                        if (count($db_vl) > 0) {
                            $table_name = $wpdb->prefix . Wf_Woocommerce_Packing_List::$template_data_tb;
                            $wpdb->query("TRUNCATE `$table_name`"); //removing existing data
                            $query = "INSERT INTO `$table_name` (template_name,template_html,template_from,is_active,template_type,created_at,updated_at) VALUES " . implode(",", $db_vl);
                            $wpdb->query($query);
                        }
                    }
                }
            }
        }

        if (isset($_POST['wt_pklist_admin_modules_btn'])) {
            if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
                return;
            }

            $wt_pklist_common_modules = get_option('wt_pklist_common_modules');
            if ($wt_pklist_common_modules === false) {
                $wt_pklist_common_modules = array();
            }
            if (isset($_POST['wt_pklist_common_modules'])) {
                $wt_pklist_post = self::sanitize_text_arr($_POST['wt_pklist_common_modules']);
                foreach ($wt_pklist_common_modules as $k => $v) {
                    if (isset($wt_pklist_post[$k]) && $wt_pklist_post[$k] == 1) {
                        $wt_pklist_common_modules[$k] = 1;
                    } else {
                        $wt_pklist_common_modules[$k] = 0;
                    }
                }
            } else {
                foreach ($wt_pklist_common_modules as $k => $v) {
                    $wt_pklist_common_modules[$k] = 0;
                }
            }

            $wt_pklist_admin_modules = get_option('wt_pklist_admin_modules');
            if ($wt_pklist_admin_modules === false) {
                $wt_pklist_admin_modules = array();
            }
            if (isset($_POST['wt_pklist_admin_modules'])) {
                $wt_pklist_post = self::sanitize_text_arr($_POST['wt_pklist_admin_modules']);
                foreach ($wt_pklist_admin_modules as $k => $v) {
                    if (isset($wt_pklist_post[$k]) && $wt_pklist_post[$k] == 1) {
                        $wt_pklist_admin_modules[$k] = 1;
                    } else {
                        $wt_pklist_admin_modules[$k] = 0;
                    }
                }
            } else {
                foreach ($wt_pklist_admin_modules as $k => $v) {
                    $wt_pklist_admin_modules[$k] = 0;
                }
            }
            update_option('wt_pklist_admin_modules', $wt_pklist_admin_modules);
            update_option('wt_pklist_common_modules', $wt_pklist_common_modules);
            wp_redirect($_SERVER['REQUEST_URI']);exit();
        }

        if (Wf_Woocommerce_Packing_List_Admin::check_role_access()) //Check access
        {
            //module debug settings saving hook
            do_action('wt_pklist_module_save_debug_settings');
        }
    }

    public static function sanitize_text_arr($arr, $type = 'text')
    {
        if (is_array($arr)) {
            $out = array();
            foreach ($arr as $k => $arrv) {
                if (is_array($arrv)) {
                    $out[$k] = self::sanitize_text_arr($arrv, $type);
                } else {
                    if ($type == 'int') {
                        $out[$k] = intval($arrv);
                    } else {
                        $out[$k] = sanitize_text_field($arrv);
                    }
                }
            }
            return $out;
        } else {
            if ($type == 'int') {
                return intval($arr);
            } else {
                return sanitize_text_field($arr);
            }
        }
    }

    /**
     * Admin settings page
     *
     * @since    4.0.0
     */
    public function admin_settings_page()
    {
        $the_options = Wf_Woocommerce_Packing_List::get_settings();
        $no_image = Wf_Woocommerce_Packing_List::$no_image;
        $order_statuses = wc_get_order_statuses();
        $wf_generate_invoice_for = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_generate_for_orderstatus');

        /**
         *    @since 4.0.9
         *    Get available PDF libraries
         */
        $pdf_libs = Wf_Woocommerce_Packing_List::get_pdf_libraries();

        wp_enqueue_media();
        wp_enqueue_script('wc-enhanced-select');

        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');

        if (!Wf_Woocommerce_Packing_List_Admin::check_role_access()) {
            wp_die(__('You are not allowed to view this page.', 'wf-woocommerce-packing-list'));
        }

        /* enable/disable modules */
        if (isset($_POST['wf_update_module_status'])) {
            // Check nonce:
            if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
                exit();
            }

            $wt_pklist_common_modules = get_option('wt_pklist_common_modules');
            if ($wt_pklist_common_modules === false) {
                $wt_pklist_common_modules = array();
            }
            if (isset($_POST['wt_pklist_common_modules'])) {
                $wt_pklist_post = self::sanitize_text_arr($_POST['wt_pklist_common_modules']);
                foreach ($wt_pklist_common_modules as $k => $v) {
                    if (isset($wt_pklist_post[$k]) && $wt_pklist_post[$k] == 1) {
                        $wt_pklist_common_modules[$k] = 1;
                    } else {
                        $wt_pklist_common_modules[$k] = 0;
                    }
                }
            } else {
                foreach ($wt_pklist_common_modules as $k => $v) {
                    $wt_pklist_common_modules[$k] = 0;
                }
            }
            update_option('wt_pklist_common_modules', $wt_pklist_common_modules);
            wp_redirect($_SERVER['REQUEST_URI']);exit();
        }

        include WF_PKLIST_PLUGIN_PATH . '/admin/partials/wf-woocommerce-packing-list-admin-display.php';
    }

    public function validate_box_packing_field($value)
    {
        $new_boxes = array();
        foreach ($value as $key => $value) {
            if ($value['length'] != '') {
                $value['enabled'] = isset($value['enabled']) ? true : false;
                $new_boxes[] = $value;
            }
        }
        return $new_boxes;
    }

    /**
     *     @since 4.0.4
     *     Set tooltip for form fields
     */
    public static function set_tooltip($key, $base_id = "", $custom_css = "")
    {
        $tooltip_text = self::get_tooltips($key, $base_id);
        if ($tooltip_text != "") {
            $tooltip_text = '<span style="color:#16a7c5; ' . ($custom_css != "" ? $custom_css : 'margin-top:0px; margin-left:2px; position:absolute;') . '" class="dashicons dashicons-editor-help wt-tips" data-wt-tip="' . $tooltip_text . '"></span>';
        }
        return $tooltip_text;
    }

    /**
     *     @since 4.0.4
     *     Get tooltip config data for non form field items
     *     @return array 'class': class name to enable tooltip, 'text': tooltip text including data attribute if not empty
     */
    public static function get_tooltip_configs($key, $base_id = "")
    {
        $out = array('class' => '', 'text' => '');
        $text = self::get_tooltips($key, $base_id);
        if ($text != "") {
            $out['text'] = ' data-wt-tip="' . $text . '"';
            $out['class'] = ' wt-tips';
        }
        return $out;
    }

    /**
     *    @since 4.0.4
     *     This function will take tooltip data from modules and store ot
     *
     */
    public function register_tooltips()
    {
        include plugin_dir_path(__FILE__) . 'data/data.tooltip.php';
        self::$tooltip_arr = array(
            'main' => $arr,
        );
        /* hook for modules to register tooltip */
        self::$tooltip_arr = apply_filters('wt_pklist_alter_tooltip_data', self::$tooltip_arr);
    }

    /**
     *     Get tooltips
     *    @since 4.0.4
     *    @param string $key array key for tooltip item
     *    @param string $base module base id
     *     @return tooltip content, empty string if not found
     */
    public static function get_tooltips($key, $base_id = '')
    {
        $arr = ($base_id != "" && isset(self::$tooltip_arr[$base_id]) ? self::$tooltip_arr[$base_id] : self::$tooltip_arr['main']);
        return (isset($arr[$key]) ? $arr[$key] : '');
    }

    /**
     *     @since 4.0.0 create form fields
     *     @since 4.0.4 Added tooltip function
     *     @since 4.1.0 Function content moved to separate file, Added product attribute field type
     */
    public static function generate_form_field($args, $base = '')
    {
        include WF_PKLIST_PLUGIN_PATH . "admin/views/_form_field_generator.php";
    }

    /**
     * Envelope settings tab content with tab div.
     * relative path is not acceptable in view file
     */
    public static function envelope_settings_tabcontent($target_id, $view_file = "", $html = "", $variables = array(), $need_submit_btn = 0)
    {
        extract($variables);
        ?>
			<div class="wf-tab-content" data-id="<?php echo $target_id; ?>">
				<?php
if ($view_file != "" && file_exists($view_file)) {
            include_once $view_file;
        } else {
            echo $html;
        }
        ?>
				<?php
if ($need_submit_btn == 1) {
            self::add_settings_footer();
        }
        ?>
			</div>
		<?php
}

    /**
     *    Add setting tab footer
     *    @since 4.1.4 Added button text, Left/Right HTML option added
     */
    public static function add_settings_footer($settings_button_title = '', $settings_footer_left = '', $settings_footer_right = '')
    {
        include WF_PKLIST_PLUGIN_PATH . "admin/views/admin-settings-save-button.php";
    }

    /**
    Registers modules: public+admin
     */
    public function admin_modules()
    {
        $wt_pklist_admin_modules = get_option('wt_pklist_admin_modules');
        if ($wt_pklist_admin_modules === false) {
            $wt_pklist_admin_modules = array();
        }
        foreach (self::$modules as $module) //loop through module list and include its file
        {
            $is_active = 1;
            if (isset($wt_pklist_admin_modules[$module])) {
                $is_active = $wt_pklist_admin_modules[$module]; //checking module status
            } else {
                $wt_pklist_admin_modules[$module] = 1; //default status is active
            }
            $module_file = plugin_dir_path(__FILE__) . "modules/$module/$module.php";
            if (file_exists($module_file) && $is_active == 1) {
                self::$existing_modules[] = $module; //this is for module_exits checking
                require_once $module_file;
            } else {
                $wt_pklist_admin_modules[$module] = 0;
            }
        }
        $out = array();
        foreach ($wt_pklist_admin_modules as $k => $m) {
            if (in_array($k, self::$modules)) {
                $out[$k] = $m;
            }
        }
        update_option('wt_pklist_admin_modules', $out);
    }

    public static function module_exists($module)
    {
        return in_array($module, self::$existing_modules);
    }

    /**
     *    @since 4.0.5
     *     Recursively calculating and retriveing total files in the plugin temp directory
     *    @since 4.0.6 [Bugfix] Error when temp directory does not exists
     */
    public static function get_total_temp_files()
    {
        $file_count = 0;
        $upload_dir = Wf_Woocommerce_Packing_List::get_temp_dir('path');
        if (is_dir($upload_dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $file_name = $file->getFilename();
                    $file_ext_arr = explode('.', $file_name);
                    $file_ext = end($file_ext_arr);
                    if (($file_ext === 'pdf') || ($file_ext === 'html')) //we are creating pdf files as temp files
                    {
                        $file_count++;
                    }
                }
            }
        }
        return $file_count;
    }

    /**
     *    @since 4.0.5
     *     Schedule temp file clearing
     */
    public function schedule_temp_file_clearing()
    {
        $is_auto_clear = Wf_Woocommerce_Packing_List::get_option('wf_pklist_auto_temp_clear');

        /* interval in minutes */
        $is_auto_clear_interval = (int) Wf_Woocommerce_Packing_List::get_option('wf_pklist_auto_temp_clear_interval');

        if ($is_auto_clear == 'Yes' && $is_auto_clear_interval > 0) //if auto clear enabled, and interval greater than zero
        {
            if (!wp_next_scheduled('wt_pklist_auto_clear_temp_files')) {
                $start_time = strtotime("now +{$is_auto_clear_interval} minutes");
                wp_schedule_event($start_time, 'wt_pklist_temp_clear_interval', 'wt_pklist_auto_clear_temp_files');
            }
        } else {
            if (wp_next_scheduled('wt_pklist_auto_clear_temp_files')) //its already scheduled then remove
            {
                $this->unschedule_temp_file_clearing();
            }
        }
    }

    /**
     *    @since 4.0.5
     *     Unschedule temp file clearing
     */
    public function unschedule_temp_file_clearing()
    {
        wp_clear_scheduled_hook('wt_pklist_auto_clear_temp_files');
    }

    /**
     *    @since 4.0.5
     *     Registering new time interval for temp file deleting cron
     */
    public function cron_interval_for_temp($schedules)
    {
        $is_auto_clear = Wf_Woocommerce_Packing_List::get_option('wf_pklist_auto_temp_clear');
        if ($is_auto_clear == 'Yes') //if auto clear enabled
        {
            /* interval in minutes */
            $is_auto_clear_interval = (int) Wf_Woocommerce_Packing_List::get_option('wf_pklist_auto_temp_clear_interval');
            if ($is_auto_clear_interval > 0) {
                $schedules['wt_pklist_temp_clear_interval'] = array(
                    'interval' => ($is_auto_clear_interval * 60),
                    'display' => sprintf(__('Every %d minutes', 'wf-woocommerce-packing-list'), $is_auto_clear_interval),
                );
            }
        }
        return $schedules;
    }

    /**
     *    @since 4.0.5
     *     Delete temp files in the plugin temp directory
     */
    public function delete_temp_files_recrusively()
    {
        $backup_dir = Wf_Woocommerce_Packing_List::get_temp_dir('path');
        if (is_dir($backup_dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backup_dir), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $file_name = $file->getFilename();
                    $file_ext_arr = explode('.', $file_name);
                    $file_ext = end($file_ext_arr);
                    if (($file_ext === 'pdf') || ($file_ext === 'zip') || ($file_ext === 'html')) //temp pdf files and zip files
                    {
                        @unlink($file);
                    }
                }
            }
        }
    }

    /**
     *    @since 4.0.5
     *     Ajax hook for deleting files in the plugin temp directory
     */
    public function delete_all_temp()
    {
        $out = array('status' => 0, 'msg' => __('Error', 'wf-woocommerce-packing-list'));

        // Check permission
        if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
            echo json_encode($out);
            exit();
        }

        /* recrusively delete files */
        $this->delete_temp_files_recrusively();

        $out['status'] = 1;
        $out['msg'] = __('Successfully cleared all temp files.', 'wf-woocommerce-packing-list');
        $out['extra_msg'] = __('No files found.', 'wf-woocommerce-packing-list');
        echo json_encode($out);
        exit();
    }

    /**
     *      Download temp zip file via a nonce URL
     *    @since 4.0.6
     */
    public function download_temp_zip_file()
    {
        if (isset($_GET['wt_pklist_download_temp_zip'])) {
            if (self::check_write_access()) /* check nonce and role */
            {
                $file_name = (isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '');
                if ($file_name != "") {
                    $file_arr = explode(".", $file_name);
                    $file_ext = end($file_arr);
                    if ($file_ext == 'zip') /* only zip files */
                    {
                        $backup_dir = Wf_Woocommerce_Packing_List::get_temp_dir('path');
                        $file_path = $backup_dir . '/' . $file_name;
                        if (file_exists($file_path)) /* check existence of file */
                        {
                            header('Pragma: public');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                            header('Cache-Control: private', false);
                            header('Content-Transfer-Encoding: binary');
                            header('Content-Disposition: attachment; filename="' . $file_name . '";');
                            header('Content-Type: application/zip');
                            header('Content-Length: ' . filesize($file_path));

                            $chunk_size = 1024 * 1024;
                            $handle = @fopen($file_path, 'rb');
                            while (!feof($handle)) {
                                $buffer = fread($handle, $chunk_size);
                                echo $buffer;
                                ob_flush();
                                flush();
                            }
                            fclose($handle);
                            exit();
                        }
                    }
                }
            }
        }
    }

    /**
     *    @since 4.0.5
     *     Download all files as zip in the plugin temp directory
     *    @since 4.0.6 Direct access to zip file blocked. Generates a nonce URL for download
     */
    public function download_all_temp()
    {
        $out = array('status' => 0, 'msg' => __('Error', 'wf-woocommerce-packing-list'), 'fileurl' => '');

        // Check permission
        if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
            echo json_encode($out);
            exit();
        }

        $zip = new ZipArchive();
        $backup_dir = Wf_Woocommerce_Packing_List::get_temp_dir('path');
        $backup_url = Wf_Woocommerce_Packing_List::get_temp_dir('url');
        $backup_file_name = 'wt_pklist_temp_backup.zip';
        $backup_file = $backup_dir . '/' . $backup_file_name;
        $backup_file_url = $backup_url . '/' . $backup_file_name;

        $zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        if (is_dir($backup_dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backup_dir), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically if not empty)
                if (!$file->isDir()) {
                    $file_name = $file->getFilename();
                    $file_ext_arr = explode('.', $file_name);
                    $file_ext = end($file_ext_arr);
                    if (($file_ext === 'pdf') || ($file_ext === 'html')) //we are creating pdf files as temp files
                    {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($backup_dir) + 1);
                        $zip->addFile($filePath, basename($backup_dir) . '/' . $relativePath);
                    }
                }
            }
        }
        $zip->close();

        $out['status'] = 1;
        $out['msg'] = '';
        $out['fileurl'] = html_entity_decode(wp_nonce_url(admin_url('admin.php?wt_pklist_download_temp_zip=true&file=' . $backup_file_name), WF_PKLIST_PLUGIN_NAME));
        echo json_encode($out);
        exit();
    }

    /**
     *    @since 4.0.5
     *     Settings validation function for modules and plugin settings
     */
    public function validate_settings_data($val, $key, $validation_rule = array())
    {
        if (isset($validation_rule[$key]) && is_array($validation_rule[$key])) /* rule declared/exists */
        {
            if (isset($validation_rule[$key]['type'])) {
                if ($validation_rule[$key]['type'] == 'text') {
                    $val = sanitize_text_field($val);
                } elseif ($validation_rule[$key]['type'] == 'text_arr') {
                    $val = self::sanitize_text_arr($val);
                } elseif ($validation_rule[$key]['type'] == 'int') {
                    $val = intval($val);
                } elseif ($validation_rule[$key]['type'] == 'float') {
                    $val = floatval($val);
                } elseif ($validation_rule[$key]['type'] == 'int_arr') {
                    $val = self::sanitize_text_arr($val, 'int');
                } elseif ($validation_rule[$key]['type'] == 'textarea') {
                    $val = sanitize_textarea_field($val);
                } else {
                    $val = sanitize_text_field($val);
                }
            }
        } else {
            $val = sanitize_text_field($val);
        }
        return $val;
    }

    /**
     *    Get available checkout fields. Default + User created
     *    @since 4.1.0
     */
    private static function get_checkout_field_list()
    {
        /* built in checkout fields */
        $default_checkout_fields = Wf_Woocommerce_Packing_List::$default_additional_checkout_data_fields;

        /* list of user created items */
        $user_created_checkout_fields = Wf_Woocommerce_Packing_List::get_option('wf_additional_checkout_data_fields');
        $user_created_checkout_fields = Wf_Woocommerce_Packing_List::process_checkout_fields($user_created_checkout_fields);

        return array_merge($default_checkout_fields, $user_created_checkout_fields);
    }

    /**
     *    Alter custom checkout fields. (Ajax sub function)
     *    @since 4.1.0
     */
    private static function edit_checkout_fields($out)
    {
        /* currently selected values */
        $vl = Wf_Woocommerce_Packing_List::get_option('wf_invoice_additional_checkout_data_fields');
        $user_selected_array = ($vl && is_array($vl)) ? $vl : array();

        /* list of user created items */
        $user_created = Wf_Woocommerce_Packing_List::get_option('wf_additional_checkout_data_fields');

        /* if it is a numeric array convert it to associative.[Bug fix 4.0.1]    */
        $user_created = Wf_Woocommerce_Packing_List::process_checkout_fields($user_created);

        /* built in checkout fields */
        $add_checkout_data_flds = Wf_Woocommerce_Packing_List::$default_additional_checkout_data_fields;

        /* form input for adding new key */
        $new_meta_key = Wf_Woocommerce_Packing_List::process_checkout_key($_POST['wt_pklist_new_custom_field_key']);
        $new_meta_vl = sanitize_text_field($_POST['wt_pklist_new_custom_field_title']);

        /* check the new key is not a built-in or custom */
        if (!isset($user_created[$new_meta_key]) && !isset($add_checkout_data_flds[$new_meta_key])) {
            /* updating new item to user created list */
            $user_created[$new_meta_key] = $new_meta_vl;
            Wf_Woocommerce_Packing_List::update_option('wf_additional_checkout_data_fields', $user_created);

            if (!in_array($new_meta_key, $user_selected_array)) /* checks not already selected */
            {
                /* add to currently selected values */
                $user_selected_array[] = $new_meta_key;
                Wf_Woocommerce_Packing_List::update_option('wf_invoice_additional_checkout_data_fields', $user_selected_array);
            }
            $action = 'add';
        } else {
            //editing...
            $action = 'edit';
        }
        $out = array(
            'key' => $new_meta_key,
            'val' => $new_meta_vl . ' (' . $new_meta_key . ')' . ($is_required == 1 ? ' (' . __('required', 'wf-woocommerce-packing-list') . ')' : ''),
            'success' => true,
            'action' => $action,
        );

        //add metakey extra information (required, placeholder etc)
        $field_extra_info = Wf_Woocommerce_Packing_List::get_option('wt_additional_checkout_field_options');
        $placeholder = (isset($_POST['wt_pklist_new_custom_field_title_placeholder']) ? sanitize_text_field($_POST['wt_pklist_new_custom_field_title_placeholder']) : '');
        $is_required = (isset($_POST['wt_pklist_cst_chkout_required']) ? intval($_POST['wt_pklist_cst_chkout_required']) : 0);
        $field_extra_info[$new_meta_key] = array('placeholder' => $placeholder, 'is_required' => $is_required, 'title' => $new_meta_vl);
        Wf_Woocommerce_Packing_List::update_option('wt_additional_checkout_field_options', $field_extra_info);

        return $out;
    }

    // public function save_intructions()
    // {
    //     global $wpdb;

    //     // check_ajax_referer('wt_pklist_nonce', 'security');

    //     if (isset($_POST['formData'])) {


    //         $form_data = $_POST['formData'];
	// 		// print_r($form_data);
	// 		// exit();
    //     	parse_str($form_data, $sanitized_data);
	// 		$text_instruction = $sanitized_data['text_instruction'];
	// 		$instruction_type = $sanitized_data['instruction_type'];
	// 		$p_parameter1 = $sanitized_data['p_parameter1'];
	// 		$p_parameter2 = $sanitized_data['p_parameter2'];
	// 		$p_parameter3 = $sanitized_data['p_parameter3'];
	// 		$p_parameter4 = $sanitized_data['p_parameter4'];
	// 		$p_parameter5 = $sanitized_data['p_parameter5'];

	// 		if ($instruction_type === 'text') {
	// 			$text_instruction = $sanitized_data['text_instruction'];
	// 		} else if ($instruction_type === 'file') {
	// 			$file_instruction = $sanitized_data['file_instruction'];
	// 		}


	// 		// insert data to packing_instructions

    //         $table_name = $wpdb->prefix . 'packing_instructions';
	// 		$data_to_insert = array(
	// 			'text_instruction' => $text_instruction,
	// 			'file_instruction' => $file_instruction,
	// 			'instruction_type' => $instruction_type,
	// 		);
	// 		print_r($data_to_insert);
	// 		// die;

    //         $insert_result = $wpdb->insert($table_name, $data_to_insert);

    //         if ($insert_result === false) {
    //             wp_send_json_error('Failed to insert data into the database.');
    //         } else {
    //             wp_send_json_success('Data inserted successfully.');
    //         }
    //     } else {
    //         wp_send_json_error('formData is missing in the request.');
    //     }

    //     wp_die();
    // }



	public function save_intructions()
	{
    	global $wpdb;

    	// Check if formData is set
   	 	if (isset($_POST['action'])) {
            // echo "<pre>";
            // print_r($_POST);
            // exit;

			if (!function_exists('wp_handle_upload')) {
				require_once(ABSPATH . 'wp-admin/includes/file.php');
			}

			if(isset($_FILES['file'])){
				$uploadedfile = $_FILES['file'];
				$upload_overrides = array('test_form' => false);
				$movefile = wp_handle_upload($uploadedfile, $upload_overrides);

				// echo $movefile['url'];
				if (isset($movefile['error'])) {
					echo $movefile['error'];
					die();
				}
			}


			// Extract data from formData
			$text_instruction = isset($_POST['text_instruction']) ? sanitize_text_field($_POST['text_instruction']) : '';
			$file_instruction = isset($_POST['file_instruction']) ? sanitize_text_field($_POST['file_instruction']) : '';
			$instruction_type = isset($_POST['instruction_type']) ? sanitize_text_field($_POST['instruction_type']) : '';
			$create_at = current_time('mysql');
			$update_at = current_time('mysql');

			// echo $text_instruction;die;

        	// Insert data into 'packing_instructions' table
        	$table_name = $wpdb->prefix . 'packing_instructions';
			$data_to_insert = array(
				'text_instruction' => $text_instruction,
				'file_instruction' => isset($movefile['url']) && $movefile['url'] != "" ? $movefile['url'] : '',
				'instruction_type' => $instruction_type,
				'created_at' => $create_at,
				'update_at' => $update_at
			);

	        $insert_result = $wpdb->insert($table_name, $data_to_insert);

    	    // Check if insertion was successful
			if ($insert_result === false) {
				wp_send_json_error('Failed to insert data into the database: ' . $wpdb->last_error);

			} else {
				$instruction_id = $wpdb->insert_id;
				$conditions_table_name = $wpdb->prefix . 'packing_conditions';
				$condition_data_to_insert = array(
					'p_parameter1' => $_POST['p_parameter1'] ? $_POST['p_parameter1'] : '',
					'p_parameter2' => $_POST['p_parameter2'] ? $_POST['p_parameter2'] : '',
					'p_parameter3' => $_POST['p_parameter3'] ? $_POST['p_parameter3'] : '',
					'p_parameter4' => $_POST['p_parameter4'] ? $_POST['p_parameter4'] : '',
					'p_parameter5' => $_POST['p_parameter5'] ? $_POST['p_parameter5'] : '',
					'create_at' => $create_at,
					'instruction_id' => $instruction_id
				);

				$insert_conditions_result = $wpdb->insert($conditions_table_name, $condition_data_to_insert);

				if ($insert_conditions_result === false) {
					wp_send_json_error('Failed to insert conditions data into the database: ' . $wpdb->last_error);
				} else {
					wp_send_json_success('Data inserted successfully.');
				}
			}
    	} else {
        	wp_send_json_error('formData is missing in the request.');
    	}

    	wp_die();
	}


    public function delete_instruction(){
        if (isset($_POST['instruction_id']) && is_numeric($_POST['instruction_id'])) {
            $instructionId = intval($_POST['instruction_id']);
            echo $instructionId;
            // require_once("wp-config.php");
            // exit;
            global $wpdb;

            // Delete instruction from packing_instructions table
            $instruction_table_name = $wpdb->prefix . 'packing_instructions';
            $wpdb->delete($instruction_table_name, array('id' => $instructionId));

            // Delete related conditions from packing_condition table
            $condition_table_name = $wpdb->prefix . 'packing_conditions';
            $wpdb->delete($condition_table_name, array('instruction_id' => $instructionId));
        } else {

            echo "Error: Invalid instruction ID.";
        }
    }
    public function delete_condition(){
        if (isset($_POST['condition_id']) && is_numeric($_POST['condition_id'])) {
            $conditionId = intval($_POST['condition_id']);
            echo $conditionId;
            global $wpdb;

            // Delete related conditions from packing_condition table
            $condition_table_name = $wpdb->prefix . 'packing_conditions';
            $wpdb->delete($condition_table_name, array('id' => $conditionId));
        } else {

            echo "Error: Invalid condition ID.";
        }
    }

    public function save_condition(){
    	global $wpdb;

    	// Check if formData is set
   	 	if (isset($_POST['action'])) {

    	    // Check if insertion was successful
            $instruction_id=$_POST['instruction_id'];

				$conditions_table_name = $wpdb->prefix . 'packing_conditions';
				$condition_data_to_insert = array(
					'p_parameter1' => $_POST['p_parameter1'] ? $_POST['p_parameter1'] : '',
					'p_parameter2' => $_POST['p_parameter2'] ? $_POST['p_parameter2'] : '',
					'p_parameter3' => $_POST['p_parameter3'] ? $_POST['p_parameter3'] : '',
					'p_parameter4' => $_POST['p_parameter4'] ? $_POST['p_parameter4'] : '',
					'p_parameter5' => $_POST['p_parameter5'] ? $_POST['p_parameter5'] : '',

					'instruction_id' => $instruction_id
				);

				$insert_conditions_result = $wpdb->insert($conditions_table_name, $condition_data_to_insert);

				if ($insert_conditions_result === false) {
					wp_send_json_error('Failed to insert conditions data into the database: ' . $wpdb->last_error);
				} else {
					wp_send_json_success('Data inserted successfully.');
				}
            }
    	wp_die();
	}





    /**
     * Ajax function to list additional checkout fields/ product meta/ order meta etc
     * @since 4.0.3
     * @since 4.0.5 Role checking and nonce checking added
     * @since 4.1.0 Added compatibility to product meta, order meta
     */

    public function custom_field_list_view()
    {
        if (!Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
            exit();
        }
        $custom_field_type = (isset($_POST['wt_pklist_custom_field_type']) ? sanitize_text_field($_POST['wt_pklist_custom_field_type']) : '');

        if ($custom_field_type != "") {

            $module_base = (isset($_POST['wt_pklist_settings_base']) ? sanitize_text_field($_POST['wt_pklist_settings_base']) : 'main');
            $module_id = ($module_base == 'main' ? '' : Wf_Woocommerce_Packing_List::get_module_id($module_base));

            $field_config = array(
                'checkout' => array(
                    'list' => 'wf_additional_checkout_data_fields',
                    'selected' => 'wf_invoice_additional_checkout_data_fields',
                ),
                'order_meta' => array(
                    'list' => 'wf_additional_data_fields',
                    'selected' => 'wf_' . $module_base . '_contactno_email',
                ),
                'product_meta' => array(
                    'list' => 'wf_product_meta_fields',
                    'selected' => 'wf_' . $module_base . '_product_meta_fields',
                ),
                'product_attribute' => array(
                    'list' => 'wt_product_attribute_fields',
                    'selected' => 'wt_' . $module_base . '_product_attribute_fields',
                ),
            );

            /* option key names for full list, selected list */
            $list_field = $field_config[$custom_field_type]['list'];
            $val_field = $field_config[$custom_field_type]['selected'];

            /* list of user created items */
            $user_created = Wf_Woocommerce_Packing_List::get_option($list_field);
            $user_created = $user_created && is_array($user_created) ? $user_created : array();

            $default_fields = array();
            $additional_field_options = array();
            if ($custom_field_type == 'checkout') {
                /* if it is a numeric array convert it to associative.[Bug fix 4.0.1]    */
                $user_created = Wf_Woocommerce_Packing_List::process_checkout_fields($user_created);
                $additional_field_options = Wf_Woocommerce_Packing_List::get_option('wt_additional_checkout_field_options');
                $default_fields = Wf_Woocommerce_Packing_List::$default_additional_checkout_data_fields;
            }

            $vl = Wf_Woocommerce_Packing_List::get_option($val_field, $module_id);
            $user_selected_arr = ($vl != '' && is_array($vl) ? $vl : array());

            //delete action
            if (isset($_POST['wf_delete_custom_field'])) {
                $data_key = sanitize_text_field($_POST['wf_delete_custom_field']);
                unset($user_created[$data_key]); //remove from field list
                Wf_Woocommerce_Packing_List::update_option($list_field, $user_created);

                if ($custom_field_type == 'checkout') {
                    unset($additional_field_options[$data_key]); //remove from field additional options
                    Wf_Woocommerce_Packing_List::update_option('wt_additional_checkout_field_options', $additional_field_options);
                }

                //remove from user selected array
                if (($delete_key = array_search($data_key, $user_selected_arr)) !== false) {
                    unset($user_selected_arr[$delete_key]);
                    Wf_Woocommerce_Packing_List::update_option($val_field, $user_selected_arr, $module_id);
                }
            }

            $fields = array_merge($default_fields, $user_created);
            if (count($fields) > 0) {
                foreach ($fields as $key => $field) {
                    $add_data = isset($additional_field_options[$key]) ? $additional_field_options[$key] : array();
                    $is_required = (int) (isset($add_data['is_required']) ? $add_data['is_required'] : 0);
                    $placeholder = (isset($add_data['placeholder']) ? $add_data['placeholder'] : '');

                    /* we are giving option to edit title of builtin items */
                    $field = (isset($add_data['title']) && trim($add_data['title']) != "" ? $add_data['title'] : $field);

                    $is_required_display = ($is_required > 0 ? ' <span style="color:red;">*</span>' : '');
                    $placeholder_display = ($placeholder != "" ? '<br /><i style="color:#666;">' . $placeholder . '</i>' : '');

                    $is_builtin = (isset($default_fields[$key]) ? 1 : 0);
                    $delete_btn = '<span title="' . __('Delete') . '" class="dashicons dashicons-trash wt_pklist_custom_field_delete ' . ($is_builtin == 1 ? 'disabled_btn' : '') . '"></span>';
                    $edit_btn = '<span title="' . __('Edit') . '" class="dashicons dashicons-edit wt_pklist_custom_field_edit"></span>';

                    //$delete_btn=($is_builtin==1 ? '' : $delete_btn);
                    $is_selected = (in_array($key, $user_selected_arr) ? '<span class="dashicons dashicons-yes-alt" style="color:green; float:right;"></span>' : '');
                    $is_selected = '';

                    $meta_key_display = Wf_Woocommerce_Packing_List::get_display_key($key);
                    ?>
					<div class="wt_pklist_custom_field_item" data-key="<?php echo $key; ?>" data-builtin="<?php echo $is_builtin; ?>"><?php echo $edit_btn . $delete_btn . $is_selected . $field . $meta_key_display . $is_required_display . $placeholder_display; ?>
						<div class="wt_pklist_custom_field_title" style="display:none;"><?php echo $field; ?></div>
						<div class="wt_pklist_custom_field_placeholder" style="display:none;"><?php echo $placeholder; ?></div>
						<div class="wt_pklist_custom_field_is_required" style="display:none;"><?php echo $is_required; ?></div>
					</div>
					<?php
}
            } else {
                ?>
				<div style="text-align:center;"><?php _e('No data found', 'wf-woocommerce-packing-list');?></div>
				<?php
}
        }
        exit();
    }

    /**
     * Fields like `Order meta fields`, `Product meta fields` etc have extra popup for saving item. Ajax hook
     * @since 4.0.0
     * @since 4.0.1 added separate fields for key and value for checkout fields and added compatibility to old users
     * @since 4.0.3 is_required and placeholder options added
     * @since 4.0.5 Combined independent hooks from each modules
     * @since 4.1.0 Edit option added to Order meta, Product meta etc, Added Product attribute
     */
    public static function advanced_settings($module_base = '', $module_id = '')
    {
        $out = array('key' => '', 'val' => '', 'success' => false, 'msg' => __('Error', 'wf-woocommerce-packing-list'));
        $warn_msg = __('Please enter mandatory fields', 'wf-woocommerce-packing-list');

        if (Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
            if (isset($_POST['wt_pklist_custom_field_btn'])) {
                //additional fields for checkout
                if (isset($_POST['wt_pklist_new_custom_field_title']) && isset($_POST['wt_pklist_new_custom_field_key']) && isset($_POST['wt_pklist_custom_field_type'])) {
                    if (trim($_POST['wt_pklist_new_custom_field_title']) != "" && trim($_POST['wt_pklist_new_custom_field_key']) != "") {
                        $custom_field_type = sanitize_text_field($_POST['wt_pklist_custom_field_type']);
                        //checkout
                        if ($custom_field_type == 'checkout') {
                            $out = self::edit_checkout_fields($out);

                        } elseif ($custom_field_type == 'order_meta' || $custom_field_type == 'product_meta' || $custom_field_type == 'product_attribute') {
                            $module_base = (isset($_POST['wt_pklist_settings_base']) ? sanitize_text_field($_POST['wt_pklist_settings_base']) : 'main');
                            $module_id = ($module_base == 'main' ? '' : Wf_Woocommerce_Packing_List::get_module_id($module_base));
                            $add_only = (isset($_POST['add_only']) ? true : false);
                            $field_config = array(
                                'order_meta' => array(
                                    'list' => 'wf_additional_data_fields',
                                    'selected' => 'wf_' . $module_base . '_contactno_email',
                                ),
                                'product_meta' => array(
                                    'list' => 'wf_product_meta_fields',
                                    'selected' => 'wf_' . $module_base . '_product_meta_fields',
                                ),
                                'product_attribute' => array(
                                    'list' => 'wt_product_attribute_fields',
                                    'selected' => 'wt_' . $module_base . '_product_attribute_fields',
                                ),
                            );

                            /* form input */
                            $new_meta_key = sanitize_text_field($_POST['wt_pklist_new_custom_field_key']);
                            $new_meta_vl = sanitize_text_field($_POST['wt_pklist_new_custom_field_title']);

                            /* option key names for full list, selected list */
                            $list_field = $field_config[$custom_field_type]['list'];
                            $val_field = $field_config[$custom_field_type]['selected'];

                            /* list of user created items */
                            $user_created = Wf_Woocommerce_Packing_List::get_option($list_field); //this is plugin main setting so no need to specify module base

                            /* updating new item to user created list */
                            $user_created = $user_created && is_array($user_created) ? $user_created : array();
                            $action = (isset($user_created[$new_meta_key]) ? 'edit' : 'add');

                            $can_add_item = true;
                            if ($action == 'edit' && $add_only) {
                                $can_add_item = false;
                            }

                            if ($can_add_item) {
                                $user_created[$new_meta_key] = $new_meta_vl;
                                Wf_Woocommerce_Packing_List::update_option($list_field, $user_created);
                            }

                            if (!$add_only) {
                                $vl = Wf_Woocommerce_Packing_List::get_option($val_field, $module_id);
                                $user_selected_arr = ($vl != '' && is_array($vl) ? $vl : array());

                                if (!in_array($new_meta_key, $user_selected_arr)) {
                                    $user_selected_arr[] = $new_meta_key;
                                    Wf_Woocommerce_Packing_List::update_option($val_field, $user_selected_arr, $module_id);
                                }
                            }

                            if ($can_add_item) {
                                $new_meta_key_display = Wf_Woocommerce_Packing_List::get_display_key($new_meta_key);

                                $dc_slug = self::sanitize_css_class_name($new_meta_key_display); /* This is for Dynamic customizer */

                                $out = array('key' => $new_meta_key, 'val' => $new_meta_vl . $new_meta_key_display, 'dc_slug' => $dc_slug, 'success' => true, 'action' => $action);
                            } else {
                                $out['msg'] = __('Item with same meta key already exists', 'wf-woocommerce-packing-list');
                            }
                        }

                    } else {
                        $out['msg'] = $warn_msg;
                    }
                }
            }
        }
        echo json_encode($out);
        exit();
    }

    public static function sanitize_css_class_name($str)
    {
        return preg_replace('/[^\-_a-zA-Z0-9]+/', '', $str);
    }

    /**
     *    @since 4.0.5
     *     Save admin settings and module settings ajax hook
     */
    public function save_settings()
    {
        $out = array(
            'status' => false,
            'msg' => __('Error', 'wf-woocommerce-packing-list'),
        );

        $base = (isset($_POST['wf_settings_base']) ? sanitize_text_field($_POST['wf_settings_base']) : 'main');
        $base_id = ($base == 'main' ? '' : Wf_Woocommerce_Packing_List::get_module_id($base));
        if (Wf_Woocommerce_Packing_List_Admin::check_write_access()) {
            $the_options = Wf_Woocommerce_Packing_List::get_settings($base_id);

            //multi select form fields array. (It will not return a $_POST val if it's value is empty so we need to set default value)
            $default_val_needed_fields = array(
                'wf_invoice_additional_checkout_data_fields' => array(),
                'woocommerce_wf_attach_shipping_label' => array(),
            ); //this is for plugin settings default. Modules can alter

            /* this is an internal filter */
            $default_val_needed_fields = apply_filters('wt_pklist_intl_alter_multi_select_fields', $default_val_needed_fields, $base_id);

            $validation_rule = array(
                'woocommerce_wf_generate_for_orderstatus' => array('type' => 'text_arr'),
                'woocommerce_wf_attach_shipping_label' => array('type' => 'text_arr'),
                'wf_additional_data_fields' => array('type' => 'text_arr'),
                'wf_product_meta_fields' => array('type' => 'text_arr'),
                'wt_product_attribute_fields' => array('type' => 'text_arr'),
                'woocommerce_wf_generate_for_taxstatus' => array('type' => 'text_arr'),
                'wf_additional_checkout_data_fields' => array('type' => 'text_arr'),
                'wf_invoice_additional_checkout_data_fields' => array('type' => 'text_arr'),
                'woocommerce_wf_packinglist_footer' => array('type' => 'textarea'),
                'woocommerce_wf_packinglist_special_notes' => array('type' => 'textarea'),
                'woocommerce_wf_packinglist_return_policy' => array('type' => 'textarea'),
                'woocommerce_wf_packinglist_transport_terms' => array('type' => 'textarea'),
                'woocommerce_wf_packinglist_sale_terms' => array('type' => 'textarea'),
                'woocommerce_wf_packinglist_boxes' => array('type' => 'text_arr'),
                'wt_additional_checkout_field_options' => array('type' => 'text_arr'),
                'wf_pklist_auto_temp_clear_interval' => array('type' => 'int'),
            ); //this is for plugin settings default. Modules can alter

            $validation_rule = apply_filters('wt_pklist_intl_alter_validation_rule', $validation_rule, $base_id);
            foreach ($the_options as $key => $value) {
                // Payment keys save
                if (($key == "woocommerce_wf_enable_payment_link_in_invoice") && (!array_key_exists('woocommerce_wf_enable_payment_link_in_invoice', $_POST)) && (array_key_exists('woocommerce_wf_payment_link_in_order_status', $_POST))) {
                    $_POST['woocommerce_wf_enable_payment_link_in_invoice'] = 0;
                } elseif (($key == "woocommerce_wf_show_pay_later_in_checkout") && (!array_key_exists('woocommerce_wf_show_pay_later_in_checkout', $_POST)) && (array_key_exists('woocommerce_wf_pay_later_title', $_POST))) {
                    $_POST['woocommerce_wf_show_pay_later_in_checkout'] = 0;
                }

                if (isset($_POST[$key])) {
                    $the_options[$key] = $this->validate_settings_data($_POST[$key], $key, $validation_rule);
                    if ($key == 'woocommerce_wf_packinglist_boxes') {
                        $the_options[$key] = $this->validate_box_packing_field($_POST[$key]);
                    }
                } else {
                    if (array_key_exists($key, $default_val_needed_fields)) {
                        /* Set a hidden field for every multi-select field in the form. This will be used to populate the multi-select field with an empty array when it does not have any value. */
                        if (isset($_POST[$key . '_hidden'])) {
                            $the_options[$key] = $default_val_needed_fields[$key];
                        }
                    }
                }
            }

            Wf_Woocommerce_Packing_List::update_settings($the_options, $base_id);
            if ($base == 'invoice') {
                Wf_Woocommerce_Packing_List_Invoice::save_paylater_settings();
            }
            do_action('wf_pklist_intl_after_setting_update', $the_options, $base_id);

            $out['status'] = true;
            $out['msg'] = __('Settings Updated', 'wf-woocommerce-packing-list');
        }
        echo json_encode($out);
        exit();
    }

    /**
     *    @since 4.0.5
     *     Strip unwanted HTML from template HTML
     */
    public static function strip_unwanted_tags($html)
    {
        $html = html_entity_decode(stripcslashes($html));
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $html = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $html);
        $html = preg_replace('#<audio(.*?)>(.*?)</audio>#is', '', $html);
        $html = preg_replace('#<video(.*?)>(.*?)</video>#is', '', $html);
        return $html;
    }

    /**
     *    @since 4.0.9
     *     List of all languages with locale name and native name
     *     @return array An associative array of languages.
     */
    public static function get_language_list()
    {
        include plugin_dir_path(__FILE__) . 'data/data.language-list.php';

        /**
         *    Alter language list.
         *    @param array An associative array of languages.
         */
        $wt_pklist_language_list = apply_filters('wt_pklist_alter_language_list', $wt_pklist_language_list);

        return $wt_pklist_language_list;
    }

    /**
     *    @since 4.0.9 Get list of RTL languages
     *    @return array an associative array of RTL languages with locale name, native name, locale code, WP locale code
     */
    public static function get_rtl_languages()
    {
        $rtl_lang_keys = array('ar', 'dv', 'he_IL', 'ps', 'fa_IR', 'ur');

        /**
         *    Alter RTL language list.
         *    @param array RTL language locale codes (WP specific locale codes)
         */
        $rtl_lang_keys = apply_filters('wt_pklist_alter_rtl_language_list', $rtl_lang_keys);

        $lang_list = self::get_language_list(); //taking full language list

        $rtl_lang_keys = array_flip($rtl_lang_keys);
        return array_intersect_key($lang_list, $rtl_lang_keys);
    }

    /**
     *    @since 4.0.9 Checks user enabled RTL and current language needs RTL support.
     *    @return boolean
     */
    public static function is_enable_rtl_support()
    {
        if (!is_null(self::$is_enable_rtl)) /* already checked then return the stored result */
        {
            return self::$is_enable_rtl;
        }
        $rtl_languages = self::get_rtl_languages();
        $current_lang = get_locale();

        self::$is_enable_rtl = isset($rtl_languages[$current_lang]);
        return self::$is_enable_rtl;
    }

    /**
     *    @since 4.0.9
     *     Get all site languages
     *     @return string[] An array of language codes.
     */
    public static function get_site_languages()
    {
        $langs = get_available_languages();
        $lang_list = self::get_language_list();
        $out = array(
            'all' => __('All', 'wf-woocommerce-packing-list'),
        );

        if (is_array($lang_list) && is_array($langs)) {
            foreach ($langs as $key) {
                if (isset($lang_list[$key])) {
                    $out[$key] = $lang_list[$key]['native_name'];
                } else {
                    $out[$key] = $key;
                }
            }
        } else {
            $out = array_merge($out, array_combine($langs, $langs));
        }

        return $out;
    }

    /**
     *    Add notices in admin dashboard
     *    @since 4.1.3
     */
    public static function admin_notices()
    {
        /* cloud print notice */
        $notice_msg = '';
        $notice_template_types = array(WF_PKLIST_POST_TYPE, 'invoice', 'proformainvoice');
        //self::show_admin_notices($notice_template_types, $notice_msg);
    }

    /**
     *    Show notices in admin dashboard.
     *    @since 4.1.3
     */
    private static function show_admin_notices($allowed_template_types, $msg)
    {
        $page = (isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '');
        if ($page != "") {
            $show_notice = false;
            if (in_array($page, $allowed_template_types)) {
                $show_notice = true;
            }
            if (!$show_notice) {
                $template_type = Wf_Woocommerce_Packing_List::get_module_base($page);
                if ($template_type && in_array($template_type, $allowed_template_types)) //valid module ID and allowed to show the notice
                {
                    $show_notice = true;
                }
            }
            if ($show_notice) {
                ?>
				<div class="wt_warn_box">
	        		<?php echo $msg; ?>
	    		</div>
				<?php
}
        }
    }

    /**
     *    @since 4.1.8
     *    Do remote printing.
     *    Checks the current module needes a remote printing now
     */
    public static function do_remote_printing($module_base_arr, $order_id, $doc_obj)
    {
        if (!is_null($doc_obj->customizer) && isset($module_base_arr[$doc_obj->module_base])) {
            $order_ids = array($order_id);
            $pdf_name = $doc_obj->customizer->generate_pdf_name($doc_obj->module_base, $order_ids);
            $html = $doc_obj->generate_order_template($order_ids, $pdf_name);
            $module_base_arr[$doc_obj->module_base] = array(
                'html' => $html,
                'pdf_file' => $doc_obj->customizer->generate_template_pdf($html, $doc_obj->module_base, $pdf_name, 'attach'),
                'title' => $pdf_name,
            );
        }
        return $module_base_arr;
    }

    /**
     * @since 4.1.9
     * Compatible with multi currency and currency switcher plugin
     */
    public static function wf_display_price($user_currency, $order, $price)
    {

        $order_id = WC()->version < '2.7.0' ? $order->id : $order->get_id();
        $price = (float) $price;
        if (WC()->version < '4.1.0') {
            $symbols = self::wf_get_woocommerce_currency_symbols();
        } else {
            $symbols = get_woocommerce_currency_symbols();
        }

        if (get_option('woocommerce_currency_pos')) {
            $currency_pos = get_option('woocommerce_currency_pos');
        } else {
            $currency_pos = "left";
        }

        $wc_currency_symbol = isset($symbols[$user_currency]) ? $symbols[$user_currency] : '';

        $wc_currency_symbol = apply_filters('wt_pklist_alter_currency_symbol', $wc_currency_symbol, $symbols, $user_currency, $order, $price);

        if (get_option('woocommerce_price_num_decimals') == true) {
            $decimal = wc_get_price_decimals();
        } else {
            $decimal = 0;
        }

        if (get_option('woocommerce_price_decimal_sep')) {
            $decimal_sep = wc_get_price_decimal_separator();
        } else {
            $decimal_sep = ".";
        }

        if (get_option('woocommerce_price_thousand_sep')) {
            $thousand_sep = wc_get_price_thousand_separator();
        } else {
            $thousand_sep = ",";
        }

        if (is_plugin_active('woocommerce-currency-switcher/index.php')) {
            if (class_exists('WOOCS')) {
                global $WOOCS;
                $multi_currencies = $WOOCS->get_currencies();
                $user_selected_currency = $multi_currencies[$user_currency];
                $currency_symbol = "";
                if (!empty($user_selected_currency)) {
                    if (array_key_exists('position', $user_selected_currency)) {
                        $currency_pos = $user_selected_currency["position"];
                    }
                    if (array_key_exists('decimals', $user_selected_currency)) {
                        $decimal = $user_selected_currency["decimals"];
                    }
                }
            }
        } elseif (is_plugin_active('woo-multi-currency/woo-multi-currency.php')) {
            if (metadata_exists('post', $order_id, 'wmc_order_info')) {
                $wmc_order_info = $order->get_meta('wmc_order_info');
                if (array_key_exists($user_currency, $wmc_order_info)) {
                    if (array_key_exists('pos', $wmc_order_info[$user_currency])) {
                        $currency_pos = $wmc_order_info[$user_currency]['pos'];
                    }
                    if (array_key_exists('decimals', $wmc_order_info[$user_currency])) {
                        $decimal = $wmc_order_info[$user_currency]['decimals'];
                    }
                }
            }
        }

        if (trim($decimal) == "") {
            $decimal = 0;
        }
        if (trim($decimal_sep) == "") {
            $decimal_sep = ".";
        }
        if (trim($thousand_sep) == "") {
            $thousand_sep = ",";
        }

        $currency_pos = apply_filters('wt_pklist_alter_currency_symbol_position', $currency_pos, $symbols, $wc_currency_symbol, $user_currency, $order, $price);
        $decimal = apply_filters('wt_pklist_alter_currency_decimal', $decimal, $wc_currency_symbol, $user_currency, $order, $price);
        $decimal_sep = apply_filters('wt_pklist_alter_currency_decimal_seperator', $decimal_sep, $symbols, $wc_currency_symbol, $user_currency, $order, $price);
        $thousand_sep = apply_filters('wt_pklist_alter_currency_thousand_seperator', $thousand_sep, $symbols, $wc_currency_symbol, $user_currency, $order, $price);
        $wf_formatted_price = number_format($price, $decimal, $decimal_sep, $thousand_sep);

        if ($wc_currency_symbol != "") {
            switch ($currency_pos) {
                case 'left':
                    $result = $wc_currency_symbol . $wf_formatted_price;
                    break;
                case 'right':
                    $result = $wf_formatted_price . $wc_currency_symbol;
                    break;
                case 'left_space':
                    $result = $wc_currency_symbol . ' ' . $wf_formatted_price;
                    break;
                case 'right_space':
                    $result = $wf_formatted_price . ' ' . $wc_currency_symbol;
                    break;
                default:
                    $result = $wc_currency_symbol . $wf_formatted_price;
                    break;
            }
        } else {
            $result = $wf_formatted_price . ' ' . $user_currency;
        }

        $result = apply_filters('wt_pklist_change_currency_format', $result, $symbols, $wc_currency_symbol, $currency_pos, $decimal, $decimal_sep, $thousand_sep, $user_currency, $price, $order);

        return '<span>' . $result . '</span>';
    }

    /**
     * @since 4.1.9
     * Convert the price with multi currency and currency switcher plugin
     */
    public static function wf_convert_to_user_currency($item_price, $user_currency, $order)
    {

        $item_price = (float) $item_price;
        $rate = 1;
        $order_id = WC()->version < '2.7.0' ? $order->id : $order->get_id();

        /* currency switcher - packinglist product table */
        if (is_plugin_active('woocommerce-currency-switcher/index.php')) {
            if (metadata_exists('post', $order_id, '_woocs_order_rate')) {
                $rate = get_post_meta($order_id, '_woocs_order_rate', true);
            } elseif (metadata_exists('post', $order_id, 'wmc_order_info')) {
                $wmc_order_info = $order->get_meta('wmc_order_info');
                $rate = $wmc_order_info[$user_currency]['rate'];
            }
        } elseif (is_plugin_active('woo-multi-currency/woo-multi-currency.php')) /* Multi currency - packinglist product table */
        {
            if (metadata_exists('post', $order_id, 'wmc_order_info')) {
                $wmc_order_info = $order->get_meta('wmc_order_info');
                $rate = $wmc_order_info[$user_currency]['rate'];
            } elseif (metadata_exists('post', $order_id, '_woocs_order_rate')) {
                $rate = get_post_meta($order_id, '_woocs_order_rate', true);
            }
        } else {
            /* currency switcher / multicurrency even plugins are not available - packinglist product table */
            if (metadata_exists('post', $order_id, '_woocs_order_rate')) {
                $rate = get_post_meta($order_id, '_woocs_order_rate', true);
            } elseif (metadata_exists('post', $order_id, 'wmc_order_info')) {
                $wmc_order_info = $order->get_meta('wmc_order_info');
                $rate = $wmc_order_info[$user_currency]['rate'];
            }
        }
        return $item_price * (float) $rate;
    }

    /**
     * @since 4.1.9
     * Get the currecy symbols array for the WC < 4.1.0
     */
    public static function wf_get_woocommerce_currency_symbols()
    {
        $symbols = array(
            'AED' => '&#x62f;.&#x625;',
            'AFN' => '&#x60b;',
            'ALL' => 'L',
            'AMD' => 'AMD',
            'ANG' => '&fnof;',
            'AOA' => 'Kz',
            'ARS' => '&#36;',
            'AUD' => '&#36;',
            'AWG' => 'Afl.',
            'AZN' => 'AZN',
            'BAM' => 'KM',
            'BBD' => '&#36;',
            'BDT' => '&#2547;&nbsp;',
            'BGN' => '&#1083;&#1074;.',
            'BHD' => '.&#x62f;.&#x628;',
            'BIF' => 'Fr',
            'BMD' => '&#36;',
            'BND' => '&#36;',
            'BOB' => 'Bs.',
            'BRL' => '&#82;&#36;',
            'BSD' => '&#36;',
            'BTC' => '&#3647;',
            'BTN' => 'Nu.',
            'BWP' => 'P',
            'BYR' => 'Br',
            'BYN' => 'Br',
            'BZD' => '&#36;',
            'CAD' => '&#36;',
            'CDF' => 'Fr',
            'CHF' => '&#67;&#72;&#70;',
            'CLP' => '&#36;',
            'CNY' => '&yen;',
            'COP' => '&#36;',
            'CRC' => '&#x20a1;',
            'CUC' => '&#36;',
            'CUP' => '&#36;',
            'CVE' => '&#36;',
            'CZK' => '&#75;&#269;',
            'DJF' => 'Fr',
            'DKK' => 'DKK',
            'DOP' => 'RD&#36;',
            'DZD' => '&#x62f;.&#x62c;',
            'EGP' => 'EGP',
            'ERN' => 'Nfk',
            'ETB' => 'Br',
            'EUR' => '&euro;',
            'FJD' => '&#36;',
            'FKP' => '&pound;',
            'GBP' => '&pound;',
            'GEL' => '&#x20be;',
            'GGP' => '&pound;',
            'GHS' => '&#x20b5;',
            'GIP' => '&pound;',
            'GMD' => 'D',
            'GNF' => 'Fr',
            'GTQ' => 'Q',
            'GYD' => '&#36;',
            'HKD' => '&#36;',
            'HNL' => 'L',
            'HRK' => 'kn',
            'HTG' => 'G',
            'HUF' => '&#70;&#116;',
            'IDR' => 'Rp',
            'ILS' => '&#8362;',
            'IMP' => '&pound;',
            'INR' => '&#8377;',
            'IQD' => '&#x639;.&#x62f;',
            'IRR' => '&#xfdfc;',
            'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
            'ISK' => 'kr.',
            'JEP' => '&pound;',
            'JMD' => '&#36;',
            'JOD' => '&#x62f;.&#x627;',
            'JPY' => '&yen;',
            'KES' => 'KSh',
            'KGS' => '&#x441;&#x43e;&#x43c;',
            'KHR' => '&#x17db;',
            'KMF' => 'Fr',
            'KPW' => '&#x20a9;',
            'KRW' => '&#8361;',
            'KWD' => '&#x62f;.&#x643;',
            'KYD' => '&#36;',
            'KZT' => '&#8376;',
            'LAK' => '&#8365;',
            'LBP' => '&#x644;.&#x644;',
            'LKR' => '&#xdbb;&#xdd4;',
            'LRD' => '&#36;',
            'LSL' => 'L',
            'LYD' => '&#x644;.&#x62f;',
            'MAD' => '&#x62f;.&#x645;.',
            'MDL' => 'MDL',
            'MGA' => 'Ar',
            'MKD' => '&#x434;&#x435;&#x43d;',
            'MMK' => 'Ks',
            'MNT' => '&#x20ae;',
            'MOP' => 'P',
            'MRU' => 'UM',
            'MUR' => '&#x20a8;',
            'MVR' => '.&#x783;',
            'MWK' => 'MK',
            'MXN' => '&#36;',
            'MYR' => '&#82;&#77;',
            'MZN' => 'MT',
            'NAD' => 'N&#36;',
            'NGN' => '&#8358;',
            'NIO' => 'C&#36;',
            'NOK' => '&#107;&#114;',
            'NPR' => '&#8360;',
            'NZD' => '&#36;',
            'OMR' => '&#x631;.&#x639;.',
            'PAB' => 'B/.',
            'PEN' => 'S/',
            'PGK' => 'K',
            'PHP' => '&#8369;',
            'PKR' => '&#8360;',
            'PLN' => '&#122;&#322;',
            'PRB' => '&#x440;.',
            'PYG' => '&#8370;',
            'QAR' => '&#x631;.&#x642;',
            'RMB' => '&yen;',
            'RON' => 'lei',
            'RSD' => '&#1088;&#1089;&#1076;',
            'RUB' => '&#8381;',
            'RWF' => 'Fr',
            'SAR' => '&#x631;.&#x633;',
            'SBD' => '&#36;',
            'SCR' => '&#x20a8;',
            'SDG' => '&#x62c;.&#x633;.',
            'SEK' => '&#107;&#114;',
            'SGD' => '&#36;',
            'SHP' => '&pound;',
            'SLL' => 'Le',
            'SOS' => 'Sh',
            'SRD' => '&#36;',
            'SSP' => '&pound;',
            'STN' => 'Db',
            'SYP' => '&#x644;.&#x633;',
            'SZL' => 'L',
            'THB' => '&#3647;',
            'TJS' => '&#x405;&#x41c;',
            'TMT' => 'm',
            'TND' => '&#x62f;.&#x62a;',
            'TOP' => 'T&#36;',
            'TRY' => '&#8378;',
            'TTD' => '&#36;',
            'TWD' => '&#78;&#84;&#36;',
            'TZS' => 'Sh',
            'UAH' => '&#8372;',
            'UGX' => 'UGX',
            'USD' => '&#36;',
            'UYU' => '&#36;',
            'UZS' => 'UZS',
            'VEF' => 'Bs F',
            'VES' => 'Bs.S',
            'VND' => '&#8363;',
            'VUV' => 'Vt',
            'WST' => 'T',
            'XAF' => 'CFA',
            'XCD' => '&#36;',
            'XOF' => 'CFA',
            'XPF' => 'Fr',
            'YER' => '&#xfdfc;',
            'ZAR' => '&#82;',
            'ZMW' => 'ZK',
        );
        return $symbols;
    }

    /**
     * @since 4.1.9
     * Generate PDF file name for invoice template
     */

    public static function get_invoice_pdf_name($template_type, $order_ids, $module_id)
    {

        $order = wc_get_order($order_ids[0]);

        Wf_Woocommerce_Packing_List_Invoice::generate_invoice_number($order, true, 'set');

        $wf_invoice_pdf_name_format = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_custom_pdf_name', $module_id);
        $wf_invoice_pdf_name_prefix = Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_custom_pdf_name_prefix', $module_id);

        if ($wf_invoice_pdf_name_format == "[prefix][order_no]") {
            $invoice_pdf_name_number_pos = $order_ids[0];
        } else {
            $invoice_pdf_name_number_pos = get_post_meta($order_ids[0], 'wf_invoice_number', true);
        }

        if (trim($wf_invoice_pdf_name_prefix) == "") {
            $invoice_pdf_name_prefix_pos = "Invoice_";
        } else {
            $invoice_pdf_name_prefix_pos = $wf_invoice_pdf_name_prefix;
        }

        if ($wf_invoice_pdf_name_format == "[prefix][invoice_no]") {
            $invoice_pdf_name_format = $wf_invoice_pdf_name_format;
        } else {
            $invoice_pdf_name_format = "[prefix][order_no]";
        }

        return str_replace(array('[prefix]', '[order_no]', '[invoice_no]'), array($invoice_pdf_name_prefix_pos, $invoice_pdf_name_number_pos, $invoice_pdf_name_number_pos), $invoice_pdf_name_format);
    }

    /**
     * @since 4.1.9
     * Shipping address with order currency symbol
     */
    public static function wf_shipping_formated_price($order)
    {
        $order_id = (WC()->version < '2.7.0' ? $order->id : $order->get_id());
        $user_currency = get_post_meta($order_id, '_order_currency', true);
        $tax_display = get_option('woocommerce_tax_display_cart');

        if (0 < abs((float) $order->get_shipping_total())) {
            if ('excl' === $tax_display) {
                // Show shipping excluding tax.
                $shipping = apply_filters('wt_pklist_change_price_format', $user_currency, $order, $order->get_shipping_total());
                if ((float) $order->get_shipping_tax() > 0 && $order->get_prices_include_tax()) {
                    $shipping .= apply_filters('woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>', $order, $tax_display);
                }
            } else {
                // Show shipping including tax.
                $tot_shipping_amount = $order->get_shipping_total() + $order->get_shipping_tax();
                $shipping = apply_filters('wt_pklist_change_price_format', $user_currency, $order, $tot_shipping_amount);
                if ((float) $order->get_shipping_tax() > 0 && !$order->get_prices_include_tax()) {
                    $shipping .= apply_filters('woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>', $order, $tax_display);
                }
            }
            /* translators: %s: method */
            $shipping .= apply_filters('woocommerce_order_shipping_to_display_shipped_via', '&nbsp;<small class="shipped_via">' . sprintf(__('via %s', 'woocommerce'), $order->get_shipping_method()) . '</small>', $order);

        } elseif ($order->get_shipping_method()) {
            $shipping = $order->get_shipping_method();
        } else {
            $shipping = __('Free!', 'woocommerce');
        }
        return $shipping;
    }

    /**
     * @since 4.2.2
     * Search orders by the invoice number
     */
    public static function wf_search_order_by_invoice_number($search_fields)
    {
        array_push($search_fields, 'wf_invoice_number');
        return $search_fields;
    }

    public function hide_pay_later_payment_in_order_pay_page($available_gateways)
    {
        // 1. On Order Pay page
        if (is_wc_endpoint_url('order-pay')) {
            // Get an instance of the WC_Order Object
            $order = wc_get_order(get_query_var('order-pay'));

            // Loop through payment gateways 'pending', 'on-hold', 'processing'
            foreach ($available_gateways as $gateways_id => $gateways) {
                // Keep paypal only for "pending" order status
                if ($gateways_id === 'wf_pay_later' && ($order->has_status('pending') || $order->has_status('on-hold') || $order->has_status('failed'))) {
                    unset($available_gateways[$gateways_id]);
                }
            }
        }
        return $available_gateways;
    }

    public function save_paylater_settings_admin()
    {
        $module_id = Wf_Woocommerce_Packing_List::get_module_id('invoice');
        if (get_option('woocommerce_wf_pay_later_settings')) {
            $paylater_default_arr = array(
                'title' => __('Pay Later', 'wf-woocommerce-packing-list'),
                'description' => '',
                'instructions' => '',
                'enabled' => "no");
            $paylater_details = get_option('woocommerce_wf_pay_later_settings', $paylater_default_arr);
            if ($paylater_details['enabled'] === "yes") {
                $show_paylater = 1;
            } else {
                $show_paylater = 0;
            }

            Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_show_pay_later_in_checkout', $show_paylater, $module_id);
            Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_pay_later_title', sanitize_text_field($paylater_details['title']), $module_id);
            Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_pay_later_description', sanitize_textarea_field($paylater_details['description']), $module_id);
            Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_pay_later_instuction', sanitize_textarea_field($paylater_details['instructions']), $module_id);
        }
    }

    public function wf_allow_payment_for_order_status($statuses, $order)
    {
        if (!in_array('on-hold', $statuses) && ($order->status === "on-hold")) {
            $statuses[] = 'on-hold';
        } elseif (!in_array('failed', $statuses) && ($order->status === "failed")) {
            $statuses[] = 'failed';
        }
        return $statuses;
    }
}
