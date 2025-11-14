<?php
/**
 * Class Brevo_Addon
 *
 * @package formsbridge
 */

namespace FORMS_BRIDGE;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once 'class-brevo-form-bridge.php';
require_once 'hooks.php';
require_once 'api.php';

/**
 * REST API Addon class.
 */
class Brevo_Addon extends Addon {

	/**
	 * Handles the addon's title.
	 *
	 * @var string
	 */
	const TITLE = 'Brevo';

	/**
	 * Handles the addon's name.
	 *
	 * @var string
	 */
	const NAME = 'brevo';

	/**
	 * Handles the addom's custom bridge class.
	 *
	 * @var string
	 */
	const BRIDGE = '\FORMS_BRIDGE\Brevo_Form_Bridge';

	/**
	 * Performs a request against the backend to check the connexion status.
	 *
	 * @param string $backend Backend name.
	 *
	 * @return boolean
	 */
	public function ping( $backend ) {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
				'endpoint' => '/v3/contacts/lists',
				'method'   => 'GET',
				'backend'  => $backend,
			)
		);

		$response = $bridge->submit( array( 'limit' => 1 ) );

		if ( is_wp_error( $response ) ) {
			Logger::log( 'Brevo backend ping error response', Logger::ERROR );
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
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
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
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => '__brevo-' . time(),
				'endpoint' => $endpoint,
				'backend'  => $backend,
				'method'   => 'GET',
			)
		);

		if ( strstr( $bridge->endpoint, 'contacts' ) ) {
			$response = $bridge
				->patch(
					array(
						'name'     => 'brevo-contacts-attributes',
						'endpoint' => '/v3/contacts/attributes',
						'method'   => 'GET',
					)
				)
				->submit();

			if ( is_wp_error( $response ) ) {
				return array();
			}

			if ( $bridge->endpoint === '/v3/contacts/doubleOptinConfirmation' ) {
				$fields = array(
					array(
						'name'     => 'email',
						'schema'   => array( 'type' => 'string' ),
						'required' => true,
					),
					array(
						'name'     => 'includeListIds',
						'schema'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'required' => true,
					),
					array(
						'name'   => 'excludeListIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'     => 'templateId',
						'schema'   => array( 'type' => 'integer' ),
						'required' => true,
					),
					array(
						'name'     => 'redirectionUrl',
						'schema'   => array( 'type' => 'string' ),
						'required' => true,
					),
					array(
						'name'   => 'attributes',
						'schema' => array(
							'type'       => 'object',
							'properties' => array(),
						),
					),
				);
			} else {
				$fields = array(
					array(
						'name'     => 'email',
						'schema'   => array( 'type' => 'string' ),
						'required' => true,
					),
					array(
						'name'   => 'ext_id',
						'schema' => array( 'type' => 'string' ),
					),
					array(
						'name'   => 'emailBlacklisted',
						'schema' => array( 'type' => 'boolean' ),
					),
					array(
						'name'   => 'smsBlacklisted',
						'schema' => array( 'type' => 'boolean' ),
					),
					array(
						'name'   => 'listIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'   => 'updateEnabled',
						'schema' => array( 'type' => 'boolean' ),
					),
					array(
						'name'   => 'smtpBlacklistSender',
						'schema' => array( 'type' => 'boolean' ),
					),
					array(
						'name'   => 'attributes',
						'schema' => array(
							'type'       => 'object',
							'properties' => array(),
						),
					),
				);
			}

			foreach ( $response['data']['attributes'] as $attribute ) {
				$fields[] = array(
					'name'   => 'attributes.' . $attribute['name'],
					'schema' => array( 'type' => 'string' ),
				);
			}

			return $fields;
		} else {
			if ( ! preg_match( '/\/([a-z]+)$/', $bridge->endpoint, $matches ) ) {
				return array();
			}

			$module   = $matches[1];
			$response = $bridge
				->patch(
					array(
						'name'     => "brevo-{$module}-attributes",
						'endpoint' => "/v3/crm/attributes/{$module}",
						'method'   => 'GET',
					)
				)
				->submit();

			if ( is_wp_error( $response ) ) {
				return array();
			}

			if ( 'companies' === $module ) {
				$fields = array(
					array(
						'name'     => 'name',
						'schema'   => array( 'type' => 'string' ),
						'required' => true,
					),
					array(
						'name'   => 'countryCode',
						'schema' => array( 'type' => 'integer' ),
					),
					array(
						'name'   => 'linkedContactsIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'   => 'linkedDealsIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'   => 'attributes',
						'schema' => array(
							'type'       => 'object',
							'properties' => array(),
						),
					),
				);
			} elseif ( 'deals' === $module ) {
				$fields = array(
					array(
						'name'     => 'name',
						'schema'   => array( 'type' => 'string' ),
						'required' => true,
					),
					array(
						'name'   => 'linkedDealsIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'   => 'linkedCompaniesIds',
						'schema' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
					array(
						'name'   => 'attributes',
						'schema' => array(
							'type'       => 'object',
							'properties' => array(),
						),
					),
				);
			}

			foreach ( $response['data'] as $attribute ) {
				switch ( $attribute['attributeTypeName'] ) {
					case 'number':
						$type = 'number';
						break;
					case 'text':
						$type = 'string';
						break;
					case 'user':
						$type = 'email';
						break;
					case 'date':
						$type = 'date';
						break;
					default:
						$type = 'string';
				}

				$fields[] = array(
					'name'   => 'attributes.' . $attribute['internalName'],
					'schema' => array( 'type' => $type ),
				);
			}

			return $fields;
		}
	}
}

Brevo_Addon::setup();
