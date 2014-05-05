<?php 
/*
 * Plugin Name: Squeeze Page Toolkit
 * Version: 1.11
 * Plugin URI: http://wordpress.org/plugins/squeeze-page-toolkit/
 * Description: The official plugin for the Squeeze Page Toolkit for WordPress, allowing you to show your squeeze pages on your WordPress website.
 * Author: WordPress Doctors
 * Author URI: http://www.wpdoctors.co.uk
 */

/** The current version of the database. */
define('SPTK_DATABASE_VERSION', 		'1.11');

/** The current version of the database. */
define('SPTK_DATABASE_KEY', 			'SPTK_Version');

/** The key used to store settings in the database. */
define('SPTK_DATABASE_SETTINGS_KEY', 	'SPTK_Settings');

/** The base URL to use for the API requests. */
define('SPTK_API_BASE',					'http://www.squeezepagetoolkit.com/api/v1'); 

/** The ID of the plugin for update purposes, must be the file path and file name. */
define('SPTK_PLUGIN_UPDATE_ID', 		'sptk-for-wp/squeeze-page-toolkit.php');

/** If true, then caching is disabled. */
define('SPTK_DEBUG_MODE', 				false);


// Load admin scripts
if (is_admin())
{
	// WP Lib
	include_once 'wplib/utils_pagebuilder.inc.php';	
	
	// Plugin
	include_once 'lib/admin_only.inc.php';
}

// Load frontend scripts
else {
	
}


// Common Code
include_once 'wplib/utils_settings.inc.php';
include_once 'lib/common.inc.php';


/**
 * Initialisation functions for plugin.
 */
function SPTK_plugin_init()
{
	// ### Admin
	if (is_admin())
	{
		// Menus
		add_action('admin_menu', 								'SPTK_menu_MainMenu');
		
		// Styles and Scripts 
		add_action('admin_print_styles',  						'SPTK_styles_backendStyles');
		
		// Meta Boxes
		add_action('add_meta_boxes', 							'SPTK_meta_attachSqueezePageSelection');
		add_action('save_post', 								'SPTK_meta_saveSqueezePageSelection');
		
		// Cleanups - SEO meta, etc
		add_action('add_meta_boxes', 							'SPTK_metabox_removeYoastSEO', 100000);
		
		// Settings link
		add_filter('plugin_action_links', 						'SPTK_plugin_settingsLinks', 10, 2);
		
		// Notices about permalinks
		add_action('admin_notices', 							'SPTK_plugin_permalinkCheck');
		add_action('generate_rewrite_rules' , 					'SPTK_plugin_permalinkCheck_permalinksUpdated', 10, 1);
	}

	// ### Frontend
	else
	{
		// Allows the template to be replace with a SPTK page
		add_action('template_redirect', 'SPTK_templates_templatePageOverride');
	}	

	// Custom post types
	SPTK_plugin_registerCustomPostTypes();
	
	// Add thanks query parameter
	add_filter('query_vars', 	'SPTK_page_addThanksParameter');
	
	// Remove permalink for squeeze pages	
	// http://vip.wordpress.com/documentation/remove-the-slug-from-your-custom-post-type-permalinks/
	
	// See if we're overriding the URL for compatibility. If we are, then we need to 
	// disable our URL searching here. 
	if (!apply_filters('sptk_compatibility_url_override', false)) 
	{
		add_action('pre_get_posts',  'SPTK_urlClean_findSqueezePage');  
		add_filter('post_type_link', 'SPTK_urlClean_removeSlug', 10, 3);	
	}
}
add_action('init', 'SPTK_plugin_init');




/**
 * Checks to see if the permalinks have been updated to be used with SPTK.
 */
function SPTK_plugin_permalinkCheck()
{
	// Check that permalinks have been updated recently, and we're using normal permalinks.
	if (get_option('permalink_structure') && get_option('sptk_permalink_check') === FALSE)
	{
		printf('<div class="updated">
					<p>%s</p>
					<p>%s <b><a href="%s">%s</a></b>.</p>
				</div>',
		__("For the <b>Squeeze Page Toolkit</b> plugin to work correctly, please ensure update your <b>permalinks</b>.", 'sptk_for_wp'),
		__("Just click '<b>Save Changes</b>' on the ", 'sptk_for_wp'),
		admin_url('options-permalink.php'),
		__('Permalink Settings page', 'sptk_for_wp')
		);
	}
}


