<?php

require_once __DIR__ . '/vendor/autoload.php';

use Fahdi\SimplyRETS;

$settings = array(
	'api_key'    => 'simplyrets', // required
	'api_secret' => 'simplyrets', // required
	'status'     => 'active'
);

// make a request for multiple listings
$response = SimplyRETS::properties($settings);

print_r( $response );
