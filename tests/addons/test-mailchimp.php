<?php
/**
 * Class MailchimpTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Mailchimp_Form_Bridge;
use FORMS_BRIDGE\Mailchimp_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Mailchimp test case.
 */
class MailchimpTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-mailchimp-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://us1.api.mailchimp.com';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-mailchimp-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-mailchimp-bridge';

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
					'schema'        => 'Basic',
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

		parse_str( $parsed_url['query'] ?? '', $query );

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
			$response_body = self::get_mock_response( $method, $path, $body, $query );
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
	 * @param array  $query Search query.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $method, $path, $body, $query ) {
		switch ( $path ) {
			case '/developer/spec/marketing.json':
				return array(
					'swagger' => '2.0',
					'paths'   => array(
						'/lists/{list_id}'          => array(
							'get'  => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),

								),
							),
							'post' => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),
								),
							),
						),
						'/lists/{list_id}/members'  => array(
							'get'  => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),
								),
							),
							'post' => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),
								),
							),
						),
						'/lists/{list_id}/segments' => array(
							'get'  => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),
								),
							),
							'post' => array(
								'parameters' => array(
									array(
										'name' => 'list_id',
										'type' => 'string',
										'in'   => 'path',
									),
									array(
										'name' => 'skip_merge_validation',
										'type' => 'boolean',
										'in'   => 'query',
									),
								),
							),
						),
					),
				);

			case '/3.0/lists':
				return array(
					'lists' => array(
						array(
							'id'   => '123456789',
							'name' => 'Test List',
						),
					),
				);

			case '/3.0/lists/123456789/members':
				if ( 'POST' === $method ) {
					return array(
						'id'            => '987654321',
						'email_address' => $body['email_address'],
						'status'        => 'subscribed',
					);
				}

				return array(
					'members' => array(
						array(
							'id'            => '987654321',
							'email_address' => 'john.doe@example.com',
							'status'        => 'subscribed',
						),
					),
				);

			case '/3.0/search-members':
				return array(
					'exact_matches' => array(
						'members' => array(
							array(
								'id'            => '987654321',
								'email_address' => $query['query'],
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Mailchimp_Addon' ) );
		$this->assertEquals( 'Mailchimp', Mailchimp_Addon::TITLE );
		$this->assertEquals( 'mailchimp', Mailchimp_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Mailchimp_Form_Bridge', Mailchimp_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Mailchimp_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
		$this->assertEquals( '/', $bridge->endpoint );
		$this->assertEquals( '', $bridge->form_id );
		$this->assertEquals( '', $bridge->backend );
		$this->assertEquals( 'POST', $bridge->method );
	}

	/**
	 * Test POST request to create a member.
	 */
	public function test_post_create_member() {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'email_address' => 'john.doe@example.com',
			'status'        => 'subscribed',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( '987654321', $response['data']['id'] );
	}

	/**
	 * Test GET request to fetch members.
	 */
	public function test_get_members() {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'members', $response['data'] );
		$this->assertEquals( 'john.doe@example.com', $response['data']['members'][0]['email_address'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'mailchimp' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'mailchimp' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( '/3.0/lists/{list_id}/members', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'mailchimp' );
		$schema = $addon->get_endpoint_schema(
			'/3.0/lists/123456789/members',
			self::BACKEND_NAME,
			'GET'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'skip_merge_validation', $field_names );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'http'     => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'title'    => 'API Key Invalid',
			'status'   => 401,
			'detail'   => 'Your request did not include an API key.',
			'type'     => 'https://mailchimp.com/developer/marketing/docs/errors/',
			'code'     => 'INVALID_TOKEN',
			'instance' => '1234abcd-1234-abcd-1234abcd',
		);

		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
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
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test duplicate member handling.
	 */
	public function test_duplicate_member_handling() {
		self::$mock_response = array(
			'http'   => array(
				'code'    => 400,
				'message' => 'Bad Request',
			),
			'title'  => 'Member Exists',
			'status' => 400,
			'detail' => 'member@example.com has already subscribed to the list',
		);

		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array( 'email_address' => 'member@example.com' ) );

		// Should not return WP_Error for Member Exists
		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test authorization header transformation.
	 */
	public function test_authorization_header_transformation() {
		$bridge = new Mailchimp_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/3.0/lists/123456789/members',
				'method'   => 'GET',
			)
		);

		$bridge->submit();

		// Check that the request was made
		$this->assertNotNull( self::$request );

		// Verify the Authorization header was transformed
		$headers = self::$request['args']['headers'] ?? array();
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertStringContainsString( 'Basic', $headers['Authorization'] );
	}
}
