<?php
/**
 * Class Mailchimp_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-mailchimp-form-bridge.php';
require_once 'hooks.php';

/**
 * Mapchimp Addon class.
 */
class Mailchimp_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Mailchimp';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'mailchimp';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Mailchimp_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Target backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => '__mailchimp-' . time(),
				'endpoint' => '/3.0/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit();

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Mailchimp backend ping error response', Logger::ERROR );
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
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => '__mailchimp-' . time(),
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
		if ( strstr( $endpoint, '/lists/' ) !== false ) {
			$fields = array(
				array(
					'name'     => 'email_address',
					'schema'   => array( 'type' => 'string' ),
					'required' => true,
				),
				array(
					'name'     => 'status',
					'schema'   => array( 'type' => 'string' ),
					'required' => true,
				),
				array(
					'name'   => 'email_type',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'interests',
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
				array(
					'name'   => 'language',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'vip',
					'schema' => array( 'type' => 'boolean' ),
				),
				array(
					'name'   => 'location',
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'latitude'  => array( 'type' => 'number' ),
							'longitude' => array( 'type' => 'number' ),
						),
					),
				),
				array(
					'name'   => 'marketing_permissions',
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'marketing_permission_id' => array(
									'type' => 'string',
								),
								'enabled'                 => array( 'type' => 'boolean' ),
							),
						),
					),
				),
				array(
					'name'   => 'ip_signup',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'ip_opt',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'timestamp_opt',
					'schema' => array( 'type' => 'string' ),
				),
				array(
					'name'   => 'tags',
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				array(
					'name'   => 'merge_fields',
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
			);

			$fields_endpoint = str_replace(
				'/members',
				'/merge-fields',
				$endpoint
			);

			$bridge = new Mailchimp_Form_Bridge(
				array(
					'name'     => '__mailchimp-' . time(),
					'endpoint' => $fields_endpoint,
					'method'   => 'GET',
					'backend'  => $backend,
				)
			);

			$response = $bridge->submit();

			if ( is_wp_error( $response ) ) {
				return array();
			}

			foreach ( $response['data']['merge_fields'] as $field ) {
				$fields[] = array(
					'name'   => 'merge_fields.' . $field['tag'],
					'schema' => array( 'type' => 'string' ),
				);
			}

			return $fields;
		}

		return array();
	}
}

Mailchimp_Addon::setup();