/**
 * Hook called when the permalinks are updated, so that the permalinks nag message does not show.
 */
function SPTK_plugin_permalinkCheck_permalinksUpdated()
{
	// Marks the permalink check as having been updated.
	update_option('sptk_permalink_check', true);
}




/**
 * Creates the squeeze page custom post type.
 */
function SPTK_plugin_registerCustomPostTypes()
{
	$labels = array(
        'name' 					=> __( 'Squeeze Pages', 					'sptk_for_wp'),
        'singular_name' 		=> __( 'Squeeze Page', 						'sptk_for_wp'),
        'add_new' 				=> __( 'Add New', 							'sptk_for_wp'),
        'add_new_item' 			=> __( 'Add New Squeeze Page', 				'sptk_for_wp'),
        'edit_item'	 			=> __( 'Edit Squeeze Page', 				'sptk_for_wp'),
        'new_item' 				=> __( 'New Squeeze Page', 					'sptk_for_wp'),
        'view_item' 			=> __( 'View Squeeze Page', 				'sptk_for_wp'),
        'search_items' 			=> __( 'Search Squeeze Pages', 				'sptk_for_wp'),
        'not_found' 			=> __( 'No squeeze pages found', 			'sptk_for_wp'),
        'not_found_in_trash'	=> __( 'No squeeze pages found in Trash', 	'sptk_for_wp'),
        'parent_item_colon' 	=> __( 'Parent Squeeze Page:', 				'sptk_for_wp'),
        'menu_name' 			=> __( 'Squeeze Pages', 					'sptk_for_wp'),
	);

	$args = array(
        'labels' 				=> $labels,
        'hierarchical' 			=> true,	// Set to true to mimic pages, and avoid page collisions.

        'supports'				=> array( 'title'),

        'public' 				=> true,
        'show_ui' 				=> true,
        'show_in_menu' 			=> true,
        'menu_position' 		=> 20,
		
		// DJH 2013-03-03 - Method #2 - Set rewrite to false, according to
		//'rewrite' 				=> array('with_front' => false, 'slug' => 'squeeze_page'),
		
		// DJH 2014-03-03 - Method #1
		// DJH 2014-05-06 - Added option using filters to override the slug URL
		'rewrite' 				=> array('slug' => apply_filters('sptk_compatibility_url_override', 'squeeze_page')),
	
        //'menu_icon' 			=> SPTK_plugin_getPluginPath().'img/icon_training_16.png',
        'show_in_nav_menus' 	=> true,		// Show in WP Custom Menus
        'publicly_queryable' 	=> true,
        'exclude_from_search' 	=> true,		// Don't show in searches
        'has_archive' 			=> false,		// Doesn't have an archive
        'query_var' 			=> true,
        'can_export' 			=> true,		// Can export
        'capability_type' 		=> 'page',
	);

	register_post_type('squeeze_page', $args );
}



/** 
 * Use a database query to try to find the post that's being fetched.
 * Method 1 - New Version
 */
// Only works with /%postname%/ - that's it!
/*function SPTK_urlClean_findSqueezePage($query)
{
    // Only noop the main query
    if (!$query->is_main_query())
        return;
 
     error_log(print_r($query->query, true));
        
    // Only noop our very specific rewrite rule match
    if (2 != count($query->query) || ! isset($query->query['page'])) {
        return;
    }
    
    // 'name' will be set if post permalinks are just post_name, otherwise the page rule will match
    if (! empty($query->query['name'])) {
        $query->set('post_type', array('post', 'squeeze_page', 'page'));
    }
}*/

/**
 * Use a database query to try to find the post that's being fetched.
 */
