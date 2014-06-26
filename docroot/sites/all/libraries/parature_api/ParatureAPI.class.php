<?php
/**
 * @file
 *   Parature API for PHP
 *
 * @version 1.0-dev
 * @author Andrew MacRobert <andrew.macrobert@gmail.com>
 * @copyright Copyright (c) 2012, Acquia, Inc.
 * @license GNU GPLv3
 */

define('PARATURE_API_VERSION', '1.0');
 
/**
 * Handles requests to the Parature API.
 */
class ParatureAPI {
  /**
   * Parature API crednetials and properties
   */
  private $hostname;      // Parature API hostname, ex. s5-sandbox.parature.com
  private $token;         // Parature API token, found in Setup > CSRs
  private $version;       // Parature API version (v1)
  private $account_id;    // Parature client Account ID
  private $department_id; // Parature department ID
  
  // Array of cached api call results
  private static $cache = array();

  /**
   * Initialize the API connection.
   *
   * @param array $credentials
   *   (optional) A keyed array of Parature credentials, including:
   *   - hostname: The base URL of API requests. Excludes protocol and trailing slash.
   *   - token: The Parature API token.
   *   - account_id: Account ID of the parature account (Acquia = 15066).
   *   - department_id: Department id within the Parature account.
   *   - version (optional): API version. Currently only supports v1.
   *   If no credentials are passed or credentials are missing, those set in
   *   ParatureCredentials are used instead.
   */
  public function __construct($credentials = array()) {
    $defaults = ParatureCredentials::get_credentials();
    $this->hostname       = isset($credentials['hostname'])       ? $credentials['hostname']      : $defaults['hostname']     ;
    $this->token          = isset($credentials['token'])          ? $credentials['token']         : $defaults['token']        ;
    $this->account_id     = isset($credentials['account_id'])     ? $credentials['account_id']    : $defaults['account_id']   ;
    $this->department_id  = isset($credentials['department_id'])  ? $credentials['department_id'] : $defaults['department_id'];
    $this->version        = isset($credentials['version'])        ? $credentials['version']       : $defaults['version']      ;
  }
  
  /**
   * Build a Parature API request URL.
   *
   * @param array $params
   *   Array of $key => $value pairs to pass as GET vars.
   * @return string
   *   The request URL.
   */
  public function _build_url($request, $params) {
    $url =
      'https://' . $this->hostname .
      '/api/' . $this->version .
      '/' . $this->account_id .
      '/' . $this->department_id .
      '/' . $request . '?' .
      http_build_query(array_merge($params, array('_token_' => $this->token)));
    
    return $url;
  }
  
  /**
   * Makes a GET request to the Parature API and returns an XML response.
   * 
   * @param string $request
   *   The request to send (such as Ticket/423 or Account)
   * @param array $params
   *   (optional) An array of GET parameters (token automatically included)
   * @param bool $bypass_cache
   *   (optional) TRUE will always make a new request to Parature, even if this
   *   request has been previously cached. Default FALSE.
   * @param bool $cache_result
   *   (optional) TRUE will save the result of this request for future identical
   *   requests.
   * @return string
   *   Parature's response, as an XML string.
   */
  public function request($request, $params = array(), $bypass_cache = FALSE, $cache_result = TRUE) {

    $url = $this->_build_url($request, $params);

    // Retrieve cached value if it exists
    if ($bypass_cache == FALSE && array_key_exists(md5($url), self::$cache))
      return self::$cache[md5($url)];

    // Make the request to Parature
    if (!$response = $this->make_http_request($url)) {
      throw new Exception('Failed to connect to Parature.');
    }

    // Cache the response
    if ($cache_result == TRUE)
      self::$cache[md5($url)] = $response;

    return $response;
  }
  
