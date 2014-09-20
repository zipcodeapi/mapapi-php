<?php

/** Copyright Redline13, LLC */

/** Reline13 Map API */
class Redline13MapApi
{
	/** Map ID */
	private $mapId = null;
	
	/** Map key */
	private $mapKey = null;
	
	/** Local server UNIX socket */
	private $serverUnixSocket = null;
	
	/** CURL Handle */
	private $curl = null;
	
	/** HTTP Protocol */
	private $protocol = 'https';
	
	/** API Host */
	const API_HOST = 'realtimemapapi.com';
	
	/** API Port */
	const API_PORT = 4434;
	
	/** Connect timeout */
	private $connectTimeout = 5;
	
	/** Request Timeout */
	private $timeout = 20;
	
	/** Last response */
	private $lastResponse = null;
	
	/**
	 * Constructor
	 *
	 * @param string $mapId Map ID
	 * @param string $mapKey Map key
	 * @param string $serverUnixSocketFilename Local server UNIX socket filename if installed
	 */
	public function __construct($mapId, $mapKey, $serverUnixSocketFilename = null)
	{
		$this->mapId = $mapId;
		$this->mapKey = $mapKey;
		
		// Check if UIX socket exists
		if ($serverUnixSocketFilename !== null && file_exists($serverUnixSocketFilename))
		{
			$this->serverUnixSocket = fsockopen('unix://' . $serverUnixSocketFilename, -1, $errno, $errstr, 5);
			if ($this->serverUnixSocket === false)
				$this->serverUnixSocket = null;
		}
		
		// Did we open te socket? No?
		if ($this->serverUnixSocket === null)
		{
			// Check if CURL is installed
			if (function_exists('curl_init'))
				$this->curl = curl_init();
		}
	}

	/**
   * Destructor
	 */
	public function __destruct()
	{
		// Close unix socket
		if ($this->serverUnixSocket !== null)
			fclose($this->serverUnixSocket);
	  // Close CURL handle
	  if ($this->curl)
	  	curl_close($this->curl);
	}
	
	/**
	 * Set timeouts
	 *
	 * @param int $connectTimeout Connect timeout in seconds
	 * @param int $requestTimeout Request timeout in seconds
	 */
	public function setTimeouts($connectTimeout, $requestTimeout)
	{
		$this->connectTimeout = $connectTimeout;
		$this->timeout = $requestTimeout;
	}
	
	/**
	 * Get last response
	 *
	 * @return mixed Last response (null, parsed JSON, or raw string)
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}
	
	/**
	 * Send points
	 *
	 * @param array $points Points
	 *
	 * @return bool True on success
	 */
	public function sendPoints($points)
	{
		$rtn = false;
		
		// Send via UNIX socket
		if ($this->serverUnixSocket !== null)
		{
			// Send each point
			$rtn = true;
			$point = null;
			foreach ($points as &$point)
			{
				// Write
				fwrite($this->serverUnixSocket, json_encode($point));
				
				// Check for response
				if (feof($this->serverUnixSocket) || fgets($this->serverUnixSocket, 1024) !== 'OK')
					$rtn = false;
			}
			unset($point);
		}
		// Send to Redline
		else
		{
			// Build POST data
			$postData = json_encode(array(
				'mapId' => $this->mapId,
				'key' => $this->mapKey,
				'points' => $points
			));
			$resp = $this->doHttpPost($postData);
			if ($resp === null)
				return false;
			
			// Remove headers
			$pos = strpos($resp, "\r\n\r\n");
			if ($pos === false)
				return false;
			$headersStr = substr($resp, 0, $pos);
			// Parse json
			$json = json_decode(substr($resp, $pos));
			if ($json === null)
				return false;
			$this->lastResponse = $json;
			
			// Return whether or not the response was valid
			$rtn = ($json === true);
		}
		
		return $rtn;
	}
	
	/**
	 * Do HTTP POST
	 *
	 * @param $postData Raw POST data
	 *
	 * @return string Response, or null
	 */
	protected function doHttpPost($postData)
	{
		// Build URL
		$url = $this->protocol . '://' . self::API_HOST;
		if (self::API_PORT !== 80)
			$url .= ':' . self::API_PORT;
		
		// Try to get url with curl
	  $resp = null;
	  if ($this->curl)
	  {
	  	// Set up curl options
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_HEADER, 1);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
			
			// Timeouts
			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
			curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
			
			curl_setopt($this->curl, CURLOPT_POST, 1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($postData)
			));
			
			// Make request
			if (($resp = curl_exec($this->curl)) === false)
				$resp = null;
	  }
	  // Use streams
	  else
	  {
			// Set up options
	  	$opts = array(
				$this->protocol => array()
			);
	  	$wrapper = &$opts[$this->protocol];
			
			$wrapper['method'] = 'POST';
			$wrapper['header'] = 'Content-Type: application/json' . "\r\n" . 'Content-Length: ' . strlen($postData);
			$wrapper['content'] = $postData;
			
	  	// Create stream
	  	$ctx = stream_context_create($opts);
			
			// Make request
			if (($resp = file_get_contents($url, 0, $ctx)) === false)
				$resp = null;
			else
			{
				// Add HTTP headers
				$resp = implode("\r\n", $http_response_header) . "\r\n\r\n" . $resp;
			}
	  }
		
		$this->lastResponse = $resp;
		return $resp;
	}
}