function SPTK_urlClean_findSqueezePage($query)
{
    global $wpdb;
    
    // 2014-01-15 - is_home() check is for Emma's issue with homepage redirecting to a squeeze page.
    if (is_admin() ||											// Checks for front of site only 
    	!$query->is_main_query() || 							// Checks that it's a main query
    	is_home() || 											// Check that we're not on the homepage
    	is_front_page()	||										// Check we haven't got a static front page
    	$query->get('page_id') == get_option('page_on_front')	// Backup check for a static front page if is_home() is false if theme is broken
    	) {				
		return;
    }
    
    //error_log(print_r($query, true));
    
    // What permalink structure do we have?
    $permalinkStructure = trim(get_option('permalink_structure'));
    $normalPermalink = false;
    
    switch($permalinkStructure)
    {		
    	// Got category in the URL for permalinks, which means WordPress
    	// mistakes the post for a category.
    	case '/%category%/%postname%/':
    			$post_name = $query->get('category_name');
    		break;
    		
    	// Normal
    	case '/%postname%/':
    			$normalPermalink = true;
    			$post_name = $query->get('name');
    		break;
    		
    	// Year/Month/Day/Post
    	// Year/Month/Post
    	default:
    			$normalPermalink = true;
    			$post_name = $query->get('pagename');
    		break;
    }

    
    // Try to locate any post matching the post name.         
    $postInfo = $wpdb->get_row($wpdb->prepare("
    	SELECT ID, post_type 
    	FROM $wpdb->posts 
    	WHERE post_name = %s LIMIT 1
    	", $post_name));

    // Only if the page was found do we change anything
    if ($postInfo)
    {
	    // If we've found something, ensure we flag it up as being a squeeze page, not 
	    // any other type of of page.
	    switch($postInfo->post_type) 
	    {
			case 'squeeze_page':
				// Triggers a reset of the query (especially for non-std categories) to pick
				// up the new page ID, regardless if it thinks this is a category or not.
				if (!$normalPermalink) {
					$query->parse_query('p=' . $post_type->ID);
				}
				
	        	$query->set('squeeze_page', $post_name);
	        	$query->set('post_type', $postInfo->post_type);
	        	$query->is_single = true;
	        	$query->is_page = false;
	        break;
	    } 
    }

    return $query;
}




/**
 * Create the main menu.
 */
function SPTK_menu_MainMenu()
{
	// ### Settings for Squeeze Pages
	add_submenu_page('edit.php?post_type=squeeze_page', 
		__('Squeeze Page Toolkit for WordPress - Settings Page', 'sptk_for_wp'),
		__('Settings', 'sptk_for_wp'),
					'manage_options', 'SPTK_showPage_Settings', 'SPTK_showPage_Settings');
}




/**
 * Adds a 'Settings' link to the plugin settings line in the plugins page.
 * 
 * @param Array $links The links that are already going to be shown.
 * @param String $file The plugin file.
 * 
 * @return Array The modified list of links.
 */
function SPTK_plugin_settingsLinks($links, $file)
{
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    // Yep, it's this plugin, show the settings link.
    if ($file == $this_plugin) 
    {
        array_unshift($links, sprintf('<a href="%sedit.php?post_type=squeeze_page&page=SPTK_showPage_Settings">%s</a>',
        	admin_url('/'), 
        	__('Settings', 'sptk_for_wp'))
        );
    }

    return $links;
}


/**
 * Get the URL for the plugin path including a trailing slash.
 * @return String The URL for the plugin path.
 */
function SPTK_plugin_getPluginPath() {
	$folder = basename(dirname(__FILE__));
	return plugins_url($folder) . '/';
}




/**
 * Get the directory path for the plugin path including a trailing slash.
 * @return String The URL for the plugin path.
 */
function SPTK_plugin_getPluginDirPath() {
	$folder = basename(dirname(__FILE__));
	return WP_PLUGIN_DIR . "/" . trailingslashit($folder);
}


/**
 * Add the styles needed for the page for this plugin.
 */
function SPTK_styles_backendStyles()
{
	// Shown on all admin pages
	wp_enqueue_style('sptk-admin', 	SPTK_plugin_getPluginPath() . 'css/admin_only.css', false, SPTK_DATABASE_VERSION);
	
	//if (!SPTK_areWeOnPluginPage())
	//return;
}



// Get rid of WordPress SEO metabox - adapted from http://wordpress.stackexchange.com/a/91184/2015
function SPTK_metabox_removeYoastSEO() {
	remove_meta_box('wpseo_meta', 'squeeze_page', 'normal'); 
}

	
/**
 * Triggered when the plugin is activated to remind about permalinks.
 */
function SPTK_plugin_activated()
{
	// Deletes the permalink check.
	delete_option('sptk_permalink_check');
}
register_activation_hook( __FILE__, 'SPTK_plugin_activated' );

?>