<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$http = get_option( 'http-bridge_general', array() ) ?: array(
	'backends'    => array(),
	'credentials' => array(),
);

update_option( 'forms-bridge_http', $http );
