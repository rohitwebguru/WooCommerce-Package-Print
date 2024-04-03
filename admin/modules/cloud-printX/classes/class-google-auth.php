<?php
/**
 * Google auth functions for cloud print module
 *
 * @link        
 *
 * @package  Wf_Woocommerce_Packing_List  
 */

if (!defined('ABSPATH')) {
    exit;
}
class Wt_Pklist_Cloud_Print_Googleauth
{
	public static $authorization_url = 'https://accounts.google.com/o/oauth2/auth';
	public static $access_token_url	 = 'https://accounts.google.com/o/oauth2/token';
	public static $revoke_url		 = 'https://oauth2.googleapis.com/revoke';
	public static $refresh_token_url = 'https://www.googleapis.com/oauth2/v3/token';

	/**
	*	Generate authorization URL.
	*/
	public static function generate_auth_url($client_id, $redirect_uri)
	{
		$params	= array(
	        'client_id' 	=> $client_id,
	        'redirect_uri' 	=> $redirect_uri,
	        'response_type' => 'code',
	        'scope'         => 'https://www.googleapis.com/auth/cloudprint',
	        'access_type' 	=> 'offline',
	        'prompt' 		=> 'consent', /* normally google will not ask for grant permission after first approval, we are forcing to ask permission. Because we are removing refresh token when they disconnect the account. So we need the refresh token every time */
	    );

	    return esc_url_raw(add_query_arg($params, self::$authorization_url));
	}

	/**
	*	Get access token by refresh token.
	*/
	public static function get_access_token_by_refresh_token($refresh_token, $client_id, $client_secret)
	{
		$params = array(       
	        'refresh_token' => $refresh_token,
	        'client_id' 	=> $client_id,
	        'client_secret' => $client_secret,
	        'grant_type' 	=> "refresh_token" 
	    );

		/*
	    *	Sample response format
	    *	Array
			(
			    [access_token] => 
			    [expires_in] => 3599
			    [refresh_token] => 
			    [scope] => https://www.googleapis.com/auth/cloudprint
			    [token_type] => Bearer
			)

	    */

	    return self::do_access_token_request(self::$refresh_token_url, $params);
	}

	/**
	*	Get access token and refresh token.
	*/
	public static function get_access_token($code, $client_id, $client_secret, $redirect_uri)
	{
		$params = array(
	        'code' 			=> $code,
	        'client_id' 	=> $client_id,
	        'client_secret' => $client_secret,
	        'redirect_uri' 	=> $redirect_uri,
	        'grant_type'   	=> 'authorization_code',
	    );

	    /*
	    *	Sample response format
	    *	Array
			(
			    [access_token] => 
			    [expires_in] => 3599
			    [refresh_token] => 
			    [scope] => https://www.googleapis.com/auth/cloudprint
			    [token_type] => Bearer
			)

	    */

	    return self::do_access_token_request(self::$access_token_url, $params);  
	}

	private static function do_access_token_request($url, $params)
	{
		$args = self::prepare_request_args($params);

	    $response=wp_remote_post($url, $args);

	    /**
	    *	POST request error
	    */
	    if(is_wp_error($response))
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

	/**
	*	Revoke access token.
	*/
	public static function revoke_access_token($token)
	{
		$params = array(
	        'token' => $token
	    );
		$args = self::prepare_request_args($params);

		$args['headers']['Content-Type']='application/x-www-form-urlencoded';

	    $response=wp_remote_post(self::$revoke_url, $args);
	    
	    /**
	    *	POST request error
	    */
	    if(is_wp_error($response))
		{
			return false;
		}else
		{
			return true;
		}
	}

	protected static function prepare_request_args($params)
	{
		global $wp_version;

		$args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    'httpversion' => '1.0',
		    'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		    'blocking'    => true,
		    'headers'     => array(),
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