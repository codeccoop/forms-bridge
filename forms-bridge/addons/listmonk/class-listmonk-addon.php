<?php

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-listmonk-form-bridge.php';
require_once 'hooks.php';

/**
 * Listmonk Addon class.
 */
class Listmonk_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Listmonk';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'listmonk';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Listmonk_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => '__listmonk-' . time(),
				'endpoint' => '/api/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();
		return ! is_wp_error( $response );
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
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => '__listmonk-' . time(),
				'method'   => 'GET',
				'endpoint' => $endpoint,
				'backend'  => $backend,
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
		if ( '/api/subscribers' === $endpoint ) {
			return array(
				array(
					'name'     => 'email',
					'schema'   => array( 'type' => 'string' ),
					'required' => true,
				),
				array(
					'name'   => 'name',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'status',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'lists',
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'number' ),
					),
				),
				array(
					'name'   => 'preconfirm_subscriptions',
					'schema' => array( 'type' => 'boolean' ),
				),
				array(
					'name'   => 'attribs',
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
			);
		}

		return array();
	}
}

Listmonk_Addon::setup();
