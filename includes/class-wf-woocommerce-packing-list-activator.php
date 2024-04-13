<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.webtoffee.com/
 * @since      4.0.0
 *
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0.0
 * @package    Wf_Woocommerce_Packing_List
 * @subpackage Wf_Woocommerce_Packing_List/includes
 * @author     WebToffee <info@webtoffee.com>
 */
class Wf_Woocommerce_Packing_List_Activator {

	/**
	 * 	Plugin activation hook
	 *
	 * 	@since   4.0.0
	 *	@since 	 4.0.6 Added option to secure directory with htaccess	
	 *	@since 	 4.1.1 Added option to update Store address from Woo
	 */
	public static function activate() 
	{ 
	    global $wpdb;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );       
        if(is_multisite()) 
        { /*
            // Get all blogs in the network and activate plugin on each one
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            foreach($blog_ids as $blog_id ) 
            {
                switch_to_blog( $blog_id );
                self::install_tables();
                self::copy_address_from_woo();
                restore_current_blog();
            } */
        }
        else 
        {
            self::install_tables();
            self::copy_address_from_woo();
        }

        self::secure_upload_dir();
	}

	/**
	*	@since 4.1.1
	*	Update store address from Woo	
	*/
	public static function copy_address_from_woo()
	{
		if(class_exists('Wf_Woocommerce_Packing_List'))
		{
			/* all fields are empty. */
			if((Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_sender_address_line1')=='' && 
	        	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_sender_address_line2') == '' && 
	        	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_sender_city') == '' && 
	        	Wf_Woocommerce_Packing_List::get_option('wf_country') == '' && 
	        	Wf_Woocommerce_Packing_List::get_option('woocommerce_wf_packinglist_sender_postalcode') == '')) 
	        {
	        	Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_packinglist_sender_address_line1', get_option('woocommerce_store_address'));
	        	Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_packinglist_sender_address_line2', get_option('woocommerce_store_address_2'));
	        	Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_packinglist_sender_city', get_option('woocommerce_store_city'));
	        	Wf_Woocommerce_Packing_List::update_option('wf_country', get_option('woocommerce_default_country'));
	        	Wf_Woocommerce_Packing_List::update_option('woocommerce_wf_packinglist_sender_postalcode', get_option('woocommerce_store_postcode'));
			}
		}
	}

	/**
	*	@since 4.0.6
	*	Secure directory with htaccess	
	*/
	public static function secure_upload_dir()
	{
		$upload_dir=Wf_Woocommerce_Packing_List::get_temp_dir('path');

        if(!is_dir($upload_dir))
        {
            @mkdir($upload_dir, 0700);
        }

        $files_to_create=array('.htaccess' => 'deny from all', 'index.php'=>'<?php // Silence is golden');
        foreach($files_to_create as $file=>$file_content)
        {
        	if(!file_exists($upload_dir.'/'.$file))
	        {
	            $fh=@fopen($upload_dir.'/'.$file, "w");
	            if(is_resource($fh))
	            {
	                fwrite($fh,$file_content);
	                fclose($fh);
	            }
	        }
        }    
	}

	public static function install_tables()
	{
		global $wpdb;
		
		//install necessary tables

		//creating table for saving template data================
        $search_query = "SHOW TABLES LIKE %s";
        $charset_collate = $wpdb->get_charset_collate();
        $tb=Wf_Woocommerce_Packing_List::$template_data_tb;
        $like = '%' . $wpdb->prefix.$tb.'%';
        $table_name = $wpdb->prefix.$tb;


        if(!$wpdb->get_results($wpdb->prepare($search_query, $like), ARRAY_N)) 
        {
            $sql_settings = "CREATE TABLE IF NOT EXISTS `$table_name` (
			  `id_wfpklist_template_data` int(11) NOT NULL AUTO_INCREMENT,
			  `template_name` varchar(200) NOT NULL,
			  `template_html` text NOT NULL,
			  `template_from` varchar(200) NOT NULL,
			  `is_dc_compatible` int(11) NOT NULL DEFAULT '0',
			  `is_active` int(11) NOT NULL DEFAULT '0',
			  `template_type` varchar(200) NOT NULL,
			  `created_at` int(11) NOT NULL DEFAULT '0',
			  `updated_at` int(11) NOT NULL DEFAULT '0',
			  PRIMARY KEY(`id_wfpklist_template_data`)
			) DEFAULT CHARSET=utf8;";
            dbDelta($sql_settings);

        }else
        {
	        $search_query = "SHOW COLUMNS FROM `$table_name` LIKE 'is_dc_compatible'";
	        if(!$wpdb->get_results($search_query,ARRAY_N)) 
	        {
	        	$wpdb->query("ALTER TABLE `$table_name` ADD `is_dc_compatible` int(11) NOT NULL DEFAULT '0' AFTER `template_from`");
	        }
        }
        //creating table for saving template data================

		$packing_instructions_table_name = $wpdb->prefix . "packing_instructions";
		$packing_instructions_table_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $packing_instructions_table_name ) );

		if (!$wpdb->get_var( $packing_instructions_table_query ) == $packing_instructions_table_name ) {
			$sql = "CREATE TABLE {$packing_instructions_table_name} (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`text_instruction` longtext NOT NULL,
				`file_instruction` longtext NOT NULL,
				`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        		`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`instruction_type` ENUM('preparation_instruction', 'packing_instruction') NOT NULL,
				UNIQUE KEY id (id));";

			dbDelta($sql);
		}

		$packing_conditions_table_name = $wpdb->prefix . "packing_conditions";
		$packing_conditions_table_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $packing_conditions_table_name ) );

		if (!$wpdb->get_var( $packing_conditions_table_query ) == $packing_conditions_table_name ) {
			$sql = "CREATE TABLE {$packing_conditions_table_name} (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`p_parameter1` varchar(255) NOT NULL,
				`p_parameter2` varchar(255) NOT NULL,
				`p_parameter3` varchar(255) NOT NULL,
				`p_parameter4` varchar(255) NOT NULL,
				`p_parameter5` varchar(255) NOT NULL,
				`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        		`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`instruction_id` int(11) NOT NULL,
				UNIQUE KEY id (id));";

			dbDelta($sql);
		}
        
	}

}