  /**
   * Make an HTTP request to the connected Parature account.
   *
   * @param string $url
   *   The address at which to make the request.
   * @param string $method
   *   GET, POST, or PUT (defaults to GET).
   * @param mixed $body
   *   (optional) Any POST fields to send with the call.
   * @return string
   *   The response of the request.
   */
  public function make_http_request($url, $method = 'GET', $body = NULL) {
    
    $request = curl_init($url);
    
    // HTTP request parameters
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($request, CURLOPT_TIMEOUT, 60);
    curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
    
    // If the post body isn't null or empty, add it to the body of the request
    if(!empty($body) && $body !== 0) {
      // Set the content length in the header
      if (is_string($body)) {
        curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($body)));
      }
      else {
        curl_setopt($request, CURLOPT_HEADER, 0);
      }
      curl_setopt($request, CURLOPT_POSTFIELDS, $body);
    }
    
    // Make the request, capturing the response and throwing an error if any occurred.
    $response = curl_exec($request);
    
    if(!$response) {
      throw new ErrorException(curl_error($request));
    }
    curl_close($request);
    
    return $response;
  }
  
  /**
   * Update/create parature data.
   *
   * @param ParatureObject $object
   *   The Parature Object to write, with all required fields set.
   * @param bool $new
   *   If TRUE, will always CREATE (never UPDATE).
   * @return string
   *   Parature's response
   */
  public function save($object, $new = FALSE) {
    if (!is_subclass_of($object, 'ParatureObject'))
      throw new ErrorException('Trying to save a non-ParatureObject to Parature.');
      
    // Strip XML elements that cause errors when saved
    if ($new == TRUE) unset($object->attributes()->id);
    unset($object->attributes()->uid, $object->attributes()->href, $object->attributes()->{'service-desk-uri'}, $object->Actions);
    
    foreach ($object->xpath("*[@editable!='true']") as $node)
      unset($object->{$node->getName()});
    
    if ($new == FALSE && $object->get_id() == NULL) $new = TRUE;
    
    $xmlBody = $object->asXML();
    // Create a HTTP request object via cURL
    $url = $this->_build_url($object->get_parature_name() . ($new ? '' : '/' . $object->get_id()), array('_enforceRequiredFields_' => 'false'));
    $response = $this->make_http_request($url, ($new ? 'POST' : 'PUT'), $xmlBody);
    
    return $response;
  }
  
  /**
   * Alias of save($object, TRUE)
   */
  public function save_new($object) {
    return $this->save($object, TRUE);
  }

  /**
   * Create a Parature object. Can be blank, a schema, or an existing object.
   *
   * @param string $object_type
   *   The type of Parature object to create.
   *   Currently supported object types:
   *   - Ticket
   *   - Customer
   *   - Csr
   *   - TicketStatus
   *   - Account
   * @param mixed $parameters
   *   (optional) The Parature object ID, or an array of parameters that
   *   uniquely identify an object.
   *   @see http://s5.parature.com/UserGuide/Content/API/ParatureApiListOperation.html
   * @param bool $blank
   *   (optional) If TRUE, the object returned is an empty XML shell. If FALSE,
   *   the returned object is a empty object schema.
   *   Applies only if $params == NULL.
   * @return ParatureObject
   *   The returned object.
   */
  public function get_object($object_type, $parameters = NULL, $blank = TRUE) {
    // No object id given; return object skeleton (schema)
    if ($parameters == NULL) {
      switch ($object_type) {
        // Note: PHP does not allow variable variables for object declarations,
        // so we have to have a case for each supported object type :-[
        default:
        case 'Ticket':
          $object = ($blank ? new Ticket('<Ticket></Ticket>') : new Ticket($this->get_schema($object_type)));
          break;
        
        case 'Customer':
          $object = ($blank ? new Customer('<Customer></Customer>') : new Customer($this->get_schema($object_type)));
          break;
        
        case 'Csr':
          $object = ($blank ? new Customer('<Csr></Csr>') : new Csr($this->get_schema($object_type)));
          break;
        
        case 'TicketStatus':
          $object = ($blank ? new TicketStatus('<TicketStatus></TicketStatus>') : new TicketStatus($this->get_schema($object_type)));
          break;
        
        case 'Account':
          $object = ($blank ? new Account('<Account></Account>') : new TicketStatus($this->get_schema($object_type)));
          break;

        case 'Asset':
          $object = ($blank ? new Account('<Account></Account>') : new TicketStatus($this->get_schema($object_type)));
          break;
      }
    }
    // Object id or parameters given; return populated object
    else {
      switch ($object_type) {
        default:
        case 'Ticket':
          if (!is_array($parameters))
            $object = new Ticket($this->request('Ticket/' . $parameters, array('_history_' => 'true')));
          else {
            $parameters['_history_'] = 'true';  // Always include a ticket's history
            $response = new SimpleXMLElement($this->request('Ticket', $parameters));
            $object = new Ticket($response->Ticket->asxml());
          }
          break;
        
        case 'Customer':
          if (!is_array($parameters))
            $object = new Customer($this->request('Customer/' . $parameters));
          else {
            $response = new SimpleXMLElement($this->request('Customer', $parameters));
            $object = new Customer($response->Customer->asxml());
          }
          break;
        
        case 'Csr':
          if (!is_array($parameters))
            $object = new Csr($this->request('Csr/' . $parameters));
          else {
            $response = new SimpleXMLElement($this->request('Csr', $parameters));
            $object = new Csr($response->Csr->asxml());
          }
          break;
        
        case 'TicketStatus':
          if (!is_array($parameters))
            $object = new TicketStatus($this->request('TicketStatus/' . $parameters));
          else {
            $response = new SimpleXMLElement($this->request('TicketStatus', $parameters));
            $object = new TicketStatus($response->TicketStatus->asxml());
          }
          break;
        
        case 'Account':
          if (!is_array($parameters))
            $object = new Account($this->request('Account/' . $parameters));
          else {
            $response = new SimpleXMLElement($this->request('Account', $parameters));
            $object = new Account($response->Account->asxml());
          }
          break;
        
        case 'Asset':
          if (!is_array($parameters))
            $object = new Asset($this->request('Asset/' . $parameters));
          else {
            $response = new SimpleXMLElement($this->request('Asset', $parameters));
            $object = new Asset($response->Asset->asxml());
          }
          break;
      }
    }
    return $object;
  }

  /**
   * Get an object type's schema.
   *
   * @param string $object_type
   *   The type of object for which to get a schema.
   * @return string
   *   The schema of the passed object type.
   */
  public function get_schema($object_type) {
    return $this->request($object_type . '/schema');
  }

  /**
   * Upload a file to the Parature file management system.
   *
   * @param string $local_uri
   *   The URI of the file to upload to Parature.
   * @return array
   *   The GUID and file name of the newly added Parature object.
   */
  public function upload_file($local_uri) {
    $remote_uri_xml = new SimpleXMLElement($this->request('Ticket/upload'));
    $remote_uri = str_replace('&amp;', '&', $remote_uri_xml->attributes()->href);
    $response = $this->make_http_request($remote_uri, POST, array('file' => '@' . realpath($local_uri)));

    if ($response == FALSE)
      throw new Exception('Parature file upload failed. ' . curl_error($request));
    $result = new SimpleXMLElement($response);

    return array(
      'guid' => (string)$result->passed->file->guid,
      'filename' => (string)$result->passed->file->filename,
    );
  }
}

