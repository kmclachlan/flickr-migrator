<?php

class Flickr {
	private $api_key = '';
	private $api_secret = '';
	
	public function __construct($key, $secret = '') {
		$this->api_key = $key;
		$this->api_secret = $secret;
	}
	
	public function auth() {
		$oauth = new OAuth($this->api_key, $this->api_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
		$oauth->enableDebug();
		
		header('Location: https://www.flickr.com/services/oauth/request_token');
	}
}

?>