<?php
/**
 * Class Finan_Coop_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-financoop-form-bridge.php';
require_once 'hooks.php';
require_once 'shortcodes.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'FinanCoop';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'financoop';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Finan_Coop_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Finan_Coop_Form_Bridge(
			array(
				'name'     => '__financoop-' . time(),
				'endpoint' => '/api/campaign',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Financoop backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Performs a GET request against the backend endpoint and retrive the response data.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array|WP_Error
	 */
	public function fetch( $endpoint, $backend ) {
		$bridge = new Finan_Coop_Form_Bridge(
			array(
				'name'     => '__financoop-' . time(),
				'endpoint' => $endpoint,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		return $bridge->submit();
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields
	 * and accepted content type.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $backend Backend name.
	 *
	 * @return array
	 */
	public function get_endpoint_schema( $endpoint, $backend ) {
		$bridge = new Finan_Coop_Form_Bridge(
			array(
				'name'     => '__financoop-' . time(),
				'endpoint' => $endpoint,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		if (
			! preg_match(
				'/\/api\/campaign\/\d+\/([a-z_]+)$/',
				$bridge->endpoint,
				$matches
			)
		) {
			return array();
		}

		$source = $matches[1];

		$common_schema = array(
			array(
				'name'   => 'vat',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'firstname',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'lastname',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'email',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'address',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'zip_code',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'phone',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'lang',
				'schema' => array( 'type' => 'string' ),
			),
			array(
				'name'   => 'country_code',
				'schema' => array( 'type' => 'string' ),
			),
		);

		switch ( $source ) {
			case 'subscription_request':
				return array_merge(
					array(
						array(
							'name'   => 'ordered_parts',
							'schema' => array( 'type' => 'integer' ),
						),
						array(
							'name'   => 'type',
							'schema' => array( 'type' => 'string' ),
						),
						array(
							'name'   => 'remuneration_type',
							'schema' => array( 'type' => 'string' ),
						),
					),
					$common_schema
				);
			case 'donation_request':
				return array_merge(
					array(
						array(
							'name'   => 'donation_amount',
							'schema' => array( 'type' => 'integer' ),
						),
						// [
						// 'name' => 'tax_receipt_option',
						// 'schema' => ['type' => 'string'],
						// ],
					),
					$common_schema
				);
			case 'loan_request':
				return array_merge(
					array(
						array(
							'name'   => 'loan_amount',
							'schema' => array( 'type' => 'integer' ),
						),
					),
					$common_schema
				);
		}
	}
}

Finan_Coop_Addon::setup();
