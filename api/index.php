<?php

// We want to see errors during debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
  
/**
 * We need to use some WordPress functions. This includes the theme files, all of active plugins, etc. BUT loading WordPress 
 * in this way doesn't parse the requested URL and doesn't run the WordPress query. So there is a slightly reduce load on the
 * website by using this method.
 */
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once($parse_uri[0] . 'wp-load.php');


// Include the API handler class
include_once('api.class.php');
 
 
// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

// Expecting URL requests like this:
// http://example.com/wp-content/plugins/squeeze-page-toolkit/api/?request=flushcache

// Check we have a request variable. If we don't, then there's an error.
if (!isset($_REQUEST['request'])) 
{
	header("HTTP/1.1 400 Bad Request");
    echo json_encode(Array('error' => $e->getMessage()));
}

// Instantiate the API
try {
    $API = new SPTK4WP_API($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} 


catch (Exception $e) {
	header("HTTP/1.1 400 Bad Request");
    echo json_encode(Array('error' => $e->getMessage()));
}



?>