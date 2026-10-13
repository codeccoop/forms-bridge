<?php
/**
 * Class Mautic_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-mautic-form-bridge.php';
require_once 'hooks.php';

/**
 * Mautic addon class.
 */
class Mautic_Addon extends Addon {

	/**
	 * Holds the addon's title.
	 *
	 * @var string
	 */
	public const TITLE = 'Mautic';

	/**
	 * Holds the addon's name.
	 *
	 * @var string
	 */
	public const NAME = 'mautic';

	/**
	 * Holds the addon's custom bridge class.
	 *
	 * @var string
	 */
	public const BRIDGE = '\FORMS_BRIDGE\Mautic_Form_Bridge';

	/**
	 * Holds the list of known Mautic API endpoints indexed by HTTP method.
	 *
	 * @var array<string, string[]>
	 */
	private const ENDPOINTS = array(
		'POST' => array(
			'/api/contacts/new',
			'/api/companies/new',
		),
		'GET'  => array(
			'/api/contacts',
			'/api/companies',
			'/api/segments',
			'/api/fields/contact',
			'/api/fields/company',
		),
	);

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Mautic_Form_Bridge(
			array(
				'name'     => '__mautic-' . time(),
				'endpoint' => '/api/contacts',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Mautic backend ping error response', Logger::ERROR );
			Logger::log( $response, Logger::ERROR );
			return false;
		}

		return true;
	}

	/**
	 * Returns the list of available API endpoints.
	 *
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array
	 */
	public function get_endpoints( $backend, $method = null ) {
		if ( $method ) {
			return self::ENDPOINTS[ strtoupper( $method ) ] ?? array();
		}

		return array_values(
			array_unique( array_merge( ...array_values( self::ENDPOINTS ) ) )
		);
	}

	/**
	 * Performs an introspection of the backend endpoint and returns API fields.
	 *
	 * The contact and company endpoints accept the instance's custom fields by
	 * alias, so the fields are fetched from the Mautic fields API.
	 *
	 * @param string      $endpoint API endpoint.
	 * @param string      $backend Backend name.
	 * @param string|null $method HTTP method.
	 *
	 * @return array List of fields and content type of the endpoint.
	 */
	public function get_endpoint_schema( $endpoint, $backend, $method = null ) {
		if ( 'POST' !== strtoupper( $method ?? 'POST' ) ) {
			return array();
		}

		if ( '/api/contacts/new' === $endpoint ) {
			$object = 'contact';
		} elseif ( '/api/companies/new' === $endpoint ) {
			$object = 'company';
		} else {
			return array();
		}

		$bridge = new Mautic_Form_Bridge(
			array(
				'name'     => '__mautic-endpoint-introspection',
				'backend'  => $backend,
				'endpoint' => "/api/fields/{$object}",
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit( array( 'limit' => 1000 ) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$fields = array();
		foreach ( $response['data']['fields'] ?? array() as $field ) {
			if ( empty( $field['alias'] ) || ( isset( $field['isPublished'] ) && ! $field['isPublished'] ) ) {
				continue;
			}

			switch ( $field['type'] ?? 'text' ) {
				case 'number':
					$schema = array( 'type' => 'number' );
					break;
				case 'boolean':
					$schema = array( 'type' => 'boolean' );
					break;
				case 'multiselect':
					$schema = array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					);
					break;
				default:
					$schema = array( 'type' => 'string' );
					break;
			}

			$fields[] = array(
				'name'     => $field['alias'],
				'schema'   => $schema,
				'required' => ! empty( $field['isRequired'] ),
			);
		}

		if ( 'contact' === $object ) {
			$fields[] = array(
				'name'   => 'ipAddress',
				'schema' => array( 'type' => 'string' ),
			);

			$fields[] = array(
				'name'   => 'tags',
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			);

			$fields[] = array(
				'name'   => 'segments',
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			);
		}

		$fields[] = array(
			'name'   => 'owner',
			'schema' => array( 'type' => 'integer' ),
		);

		$fields[] = array(
			'name'   => 'overwriteWithBlank',
			'schema' => array( 'type' => 'boolean' ),
		);

		return OpenAPI::expand_fields_schema( $fields );
	}

	/**
	 * Gets expiration time for introspection cache based on the introspection
	 * method.
	 *
	 * @param string $method Introspection method (ping, endpoints, schema).
	 *
	 * @return int Time in seconds.
	 */
	public function introspection_cache_expiration( $method ) {
		if ( Logger::is_active() ) {
			return 0;
		}

		switch ( $method ) {
			case 'ping':
			case 'endpoints':
			case 'schema':
				return 60 * 60 * 24;
			default:
				return 0;
		}
	}
}

Mautic_Addon::setup();
