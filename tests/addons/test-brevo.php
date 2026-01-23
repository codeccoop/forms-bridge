<?php
/**
 * Class BrevoTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Brevo_Form_Bridge;
use FORMS_BRIDGE\Brevo_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Brevo test case.
 */
class BrevoTest extends WP_UnitTestCase {

	/**
	 * Handles the last intercepted http request data.
	 *
	 * @var array
	 */
	private static $request;

	/**
	 * Handles the mock response to return.
	 *
	 * @var array|null
	 */
	private static $mock_response;

	/**
	 * Holds the mocked backend name.
	 *
	 * @var string
	 */
	private const BACKEND_NAME = 'test-brevo-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://api.brevo.com';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-brevo-bridge';

	/**
	 * Test backend provider.
	 *
	 * @return Backend[]
	 */
	public static function backends_provider() {
		return array(
			new Backend(
				array(
					'name'     => self::BACKEND_NAME,
					'base_url' => self::BACKEND_URL,
					'headers'  => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/json',
						),
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
						array(
							'name'  => 'api-key',
							'value' => 'test-brevo-api-key',
						),
					),
				)
			),
		);
	}

	/**
	 * HTTP requests interceptor.
	 *
	 * @param mixed  $pre Initial pre hook value.
	 * @param array  $args Request arguments.
	 * @param string $url Request URL.
	 *
	 * @return array
	 */
	public static function pre_http_request( $pre, $args, $url ) {
		self::$request = array(
			'args' => $args,
			'url'  => $url,
		);

		$http = array(
			'code'    => 200,
			'message' => 'OK',
		);

		$method = $args['method'] ?? 'POST';

		// Parse URL to determine the endpoint being called.
		$parsed_url = wp_parse_url( $url );
		$path       = $parsed_url['path'] ?? '';

		// Parse the body to determine the method being called.
		$body = array();
		if ( ! empty( $args['body'] ) ) {
			if ( is_string( $args['body'] ) ) {
				$body = json_decode( $args['body'], true );
			} else {
				$body = $args['body'];
			}
		}

		// Return appropriate mock response based on endpoint.
		if ( self::$mock_response ) {
			$http = self::$mock_response['http'] ?? $http;
			unset( self::$mock_response['http'] );
			$response_body = self::$mock_response;

			self::$mock_response = null;
		} else {
			$response_body = self::get_mock_response( $method, $path, $body );
		}

		return array(
			'response'      => $http,
			'headers'       => array( 'Content-Type' => 'application/json' ),
			'cookies'       => array(),
			'body'          => wp_json_encode( $response_body ),
			'http_response' => null,
		);
	}

	/**
	 * Get mock response based on API endpoint.
	 *
	 * @param string $method HTTP method.
	 * @param string $path API endpoint path.
	 * @param array  $body Request body.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $method, $path, $body ) {
		switch ( $path ) {
			case '/v3/swagger_definition_v3.yml':
				return array(
					'openapi' => '3.0.1',
					'paths'   => array(
						'/contacts'      => array(
							'get'  => array(
								'parameters' => array(
									array(
										'in'     => 'query',
										'name'   => 'limit',
										'schema' => array( 'type' => 'integer' ),
									),
								),
							),
							'post' => array(
								'requestBody' => array(
									'content' => array(
										'application/json' => array(
											'schema' => array(
												'type' => 'object',
												'properties' => array(
													'email'   => array( 'type' => 'string' ),
													'listIds' => array( 'type' => 'array' ),
												),
											),
										),
									),
								),
							),
						),
						'/contacts/list' => array(
							'get'  => array(
								'parameters' => array(
									array(
										'in'     => 'query',
										'name'   => 'limit',
										'schema' => array( 'type' => 'integer' ),
									),
								),
							),
							'post' => array(
								'requestBody' => array(
									'content' => array(
										'application/json' => array(
											'schema' => array(
												'type' => 'object',
												'properties' => array(
													'folderId' => array( 'type' => 'integer' ),
													'name' => array( 'type' => 'string' ),
												),
											),
										),
									),
								),
							),
						),
					),
				);

			case '/v3/contacts/lists':
				return array(
					'lists' => array(
						array(
							'id'   => 1,
							'name' => 'Test List',
						),
					),
				);

			case '/v3/contacts':
				if ( 'POST' === $method ) {
					return array(
						'id'    => 123456789,
						'email' => $body['email'],
					);
				}

				return array(
					'contacts' => array(
						array(
							'id'    => 123456789,
							'email' => 'john.doe@example.com',
						),
					),
				);

			default:
				return array();
		}
	}

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		self::$request       = null;
		self::$mock_response = null;

		tests_add_filter( 'http_bridge_credentials', array( self::class, 'credentials_provider' ), 10, 0 );
		tests_add_filter( 'http_bridge_backends', array( self::class, 'backends_provider' ), 10, 0 );
		tests_add_filter( 'pre_http_request', array( self::class, 'pre_http_request' ), 10, 3 );
	}

	/**
	 * Tear down test filters.
	 */
	public function tear_down() {
		remove_filter( 'http_bridge_credentials', array( self::class, 'credentials_provider' ), 10, 0 );
		remove_filter( 'http_bridge_backends', array( self::class, 'backends_provider' ), 10, 0 );
		remove_filter( 'pre_http_request', array( self::class, 'pre_http_request' ), 10, 3 );

		parent::tear_down();
	}

	/**
	 * Test that the addon class exists and has correct constants.
	 */
	public function test_addon_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Brevo_Addon' ) );
		$this->assertEquals( 'Brevo', Brevo_Addon::TITLE );
		$this->assertEquals( 'brevo', Brevo_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Brevo_Form_Bridge', Brevo_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Brevo_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertFalse( $bridge->is_valid );
	}

	/**
	 * Test POST request to create a contact.
	 */
	public function test_post_create_contact() {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		);

		$payload = array( 'email' => 'john.doe@example.com' );

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 123456789, $response['data']['id'] );
	}

	/**
	 * Test GET request to fetch contacts.
	 */
	public function test_get_contacts() {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v3/contacts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'contacts', $response['data'] );
		$this->assertEquals( 'john.doe@example.com', $response['data']['contacts'][0]['email'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'brevo' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'brevo' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( '/v3/contacts', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'brevo' );
		$schema = $addon->get_endpoint_schema(
			'/v3/contacts',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'email', $field_names );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'http'    => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'code'    => 'unauthorized',
			'message' => 'Key not found',
		);

		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test duplicate contact handling.
	 */
	public function test_duplicate_contact_handling() {
		self::$mock_response = array(
			'http'    => array(
				'code'    => 425,
				'message' => 'TOO_EARLY',
			),
			'code'    => 'duplicate_parameter',
			'message' => 'email is already associated with another Contact',
		);

		$bridge = new Brevo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v3/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array( 'email' => 'duplicate@example.com' ) );

		// Should not return WP_Error for duplicate_parameter
		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}
}
