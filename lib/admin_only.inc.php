<?php


 
// Not used currently, kept here for future implementation.
/**
 * Add new columns (and labels) to the squeeze page meta data for the page summary.
 * @param Array $listing_columns The column data being shown.
 * @return Array The modified columns to render. 
 */
/*
function SPTK_admin_table_addPageMetadata_headings($listing_columns)
{
	// Move the date to the end.
	$dateField = $listing_columns['date'];
	unset($listing_columns['date']);
	
	// Stores the type - normal or split test.
    $listing_columns['sptk_sp_cache_status'] = __('Cached?', 'sptk_for_wp');
    
    // Add date back in
    $listing_columns['date'] = $dateField;

    return $listing_columns;
}
*/


/**
 * Handle showing the meta data for each squeeze page.
 * 
 * @param String $column_name The name of the column to modify.
 * @param Integer $post_id The ID of the post being rendered.
 */
/*function SPTK_admin_table_addPageMetadata_content($column_name, $post_id) 
{
    global $wpdb;
    switch ($column_name)
    {
    	// Shows if a page is currently being cached or not.
	    case 'sptk_sp_cache_status':
	    	
	    	// Get the associated pages first
	    	$associatedPage = get_post_meta($post_id, '_sptk_associated_page_id', true);
	    	$pageList = SPTK_cache_getAllPagesForCacheID('sptk_page_cache_' . $associatedPage);
	    	
	    	// Page List should be full if so.
	    	if (!empty($pageList))
	    	{
	    		_e('Cached', 'sptk_for_wp');
	    	}
	    	
	    	// Not cached
	    	else {
	    		echo '-';
	    	}
	    	
		break;
		
    } // end switch
}  */



/**
 * Function that attaches the squeeze page selection panels to WordPress.
 */
function SPTK_meta_attachSqueezePageSelection()
{
    $screens = array('');

	add_meta_box('sptk_meta_squeeze_page_selection', __('Squeeze Page Toolkit - Associated Squeeze Page', 'sptk_for_wp'), 'SPTK_meta_createSelectionBox', 'squeeze_page', 'normal', 'high');
}


/**
 * Actually creates the form where a squeeze page can be selected.
 * @param Object $post The post being edited and saved.
 */
function SPTK_meta_createSelectionBox($post)
{
	// Get the existing selection
	$selectedPage = get_post_meta($post->ID, '_sptk_associated_page_id', true);
	
	// Get list of pages you can select
	$pageFetchInfo = SPTK_api_getListOfSqueezePages();
	
	printf('<p>%s</p>', __('This automatically fetches the squeeze pages from the <b>Squeeze Page Toolkit</b>, which you can use to show on this page.', 'sptk_for_wp'));
	
	// Something went wrong, so need to show an error message.
	if (!$pageFetchInfo['success'])
	{
		printf('<div class="sptk_msg sptk_msg_error">%s<br/><br/><b>%s</b>: %s</div>', 
			__('<b>Error!</b> WordPress was <b>not able</b> to fetch your squeeze pages from the Squeeze Page Toolkit.', 'sptk_for_wp'),
			__('Reason Why', 'sptk_for_wp'),
			$pageFetchInfo['error_msg']
		);	
	}
	
	// Only add choices if there are any.
	$pagesToChooseFrom = array('' => __('-- Choose a page ---'));
	if ($pageFetchInfo['data'] && is_array($pageFetchInfo['data'])) {
		$pagesToChooseFrom += $pageFetchInfo['data']; 
	}	
	
	// Page Selection
	printf('<div class="sptk_forms_control">');
		printf('<label for="sptk_associated_page_id">%s</label>&nbsp;&nbsp;&nbsp;', __('Squeeze page to show:', 'sptk_for_wp'));
		echo SPTK_forms_createDropdown('sptk_associated_page_id', $pagesToChooseFrom, $selectedPage, 'sptk_forms_associated_page_id');
		
		// Control to refresh the list of pages via AJAX
		//printf('<a href="#" class="button-secondary">%s</a>', __('Refresh List', 'squeeze_page'));
	printf('</div>');
	
	
	// Tip
	printf('<p><b>%s:</b> %s</p>', 
		__('Quick tip', 'sptk_for_wp'),
		__('If you make changes on your page on the Squeeze Page Toolkit, you can ensure WordPress <b>shows the latest changes</b> by resaving this page by clicking on the \'<b>Update</b>\' on the right.', 'sptk_for_wp')
	);
}


