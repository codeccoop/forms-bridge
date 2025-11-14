<?php
/**
 * Class Dolibarr_Form_Bridge
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Form bridge implamentation for the Dolibarr REST API.
 */
class Dolibarr_Form_Bridge extends Form_Bridge {

	/**
	 * Bridge constructor with addon name provisioning.
	 *
	 * @param array $data Bridge data.
	 */
	public function __construct( $data ) {
		parent::__construct( $data, 'dolibarr' );
	}
}
