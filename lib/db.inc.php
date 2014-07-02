<?php


/**
 * Creates an object containing all of the tables being used in this plugin.
 */
class SPTK_Database
{
	/** Constant: Database table of the page cache. */	
	public $page_cache;	
		
	
	/**
	 * Initialise table names.
	 */
	function __construct() 
	{
		global $wpdb;	
		
		// Create full table names from Wordpress
		$this->page_cache 				= $wpdb->prefix . 'sptk_page_cache';
	}
}

$sptkdb = new SPTK_Database();


?>