/**
 * When the post is saved, saves our page selection.
 * @param Integer $post_id The ID of the post being saved.
 */
function SPTK_meta_saveSqueezePageSelection($post_id) 
{
	$postType = get_post_type($post_id);
	if ('squeeze_page' != $postType) {
		return;
	}
	
	// No associated page, abort.
	if (!isset($_POST['sptk_associated_page_id'])) {
		return;
	}
	
	// Sanitize user input.
	$pageSelection = sanitize_text_field($_POST['sptk_associated_page_id']);

	// Update the meta field in the database.
	update_post_meta($post_id, '_sptk_associated_page_id', $pageSelection);
	
	// Remove the cached page data when saving the page.
	SPTK_cache_clearPageData('sptk_page_cache_'.$pageSelection); 
	SPTK_cache_clearPageData('sptk_page_cache_'.$pageSelection.'_thanks'); // The associated thanks page.
}


/**
 * Shows the settings page for the plugin.
 */
function SPTK_showPage_Settings()
{
	$page = new PageBuilder(true);
	$page->showPageHeader(__('Squeeze Page Toolkit for WordPress - Settings', 'sptk_for_wp'));
	
	
	// Check for cache cleaning.
	if (isset($_GET['clear_cache']) && 'true' == $_GET['clear_cache'])
	{
		SPTK_cache_clearCacheAllPages();
		$page->showMessage(__('Squeeze page cache has been cleared.', 'sptk_for_wp'));
	}
	
	
	
	$page->openPane('sptk_for_wp_settings', __('Your Settings', 'sptk_for_wp'));
	
	$settingsFields = array(
		'remote_api_key' => array(
				'label' 	=> __('Your Access Key', 'sptk_for_wp'),
				'required'	=> true,
				'type'  	=> 'text',
				'cssclass'	=> 'sptk_form_input_medium',
				'desc'  	=> '&bull; ' . __('Your access key for the <b>Squeeze Page Toolkit</b>, which will allow WordPress to fetch your squeeze pages. You can find this within in your Squeeze Page Toolkit account by clicking on <b>your name</b> and then -> <b>SPTK for WordPress</b>.', 'sptk_for_wp') .  
							   '<br/>&bull; ' . __('An example of your access key is: <code>1ba12bbb34c3a3cee4b28d7839006e35</code>', 'sptk_for_wp'), 
				'validate'	 	=> array(
					'type'		=> 'string',
					'maxlen'	=> 32,
					'minlen'	=> 32,
					'regexp'	=> '/^[A-Za-z0-9]+$/',
					'error'		=> __('Please enter your 32 character access key, which contains only letters and numbers.', 'sptk_for_wp'),
				)	
			), 		
			
		'homepage_squeeze' => array(
				'label' 	=> __('Use page for for your homepage', 'sptk_for_wp'),
				'required'	=> false,
				'type'  	=> 'select',
				'cssclass'	=> 'sptk_form_select',
				'desc'  	=> '&bull; ' . __('If you would like to use any of your pages in the <b>Squeeze Page Toolkit</b> to replace your current home page, you can select it here.', 'sptk_for_wp') .  
								'<br/>&bull; ' . __('Or you can select <b>Use normal WordPress page</b> to disable using a toolkit page.', 'sptk_for_wp'), 
				'data'		=> SPTK_page_getListOfPagesForHomepageSelect($page),	
			)
			
	);
	
	// ### Page Settings - Show the form
	$settings = new SettingsForm($settingsFields, SPTK_DATABASE_SETTINGS_KEY, 'sptk_form_settings');
	
	// Form event handlers - processes the saved settings in some way 
	$settings->afterSaveFunction = 'SPTK_showPage_Settings_afterSave';		
	
	$settings->show();
	
	
	// ### Cache Clean - Show the section to clear the page cache.
	$page->openPane('sptk_for_wp_settings_cache', __('Squeeze Page Cache', 'sptk_for_wp'));
	printf('<p>%s</p>', __('To ensure your squeeze pages load as quickly as possible, they are cached on the same server as your WordPress website. If you need to clean out that cache for any reason (such as your pages are not refreshing), then just click on the button below.', 'sptk_for_wp'));
	
	printf('<p><a href="%s" class="button-primary">%s</a></p>',
		admin_url('edit.php?post_type=squeeze_page&page=SPTK_showPage_Settings&clear_cache=true'), 
		__('Clear Squeeze Page Cache', 'sptk_for_wp')
	);
	 
	
	$page->showPageFooter();
}

