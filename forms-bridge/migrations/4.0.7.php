<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

function forms_bridge_migration_407() {
	$http = get_option( 'http-bridge_general', array() ) ?: array(
		'backends'    => array(),
		'credentials' => array(),
	);

	update_option( 'forms-bridge_http', $http );
}
