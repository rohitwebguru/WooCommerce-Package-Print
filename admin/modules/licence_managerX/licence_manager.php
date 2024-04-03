<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Wf_Woocommerce_Packing_List_Licence_Manager
{
	public $module_id='';
	public $module_base='licence_manager';
	public $api_url='https://www.webtoffee.com/';
	public $main_plugin_slug='';
	public $tab_icons=array(
		'active'=>'<span class="dashicons dashicons-yes" style="color:#03da01; font-size:25px;"></span>',   
	    'inactive'=>'<span class="dashicons dashicons-warning" style="color:#ff1515; font-size:25px;"></span>'
	);

	public $products=array();

	public function __construct()
	{
		$this->module_id 			=Wf_Woocommerce_Packing_List::get_module_id($this->module_base);
		$this->my_account_url		=$this->api_url.'my-account';
		$this->main_plugin_slug		=dirname(WF_PKLIST_PLUGIN_BASENAME);

		require_once plugin_dir_path(__FILE__).'classes/class-edd.php';	
		require_once plugin_dir_path(__FILE__).'classes/class-wc.php';	

		$this->products=array(
			$this->main_plugin_slug=>array(
				'product_id'			=>	WF_PKLIST_ACTIVATION_ID,
				'product_edd_id'		=>	WT_PKLIST_EDD_ACTIVATION_ID,
				'plugin_settings_url'	=>	admin_url('admin.php?page='.WF_PKLIST_POST_TYPE.'#wt-licence'),
				'product_version'		=>	WF_PKLIST_VERSION,
				'product_name'			=>	WF_PKLIST_PLUGIN_BASENAME, 
				'product_slug'			=>	$this->main_plugin_slug,
				'product_display_name'	=>	'WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels', 
			)
		);

		add_action('plugins_loaded', array($this, 'init'), 1);

		/**
		*	Add tab to settings section
		*/
		add_filter('wt_pklist_plugin_settings_tabhead', array($this, 'licence_tabhead'));
		add_action('wt_pklist_plugin_out_settings_form', array($this, 'licence_content'));

		/**
		*	 Main Ajax hook to handle all ajax requests 
		*/
		add_action('wp_ajax_wt_pklist_licence_manager_ajax', array($this, 'ajax_main'),11);

		/**
		*	 Check for plugin updates
		*/
		add_filter( 'pre_set_site_transient_update_plugins',array($this, 'update_check'));

		/** 
		*	Check For Plugin Information to display on the update details page
		*/
		add_filter('plugins_api', array( $this, 'update_details'), 10, 3);
	}

	public function init()
	{
		/**
		*	Add products to licence manager
		*/
		$this->products=apply_filters('wt_pklist_add_licence_manager', $this->products);
	}

	/**
	*	Fetch the details of the new update.
	*	This will show in the plugins page as a popup
	*/
	public function update_details($false, $action, $args)
	{		
		if(!isset($args->slug))
		{
			return $false;
		}

		/**
		*	Get licence info
		*/
		$licence_data=$this->get_licence_data($args->slug);
	
		if(!$licence_data) /* no licence exists */
		{
			return $false;
		}

		/**
		*	Check product exists
		*/
		if(!isset($this->products[$args->slug]))
		{
			return $false;
		}

		/**
		*	Get product info
		*/
		$product_data=$this->products[$args->slug];

		return $this->get_license_type_obj($licence_data)->update_details($this, $product_data, $licence_data, $false, $action, $args);
	}


	/**
	* 	Check for plugin updates 
	*/
	public function update_check($transient)
	{
		if(empty( $transient->checked ))
		{
			return $transient;
		}

		$home_url=urlencode(home_url());

		/**
		*	Get all licence info
		*/
		$licence_data=$this->get_licence_data();

		/**
		*	Main product data
		*/
		$product_data=$this->products[$this->main_plugin_slug];

		/* This is for WC type licenese */
		include_once "classes/class-wt-response-error-messages.php";
		$error_message_obj=new Wt_Pklist_licence_manager_error_messages($product_data['plugin_settings_url'], $product_data['product_display_name'], $this->my_account_url);

		
		if(!function_exists('get_plugin_data')) /* this function is required for fetching current plugin version */
		{
		    require_once ABSPATH.'wp-admin/includes/plugin.php';
		}

		$timestamp=time(); //current timestamp
		foreach ($licence_data as $product_slug => $value)
		{
			if($value['status']=='active' && isset($this->products[$product_slug]))
			{
				$product_data=$this->products[$product_slug];

				/**
				*	Taking the last update check time
				*/
				$last_check=get_option($product_slug.'-last-update-check');
				if($last_check==false) //first time so add a four hour back time.
				{ 
					$last_check=$timestamp-14402;
					update_option($product_slug.'-last-update-check', $last_check);
				}

				/**
				* 	Previous check is before 4 hours or Force check
				*/
				if(($timestamp-$last_check)>14400 || (isset($_GET['force-check']) && $_GET['force-check']==1)) 
				{
					$license_type=$this->get_license_type($value);
					if($license_type=='WC')
					{

						$args = array(
							'request'			=>	'pluginupdatecheck',
							'slug'				=>	'',
							'plugin_name'		=>	'',
							'version'			=>	'',
							'product_id'		=>	'',
							'domain'			=>	$home_url,
							'software_version'	=>	'',
							'extra'				=> 	'',
							'wc-api'			=>	'upgrade-api',

							/* product details */
							'slug'				=>	$product_data['product_slug'],
							'plugin_name'		=>	$product_data['product_name'],
							'version'			=>	$product_data['product_version'],
							'product_id'		=>	$product_data['product_id'],
							'software_version'	=>	$product_data['product_version'],
							
							/* licence details */
							'api_key'			=>	$value['key'],
							'activation_email'	=>	$value['email'],
							'instance'			=>	$value['instance_id'],
						);

					}else
					{
						$args = array(
							'edd_action'		=> 	'get_version',
							'url' 				=> 	$home_url,
							
							/* product details */
							'item_id' 			=> 	(isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0),
							'license' 			=> 	$value['key'],
						);
					}


					/* fetch plugin response */
					$response = $this->fetch_plugin_info($args);
					
					if(isset($response) && is_object($response) && $response!== false )
					{
						$plugin_slug=$product_slug;
						if($license_type=='WC')
						{
							if(!property_exists($response, 'errors'))
							{
								$transient=$this->add_update_availability($transient, $plugin_slug, $response);
							}else
							{
								/**
								*	Displays an admin error message in the WordPress dashboard
								*/
								$error_message_obj->product_display_name=$product_data['product_display_name'];
								$error_message_obj->plugin_settings_url=$product_data['plugin_settings_url'];
								$error_message_obj->check_response_for_errors($response);
							}

						}else
						{
							$transient=$this->add_update_availability($transient, $plugin_slug, $response);
						}
					}

					/**
					*	Update last check time with current time
					*/
					update_option($product_slug.'-last-update-check', $timestamp);
				}			
			}
		}
		return $transient;
	}

	/**
	*	Add plugin update availability to transient 
	*/
	public function add_update_availability($transient, $plugin_slug, $response)
	{
		/* a compatibility fix */
		$plugin_file_name=($plugin_slug=='wt-woocommerce-packing-list' ? 'wf-woocommerce-packing-list' : $plugin_slug);

		$plugin_base_path="$plugin_slug/$plugin_file_name.php";
		if(is_plugin_active($plugin_base_path)) /* checks the plugin is active */
		{
			$current_plugin_data=get_plugin_data(WP_PLUGIN_DIR."/$plugin_base_path");
			$current_version=$current_plugin_data['Version'];
			$new_version=$response->new_version;
			if(version_compare($new_version, $current_version, '>')) /* new version available */
			{
				$obj 									= new stdClass();
				$obj->slug 								= $plugin_slug;
				$obj->plugin 							= $plugin_base_path;
				$obj->new_version 						= $new_version;
				$obj->url 								= (isset($response->url) ? $response->url : '');
				$obj->package 							= (isset($response->package) ? $response->package : '');
				$obj->icons 							= (isset($response->icons) ? maybe_unserialize($response->icons) : array());
				$transient->response[$plugin_base_path] = $obj;
			}
		}

		return $transient;
	}

	/**
	*	Fetch plugin info for update check and update info
	*/
	public function fetch_plugin_info($args)
	{
		$request=$this->remote_get($args);

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request)!=200)
		{
			return false;
		}

		if(isset($args['api_key'])) //WC type. In EDD `license` instead of `api_key`
		{
			$response=maybe_unserialize(wp_remote_retrieve_body($request));
		}else
		{
			$response=json_decode(wp_remote_retrieve_body($request));
		}
				
		if(is_object($response))
		{
			return $response;
		}else
		{
			return false;
		}
	}

	/**
	* Main Ajax hook to handle all ajax requests. 
	*/
	public function ajax_main()
	{
		$allowed_actions=array('activate', 'deactivate', 'delete', 'licence_list', 'check_status');
		$action=(isset($_POST['wt_pklist_licence_manager_action']) ? sanitize_text_field($_POST['wt_pklist_licence_manager_action']) : '');
		$out=array('status'=>true, 'msg'=>'');
		if(!Wf_Woocommerce_Packing_List_Admin::check_write_access(WF_PKLIST_POST_TYPE))
		{
			$out['status']=false;

		}else
		{
			if(in_array($action,$allowed_actions))
			{
				if(method_exists($this,$action))
				{
					$out=$this->{$action}($out);
				}
			}
		}
		echo json_encode($out);
		exit();	
	}

	/**
	*	Ajax sub function to check licence status
	*/
	public function check_status($out)
	{
		$licence_data_arr=$this->get_licence_data();
		
		foreach($licence_data_arr as $product_slug => $licence_data)
		{
			if(isset($this->products[$product_slug])) /* product currently exists */
			{
				$product_data=$this->products[$product_slug];
				$response=$this->fetch_status($product_data, $licence_data);
				$response_arr=json_decode($response, true);
						
				
				$new_status=$this->get_license_type_obj($licence_data)->check_status($licence_data, $response_arr);

				/* check update needed */
				if($licence_data['status']!=$new_status)
				{
					$licence_data['status']=$new_status;
					$this->update_licence_data($product_slug, $licence_data);
				}
			}
		}

		$out['status']=true;
		return $out;		
	}

	/**
	*	Fetch licence status
	*/
	public function fetch_status($product_data, $licence_data)
	{
		if($this->get_license_type($licence_data)=='WC')
		{
			$args = array(
				'request' 		=> 'status',
				'email'			=> $licence_data['email'],
				'licence_key'	=> $licence_data['key'], 
				'product_id' 	=> $product_data['product_id'],
				'instance' 		=> $licence_data['instance_id'],
				'platform' 		=> home_url(),
				'wc-api'		=> 'am-software-api', //End point
			);
		}else
		{

			$args = array(
				'edd_action' 	=> 'check_license',
				'license'		=> $licence_data['key'], 
				'item_id' 		=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0),
				'url' 			=> urlencode(home_url()),
			);
		}

		$request=$this->remote_get($args);
		
		$response = wp_remote_retrieve_body($request);

		return $response;
	}

	/**
	*	Ajax sub function to delete licence
	*/
	public function delete($out)
	{
		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_pklist_licence_product']) ? sanitize_text_field($_POST['wt_pklist_licence_product']) : '');
		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Error !!!', 'wf-woocommerce-packing-list');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wf-woocommerce-packing-list');
			}
		}
		if($er==0)
		{
			$this->remove_licence_data($licence_product);
            $out['status']=true;
			$out['msg']=__("Successfully deleted.", 'wf-woocommerce-packing-list');
		}

		return $out;
	}

	/**
	*	Ajax sub function to deactivate licence
	*/
	public function deactivate($out)
	{

		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_pklist_licence_product']) ? sanitize_text_field($_POST['wt_pklist_licence_product']) : '');
		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Error !!!', 'wf-woocommerce-packing-list');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wf-woocommerce-packing-list');
			}
		}

		if($er==0)
		{
			$licence_data=$this->get_licence_data($licence_product);
			if(!$licence_data)
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wf-woocommerce-packing-list');
			}
		}

		$product_data=$this->products[$licence_product];
		if($er==0)
		{
			$license_type=$this->get_license_type($licence_data);

			if($license_type=='WC')
			{
				$args=array(
					'request' 		=> 'deactivation',
					'email'			=> $licence_data['email'],
					'licence_key'	=> $licence_data['key'],
					'product_id' 	=> $product_data['product_id'],
					'instance' 		=> $licence_data['instance_id'],
					'platform' 		=> home_url(),
					'wc-api'		=> 'am-software-api', //Endpoint
				);
			}else
			{
				$args=array(
					'edd_action'	=> 'deactivate_license',
					'license'		=> $licence_data['key'],
					//'item_name' 	=> $product_data['product_display_name'], //name in EDD
					'item_id' 		=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0), //ID in EDD
					'url' 			=> urlencode(home_url()),
				);
			}
			$response=$this->remote_get($args);
			
			
			
			if(is_wp_error($response) || wp_remote_retrieve_response_code($response)!=200)
			{
				$out['msg']=__("Request failed, Please try again", 'wf-woocommerce-packing-list');
			}else
	        {
	        	$response=json_decode(wp_remote_retrieve_body($response), true);
	        	$success=false;
	        	if($license_type=='WC')
				{					
		        	if(!isset($response['error']))
		        	{
		        		$success=true;
		        	}
				}else
				{
		        	if(isset($response['success']) && $response['success']===true)
		        	{
		        		$success=true;
		        	}
		        }

		        if($success)
		        {
		        	$this->remove_licence_data($licence_product);
		            $out['status']=true;
					$out['msg']=__("Successfully deactivated.", 'wf-woocommerce-packing-list'); 
		        }else
		        {
		        	$out['msg']=__('Error', 'wf-woocommerce-packing-list');
		        }

	        }
		}
		return $out;
	}

	public function remote_get($args)
	{
		global $wp_version;
		$target_url=esc_url_raw($this->create_api_url($args));

		$def_args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    'httpversion' => '1.0',
		    'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		    'blocking'    => true,
		    'headers'     => array(),
		    'cookies'     => array(),
		    'body'        => null,
		    'compress'    => false,
		    'decompress'  => true,
		    'sslverify'   => false,
		    'stream'      => false,
		    'filename'    => null
		);
		return wp_remote_get($target_url, $def_args);
	}

	/**
	*	Ajax sub function to activate licence
	*/
	public function activate($out)
	{
		global $wp_version;

		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_pklist_licence_product']) ? sanitize_text_field($_POST['wt_pklist_licence_product']) : '');
		$licence_key=trim(isset($_POST['wt_pklist_licence_key']) ? sanitize_text_field($_POST['wt_pklist_licence_key']) : '');
		$licence_email=trim(isset($_POST['wt_pklist_licence_email']) ? sanitize_text_field($_POST['wt_pklist_licence_email']) : '');

		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Please select a product', 'wf-woocommerce-packing-list');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Invalid product', 'wf-woocommerce-packing-list');
			}
		}
		if($er==0 && $licence_key=="")
		{
			$er=1;
			$out['msg']=__('Please enter Licence key', 'wf-woocommerce-packing-list');
		}
		if($er==0 && $licence_key!="")
		{
			/* check the licence key already applied */
			$licence_data=$this->get_licence_data();
			foreach ($licence_data as $product_slug => $licence_info)
			{
				if($product_slug==$licence_product) /* already one licence exists */
				{
					if($licence_info['status']=='active')
					{
						$er=1;
						$out['msg']=__('The chosen plugin already has an active licence.', 'wf-woocommerce-packing-list');
						break;
					}
				}

				/* current licence key matches with another product */
				if($licence_key==$licence_info['key'] && $product_slug!=$licence_product && $licence_info['status']=='active')
				{
					$er=1;
					$out['msg']=__('This licence key has already been activated for another product. Please provide another licence key.', 'wf-woocommerce-packing-list');
					break;
				}
			}
		}

		if($er==0) /* check the entered license belongs to which type */
		{
			$license_type=$this->get_license_type( array('key'=>$licence_key) );
			if($license_type=='WC')
			{
				if($licence_email=="")
				{
					$er=1;
					$out['msg']=__('Please enter Email', 'wf-woocommerce-packing-list');
				}
			}
		}

		if($er==0)
		{
			$product_data=$this->products[$licence_product];
			if($license_type=='WC')
			{
				require_once plugin_dir_path(__FILE__).'classes/class-wc-api-manager-passwords.php';	
				$password_management = new API_Manager_Password_Management();

				// Generate a unique installation $instance id
				$instance = $password_management->generate_password(12, false);

				$args = array(
					'email'				=> $licence_email,
					'licence_key'		=> $licence_key,
					'request' 			=> 'activation',
					'product_id' 		=> $product_data['product_id'],
					'instance' 			=> $instance,
					'platform' 			=> home_url(),
					'software_version' 	=> $product_data['product_version'],
					'wc-api'			=> 'am-software-api', //End point
				);

			}else
			{
				$args = array(
					'edd_action'		=> 'activate_license',
					'license'			=> $licence_key,
					//'item_name' 		=> $product_data['product_display_name'], //name in EDD
					'item_id' 			=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0), //ID in EDD
					'url' 				=> urlencode(home_url()),
				);
			}
			$response=$this->remote_get($args);

			// Request failed
			if(is_wp_error($response))
			{
				$out['msg']=$response->get_error_message();
			}
			elseif( wp_remote_retrieve_response_code( $response ) != 200 )
			{
				$out['msg']=__("Request failed, Please try again", 'wf-woocommerce-packing-list');
			}
	        else
	        {	        	
	        	$response_arr=json_decode($response['body'], true);
		        if($license_type=='WC')
				{
		        	if(!isset($response_arr['error']) && isset($response_arr['activated']) && $response_arr['activated']===true)
		        	{
		        		$licence_data=array(
							'key'			=> $licence_key,
							'email'			=> $licence_email,
							'status'		=> 'active',
							'products'		=> $product_data['product_display_name'], 
							'instance_id'	=> $instance,
						);
						$out['status']=true;
		        	}else
		        	{	
		        		$out['msg']=$response_arr['error'];
		        	}

				}else
				{	
		        	if(isset($response_arr['success']) && $response_arr['success']===true) /* success */
		        	{
	        			$licence_data=array(
							'key'			=> $licence_key,
							'email'			=> (isset($response_arr['customer_email']) ? sanitize_text_field($response_arr['customer_email']) : ''), //from EDD
							'status'		=> 'active',
							'products'		=> $product_data['product_display_name'], 
							'instance_id'	=> (isset($response_arr['checksum']) ? sanitize_text_field($response_arr['checksum']) : ''), //from EDD
						);						
						$out['status']=true;	        		
		        	}

		        	if(!$out['status']) /* error */
		        	{	
		        		$out['msg']=$this->process_error_keys( (isset($response_arr['error']) ? $response_arr['error'] : '') );
		        	}

		        }

		        if($out['status']===true) /* success. Save license info */
		        {
		        	$this->add_new_licence_data($licence_product, $licence_data);
		        	$out['msg']=__("Successfully activated.", 'wf-woocommerce-packing-list');
		        }

	        }
		}
		return $out;
	}

	/**
	*	Ajax sub function to get license list
	*/
	public function licence_list($out)
	{
		$licence_data_arr=$this->get_licence_data(); //taking all license info
		ob_start();
		include plugin_dir_path(__FILE__).'views/_licence_list.php';
		$out['html']=ob_get_clean();
		return $out;
	}

	/**
	*	Mask licence key
	*/
	public function mask_licence_key($key)
	{
		$total_length=strlen($key);
		$non_mask_length=6; //including both side
		$mask_length=$total_length-$non_mask_length;
		
		if($mask_length>=1) //atleast one character
		{
			$key=substr_replace($key, str_repeat("*", $mask_length), floor($non_mask_length/2), ($total_length-$non_mask_length));
		}else
		{
			$key=str_repeat("*", $total_length); //replace all character
		}
		return $key;		
	}

	/**
	*	Licence tab head
	*/
	public function licence_tabhead($arr)
	{	
		$status=true;
		$licence_data=$this->get_licence_data();
		if(!$licence_data)
		{
			$status=false; //no licence found
		}

		if($status && count($licence_data)!=count($this->products))
		{
			$status=false; //licence misisng for some products
		}

		if($status)
		{
			$licence_statuses=array_column($licence_data, 'status');
			if(count($licence_statuses)==0 || in_array('inactive', $licence_statuses) || in_array('', $licence_statuses)) //inactive licence
			{
				$status=false;
			}		
		}

		if($status)
	    {
	        $activate_icon=$this->tab_icons['active'];   
	    }else
	    {
	        $activate_icon=$this->tab_icons['inactive'];
	    }
		$arr['wt-licence']=array(__('Licence','wf-woocommerce-packing-list'),$activate_icon);
		return $arr;
	} 

	/**
	*	Licence tab content
	*/
	public function licence_content()
	{
		wp_enqueue_script($this->module_id, plugin_dir_url( __FILE__ ).'assets/js/main.js', array('jquery'), WF_PKLIST_VERSION);

		$params=array(
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce(WF_PKLIST_POST_TYPE),
	        'tab_icons'=>$this->tab_icons,
	        'msgs'=>array(
	        	'key_mandatory'=>__('Please enter Licence key', 'wf-woocommerce-packing-list'),
	        	'email_mandatory'=>__('Please enter Email', 'wf-woocommerce-packing-list'),
	        	'product_mandatory'=>__('Please select a product', 'wf-woocommerce-packing-list'),
	        	'please_wait'=>__('Please wait...', 'wf-woocommerce-packing-list'),
	        	'error'=>__('Error', 'wf-woocommerce-packing-list'),
	        	'success'=>__('Success', 'wf-woocommerce-packing-list'),
	        	'unable_to_fetch'=>__('Unable to fetch Licence details', 'wf-woocommerce-packing-list'),
	        	'no_licence_details'=>__('No Licence details found.', 'wf-woocommerce-packing-list'),
	        	'sure'=>__('Are you sure?', 'wf-woocommerce-packing-list'),
	        )
		);
		wp_localize_script($this->module_id, 'wt_pklist_licence_params', $params);


		$view_file=plugin_dir_path(__FILE__).'views/licence-settings.php';	
		$params=array(
			'products'=>$this->products
		);
		Wf_Woocommerce_Packing_List_Admin::envelope_settings_tabcontent('wt-licence', $view_file, '', $params, 0);
	}

	public function get_status_label($status)
	{
		$color_arr=array(
			'active'=>'#5cb85c',
			'inactive'=>'#ccc',
		);
		$color_css=(isset($color_arr[$status]) ? 'background:'.$color_arr[$status].';' : '');
		return '<span class="wt_pklist_badge" style="'.$color_css.'">'.ucfirst($status).'</span>';
	}

	public function get_display_name($product_slug)
	{
		if(isset($this->products[$product_slug]))
		{
			return $this->products[$product_slug]['product_display_name'];
		}
		return '';
	}

	private function create_api_url($args)
	{
		return urldecode(add_query_arg($args, $this->api_url));	
	}

	/**
	*	Add new licence info
	*/
	private function add_new_licence_data($product_slug, $licence_data)
	{
		update_option($product_slug.'_licence_data', $licence_data);
	}

	private function remove_licence_data($product_slug)
	{
		delete_option($product_slug.'_licence_data');
	}

	private function update_licence_data($product_slug, $licence_data)
	{
		update_option($product_slug.'_licence_data', $licence_data);
	}

	private function get_licence_data($product_slug="")
	{
		if($product_slug!="")
		{
			$licence_data=get_option($product_slug.'_licence_data', false);
		}else
		{
			$licence_data=array();
			foreach ($this->products as $product_slug => $product)
			{
				$licence_info=get_option($product_slug.'_licence_data', false);
				if($licence_info) //licence exists
				{
					$licence_data[$product_slug]=$licence_info;	
				}
			}
		}
		return $licence_data;
	}

	/**
	*	Check the licence type is EDD or WC
	*/
	private function get_license_type_obj($licence_data)
	{
		if($this->get_license_type($licence_data)=='WC')
		{
			return Wf_Woocommerce_Packing_List_Licence_Manager_Wc::get_instance();
		}
		return Wf_Woocommerce_Packing_List_Licence_Manager_Edd::get_instance();
	}

	/**
	*	Check the licence type is EDD or WC
	*/
	private function get_license_type($licence_data)
	{
		$key=$licence_data['key'];
		if(strpos($key, 'wc_order_')===0)
		{
			return 'WC';
		}
		return 'EDD';
	}

	private function process_error_keys($key)
	{
		$msg_arr=array(
			"missing" => __("License doesn't exist", 'wf-woocommerce-packing-list'),
			"missing_url" => __("URL not provided", 'wf-woocommerce-packing-list'),
			"license_not_activable" => __("Attempting to activate a bundle's parent license", 'wf-woocommerce-packing-list'),
			"disabled" => __("License key revoked", 'wf-woocommerce-packing-list'),
			"no_activations_left" => __("No activations left", 'wf-woocommerce-packing-list'),
			"expired" => __("License has expired", 'wf-woocommerce-packing-list'),
			"key_mismatch" => __("License is not valid for this product", 'wf-woocommerce-packing-list'),
			"invalid_item_id" => __("Invalid Product", 'wf-woocommerce-packing-list'),
			"item_name_mismatch" => __("License is not valid for this product", 'wf-woocommerce-packing-list'),
		);
		return (isset($msg_arr[$key]) ? $msg_arr[$key] : __("Error", 'wf-woocommerce-packing-list'));
	}
}
new Wf_Woocommerce_Packing_List_Licence_Manager();