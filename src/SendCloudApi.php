<?php
if (!function_exists('json_last_error_msg')) {
	function json_last_error_msg() {
		static $ERRORS = array(
			JSON_ERROR_NONE => 'No error',
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
			JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error',
			JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);

		$error = json_last_error();
		return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
	}
}

/**
 * @version 2.1.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApi
{
	const HOST_URL = 'https://panel.sendcloud.sc/api/v2/';

	/*
		API information
		API Key: Your public api key
		API Secret: Your secret api key
		Partner uuid: Your SendCloud partner uuid
	*/
	protected $api_key;
	protected $api_secret;
	protected $partner_uuid;

	/**
	 * @var SendCloudApiParcelResource
	 */
	public $parcels;

	/**
	 * @var SendCloudApiParcelStatusResource
	 */
	public $parcel_statuses;

	/**
	 * @var SendCloudApiShippingResource
	 */
	public $shipping_methods;

	/**
	 * @var SendCloudApiUserResource
	 */
	public $user;

	/**
	 * @var SendCloudApiLabelResource
	 */
	public $label;

	/**
	 * @var SendCloudInvoicesResource
	 */
	public $invoices;

	/**
	 * @var SendCloudSenderAddressResource
	 */
	public $sender_addresses;

	protected $apiUrl;


	/**
	 * Making sure the right construct function gets called
	 */
	function __construct()
	{
		$a = func_get_args();
		$i = func_num_args();
		if (method_exists($this,$f='__construct'.$i)) {
			call_user_func_array(array($this,$f),$a);
		}
	}

	/**
	 * Wrapper constructor
	 * @param string $api_key
	 * @param string $api_secret
	 * @return void
	 */
	function __construct2($api_key, $api_secret) {
		$this->setApiKeys($api_key, $api_secret);
		$this->apiUrl = self::HOST_URL;
		$this->_setupResources();
	}

	/**
	 * Partner wrapper contructor
	 * @param string $api_key
	 * @param string $api_secret
	 * @param string $partner_uuid
	 * @return void
	 */
	function __construct3($api_key, $api_secret, $partner_uuid) {
		$this->__construct2($api_key, $api_secret);
		$this->partner_uuid = $partner_uuid;
	}

	public function setApiUrl($url) {
		$this->apiUrl = $url;
	}

	/**
	 * Sets the API-Key and API-Secret
	 * @param string $api_key
	 * @param string $api_secret
	 * @return void
	 * @throws SendCloudApiException Exception thrown if one of the arguments are not passed
	 */
	function setApiKeys($api_key = false, $api_secret = false) {
		if ($api_key || $api_secret) {
			$this->api_key = $api_key;
			$this->api_secret = $api_secret;
		} else {
			throw new SendCloudApiException('You must have an API key and an API secret key');
		}
	}

	/**
	 * Internal method that initializes Objects
	 * @return void
	 */
	private function _setupResources() {
		$this->parcels = new SendCloudApiParcelsResource($this);
		$this->parcel_statuses = new SendCloudApiParcelStatusResource($this);
		$this->shipping_methods = new SendCloudApiShippingResource($this);
		$this->user = new SendCloudApiUserResource($this);
		$this->label = new SendCloudApiLabelResource($this);
		$this->invoices = new SendCloudApiInvoicesResource($this);
		$this->sender_addresses = new SendCloudApiSenderAddressResource($this);
	}

	/**
	 * Returns API-Key
	 * @return string
	 */
	function getApiKey() {
		return $this->api_key;
	}

	/**
	 * Returns API-Secret
	 * @return string
	 */
	function getApiSecret() {
		return $this->api_secret;
	}

	/**
	 * Creates an object
	 * @param string $url
	 * @param array $post
	 * @param array $return_object
	 * @return object
	 */
	public function create($url, $post, $return_object) {
		return $this->sendRequest($url, 'post', $post, $return_object);
	}

	/**
	 * Gets one or more objects
	 * @param string $url
	 * @param array $params
	 * @param array $return_object
	 * @return object
	 */
	public function get($url, $params, $return_object) {
		return $this->sendRequest($url, 'get', $params, $return_object);
	}

	/**
	 * Updates an object
	 * @param string $url
	 * @param array $params
	 * @param array $return_object
	 * @return object
	 */
	public function update($url, $params, $return_object) {
		return $this->sendRequest($url, 'put', $params, $return_object);
	}

	/**
	 * Updates an object
	 * @param string $url
	 * @return object
	 */
	public function cancel_parcel($url) {
		return $this->sendRequest($url, 'post', array(), '');
	}


	/**
	 * Generates the url that can be used for the call
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	public function getUrl($url, $params = null) {
		$api_url		= $this->apiUrl;
		$api_parsed		= parse_url($api_url);
		$resource_url	= parse_url($url);
		// When the port is in the parsed url, add the port.
		$port = (isset($api_parsed['port']) ? ":".$api_parsed['port'] : "");
		$apiUrl = $api_parsed['scheme'].'://'.$this->getApiKey().':'.$this->getApiSecret().'@'.$api_parsed['host'].$port.'/';

		if(isset($api_parsed['path']) && strlen(trim($api_parsed['path'], '/'))) {
			$apiUrl .= trim($api_parsed['path'], '/').'/';
		}

		$apiUrl .= $resource_url['path'];
		if(isset($resource_url['query'])) {
			$apiUrl .= '?'.$resource_url['query'];
		} elseif($params && is_array($params)) {
			$queryParameters = array();

			foreach($params as $key => $value) {
				if(!is_array($value)) {
					$queryParameters[] = $key.'='.urlencode($value);
				}
			}

			$queryParameters = implode('&', $queryParameters);

			if(!empty($queryParameters)) {
				$apiUrl .= '?'.$queryParameters;
			}
		}

		return $apiUrl;
	}

	/**
	 * Sends the API Request to SendCloud and returns the response body
	 * @param string $url
	 * @param string $method
	 * @param object $object
	 * @param object $return_object
	 * @return object
	 * @throws SendCloudApiException Exception thrown if $object isn't an object
	 * @throws SendCloudApiException Exception thrown if server returns an error
	 */
	public function sendRequest($url, $method, $object, $return_object) {
		$curl_options = array();
		if ($method == 'post' || $method == 'put') {
			$headers = array('Content-Type: application/json');

			if (!empty($this->partner_uuid)) {
				$headers[] = 'Sendcloud-Partner-Id: ' . $this->partner_uuid;
			}

			$curl_options = array(
				CURLOPT_URL				=> $this->getUrl($url),
				CURLOPT_HTTPHEADER		=> $headers,
				CURLOPT_CUSTOMREQUEST	=> strtoupper($method),
				CURLOPT_POSTFIELDS		=> json_encode($object),
			);
		} else {
			// The else. It's probally an get request
			$curl_options = array(
				CURLOPT_URL				=> $this->getUrl($url, $object),
			);
		}

		$curl_options += array(
			CURLOPT_HEADER				=> false,
			CURLOPT_RETURNTRANSFER		=> true,
			CURLOPT_SSL_VERIFYPEER		=> true,
			CURLOPT_SSLVERSION 			=> 1,
		);

		$curl_handler = curl_init();

		curl_setopt_array($curl_handler, $curl_options);

		$response_body	= curl_exec($curl_handler);
		$response_body	= json_decode($response_body, true);
		$response_code	= curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
		if(json_last_error() != JSON_ERROR_NONE) {
			throw new SendCloudApiException("Error parsing json: ".json_last_error_msg());
		}
		if(curl_errno($curl_handler)){
			throw new SendCloudApiException(curl_error($curl_handler));
		}
		curl_close($curl_handler);

		$different_error_structure = array_key_exists('status', $response_body) && array_key_exists('message', $response_body) && $response_code > 399;
		if (
			$response_code < 200 || 
			empty($response_body) || 
			$response_code > 299 || 
			array_key_exists('error', $response_body) || 
			$different_error_structure
		) {
			if ($different_error_structure) {
				$response_body = array('error' => $response_body);
			}
			$this->handleResponseError($response_code, $response_body);
		}

		if (!empty($return_object)) {
			$response_body = array_shift($response_body);
		}

		if (array_key_exists($return_object, $response_body)) {
			return $response_body[$return_object];
		} else {
			return $response_body;
		}
	}

	/**
	 * Internal method that is fired when the server returns an error
	 * @param int $response_code
	 * @param string $response_body
	 * @return void
	 * @throws SendCloudApiException Exception containing the response error received from the server
	 */
	private function handleResponseError($response_code, $response_body) {
		$error = $response_body['error'];

		if (!is_array($error) || !array_key_exists('message', $error)) {
			switch($response_code) {
				case 404:
					$message = 'Page not found.';
					break;
				default:
					$message = 'Unknown error';
			}
		} else {
			$message = $error['message'];
		}

		if (!is_array($error) || !array_key_exists('code', $error)) {
			$code = -99;
		} else {
			$code = $error['code'];
		}

		throw new SendCloudApiException($message, $code);
	}
}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiException extends Exception {
	public $message;
	public $code;

	function __construct($message, $code = false) {
		$this->message = $message;
		$this->code = $code;
	}

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
abstract class SendCloudApiAbstractResource {

	/**
	 * @var WebshopappApiClient
	 */
	protected $client;

	/**
	* Settings to other classes
	**/
	protected $create_request = true;
	protected $get_request = true;
	protected $update_request = true;
	protected $single_resource = '';
	protected $list_resource = '';
	protected $create_resource = '';
	protected $update_resource = '';
	protected $resource = '';

	function __construct($client) {
		$this->client = $client;
	}


	/**
	 * Sends the request to create the requested object on the server
	 * @param array $object
	 * @return array
	 */
	function create($object) {
		if ($this->create_request) {
			$data = array($this->create_resource => $object);
			return $this->client->create($this->resource, $data, $this->create_resource);
		}
	}

	/**
	 * Sends the request to get the requested object from the server
	 * @param int $object_id
	 * @param array $params
	 * @return array
	 */
	function get($object_id = false, $params = null) {
		if ($this->get_request) {
			if ($object_id) {
				return $this->client->get($this->resource . '/' . $object_id, $params, $this->single_resource);
			} else {
				return $this->client->get($this->resource, $params, $this->list_resource );
			}
		}
	}

	/**
	 * Sends the request to update an object on the server
	 * @param int $object_id
	 * @param array $data
	 * @return array
	 */
	function update($object_id = false, $data) {
		if ($this->update_request) {
			if ($object_id) {
				$fields = array($this->update_resource => $data);
				return $this->client->update($this->resource . '/' . $object_id, $fields, $this->update_resource, array(), '');
			}
		}
	}

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiParcelsResource extends SendCloudApiAbstractResource {

	protected $resource = 'parcels';
	protected $create_resource = 'parcels';
	protected $update_resource = 'parcel';
	protected $list_resource = 'parcels';
	protected $single_resource = 'parcel';

	/**
	 * Sends the request to create multiple parcels to the Server
	 *
	 * Example $object:
	 * array(
	 * 		array(
	 * 			'name' => 'John Doe',
	 * 			'company_name' => 'Stationsweg 20'
	 * 			....
	 * 		),
	 * 		array(
	 * 			'name' => 'Jan Smit',
	 * 			'company_name' => 'Stadhuisplein 10'
	 * 			....
	 * 		)
	 * );
	 *
	 * @param array $object
	 * @return array
	 */
	function create_bulk($object) {
		if ($this->create_request) {
			$data = array($this->list_resource => $object);
			return $this->client->create($this->resource, $data, $this->list_resource);
		}
	}

	/**
	 * Override the default update method from the `SendCloudApiAbstractResource`
	 * in order to match the API documentation. Parcel updates should use the
	 * `/parcels/` endpoint with the resource ID included in the payload.
	 */
	function update($object_id = false, $data)
	{
		if ($this->update_request && $object_id) {
			$data = $data + array('id' => $object_id);
			$fields = array($this->update_resource => $data);
			return $this->client->update($this->resource, $fields, $this->update_resource);
		}
	}

	function cancel($object_id) {
		if ($object_id) {
			return $this->client->cancel_parcel($this->resource . '/' . $object_id .'/cancel/');
		}
	}

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiLabelResource extends SendCloudApiAbstractResource {

	protected $resource = 'labels';
	protected $list_resource = 'label';
	protected $single_resource = 'label';
	protected $create_resource = 'label';
	protected $create_request = true;

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiUserResource extends SendCloudApiAbstractResource {

	protected $resource = 'user';
	protected $list_resource = 'user';
	protected $single_resource = 'user';
	protected $create_request = false;
	protected $update_request = false;
}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiShippingResource extends SendCloudApiAbstractResource {

	protected $resource = 'shipping_methods';
	protected $list_resource = 'shipping_methods';
	protected $single_resource = 'shipping_method';
	protected $create_request = false;
	protected $update_request = false;

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiParcelStatusResource extends SendCloudApiAbstractResource {

	protected $resource = 'parcels/statuses';
	protected $list_resource = '';
	protected $single_resource = '';
	protected $create_request = false;
	protected $update_request = false;

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiInvoicesResource extends SendCloudApiAbstractResource {

	protected $resource = 'user/invoices';
	protected $list_resource = 'invoices';
	protected $single_resource = 'invoice';
	protected $create_request = false;
	protected $update_request = false;

}

/**
 * @version 2.0.0
 * @package SendCloud
 * @see https://docs.sendcloud.sc/api/v2/index.html
 */
class SendCloudApiSenderAddressResource extends SendCloudApiAbstractResource {

	protected $resource = 'user/addresses/sender';
	protected $list_resource = 'sender_addresses';
	protected $single_resource = 'sender_address';
	protected $create_request = false;
	protected $update_request = false;

}
