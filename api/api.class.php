<?php

/**
 * Abstract class for the API. 
 * 
 * Based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 *  
 * @version 1.0.0
 */
class SPTK4WP_API
{
	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method = '';

	/**
	 * Property: endpoint
	 * The Model requested in the URI. eg: /files
	 */
	protected $endpoint = '';

	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /files/process
	 */
	protected $verb = '';

	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = Array();

	/**
	 * Property: file
	 * Stores the input of the PUT request
	 */
	protected $file = Null;

	/**
	 * Constructor: __construct
	 * Allow for CORS, assemble and pre-process the data
	 */
	public function __construct($request)
	{
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");

		$this->args = explode('/', rtrim($request, '/'));
		$this->endpoint = array_shift($this->args);
		if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
			$this->verb = array_shift($this->args);
		}

		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new Exception("Unexpected Header");
			}
		}

		switch ($this->method)
		{
			// Currently only allowing GET methods
			 case 'GET':
				$this->request = $this->_cleanInputs($_GET);
			 break;
			
			/*
			  case 'POST':
				$this->request = $this->_cleanInputs($_POST);
				break;
			  
			  case 'PUT':
				$this->request = $this->_cleanInputs($_GET);
				$this->file = file_get_contents("php://input");
				break;
				
			case 'DELETE':
				$this->request = $this->_cleanInputs($_POST);
				break;*/
				
			default:
				echo $this->_response(array('error' => 'Invalid Method'), 405);
				exit();
				break;
		}
	}

	/**
	 * Try to process the error, returning a 400 error if the method does not exist.
	 */
	public function processAPI() 
	{
		// Check endpoint exists as a method, and doesn't start with an underscore.
		if (method_exists($this, $this->endpoint) == TRUE && substr($this->endpoint, 0, 1) != '_') {
			return $this->_response($this->{$this->endpoint}($this->args));
		}
		return $this->_response(array('error' => 'Method does not exist.'), 400);
	}

	/**
	 * Encode the response as JSON with an appropriate status code.
	 * 
	 * @param Mixed $data The data to return.
	 * @param Integer $status The HTTP status code.
	 * return String JSON encoded data.
	 */
	protected function _response($data, $status = 200)
	{
		header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
		return json_encode($data);
	}

	
	/**
	 * Cleans incoming data for any stuff
	 * @param Mixed $data The data to clean.
	 * @return Mixed The cleaned data.
	 */
	protected function _cleanInputs($data)
	{
		$clean_input = Array();
		if (is_array($data)) 
		{
			foreach ($data as $k => $v) {
				$clean_input[$k] = $this->_cleanInputs($v);
			}
		} 
		
		// Remove whitespace and tags
		else {
			$clean_input = trim(strip_tags($data));
		}
		return $clean_input;
	}

	
	
	/**
	 * Return the appropriate HTTP error code message for the numerical code.
	 * @param Integer $code The code to return
	 * @return String The message associated with the specified HTTP error code.
	 */
	protected function _requestStatus($code)
	{
		$status = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported'
		);
		return ($status[$code])?$status[$code]:$status[500];
	}
	
	
	/**
	 * Check if a method supports a request method, if not, an error is thrown. 
	 * @param Array $methodsAccepted The list of methods to check for. 
	 */
	protected function onlyAllowRequestMethod($methodsAccepted)
     {
     	$errorMessage = sprintf("This method does not support %s requests.", $this->method);
     	
     	if (!in_array($this->method, $methodsAccepted)) {
     		echo $this->_response(array('error' => $errorMessage), 405);
     		exit;
     	}
     }
     
     
     /**
      * Method that completely flushes the cache for the pages, when called back from
      * SPTK server once the page cache has been changed.
      * 
      * e.g. 
      * http://example.com/wp-content/plugins/squeeze-page-toolkit/api/?request=flushcache_single&page_id=ZAopxnVRdO
      */
     public function flushcache_single()
     {
     	// Need a page ID to work
		if (!array_key_exists('page_id', $this->request)) {
        	throw new Exception('Page ID is missing.');
        } 
        
        // Validate that the page ID is ok
		else if (!preg_match('/^[a-zA-Z0-9]{6,10}$/', $this->request['page_id'], $matches)) {
        	throw new Exception('Page ID is invalid');
        }
     	
     	// Flush cache for specific page
		SPTK_cache_clearPageData_byHash($this->request['page_id']);
     	return array('result' => 'success');
     }
}
?>