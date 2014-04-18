<?php


/**
 * Create a dropdown box using the list of values provided and select a value if $selected is specified.
 * @param $name String The name of the drop down box.
 * @param $values String  The values to use for the drop down box.
 * @param $selected String  If specified, the value of the drop down box to mark as selected.
 * @param $cssid String The CSS ID of the drop down list.
 * @param $cssclass String The CSS class for the drop down list.
 * @return String The HTML for the select box.
 */
function SPTK_forms_createDropdown($name, $values, $selected, $cssid = false, $cssclass = false)
{
	if (!$values) {
		return false;
	}
	
	$selectedhtml = 'selected="selected" ';
	
	// CSS Attributes
	$css_attrib = false;
	if ($cssid) {
		$css_attrib = "id=\"$cssid\" ";
	}
	if ($cssclass) {
		$css_attrib .= "class=\"$cssclass\" ";
	}
	
	$html = sprintf('<select name="%s" %s>', $name, $css_attrib);	
	
	foreach ($values as $key => $label)
	{
		$html .= sprintf('<option value="%s" %s>%s&nbsp;&nbsp;</option>', $key, ($key == $selected ? $selectedhtml : ''), $label);
	}
		
	return $html . '</select>';
}


/**
 * Uses the API to get a list of pages from the squeeze page toolkit.
 */
function SPTK_api_getListOfSqueezePages()
{
	$resultData = array(
		'error_msg'	=> '',
		'success'	=> false,
		'data'		=> false,
	);
	
	$httpArgs = array(
		'body'	=> array('apiKey' => TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'remote_api_key')),
	);
	
	// Do the actual request now
	$response = wp_remote_post(SPTK_API_BASE . '/pages/', $httpArgs);
	
	// Check for WordPress error.
	if (is_wp_error($response)) {  
		$resultData['error_msg'] = $response->get_error_message();
	}
	
	else 
	{
		// Check for an error in the HTTP response code.
		if (200 != $response['response']['code']) {
			$resultData['error_msg'] = SPTK_api_getErrorFromBody($response);
		}
		
		// All ok, so use the JSON to get the data.
		else 
		{
			$resultData['success'] 	= true;
			$resultData['data'] 	= json_decode($response['body'], true);
		}
	}
	
	return $resultData;
}




/**
 * Adds the thanks parameter to the URL for the thanks page.
 * @param Array $qvars The list of query arguments WP knows about.
 * @return Array The list of query arguments.
 */
function SPTK_page_addThanksParameter($qvars)
{
	$qvars[] = 'thanks';
	return $qvars;
}


/**
 * Uses the API to get a specific page from the squeeze page toolkit.
 * 
 * @param Integer $squeezePageID The ID of the page to fetch.
 * @param Boolean $getThanksPage If true, get the thanks page.
 * @param String $localPagePath The local path of the page that's being rendered in WordPress
 * 
 * @return Array The result object, with the page data if found.
 */
function SPTK_api_getSqueezePage($squeezePageID, $getThanksPage, $localPagePath)
{
	$resultData = array(
		'error_msg'	=> '',
		'success'	=> false,
		'data'		=> false,
	);
	
	$httpArgs = array(
		'body'	=> array(
			'page_id'		=> $squeezePageID,							// The hash of the page ID.
			'page_source'	=> 'wordpress',								// Add WordPress for tracking
			'page_type'		=> ($getThanksPage ? 'thanks' : 'main'),	// Thanks or main page to show?
			'page_redirect' => $localPagePath, 							// Redirect back to WP for the thanks page.
			'apiKey' 		=> TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'remote_api_key')
		),
	);
	
	// Do the actual request now
	$response = wp_remote_post(SPTK_API_BASE . '/singlepage/', $httpArgs);
	
	// Check for WordPress error.
	if (is_wp_error($response)) {  
		$resultData['error_msg'] = $response->get_error_message();
	}
	
	else 
	{
		// Check for an error in the HTTP response code.
		if (200 != $response['response']['code']) {
			$resultData['error_msg'] = SPTK_api_getErrorFromBody($response);
		}
		
		// All ok, so use the JSON to get the data.
		else 
		{
			$resultData['success'] 	= true;
			$resultData['data'] 	= json_decode($response['body'], true);
		}
	}
	
	return $resultData;
}



/**
 * Uses the API to check that the credentials are correct.
 */
