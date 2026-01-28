<?php
/**
 * Class BiginTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Bigin_Form_Bridge;
use FORMS_BRIDGE\Bigin_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Bigin addon test case.
 */
class BiginTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-bigin-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://www.zohoapis.com';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-bigin-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-bigin-bridge';

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
					'schema'        => 'OAuth',
					'client_id'     => 'test-client-id',
					'client_secret' => 'test-client-secret',
					'region'        => 'zoho.eu',
					'access_token'  => 'test-access-token',
					'refresh_token' => 'test-refresh-token',
					'expires_at'    => time() + 3600,
					'scope'         => 'ZohoBigin.modules.ALL,ZohoBigin.settings.modules.READ,ZohoBigin.settings.layouts.READ,ZohoBigin.users.READ',
					'oauth_url'     => 'https://accounts.{region}/oauth/v2',
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
			'message' => 'SUCCESS',
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
			case '/bigin/v2/users':
				return array(
					'users' => array(
						array(
							'id'        => '123456789',
							'email'     => 'user@example.com',
							'full_name' => 'Test User',
						),
					),
				);

			case '/bigin/v2/settings/modules':
				return array(
					'modules' => array(
						array(
							'api_name' => 'Contacts',
							'name'     => 'Contacts',
						),
						array(
							'api_name' => 'Leads',
							'name'     => 'Leads',
						),
						array(
							'api_name' => 'Accounts',
							'name'     => 'Accounts',
						),
						array(
							'api_name' => 'Deals',
							'name'     => 'Deals',
						),
					),
				);

			case '/bigin/v2/settings/layouts':
				return array(
					'layouts' => array(
						array(
							'sections' => array(
								array(
									'fields' => array(
										array(
											'api_name'  => 'First_Name',
											'json_type' => 'string',
										),
										array(
											'api_name'  => 'Last_Name',
											'json_type' => 'string',
										),
										array(
											'api_name'  => 'Email',
											'json_type' => 'string',
										),
										array(
											'api_name'  => 'Phone',
											'json_type' => 'string',
										),
										array(
											'api_name'  => 'Amount',
											'json_type' => 'double',
										),
									),
								),
							),
						),
					),
				);

			case '/bigin/v2/Contacts':
				if ( 'POST' === $method ) {
					return array(
						'data' => array(
							array(
								'code'    => 'SUCCESS',
								'details' => array( 'id' => '123456789' ),
								'message' => 'record added',
								'status'  => 'success',
							),
						),
					);
				}
				return array(
					'data' => array(
						array(
							'First_Name' => 'John',
							'Last_Name'  => 'Doe',
							'Email'      => 'john.doe@example.com',
						),
					),
				);

			case '/bigin/v2/Deals':
				if ( 'POST' === $method ) {
					return array(
						'data' => array(
							array(
								'code'    => 'SUCCESS',
								'details' => array(
									'id' => '987654321',
								),
								'message' => 'record added',
								'status'  => 'success',
							),
						),
					);
				}

				return array(
					'data' => array(
						array(
							'Deal_Name' => 'Test Deal',
							'Amount'    => 1000.00,
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Bigin_Addon' ) );
		$this->assertEquals( 'Bigin', Bigin_Addon::TITLE );
		$this->assertEquals( 'bigin', Bigin_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Bigin_Form_Bridge', Bigin_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Bigin_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'POST',
			),
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			),
		);

		$this->assertFalse( $bridge->is_valid );
	}

	/**
	 * Test POST request to create a contact.
	 */
	public function test_post_create_contact() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'POST',
			),
			'bigin'
		);

		$payload = array(
			'data' => array(
				array(
					'First_Name' => 'John',
					'Last_Name'  => 'Doe',
					'Email'      => 'john.doe@example.com',
				),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'SUCCESS', $response['data']['data'][0]['code'] );
		$this->assertEquals( 'record added', $response['data']['data'][0]['message'] );
	}

	/**
	 * Test POST request to create a deal.
	 */
	public function test_post_create_deal() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Deals',
				'method'   => 'POST',
			),
			'bigin'
		);

		$payload = array(
			'data' => array(
				array(
					'Deal_Name' => 'Test Deal',
					'Amount'    => 1000.00,
				),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'SUCCESS', $response['data']['data'][0]['code'] );
		$this->assertEquals( 'record added', $response['data']['data'][0]['message'] );
	}

	/**
	 * Test GET request to fetch contacts.
	 */
	public function test_get_contacts() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'GET',
			),
			'bigin'
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'First_Name', $response['data']['data'][0] );
		$this->assertEquals( 'John', $response['data']['data'][0]['First_Name'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon  = Addon::addon( 'bigin' );
		$result = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $result );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'bigin' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( '/bigin/v2/Contacts', $endpoints );
		$this->assertContains( '/bigin/v2/Leads', $endpoints );
		$this->assertContains( '/bigin/v2/Accounts', $endpoints );
		$this->assertContains( '/bigin/v2/Deals', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'bigin' );
		$schema = $addon->get_endpoint_schema(
			'/bigin/v2/Contacts',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'First_Name', $field_names );
		$this->assertContains( 'Last_Name', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertContains( 'Phone', $field_names );
		$this->assertContains( 'Amount', $field_names );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'http'    => array(
				'code'    => 401,
				'message' => 'UNAUTHORIZED',
			),
			'code'    => 'INVALID_TOKEN',
			'message' => 'Invalid authentication token',
		);

		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'POST',
			),
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'POST',
			),
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test duplicate data handling.
	 */
	public function test_duplicate_data_handling() {
		self::$mock_response = array(
			'data' => array(
				array(
					'code'    => 'DUPLICATE_DATA',
					'message' => 'Duplicate record found',
				),
			),
		);

		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'POST',
			),
		);

		$response = $bridge->submit(
			array(
				'data' => array(
					array(
						'Email' => 'duplicate@example.com',
					),
				),
			)
		);

		// Should not return WP_Error for DUPLICATE_DATA
		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test authorization header transformation.
	 */
	public function test_authorization_header_transformation() {
		$bridge = new Bigin_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/bigin/v2/Contacts',
				'method'   => 'GET',
			),
		);

		$bridge->submit();

		// Check that the request was made
		$this->assertNotNull( self::$request );

		// Verify the Authorization header was transformed
		$headers = self::$request['args']['headers'] ?? array();
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertStringContainsString( 'Zoho-oauthtoken', $headers['Authorization'] );
	}
}