/**
 * Stores default ParatureAPI credentials.
 * 
 * Third-party applications (such as Drupal modules) can set credentials here
 * before a new ParatureAPI class is instantiated. When a ParatureAPI is created
 * without credentials, it uses credentials set in ParatureCredentials.
 */
abstract class ParatureCredentials {
  private static $hostname;
  private static $token;
  private static $version = 'v1';
  private static $account_id;
  private static $department_id;

  /**
   * Set default credentials used by ParatureAPI.
   *
   * @param array $credentials
   *   An array of Parature credentials, as ParatureAPI::__construct() expects.
   */
  public static function load($credentials) {
    ParatureCredentials::$hostname = $credentials['hostname'];
    ParatureCredentials::$token = $credentials['token'];
    ParatureCredentials::$version = isset($credentials['version']) ? $credentials['version'] : 'v1';
    ParatureCredentials::$account_id = $credentials['account_id'];
    ParatureCredentials::$department_id = $credentials['department_id'];
  }
  
  /**
   * Get stored credentials.
   *
   * @return array
   *   An array of default Parature credentials.
   */
  public static function get_credentials() {
    return array(
      'hostname' => ParatureCredentials::$hostname,
      'token' => ParatureCredentials::$token,
      'version' => ParatureCredentials::$version,
      'account_id'=> ParatureCredentials::$account_id,
      'department_id' => ParatureCredentials::$department_id,
    );
  }
}