function SPTK_api_areCredentialsCorrect()
{
	$resultData = array(
		'error_msg'	=> '',
		'success'	=> false,
	);
	
	$httpArgs = array(
		'body'	=> array('apiKey' => TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'remote_api_key')),
	);
	
	// Do the actual request now
	$response = wp_remote_post(SPTK_API_BASE . '/ping/', $httpArgs);
	
	// Check for WordPress error.
	if (is_wp_error($response)) {  
		$resultData['error_msg'] = $response->get_error_message();
	}
	
	else 
	{
		// Check for an error in the HTTP response code.
		if (200 != $response['response']['code']) {
			$resultData['error_msg'] = SPTK_api_getErrorFromBody($response);
		}
		
		// All ok, so use the JSON to get the data.
		else  {
			$resultData['success'] 	= true;
		}
	}
	
	return $resultData;
}


/**
 * Extracts the error message from JSON if it's in the error body.
 * 
 * @param Object $response The raw HTTP response from SPTK.
 * @return The extracted message from JSON or HTTP.
 */
function SPTK_api_getErrorFromBody($response)
{
	$errorMessage = false;
	
	// Use detailed error from API if there is one.
	if ($response['body'])
	{
		// Decode JSON and use the error message, which is usually very specific.
		$decodedBody = json_decode($response['body']);
		if (json_last_error() == JSON_ERROR_NONE) {
			$errorMessage = $decodedBody->error; 		
		}
	}
	
	// Ensure we have some kind of error message if there's nothing provided in the JSON body.
	if (!$errorMessage) {			
		$errorMessage = $response['response']['code'] . ' ' . $response['response']['message'];
	}
	
	return $errorMessage;
}



/**
 * Function that handles showing the SPTK page, rather than a template page.
 */
function SPTK_templates_templatePageOverride()
{
	global $post;
	
    // 1) See if there's a homepage override
    if (is_front_page())
    {    	
    	// See if we have a page to use from the toolkit.
		$homepageID = TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'homepage_squeeze', false);
		if ($homepageID)
		{
			// Use the homepage URL.
			$localPagePermalink = home_url('/');
			
			SPTK_page_getPageViaAPI($homepageID, $localPagePermalink, '_homepage_squeeze');
			exit;
		}
    }
     
	// 2) Normal Squeeze Page 
	else if ($post->post_type == 'squeeze_page')
	{
		// Need ID from meta to determine which page to use
		$selectedPage = trim(get_post_meta($post->ID, '_sptk_associated_page_id', true));
		
		// Get the full page of the page to use for the opengraph data.		 
		$localPagePermalink = get_permalink($post);
		
		// Ensure that the page contains something, as a quick sanity check
		if (!$selectedPage) {
			printf('<p>%s</p>', __('Oops, you need to select a squeeze page first.', 'sptk_for_wp'));
		}
		
		SPTK_page_getPageViaAPI($selectedPage, $localPagePermalink);
		
		exit;
     }
}


/**
 * Attempt to fetch a page using the API, using the local URL to replace any tracking code.
 * 
 * @param String $selectedPage The page ID to use (direct from the API)
 * @param String $localPagePermalink The URL to use in tracking for this page.
 * @param String $transientKeySuffix The string to use for the transient for caching the page.
 */
function SPTK_page_getPageViaAPI($selectedPage, $localPagePermalink, $transientKeySuffix = false)
{
	// Create the ID we use for the transient.
	$pageCacheID = 'sptk_page_cache_'.$selectedPage . $transientKeySuffix;
	
	// Are we showing the thanks page?
	global $wp_query;
	$thanksMode = false;
	if ($wp_query->get('thanks') == 'yep')
	{
		// Change the cache ID to use the thanks page ID, so we can cache that.
    	$pageCacheID .= '_thanks'; 
    	
    	// We want the thanks page, not the landing page.
    	$thanksMode = true;
    } 				
	
	// See if we have any cached page stored, if so, use it.
	if (!SPTK_DEBUG_MODE && ($htmlCached = get_transient($pageCacheID)) !== false) {
		echo $htmlCached;
		exit;
	}
	
	// We have an ID, attempt to get it via the API.	
	$resultData = SPTK_api_getSqueezePage($selectedPage, $thanksMode, $localPagePermalink);
	if ($resultData['success'])
	{			
		// Fetch the page data 
		$html = $resultData['data']['html'];
		
		// Do the changes to the URL for open-graph that makes it use the right permalink.
		//<meta property="og:url" content="http://sptk.squeezepagetoolkit.com/free-tips"/>
		$html = preg_replace('%<meta property="og:url" content="[^>]+?"/>%', '<meta property="og:url" content="' . $localPagePermalink .'"/>', $html);
		
		// Cache the page results
		set_transient($pageCacheID, $html, 43200); // 12 hours
		
		// Show it
		echo $html;
	}
	
	// Page not found, so an error.
	else {
		printf('<p>%s</p>', __('Unfortunately this page could not be shown. ', 'sptk_for_wp'));
		printf('<p><b>%s</b> %s</p>', __('The reason why:', 'sptk_for_wp'), $resultData['error_msg']);
	}
}



