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
		'body'		=> array('apiKey' => TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'remote_api_key')),
		'timeout'	=> 15, // 15 seconds
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
			// The hash of the page ID. - the page being fetched.
			'page_id'			=> $squeezePageID,							
	
			// Add WordPress for tracking - added to meta for statistics
			'page_source'		=> 'wordpress',								
	
			// Thanks or main page to show?
			'page_type'			=> ($getThanksPage ? 'thanks' : 'main'),	
	
			// URL to redirect back to WP for the thanks page.
			'page_redirect' 	=> $localPagePath, 								
	
			// API key for access to an account
			'apiKey' 			=> TidySettings_getSettingSingle(SPTK_DATABASE_SETTINGS_KEY, 'remote_api_key'),	
	
			// Site URL - needed for dynamic cache flushing from SPTK when pages are updated.
			'page_host'			=> home_url('/'), 
		),
	);
	
	// Do the actual request now
	$response = wp_remote_post(SPTK_API_BASE . '/single_page_details/', $httpArgs);
	
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
	
	// See if there is a post object to override.
	if ($post)
	{	
	    // 1) See if there's a homepage override.
	    // If there's a parameter of 'thanks=yep', then is_front_page() returns false for some odd reason.
	    // So we check for the thanks parameter to determine if this is really the thanks page for home.
	    if (is_front_page() || (is_home() && isset($_GET['thanks'])))
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
	$pageList = SPTK_cache_getAllPagesForCacheID($pageCacheID);
	if (!empty($pageList)) {
		echo SPTK_page_returnRandomPage_fromPageCacheList($pageList, $pageCacheID);
		exit;
	}
	
	// We have an ID, attempt to get it via the API.	
	$resultData = SPTK_api_getSqueezePage($selectedPage, $thanksMode, $localPagePermalink);
	if ($resultData['success'])
	{			
		// Clear cache for all variations of this particular page, before we add the cached
		// versions.
		SPTK_cache_clearPageData($pageCacheID);
		
		// Data for each page is stored in page_0, page_1, etc.
		// If there is just 1 page, then just page_0 is returned
		$pageIndex = 0;
		while (isset($resultData['data']['page_' . $pageIndex]))
		{
			$dataIndex = 'page_' . $pageIndex;
			
			// Get this particular page of data
			$html = $resultData['data'][$dataIndex];
			
			// Get the SPTK Hash of the page. Make the hash unique for this website. It's assumed
			// that the hash is valid and being returned, hence no error checking here.
			$pageHash = $resultData['data'][$dataIndex . '_hash'];
			$pageHash = md5(home_url('/') . $dataIndex . $pageHash);
			
			// Do the changes to the URL for open-graph that makes it use the right permalink.
			//<meta property="og:url" content="http://sptk.squeezepagetoolkit.com/free-tips"/>
			$html = preg_replace('%<meta property="og:url" content="[^>]+?"/>%', '<meta property="og:url" content="' . $localPagePermalink .'"/>', $html); 

			// Add this page to the cache
			SPTK_cache_addPageData($pageCacheID, $dataIndex, $html, $pageHash);
			
			// Try next page
			$pageIndex++;
		}
		
		$pageList = SPTK_cache_getAllPagesForCacheID($pageCacheID);
		if (!empty($pageList)) {
			echo SPTK_page_returnRandomPage_fromPageCacheList($pageList, $pageCacheID);
			exit;
		} 
		
		// Just in case.
		else {
			$resultData['error_msg'] = __('Could not fetch the pages from the cache.', 'sptk_for_wp');	
		}
	}
	
	// Page not found, so an error. If we got here, then 
	printf('<p>%s</p>', __('Unfortunately this page could not be shown. ', 'sptk_for_wp'));
	printf('<p><b>%s</b> %s</p>', __('The reason why:', 'sptk_for_wp'), $resultData['error_msg']);
}


/**
 * Assuming we have an array of page cache objects, we should just be able to choose
 * a random item from the list.
 * 
 * @param $pageList Array The list of pages from the cache to choose from.
 * @param $selectedPageID String The ID of the page that we're showing this for. 
 * 
 * @return String The HTML for the random page.
 */
