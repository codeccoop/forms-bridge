<?php
/**
 * Class DolibarrTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Dolibarr_Form_Bridge;
use FORMS_BRIDGE\Dolibarr_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Dolibarr test case.
 */
class DolibarrTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-dolibarr-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://erp.example.coop';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-dolibarr-bridge';

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
							'name'  => 'DOLAPIKEY',
							'value' => 'test-dolapikey',
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
			case '/api/index.php/status':
				return array( 'success' => array( 'code' => 200 ) );

			case '/api/index.php/explorer/swagger.json':
				return array(
					'swagger' => '2.0',
					'paths'   => array(
						'/contacts' => array(
							'post' => array(
								'parameters' => array(
									array(
										'name'     => 'lastname',
										'type'     => 'string',
										'in'       => 'body',
										'required' => true,
									),
									array(
										'name' => 'firstname',
										'type' => 'string',
										'in'   => 'body',
									),
									array(
										'name' => 'email',
										'type' => 'string',
										'in'   => 'body',
									),
								),
							),
						),
					),
				);

			case '/api/index.php/contacts':
				if ( 'POST' === $method ) {
					return array( 'id' => 123456789 );
				}
				return array(
					array(
						'id'        => 1,
						'lastname'  => 'Doe',
						'firstname' => 'John',
						'email'     => 'john.doe@example.com',
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Dolibarr_Addon' ) );
		$this->assertEquals( 'Dolibarr', Dolibarr_Addon::TITLE );
		$this->assertEquals( 'dolibarr', Dolibarr_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Dolibarr_Form_Bridge', Dolibarr_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Dolibarr_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
		$this->assertEquals( '/', $bridge->endpoint );
		$this->assertEquals( '', $bridge->backend );
		$this->assertEquals( '', $bridge->form_id );
		$this->assertEquals( 'POST', $bridge->method );
	}

	/**
	 * Test POST request to create a contact.
	 */
	public function test_post_create_contact() {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'lastname'  => 'Doe',
			'firstname' => 'John',
			'email'     => 'john.doe@example.com',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 123456789, $response['data']['id'] );
	}

	/**
	 * Test GET request to fetch contacts.
	 */
	public function test_get_contacts() {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'lastname', $response['data'][0] );
		$this->assertEquals( 'Doe', $response['data'][0]['lastname'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'dolibarr' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'dolibarr' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( '/api/index.php/contacts', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'dolibarr' );
		$schema = $addon->get_endpoint_schema(
			'/api/index.php/contacts',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'lastname', $field_names );
		$this->assertContains( 'firstname', $field_names );
		$this->assertContains( 'email', $field_names );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'http'  => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'error' => array(
				'code'    => 401,
				'message' => 'Unauthorized: Failed to login to API. No parameter \'HTTP_DOLAPIKEY\' on HTTP header (and no parameter DOLAPIKEY in URL).',
			),
			'debug' => array(
				'source' => 'api_access.class.php:219 at authenticate stage',
				'stages' => array(
					'success' => array( 'get', 'route', 'negotiate' ),
					'failure' => array( 'authenticate', 'message' ),
				),
			),
		);

		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Dolibarr_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}
}