/**
 * Function called when a new squeeze page has been saved, which
 * ensures that the rewrite rules are correct.
 * 
 * @param Integer $post_id The ID of the squeeze page.
 */
// 2013-10-15 - Disabled - as appears to a really buggy way of handling the URL detection
/*
function SPTK_urlClean_postSave($post_id) 
{
	$post_type = get_post_type($post_id);

	// Skip any other post type. Just clear it for squeeze pages.
	if ('squeeze_page' == $post_type) 
	{
		SPTK_urlClean_rewriteRules(true); 				
		
		// Clear this page cache when saving this post.
		$selectedPage = trim(get_post_meta($post_id, '_sptk_associated_page_id', true));
		if ($selectedPage) {
			delete_transient('sptk_page_cache_'.$selectedPage); 
		}
	}
}*/


/**
 * Called when a new squeeze page has been saved, which updates all of the squeeze pages
 * to use redirect without using the squeeze page slug.
 * 
 * @param Boolean $flash If true, force a refresh of the rewrite rules.
 */
// 2013-10-15 - Disabled - as appears to a really buggy way of handling the URL detection
/*
function SPTK_urlClean_rewriteRules($flash = false)
{
	global $wpdb;
	$customPostSlug = 'squeeze_page';
	
	$querystr = "
		SELECT {$wpdb->posts}.post_name 
		FROM {$wpdb->posts} 
		WHERE {$wpdb->posts}.post_status = 'publish' 
		  AND {$wpdb->posts}.post_type = '{$customPostSlug}'
		  AND {$wpdb->posts}.post_date < NOW()
	";
	
	$posts = $wpdb->get_results($querystr, OBJECT);
	
	// Effectively creates a rewrite rule for each page that matches the squeeze page type.
	foreach ($posts as $post)
	{
		$regex = "{$post->post_name}\$";
		add_rewrite_rule($regex, "index.php?{$customPostSlug}={$post->post_name}", 'top');			
	}
	
	// If true, then flush the WP rewrite rules.
	if ($flash == true) {
		flush_rewrite_rules(false);
	}
}*/
	


/**
 * Removes the 'squeeze_page' slug from the URL. 
 * 
 * @param String $permalink The original link to use.
 * @param Object $post The post object for this URL.
 * @param Boolean type $leavename If true, don't modify the name.
 * 
 * @return String The modified permalink for squeeze pages.
 */
function SPTK_urlClean_removeSlug($permalink, $post, $leavename)
{
	if ( 'squeeze_page' != $post->post_type || 'publish' != $post->post_status ) {
        return $permalink;
    }
 
    $permalink = str_replace( '/' . $post->post_type . '/', '/', $permalink );
 
    return $permalink;
}





/**
 * Simple debug function to echo a variable to the page.
 * @param Array $showvar The variable to echo.
 * @param Boolean $return If true, then return the information rather than echo it.
 * @return String The HTML to render the array as debug output.  
 */
function SPTK_debug_showArray($showvar, $return = false)
{
	$html = "<pre style=\"background: #FFF; margin: 10px; padding: 10px; border: 2px solid grey; clear: both; display: block;\">";
	$html .= print_r($showvar, true);
	$html .= "</pre>";
 
	if (!$return) {
		echo $html;
	}
	return $html;
}


/**
 * Safe method to get the value from an array using the specified key.
 * @param Array $array The array to search.
 * @param String $key The key to use to index the array.
 * @param Mixed $returnDefault Return this value if the value is not found.
 * @return String The array value.
 */
function SPTK_arrays_getValue($array, $key, $returnDefault = false)
{
	if ($array && isset($array[$key])) {
		return $array[$key];
	}
	
	return $returnDefault;
}


?>