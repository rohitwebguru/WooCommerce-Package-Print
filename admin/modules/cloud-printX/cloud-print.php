<?php

/**
 * Cloud print section of the plugin
 *
 * @link       
 * @since 4.0.9    
 *
 * @package  Wf_Woocommerce_Packing_List  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wf_Woocommerce_Packing_List_Cloud_Print
{
	public $module_id='';
	public static $module_id_static='';
	public $module_base='cloud-print';
    private $customizer=null;
    private $seq_number=null;
    public $documents=array(); /* cloud printing enabled modules */
    public $connection_option_key='wt_pklist_cloud_print';
    public $default_printer_option_key='wt_pklist_cloud_print_default_printer';
	public function __construct()
	{
		$this->module_id=Wf_Woocommerce_Packing_List::get_module_id($this->module_base);
		self::$module_id_static=$this->module_id;

		/* deprecation after dec 31st */
		if(time()>=1609459200) 
		{
			/*
			*	Init
			*/
			add_action('init', array($this, 'show_deprecated_msg'));

		}else
		{
			/*
			*	Init
			*/
			add_action('init', array($this, 'init'));

			/**
			* 	Main hook to handle all ajax actions 
			*/
			add_action('wp_ajax_wt_pklist_cloud_print_ajax', array($this, 'ajax_main'), 1);

			add_action('admin_enqueue_scripts', array($this, 'enqueue_manual_print_js'), 10, 1);

			add_filter('wt_pklist_alter_tooltip_data', array($this, 'register_tooltips'),1);
		}	
	}

	/**
	* 	@since 4.1.4
	* 	Show a deprecated message
	*/
	public function show_deprecated_msg()
	{
		/**
		*	Add settings tab
		*/
		add_filter('wt_pklist_plugin_settings_tabhead', array( $this, 'settings_tabhead'));
		add_action('wt_pklist_plugin_out_settings_form', array($this, 'deprecated_tab'));
	}

	public function deprecated_tab()
	{
		$params=array(
			'module_id'=>$this->module_id,
		);
		$view_file=plugin_dir_path( __FILE__ ).'views/deprecated.php';
		
		Wf_Woocommerce_Packing_List_Admin::envelope_settings_tabcontent('cloud-print', $view_file, '', $params, 0);
	}




	/**
	* 	@since 4.0.9
	* 	Hook the tooltip data to main tooltip array
	*/
	public function register_tooltips($tooltip_arr)
	{
		include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
		$tooltip_arr[$this->module_id]=$arr;
		return $tooltip_arr;
	}

	/**
	*	Initiate module
	*/
	public function init()
	{
		/**
		*	Cloud printing enabled docs
		*/
		$this->documents=apply_filters('wt_pklist_add_to_cloud_printing', $this->documents);

		/* filter to set module default settings */
		add_filter('wf_module_default_settings', array($this, 'default_settings'), 10, 2);

		/**
		*	Set up auth callback URL. Checking this is a auth callback URL
		*/
		$this->verify_auth_callback();

		/** 
		* Declaring multi select form fields in settings form 
		*/
		add_filter('wt_pklist_intl_alter_multi_select_fields', array($this, 'alter_multi_select_fields'), 10, 2);

		/** 
		* Declaring validation rule for form fields in settings form 
		*/
		add_filter('wt_pklist_intl_alter_validation_rule', array($this, 'alter_validation_rule'), 10, 2);

		/**
		* 	Hook to woocommerce status changed to do automatic printing 
		*/ 
		add_action('woocommerce_order_status_changed', array($this, 'auto_cloud_print'), 10, 3);

		/**
		* 	Add manual cloud printing buttons, If enabled 
		*/ 
		$this->manual_cloud_print();

		/**
		*	Add settings tab
		*/
		add_filter('wt_pklist_plugin_settings_tabhead', array( $this, 'settings_tabhead'));
		add_action('wt_pklist_plugin_out_settings_form', array($this, 'out_settings_form'));
	}

	/**
	*	This method will add manual cloud print buttons
	*/
	public function manual_cloud_print()
	{
		$manual_cloud_print=Wf_Woocommerce_Packing_List::get_option('wt_pklist_cloud_print_manual', $this->module_id);
		
		if(is_array($this->documents) && count($this->documents)>0 && $manual_cloud_print=='Yes') /* check cloud print docs available */
		{
			foreach ($this->documents as $doc_key => $doc_title)
			{
				add_filter('wt_pklist_after_'.$doc_key.'_print_button_list', array($this, 'manual_cloud_print_buttons'), 10, 4);
			}
		}
	}

	/**
	*	Sub function to add manual cloud print buttons
	*/
	public function manual_cloud_print_buttons($item_arr, $order, $button_location, $template_type)
	{		
		if($button_location=='detail_page') /* we are adding button to detail page only */
		{
			$last_item=end($item_arr); //last item will be the button data of current document type
			
			if(isset($last_item['button_type']) && ($last_item['button_type']=='dropdown' || $last_item['button_type']=='aggregate')) /* only for dropdown/aggregate buttons */
			{
				if(isset($last_item['items']) && is_array($last_item['items']))
				{
					/* add new button data */
					$last_item['items'][]=array(  
						'label'=>__('Cloud print', 'wf-woocommerce-packing-list'),
						'action'=>$template_type.'_cloud_print',
						'icon'=>'cloud-saved',
						'tooltip'=>__('Invoice Cloud print', 'wf-woocommerce-packing-list'),
						'is_show_prompt'=>0,
						'button_location'=>$button_location,						
						'css_class'=>'wt_pklist_manual_cloud_print',						
						'custom_attr'=>' data-template_type="'.$template_type.'"',						
					);

					$item_arr[(count($item_arr)-1)]=$last_item; /* add to main item array */
				}
			}
		}
		return $item_arr;
	}


	/**
	*	This method will do automatic cloud printing
	*/
	public function auto_cloud_print($order_id, $old_status, $new_status)
	{
		if(!$order_id)
		{
        	return;
    	}
    	$need_cloud_print_arr=array();
		if(is_array($this->documents) && count($this->documents)>0) /* check cloud print docs available */
		{
			$settings=Wf_Woocommerce_Packing_List::get_settings($this->module_id);
			
        	$status=get_post_status($order_id);
			
			foreach ($this->documents as $doc_key => $doc_title)
			{
				$settings_key='wt_pklist_cloud_print_automatic_'.$doc_key.'_statuses';

				/* check cloud print enabled for current category */
				if(isset($settings[$settings_key]) && is_array($settings[$settings_key]) && in_array($status, $settings[$settings_key]))
				{
					$need_cloud_print_arr[$doc_key]=array();
				}
			}
		}		

		if(count($need_cloud_print_arr)>0) /* some modules need cloud print on current order status */
		{
			$out=array(
				'status'=>0
			);

			$printer_id=$this->get_default_printer(); /* taking default printer id */
			if(!isset($printer_id) || !$printer_id)
			{
				$out['msg']=__('Please set up a default printer', 'wf-woocommerce-packing-list');
			}else
			{
				$out['status']=1;
			}

			/**
			*	Checking connection status and access token expiry
			*/
			if($out['status']==1)
			{
				$out['status']=0; //resetting
				$out=$this->do_pre_print_actions($out);
			}
		

			if($out['status']==1) /* all are okay then do the print job */
			{
				/* modules needed to check their module base is avaialable for cloud print. If yes then they need to return back the processessed template HTML */
				$cloud_print_arr=apply_filters('wt_pklist_do_cloud_printing', $need_cloud_print_arr, $order_id);				

				if(is_array($cloud_print_arr) && count($cloud_print_arr)>0)
				{
					$connection_data=$this->get_connection_data();
					$this->include_google_cloud_print(); /* include cloud print library */
					
					$error_msg_arr=array(); /* this array for email notification */
					foreach ($cloud_print_arr as $doc_key => $doc_data)
					{
						if(isset($doc_data['html']) && $doc_data['html']!="") /* post type module responded with template HTML */
						{
							$job_title=(isset($doc_data['title']) ? $doc_data['title'] : $doc_key.'_'.$order_id);
							$args=array(
					    		'access_token'=>$connection_data['access_token'],
					    		'printer_id'=>$printer_id,
					    		'job_title'=>$job_title,
					    		'content_type'=>'text/html',
					    		'file_data'=>$doc_data['html'],
					    	);
							$printer_response=Wt_Pklist_Google_Cloud_Print::send_to_printer($args); /* send job to printer */ 
							
							/* process printer response */
							$out=$this->process_printer_response($printer_response, $out);
							if($out['status']==0) /* an error, then record the message in separate array because next value in the foreach loop may overwrite the value */
							{
								$error_msg_arr[]=$out['msg'];
							}
						}
					}
				}
			}

			/**
			*	Check email notification enabled.
			*/
			if(Wf_Woocommerce_Packing_List::get_option('wt_pklist_cloud_print_email_notification', $this->module_id)=='Yes')
			{
				/**
				*	Printing not success
				*	We also need to check this with the `error_msg_arr` array count because we must send the error message when an item in the middle of the above foreach loop is failed
				*/
				if($out['status']==0 || count($error_msg_arr)>0) 
				{

					$order=new WC_Order($order_id);
					$order_number=$order->get_order_number(); /* compatibility for Order number plugins. Eg: Sequential order number */

					$to=array(get_option('admin_email'));
					$to=apply_filters('wt_pklist_cloud_print_alter_notification_email_list', $to, $order);
					
					if((is_string($to) && $to!="") || (is_array($to) && count($to)>0))
					{
						$subject =sprintf(__('Cloud print error: %s', 'wf-woocommerce-packing-list'), $order_number).' - '.get_bloginfo('name');
					
						$body=sprintf(__('Automatic cloud printing of order number %s was failed because of these reason(s).', 'wf-woocommerce-packing-list'), $order_number).'<br />';
						$body.=(count($error_msg_arr)>0 ? implode('<br />', $error_msg_arr) : $out['msg']);

						$headers = array('Content-Type: text/html; charset=UTF-8');
						wp_mail($to, $subject, $body, $headers);
					}
					
				}	
			}

		}
	}

	/**
	*	Checking this is a auth callback URL
	*/
	public function verify_auth_callback()
	{
		if(isset($_GET['wt_pklist_cloud_print_auth']))
		{
			if(isset($_GET['code']) && !empty($_GET['code'])) /* Is Code got from google? */
			{
				$code=sanitize_text_field($_GET['code']);

				/* include google auth library */
				$this->include_google_auth(); 
				$api_credentials=$this->get_api_credentials();
				$redirect_uri=self::generate_auth_redirection_url();
				
				$status=1;
				$msg=__('Success', 'wf-woocommerce-packing-list');

				extract($api_credentials);
				if($client_id!="" && $client_secret!="") 
				{
					$access_token_arr=Wt_Pklist_Cloud_Print_Googleauth::get_access_token($code, $client_id, $client_secret, $redirect_uri);
					if($access_token_arr)
					{
						/* save connection info */
						$this->update_connection_data($access_token_arr);
					}else
					{
						$status=0;
						$msg=__('Unable to generate access token.', 'wf-woocommerce-packing-list');
					}
				}else
				{
					// client_id or client_secret is missing
					$status=0;
					$msg=__('Client ID / Client secret is missing.', 'wf-woocommerce-packing-list');
				}
				?>
				<script type="text/javascript">
					window.opener.wt_pklist_cloud_print.process_auth_response(<?php echo $status;?>, "<?php echo $msg;?>");
					window.close();
				</script>
				<?php
				exit(); //no need to render the HTML. Just process the response.
			}
		}
	}

	/**
	* 	Ajax main hook for all actions
	*/
	public function ajax_main()
	{
		$out=array(
			'status'=>0,
			'msg'=>__("Error", 'wf-woocommerce-packing-list')
		);
    	if(Wf_Woocommerce_Packing_List_Admin::check_write_access($this->module_id)) //no error then proceed
    	{
			$allowed_actions=array('authorize', 'deauthorize', 'refresh_status', 'search_printers', 'test_printer', 'toggle_default_printer', 'manual_print');
			$cloud_print_action=sanitize_text_field($_REQUEST['cloud_print_action']);
			if(method_exists($this, $cloud_print_action))
			{
				$out=$this->{$cloud_print_action}($out);
			}
		}
		echo json_encode($out);
		exit();
	}


	/**
	*	Ajax sub hook
	*	Manual cloud print
	*/
	public function manual_print($out)
	{
		$template_type=(isset($_POST['template_type']) ? sanitize_text_field($_POST['template_type']) : '');
		if($template_type=="")
		{
			$out['msg']=__('Invalid document type.', 'wf-woocommerce-packing-list');
			return $out;
		}

		$order_id=(isset($_POST['order_id']) ? absint($_POST['order_id']) : 0);
		if($order_id===0)
		{
			$out['msg']=__('Invalid order id.', 'wf-woocommerce-packing-list');
			return $out;
		}

		$printer_id=$this->get_default_printer(); /* taking default printer id */
		if(!isset($printer_id) || !$printer_id)
		{
			$out['msg']=__('Please set up a default printer', 'wf-woocommerce-packing-list');
			return $out;
		}

		/**
		*	Checking connection status and access token expiry
		*/
		$out=$this->do_pre_print_actions($out);

		if($out['status']==1) /* all are okay then do the print job */
		{
			/* module needed to check their module base is avaialable for cloud print. If yes then they need to return back the processessed template HTML */
			$cloud_print_arr=apply_filters('wt_pklist_do_cloud_printing', array($template_type=>array()), $order_id);				

			if(is_array($cloud_print_arr) && isset($cloud_print_arr[$template_type]) && $cloud_print_arr[$template_type]['html']!="")
			{
				$connection_data=$this->get_connection_data();
				$this->include_google_cloud_print(); /* include cloud print library */
				
				$job_title=(isset($cloud_print_arr[$template_type]['title']) ? $cloud_print_arr[$template_type]['title'] : $doc_key.'_'.$order_id);
				$args=array(
		    		'access_token'=>$connection_data['access_token'],
		    		'printer_id'=>$printer_id,
		    		'job_title'=>$job_title,
		    		'content_type'=>'text/html',
		    		'file_data'=>$cloud_print_arr[$template_type]['html'],
		    	);
				$printer_response=Wt_Pklist_Google_Cloud_Print::send_to_printer($args); /* send job to printer */
				
				/* process printer response */
				$out=$this->process_printer_response($printer_response, $out);
			}else
			{
				$out['status']=0;
				$out['msg']=__("Failed to generate document template.", 'wf-woocommerce-packing-list');
			}
		}

		return $out;
	}

	/**
	*	Process the response got from printer while adding a job
	*/
	private function process_printer_response($printer_response, $out)
	{
		if($printer_response && is_array($printer_response)) /* expected response format */
		{
			if(isset($printer_response['status']))
			{
				if($printer_response['status']!==true)
				{
					$out['status']=0;
					$out['msg']=$printer_response['msg']; 
				}else
				{
					$out['status']=1;
					$out['msg']='';
				}
			}else
			{
				$out['status']=0;
				$out['msg']=__('Error', 'wf-woocommerce-packing-list'); 
			}
		}else
		{
			$out['status']=0;
			$out['msg']=__('Error', 'wf-woocommerce-packing-list');
		}
		return $out;
	}


	/**
	*	Ajax sub hook
	*	Set/Remove default printer
	*/
	public function toggle_default_printer($out)
	{
		$printer_id=(isset($_POST['printer_id']) ? sanitize_text_field($_POST['printer_id']) : '');
		if($printer_id!="")
		{
			$default_printer_id=$this->get_default_printer();
			if($printer_id===$default_printer_id) /* current default printer ID equals the requested one then the action is remove */
			{
				$printer_id=0; /* reset the printer id */
			}
			$this->update_default_printer($printer_id);
			$out['default_printer_id']=$printer_id;
			$out['status']=1;
			$out['msg']=__("Success", 'wf-woocommerce-packing-list');
		}else
		{
			$out['status']=0;
			$out['msg']=__("Invalid Printer ID", 'wf-woocommerce-packing-list');
		}
		return $out;
	}

	/**
	*	Ajax sub hook
	*	Test printer by sending sample file
	*/
	public function test_printer($out)
	{
		$printer_id=(isset($_POST['printer_id']) ? sanitize_text_field($_POST['printer_id']) : '');
		if($printer_id!="")
		{
			/* do an access check */
			$out=$this->do_pre_print_actions($out);

			if($out['status']==1) /* all are okay then do test print job */
			{
				$connection_data=$this->get_connection_data();
				$this->include_google_cloud_print();

				$args=array(
		    		'access_token'=>$connection_data['access_token'],
		    		'printer_id'=>$printer_id,
		    		'job_title'=>__("Printer test", 'wf-woocommerce-packing-list'),
		    		'content_type'=>'application/pdf',
		    		'file_path'=>plugin_dir_path(__FILE__)."data/printer_test.pdf",
		    	);

				$printer_response=Wt_Pklist_Google_Cloud_Print::send_to_printer($args);
				
				/* process printer response */
				$out=$this->process_printer_response($printer_response, $out);
			}
		}else
		{
			$out['status']=0;
			$out['msg']=__("Invalid Printer ID", 'wf-woocommerce-packing-list');
		}
		return $out;
	}

	/**
	*	Ajax sub hook
	*	Search printers
	*/
	public function search_printers($out)
	{
		/* do an access check */
		$out=$this->do_pre_print_actions($out);

		if($out['status']==1) /* all are okay then search for the printers */
		{
			$connection_data=$this->get_connection_data();
			$this->include_google_cloud_print();
			$printer_arr=Wt_Pklist_Google_Cloud_Print::search_printers($connection_data['access_token']);
			$out['msg']='';
			$default_printer_id=$this->get_default_printer();
			ob_start();
			include plugin_dir_path(__FILE__)."views/_printer_list.php"; 
			$out['html']=ob_get_clean();
		}

		return $out;
	}

	/**
	*	This method will check the connection and access token details
	*/
	private function do_pre_print_actions($out)
	{
		$out['status']=0;
		$api_credentials=$this->get_api_credentials();
		extract($api_credentials);
		if($this->check_connection_status() && $client_id!="" && $client_secret!="") /* connected */
		{
			/* check token is expired */
			if($this->is_token_expired())
			{
				/* request for new token with refresh token */
				$connection_data=$this->get_connection_data();
				$refresh_token=(isset($connection_data['refresh_token']) ? $connection_data['refresh_token'] : '');
				if($refresh_token!="")
				{
					$this->include_google_auth();
					$access_token_arr=Wt_Pklist_Cloud_Print_Googleauth::get_access_token_by_refresh_token($refresh_token, $client_id, $client_secret);
					if($access_token_arr)
					{
						/* save connection info */
						$access_token_arr['refresh_token']=$refresh_token; /* may be access token is missing in the request */
						$this->update_connection_data($access_token_arr);
						$out['status']=1;
					}else
					{
						$out['msg']=__('Unable to generate access token. Please disconnect and then connect again.', 'wf-woocommerce-packing-list');
					}
				}else
				{
					$out['msg']=__("Unable to establish connection. Please disconnect and then connect again.", 'wf-woocommerce-packing-list');
				}
			}else
			{
				$out['status']=1;
			}

		}else
		{
			$out['msg']=__("Unable to establish connection.", 'wf-woocommerce-packing-list');
		}
		return $out;
	}

	/**
	*	Ajax sub hook
	*	Refresh connection status
	*/
	public function refresh_status($out)
	{
		$connection_status=$this->check_connection_status();
		$out['html']=$this->prepare_connection_info($connection_status);
		$out['connected']=$connection_status;
		$out['status']=1;
		return $out;
	}

	/**
	*	Ajax sub hook
	*	To authorize the application
	*/
	public function authorize($out)
	{
		$client_id=(isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '');
		$client_secret=(isset($_POST['client_secret']) ? sanitize_text_field($_POST['client_secret']) : '');
		
		$out['status']=1;
		if(trim($client_id)=='')
		{
			$out['status']=0;
			$out['msg']=__('Client ID is mandatory', 'wf-woocommerce-packing-list');
		}
		if($out['status']==1 && trim($client_secret)=='')
		{
			$out['status']=0;
			$out['msg']=__('Client secret is mandatory', 'wf-woocommerce-packing-list');
		}
		if($out['status']==1)
		{
			/**
			*	Update client ID and Client secret. This will be needed when generating access token
			*/
			$this->update_api_credentails($client_id, $client_secret);

			$this->include_google_auth(); 
			$redirect_uri=self::generate_auth_redirection_url();
			$out['url']=Wt_Pklist_Cloud_Print_Googleauth::generate_auth_url($client_id, $redirect_uri);
			$out['msg']='';
		}
		return $out;
	}

	/**
	*	Ajax sub hook
	*	Deauthorize the application
	*/
	public function deauthorize($out)
	{
		$out['status']=1;
		$out['msg']='';
		if($this->check_connection_status()) /* is it connected ? */
		{
			$connection_data=$this->get_connection_data();
			$this->include_google_auth();
			$token=(isset($connection_data['refresh_token']) && $connection_data['refresh_token']!="" ? $connection_data['refresh_token'] : $connection_data['access_token']);

			if(!Wt_Pklist_Cloud_Print_Googleauth::revoke_access_token($token))
			{
				$out['status']=0;
			}else
			{
				/* successfully revoked now remove connection data */
				$this->remove_connection_data();
			}
		}
		return $out;
	}

	/**
	* 	Declaring validation rule for form fields in settings form
	*/
	public function alter_validation_rule($arr, $base_id)
	{
		if($base_id == $this->module_id)
		{
			$arr=array();
			if(is_array($this->documents))
			{
				foreach ($this->documents as $key => $value)
				{
					$arr['wt_pklist_cloud_print_automatic_'.$key.'_statuses']=array('type'=>'text_arr');
				}
			}
		}
		return $arr;
	}

	/**
	* 	Declaring multi select form fields in settings form
	*/
	public function alter_multi_select_fields($arr, $base_id)
	{
		if($base_id==$this->module_id)
		{
			$arr=array();
			if(is_array($this->documents))
			{
				foreach ($this->documents as $key => $value)
				{
					$arr['wt_pklist_cloud_print_automatic_'.$key.'_statuses']=array();
				}
			}
		}
		return $arr;
	}

	/**
	*	Default settings
	*/
	public function default_settings($settings, $base_id)
	{
		if($base_id==$this->module_id)
		{
			$def_settings=array(
				'wt_pklist_cloud_print_client_id'=>'',
				'wt_pklist_cloud_print_client_secret'=>'',
				'wt_pklist_cloud_print_client_redirect_url'=>$this->generate_auth_redirection_url(),
				'wt_pklist_cloud_print_automatic'=>'No',
				'wt_pklist_cloud_print_manual'=>'No',
				'wt_pklist_cloud_print_email_notification'=>'No',
			);
			if(is_array($this->documents))
			{
				foreach ($this->documents as $key => $value)
				{
					$def_settings['wt_pklist_cloud_print_automatic_'.$key.'_statuses']=array();
				}
			}
			return $def_settings;
		}else
		{
			return $settings;
		}
	}

	/**
	 * 	Tab head for admin settings page
	 **/
	public function settings_tabhead($arr)
	{
		$added=0;
		$out_arr=array();
		$menu_pos_key='wf-advanced'; /* after advanced tab */

		foreach($arr as $k=>$v)
		{
			$out_arr[$k]=$v;
			if($k==$menu_pos_key && $added==0)
			{				
				$out_arr['cloud-print']=__('Cloud print', 'wf-woocommerce-packing-list');
				$added=1;
			}
		}
		if($added==0){
			$out_arr['cloud-print']=__('Cloud print', 'wf-woocommerce-packing-list');
		}
		return $out_arr;
	}

	/**
	*	JS for manual cloud print
	*	This will only enqueue JS in order detail page
	*/
	public function enqueue_manual_print_js($hook)
	{
		global $post;

		if($hook=='post-new.php' || $hook=='post.php')
		{
	        if($post->post_type=='shop_order') //only in order detail page
	        {     
	            wp_enqueue_script($this->module_id.'_manual', plugin_dir_url( __FILE__ ).'assets/js/manual_print.js', array('jquery'), WF_PKLIST_VERSION);
	            $default_printer_id=$this->get_default_printer();
	            $params=array(
					'nonces' => array(
			            'main'=>wp_create_nonce($this->module_id),
			        ),
			        'ajax_url' => admin_url('admin-ajax.php'),
			        'default_printer_id'=>$default_printer_id,
			        'msgs'=>array(
			        	'error'=>__('Cloud print error', 'wf-woocommerce-packing-list'),
			        	'success'=>__('Successfully send to Cloud print', 'wf-woocommerce-packing-list'),
			        	'wait'=>__('Please wait...', 'wf-woocommerce-packing-list'),
			        )
				);
				wp_localize_script($this->module_id.'_manual', 'wt_pklist_manual_cloud_print_params', $params);
	        }
	    }
	}

	/**
	 * Settings form
	 *
	 **/
	public function out_settings_form($args)
	{
		$order_statuses = wc_get_order_statuses();
		wp_enqueue_script('wc-enhanced-select');

		$disconnect_label=__('Disconnect', 'wf-woocommerce-packing-list');
		$connect_label=__('Connect', 'wf-woocommerce-packing-list');

		$default_printer_id=$this->get_default_printer();

		/**
		*	Alter the button attributes/status info based on connection status
		*/		
		$connection_status=$this->check_connection_status();
		$btn_label=($connection_status ? $disconnect_label : $connect_label);
		$btn_action=($connection_status ? 'disconnect' : 'connect');
		$connection_info_html=$this->prepare_connection_info($connection_status);
		
		wp_enqueue_script($this->module_id, plugin_dir_url( __FILE__ ).'assets/js/main.js', array('jquery'), WF_PKLIST_VERSION);

		$params=array(
			'nonces' => array(
	            'main'=>wp_create_nonce($this->module_id),
	        ),
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'connected'=>$connection_status,
	        'default_printer_id'=>$default_printer_id,
	        'msgs'=>array(
	        	'error'=>__('Error', 'wf-woocommerce-packing-list'),
	        	'success'=>__('Success', 'wf-woocommerce-packing-list'),
	        	'unable_to_generate_auth_url'=>__('Unable to generate Auth URL', 'wf-woocommerce-packing-list'),
	        	'client_id_mandatory'=>__('Client ID is mandatory', 'wf-woocommerce-packing-list'),
	        	'client_secret_mandatory'=>__('Client secret is mandatory', 'wf-woocommerce-packing-list'),
	        	'connecting'=>__('Working...', 'wf-woocommerce-packing-list'),
	        	'waiting'=>__('Waiting...', 'wf-woocommerce-packing-list'),
	        	'connect'=>$connect_label,
	        	'disconnect'=>$disconnect_label,
	        	'finding'=>__('Searching for printers...', 'wf-woocommerce-packing-list'),
	        	'set_as_default'=>__('Set as default', 'wf-woocommerce-packing-list'),
	        	'remove_from_default'=>__('Remove from default', 'wf-woocommerce-packing-list'),
	        	'printer_search_error'=>sprintf(__('An error occurred. Please %stry again.%s', 'wf-woocommerce-packing-list'), '<a class="wt_pklist_cloud_setup_printer" onclick="wt_pklist_cloud_print.find_printers()">', '</a>'),
	        	'printer_test_error'=>__('Testing failed.', 'wf-woocommerce-packing-list'),
	        	'printer_test_success'=>__('Tested OK. Verify print.', 'wf-woocommerce-packing-list'),
	        	'printer_test_gdrive_success'=>sprintf(__('Tested OK. Verify the print %shere%s', 'wf-woocommerce-packing-list'), '<a href="https://drive.google.com/" target="_blank">', '</a>'),
	        	'default_printer'=>__('Default printer. This printer will be the default one to print documents.', 'wf-woocommerce-packing-list'),
	        )
		);
		wp_localize_script($this->module_id, 'wt_pklist_cloud_print_params', $params);
		
		/**
		*	Cloud enabled docs available
		*/
		if($this->documents && is_array($this->documents) && count($this->documents)>0)
		{	
			$params=array(
				'module_id'=>$this->module_id,
				'module_base'=>$this->module_base,
				'order_statuses'=>$order_statuses,
				'documents'=>$this->documents, /* cloud print enabled docs */
				'btn_label'=>$btn_label, /* Button text for connect button */
				'btn_action'=>$btn_action, /* Button action for connect button */
				'connection_info_html'=>$connection_info_html, /* Connection info HTML */
				'default_printer_id'=>$default_printer_id,
				'connection_status'=>$connection_status,
			);
			$view_file=plugin_dir_path( __FILE__ ).'views/settings.php';
		}else
		{
			$params=array(
				'module_id'=>$this->module_id,
			);
			$view_file=plugin_dir_path( __FILE__ ).'views/no_cloud_enabled_docs.php';
		}
		
		Wf_Woocommerce_Packing_List_Admin::envelope_settings_tabcontent('cloud-print', $view_file, '', $params, 0);
	}

	/**
	*	Include google auth class
	*/
	public function include_google_auth()
	{
		include_once plugin_dir_path(__FILE__)."classes/class-google-auth.php";
	}

	/**
	*	Include google cloud print class
	*/
	public function include_google_cloud_print()
	{
		include_once plugin_dir_path(__FILE__)."classes/class-google-cloud-print.php";
	}

	/**
	*	Update/Add default printer.
	*/
	protected function update_default_printer($printer_id)
	{
		update_option($this->default_printer_option_key, $printer_id);
	}

	/**
	*	Get ID of default printer
	*/
	protected function get_default_printer()
	{
		return get_option($this->default_printer_option_key, 0);
	}

	/**
	*	Get connection details
	* 	Access token, Refresh token etc
	*/
	protected function get_connection_data()
	{
		return get_option($this->connection_option_key, array());
		
		/* for testing purpose only */
		return array(
			'access_token'=>'123abcd'
		); 
	}

	/**
	*	Update/Add connection details.
	*	Access token, Refresh token etc
	*/
	protected function update_connection_data($connection_data)
	{
		/**
		*	Convert it to timestamp
		*/
		$connection_data['expires_at']=time()+$connection_data['expires_in'];

		update_option($this->connection_option_key, $connection_data);
	}

	/**
	*	Remove/empty connection data.
	*	On deauthorize request
	*/
	protected function remove_connection_data()
	{
		update_option($this->connection_option_key, array());
	}

	/**
	*
	*	Get Client ID and Client secret 	
	*/
	protected function get_api_credentials()
	{
		return array(
			'client_id'=>Wf_Woocommerce_Packing_List::get_option('wt_pklist_cloud_print_client_id', $this->module_id),
			'client_secret'=>Wf_Woocommerce_Packing_List::get_option('wt_pklist_cloud_print_client_secret', $this->module_id),
		);
	}

	/**
	*
	*	Update Client ID and Client secret on `Connect` action.
	*	On normal form submission(Settings form) these values are saved by `save_settings` method	
	*/
	protected function update_api_credentails($client_id, $client_secret)
	{
		Wf_Woocommerce_Packing_List::update_option('wt_pklist_cloud_print_client_id', $client_id, $this->module_id);
		Wf_Woocommerce_Packing_List::update_option('wt_pklist_cloud_print_client_secret', $client_secret, $this->module_id);
	}

	/**
	*	Prepare connection status HTML
	*/
	protected function prepare_connection_info($status)
	{
		$html='';
		if($status)
		{
			$html='<span style="color:green;"><span class="dashicons dashicons-yes-alt"></span> '.__('Connected', 'wf-woocommerce-packing-list').'</span> <button type="button" class="button button-primary wt_pklist_cloud_setup_printer" onclick="wt_pklist_cloud_print.find_printers()"> '.__('Add a printer', 'wf-woocommerce-packing-list').'</button>';
		}
		return $html;
	}

	/**
	*	Check connected or not
	*/
	protected function check_connection_status()
	{
		$connection_data=$this->get_connection_data();
		return (isset($connection_data['access_token']) && $connection_data['access_token']!="");
	}

	/**
	*	Check token is expired or not
	*	Must call after connected checking is true.
	*	@return boolean true on expired and false on valid
	*/
	protected function is_token_expired()
	{
		$connection_data=$this->get_connection_data();
		$expires_at=(isset($connection_data['expires_at']) ? absint($connection_data['expires_at']) : 0);
		if($expires_at<time()) /* expired */
		{
			return true;
		}
		return false;
	}

	/**
	*	Generate autorization redirect URL. 
	*	Google will return access code via this URL
	*/
	protected function generate_auth_redirection_url()
	{
		return site_url().'?wt_pklist_cloud_print_auth=1';
	}

}
new Wf_Woocommerce_Packing_List_Cloud_Print();