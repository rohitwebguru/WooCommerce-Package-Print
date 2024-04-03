<?php
/**
 * Google Cloud Print functions for cloud print module
 *
 * @link        
 *
 * @package  Wf_Woocommerce_Packing_List  
 */

if (!defined('ABSPATH')) {
    exit;
}
class Wt_Pklist_Google_Cloud_Print
{
	public static $search_url 	= "https://www.google.com/cloudprint/search";
	public static $print_url	= "https://www.google.com/cloudprint/submit";
    public static $jobs_url		= "https://www.google.com/cloudprint/jobs";

    public static function search_printers($access_token)
    {
    	$printers=array();
    	$headers=array(
    		'Authorization'=>"Bearer ".$access_token,
    	);
    	
    	$printers_json=self::do_post_request(self::$search_url, array(), $headers);
    	
    	if($printers_json)
    	{
    		/* process json object */
    		$printers=self::process_printer_list($printers_json);	
    	}

    	return $printers;
    }

    /**
	*	Send Job data to printer
	*	@param $args array config data array
	*
	*	Sample argument array
	*	$args=array(
    		'access_token'=>$access_token,
    		'printer_id'=>$printer_id,
    		'job_title'=>$job_title,
    		'content_type'=>$content_type,
    		'file_path'=>$file_path, // please specify any one `file_path` or `file_data`
    		'file_data'=>$file_data,
    	);
	*/
    public static function send_to_printer($args)
    {
    	$out=array(
    		'status' 		=>false,
    		'error_code' 	=>'',
    		'msg'			=>__('Error', 'wf-woocommerce-packing-list'), 
    		'job_id' 		=>0
    	);
    	extract($args);
    	if(!isset($access_token) || !isset($printer_id) || !isset($job_title) || !isset($content_type))
    	{
    		$out['msg']=__('Necessary arguments are missing.', 'wf-woocommerce-packing-list');
    		return $out;
    	}
    	if(!isset($file_path) && !isset($file_data))
    	{
    		$out['msg']=__('File input is missing.', 'wf-woocommerce-packing-list');
    		return $out;
    	}
    	
    	if(isset($file_path)) /* If `file_path` and `file_data` exists we are giving preference to file_path */
    	{
    		if(file_exists($file_path))
    		{
    			$file_data=file_get_contents($file_path);
    		}else
	    	{
	    		$out['msg']=__('Input file not exists.', 'wf-woocommerce-packing-list');
	    		return $out;
	    	}   		
    	}

    	if(isset($file_data))
		{
	    	/* post data */
			$post_data=array(				
				'printerid' 				=> $printer_id,
				'title' 					=> $job_title,
				'contentTransferEncoding' 	=> 'base64',
				'content' 					=> base64_encode($file_data), /* encode file data using base64 */
				'contentType'				=> $content_type		
			);

			/* headers */
			$headers=array(
	    		'Authorization'=>"Bearer ".$access_token,
	    	);

	    	$response=self::do_post_request(self::$print_url, $post_data, $headers);
	    	if($response)
	    	{
	    		if(isset($response['success']) && $response['success']=="1")
	    		{	
	    			$out['status']=true;	
	    			$out['job_id']=$response['job']['id'];
	    			$out['msg']='';
				}
				else
				{
					$out['error_code']=$response['errorCode'];	
	    			$out['msg']=$response['message'];
				}
	    	}
		}

		return $out;
    }

    private static function process_printer_list($printers_json)
    {
		$printers = array();
		if(isset($printers_json['printers']))
		{
			foreach($printers_json['printers'] as $printer)
			{
				$printers[]=array(
					'id' 				=> $printer['id'],
					'name' 				=> $printer['name'],
					'display_name' 		=> $printer['displayName'],
					'connection_status' => $printer['connectionStatus'],
				);
			}
		}
		return $printers;
	}

    protected static function do_post_request($url, $params, $headers=array())
    {
    	$args = self::prepare_request_args($params, $headers);

	    $response=wp_remote_post($url, $args);	    

	    /**
	    *	POST request error
	    */
	    if(is_wp_error($response) || wp_remote_retrieve_response_code($response)!=200)
		{
			return false;
		}

	    $response=json_decode(wp_remote_retrieve_body($response), true);
	    
	    /**
	    *	Unable to proccess the JSON
	    */
	    if(is_null($response))
	    {
	    	return false;	
	    }

	    /**
	    *	Invalid response, may be the auth code was expired
	    */
	    if(isset($response['error']))
	    {
	    	return false;
	    }

	    return $response;	
    }

    protected static function prepare_request_args($params, $headers=array())
	{
		global $wp_version;

		$args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    'httpversion' => '1.0',
		    'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		    'blocking'    => true,
		    'headers'     => $headers,
		    'cookies'     => array(),
		    'body'        => $params,
		    'compress'    => false,
		    'decompress'  => true, 
			'stream'      => false,
		    'filename'    => null, 
		    'sslverify'   => false,
		);

		return $args;
	}
}