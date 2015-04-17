<?php
	/*
		Plugin Name: Streamio
		Plugin URI:  http://www.streamio.com
		Description: Adds basic Streamio support
		Version:     1.0
		Author:      Rackfish AB
		Author URI:  http://www.rackfish.com
		License:     GPLv2 or later
	*/
	
	/*
		Streamio by Rackfish
		Copyright (C) 2015 Rackfish AB

		This plugin currently adds Streamio to the list of supported oEmbed providers.
		
		Higher integration features are planned.
	*/
	
	add_action('plugins_loaded', 'streamio_register_oembed_provider');
	add_action('init', 'streamio_handle_oembed');
	
	function streamio_handle_oembed() {
		if ( isset($_GET['data_oembed'])) {
			$url = $_REQUEST['url'];
			
			// Try to use fopen, if not use curl
			if( ini_get('allow_url_fopen') ) 
				streamio_get_oembed_fopen($url);
			else if ( function_exists('curl_init') )
				streamio_get_oembed_curl($url);				
			die();
		}
	}

	function streamio_find_location($headers) {
		for ($i=0; $i<count($headers); $i++) {
			if ( strstr($headers[$i], 'Location')!=false ) {
				return explode(':', $headers[$i], 2)[1];
			}
		}
		return null;
	}

	function streamio_get_oembed_fopen($url) {
		// Fetch the headers received from the http://s3m.io shortlink.
		$options['http'] = array(
			'method' => "HEAD",
			'ignore_errors' => 1,
		);
		$context = stream_context_create($options);
		$body = file_get_contents($url, NULL, $context);
		$headers = $http_response_header;
		
		// The header will contain a new location (redirect) to the Streamio API public_show call
		$location = streamio_find_location($headers);
		
		// Send this url to Streamio's oEmbed provider and display the response
		$url_oembed = 'https://streamio.com/api/v1/oembed?url='.urlencode($location);
		$oembed = file_get_contents($url_oembed);
		echo $oembed;
	}
	
	function streamio_get_oembed_curl($url)	{
		// Fetch the headers received from the http://s3m.io shortlink.
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_NOBODY, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$headers = explode("\n", curl_exec($curl));

		// The header will contain a new location (redirect) to the Streamio API public_show call
		$location = streamio_find_location($headers);
		
		// Send this url to Streamio's oEmbed provider and display the response
		$url_oembed = 'https://streamio.com/api/v1/oembed?url='.urlencode($location);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_URL, $url_oembed);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_NOBODY, 0);
		$oembed = curl_exec($curl);
		echo $oembed;
	}

	function streamio_register_oembed_provider() {
		$oembed_url = home_url('/');
		$oembed_url = add_query_arg( array('data_oembed' => 1), $oembed_url);
		wp_oembed_add_provider('#http://(www.)?s3m.io/.*#i', $oembed_url, true);
	}