function SPTK_page_returnRandomPage_fromPageCacheList($pageList, $selectedPageID)
{
	if (empty($pageList)) {
		return __('Could not load page data.', 'sptk_for_wp');
	}
	
	// There's only 1 item in the list, so return it.
	if (1 == count($pageList))
	{
		return $pageList[0]->page_html;
	}
	
	// Got more than 1 choice, so we need to check for a cookie to see if we're using
	// a specific page.
	else 
	{
		// Stores the ID within $pageList of the item to use for the variation.
		// This is a 0-index array, so invalid value is less than 0.
		$pageListIndexToUse = -1;
		
		// Create a unique parameter for the cookie that uses the selected page ID
		// i.e. the controling page/url we're showing a split test variation for.
		$variationCookieName = 'sptk_ab_var_' . md5($selectedPageID);
		
		// Try to get the value stored in the cookie - to see if it matches one of the
		// variations in our list.
		$variationHashToUse_unchecked = false;
		if (isset($_COOKIE[$variationCookieName]) && $variationHashToUse_unchecked = $_COOKIE[$variationCookieName])
		{
			// Assume that the data may be dirty, hence the regex
			if (preg_match('/^[a-zA-Z0-9]{32}$/', $variationHashToUse_unchecked))
			{
				// Try to find this hash inside the list of pages.
				foreach ($pageList as $variation)
				{
					// Found the variation locked through the cookie. Stop here.
					if ($variation->page_hash == $variationHashToUse_unchecked) {
						return $variation->page_html;
					}
				}
			}
		}
		
		// Got here, so we've not already locked the variation. So lock it and return a random selection.
		// Use PHP randomisation to find a page and then return it.
		$indexOfChoice = array_rand($pageList, 1);
		
		// Locked in a cookie - time delay of 1 year.
		setcookie($variationCookieName, $pageList[$indexOfChoice]->page_hash, time()+31556926, '/');
		
		// Finally return the HTML.
		return $pageList[$indexOfChoice]->page_html;
	}
}


/**
 * Remove the page cache for the specified cached page ID (this will be for an actual page, or a thank you page).
 * 
 * @param String $pageCacheID The ID of the page in the cache.
 */
function SPTK_cache_clearPageData($pageCacheID)
{
	global $wpdb, $sptkdb;
	$wpdb->show_errors();
	
	// Code assumes that there are no prior entries for this page before insert
	// simply because we clear this cache before calling this function
	$wpdb->query($wpdb->prepare("
		DELETE FROM $sptkdb->page_cache
		WHERE page_cache_id = %s
	", $pageCacheID
	));
}


/**
 * Remove the page cache for all pages that use part of an ID, usually just the hashed page ID.
 * 
 * @param String $pageHash The hash of the page in the cache.
 */
function SPTK_cache_clearPageData_byHash($pageHash)
{
	global $wpdb, $sptkdb;
	$wpdb->show_errors();
	
	// Code assumes that there are no prior entries for this page before insert
	// simply because we clear this cache before calling this function
	$wpdb->query($wpdb->prepare("
		DELETE FROM $sptkdb->page_cache
		WHERE page_cache_id LIKE %s
	", '%%_' . $pageHash . '%%'
	));
}

/**
 * Gets all of the cached HTML data for pages that match the specified page ID.
 * 
 * @param String $pageCacheID The ID of the page in the cache to get.
 */
function SPTK_cache_getAllPagesForCacheID($pageCacheID)
{
	global $wpdb, $sptkdb;
	$wpdb->show_errors();
	
	// Get the cache expire after 7 days?
	/*$wpdb->query($wpdb->prepare("
		DELETE FROM $sptkdb->page_cache
		WHERE page_cached_date < %s
	", date('Y-m-d H:i:s', current_time('timestamp') - (7*24*60*60))
	));*/
	
	// Code assumes that there are no prior entries for this page before insert
	// simply because we clear this cache before calling this function
	return $wpdb->get_results($wpdb->prepare("
		SELECT * 
		FROM $sptkdb->page_cache
		WHERE page_cache_id = %s
	", $pageCacheID
	));
}




/**
 * Add the specified page to the cache.
 * 
 * @param String $pageCacheID The ID of the page in the cache.
 * @param String $dataIndex The index of the page when showing multiple pages.
 * @param String $html The HTML for this specific page.
 * @param String $pageHash The page ID hash for the cache.
 */
function SPTK_cache_addPageData($pageCacheID, $dataIndex, $html, $pageHash)
{
	global $wpdb, $sptkdb;
	$wpdb->show_errors();
	
	// Code assumes that there are no prior entries for this page before insert
	// simply because we clear this cache before calling this function
	$wpdb->query($wpdb->prepare("
		INSERT INTO $sptkdb->page_cache
		(page_cache_id, page_index, page_html, page_cached_date, page_hash) 
		VALUES (%s, %s, %s, %s, %s)
	", $pageCacheID, $dataIndex, $html, current_time('mysql'), $pageHash
	));
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



/**
 * Function that can be used to clear all page caches.
 */
function SPTK_cache_clearCacheAllPages()
{
	global $wpdb, $sptkdb;
	$wpdb->show_errors();
	
	// Just delete all of the caches - very simple.
	$wpdb->query("TRUNCATE TABLE $sptkdb->page_cache");
}


?>