/**
 * Generates the list of pages to select from for the homepage selection.
 * @param PageBuilder The current page object
 * @return Array The list of pages the user can select, plus the null option.
 */
function SPTK_page_getListOfPagesForHomepageSelect($page)
{
	// Get list of pages you can select
	$pageFetchInfo = SPTK_api_getListOfSqueezePages();
	
	// Something went wrong, so need to show an error message.
	if (!$pageFetchInfo['success'])
	{
		$page->showMessage(
			sprintf('%s<br/><br/><b>%s</b>: %s', 
				__('<b>Error!</b> WordPress was <b>not able</b> to fetch your squeeze pages from the Squeeze Page Toolkit.', 'sptk_for_wp'),
				__('Reason Why', 'sptk_for_wp'),
				$pageFetchInfo['error_msg']
			), true);	
	}
	
	// Only add choices if there are any.
	$pagesToChooseFrom = array('' => __('-- Use normal WordPress page ---', 'sptk_for_wp'));
	if ($pageFetchInfo['data'] && is_array($pageFetchInfo['data'])) {
		$pagesToChooseFrom += $pageFetchInfo['data']; 
	}	
	
	return $pagesToChooseFrom;
}


/**
 * Function that processes the data after the settings have been saved.
 * Use this to check that the API is working ok.
 * 
 * @param Array $formValues The list of values that have been saved  
 */
function SPTK_showPage_Settings_afterSave($formValues)
{
	$resultData = SPTK_api_areCredentialsCorrect();
	
	// Verification happened fine
	if ($resultData['success']) {
		printf('<div class="sptk_msg sptk_msg_ok">%s</div>', __('<b>Success!</b> WordPress was able to talk to the Squeeze Page Toolkit.', 'sptk_for_wp'));
	}
	
	// Something went wrong.
	else { 
		printf('<div class="sptk_msg sptk_msg_error">%s<br/><br/><b>%s</b>: %s</div>', 
			__('<b>Error!</b> WordPress was <b>not able</b> to talk to the Squeeze Page Toolkit.', 'sptk_for_wp'),
			__('Reason Why', 'sptk_for_wp'),
			$resultData['error_msg']
		);
	}
	
	// Flush the cache for the selected home page if there is one.
	if ($pageSelection = SPTK_arrays_getValue($formValues, 'homepage_squeeze', false))
	{
		// Homepage cache name have a slightly different name to standard pages.
		SPTK_cache_clearPageData('sptk_page_cache_' . $pageSelection . '_homepage_squeeze'); 
		SPTK_cache_clearPageData('sptk_page_cache_' . $pageSelection . '_thanks_homepage_squeeze'); // The associated thanks page.

	}
}


?>