<?php
/**
 * Class ListmonkTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Listmonk_Form_Bridge;
use FORMS_BRIDGE\Listmonk_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Listmonk test case.
 */
class ListmonkTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-listmonk-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://listmonk.example.coop';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-listmonk-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-listmonk-bridge';

	/**
	 * Test credential provider.
	 *
	 * @return Credential[]
	 */
	public static function credentials_provider() {
		return array(
			new Credential(
				array(
					'name'          => self::CREDENTIAL_NAME,
					'schema'        => 'Token',
					'client_id'     => 'test-client-id',
					'client_secret' => 'test-client-secret',
				)
			),
		);
	}

	/**
	 * Test backend provider.
	 *
	 * @return Backend[]
	 */
	public static function backends_provider() {
		return array(
			new Backend(
				array(
					'name'       => self::BACKEND_NAME,
					'base_url'   => self::BACKEND_URL,
					'credential' => self::CREDENTIAL_NAME,
					'headers'    => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/json',
						),
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
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
			case '/api/lists':
				return array(
					'data' => array(
						'results' => array(
							array(
								'id'   => 1,
								'name' => 'Test List',
							),
						),
					),
				);

			case '/api/subscribers':
				if ( 'POST' === $method ) {
					return array(
						'data' => array(
							'id'     => 123456789,
							'email'  => $body['email'],
							'name'   => $body['name'],
							'status' => 'enabled',
						),
					);
				}
				return array(
					'data' => array(
						'results' => array(
							array(
								'id'     => 1,
								'email'  => 'john.doe@example.com',
								'name'   => 'John Doe',
								'status' => 'enabled',
							),
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Listmonk_Addon' ) );
		$this->assertEquals( 'Listmonk', Listmonk_Addon::TITLE );
		$this->assertEquals( 'listmonk', Listmonk_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Listmonk_Form_Bridge', Listmonk_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Listmonk_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Listmonk_Form_Bridge(
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
	 * Test POST request to create a subscriber.
	 */
	public function test_post_create_subscriber() {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'email' => 'john.doe@example.com',
			'name'  => 'John Doe',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 123456789, $response['data']['data']['id'] );
	}

	/**
	 * Test GET request to fetch subscribers.
	 */
	public function test_get_subscribers() {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'results', $response['data']['data'] );
		$this->assertEquals( 'john.doe@example.com', $response['data']['data']['results'][0]['email'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'listmonk' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'listmonk' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( '/api/subscribers', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'listmonk' );
		$schema = $addon->get_endpoint_schema(
			'/api/subscribers',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'name', $field_names );
		$this->assertContains( 'status', $field_names );
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
			'message' => 'Invalid authentication token',
		);

		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
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
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/api/subscribers',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test duplicate subscriber handling.
	 */
	public function test_duplicate_subscriber_handling() {
		self::$mock_response = array(
			'http'    => array(
				'code'    => 409,
				'message' => 'Conflict',
			),
			'message' => 'Subscriber already exists',
		);

		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array( 'email' => 'duplicate@example.com' ) );

		// Should not return WP_Error for DUPLICATE_SUBSCRIBER
		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test authorization header transformation.
	 */
	public function test_authorization_header_transformation() {
		$bridge = new Listmonk_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/subscribers',
				'method'   => 'GET',
			)
		);

		$bridge->submit();

		// Check that the request was made
		$this->assertNotNull( self::$request );

		// Verify the Authorization header was transformed
		$headers = self::$request['args']['headers'] ?? array();
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertStringContainsString( 'token', $headers['Authorization'] );
	}
}
