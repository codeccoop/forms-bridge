<?php

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the Dolibarr REST API.
 */
class Dolibarr_Form_Bridge extends Form_Bridge {

	public function __construct( $data ) {
		parent::__construct( $data, 'dolibarr' );
	}
}
