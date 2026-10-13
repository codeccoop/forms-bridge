<?php
/**
 * Class MauticTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Mautic_Form_Bridge;
use FORMS_BRIDGE\Mautic_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Mautic test case.
 */
class MauticTest extends WP_UnitTestCase {

	/**
	 * Handles the intercepted http requests data.
	 *
	 * @var array
	 */
	private static $requests;

	/**
	 * Handles the mock responses to return, keyed by request path.
	 *
	 * @var array
	 */
	private static $mock_responses;

	/**
	 * Holds the mocked backend name.
	 *
	 * @var string
	 */
	private const BACKEND_NAME = 'test-mautic-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://mautic.example.coop';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-mautic-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-mautic-bridge';

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
					'client_id'     => 'test-user',
					'client_secret' => 'test-password',
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
		$parsed_url = wp_parse_url( $url );
		$path       = $parsed_url['path'] ?? '';
		$method     = $args['method'] ?? 'POST';

		$body = array();
		if ( ! empty( $args['body'] ) ) {
			$body = is_string( $args['body'] ) ? json_decode( $args['body'], true ) : $args['body'];
		}

		self::$requests[] = array(
			'args'   => $args,
			'url'    => $url,
			'path'   => $path,
			'method' => $method,
			'body'   => $body,
		);

		$http = array(
			'code'    => 200,
			'message' => 'OK',
		);

		if ( isset( self::$mock_responses[ $path ] ) ) {
			$response_body = self::$mock_responses[ $path ];
			$http          = $response_body['http'] ?? $http;
			unset( $response_body['http'] );
		} else {
			$response_body = self::get_mock_response( $path, $body );
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
	 * @param string $path API endpoint path.
	 * @param array  $body Request body.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $path, $body ) {
		if ( preg_match( '/^\/api\/segments\/\d+\/contact\/\d+\/add$/', $path ) ) {
			return array( 'success' => 1 );
		}

		switch ( $path ) {
			case '/api/contacts/new':
				return array(
					'contact' => array(
						'id'     => 47,
						'fields' => array(
							'all' => array(
								'id'    => 47,
								'email' => $body['email'] ?? null,
							),
						),
					),
				);
			case '/api/contacts':
				return array(
					'total'    => '1',
					'contacts' => array(
						'47' => array(
							'id' => 47,
						),
					),
				);
			case '/api/segments':
				return array(
					'total' => 2,
					'lists' => array(
						'3' => array(
							'id'   => 3,
							'name' => 'Newsletter',
						),
						'5' => array(
							'id'   => 5,
							'name' => 'Leads',
						),
					),
				);
			case '/api/fields/contact':
				return array(
					'total'  => 4,
					'fields' => array(
						array(
							'id'          => 1,
							'alias'       => 'email',
							'type'        => 'email',
							'isRequired'  => false,
							'isPublished' => true,
						),
						array(
							'id'          => 2,
							'alias'       => 'firstname',
							'type'        => 'text',
							'isRequired'  => true,
							'isPublished' => true,
						),
						array(
							'id'          => 3,
							'alias'       => 'points',
							'type'        => 'number',
							'isRequired'  => false,
							'isPublished' => true,
						),
						array(
							'id'          => 4,
							'alias'       => 'legacy',
							'type'        => 'text',
							'isRequired'  => false,
							'isPublished' => false,
						),
					),
				);
			default:
				return array();
		}
	}

	/**
	 * Returns the intercepted requests to the given path.
	 *
	 * @param string $path Request path.
	 *
	 * @return array
	 */
	private static function requests_to( $path ) {
		return array_values(
			array_filter(
				self::$requests,
				function ( $request ) use ( $path ) {
					return $request['path'] === $path;
				}
			)
		);
	}

	/**
	 * Returns the contact email used on the payloads.
	 *
	 * @return string
	 */
	private static function email() {
		return sprintf( '%s@%s', 'john.doe', 'example.coop' );
	}

	/**
	 * Returns a contact creation bridge.
	 *
	 * @return Mautic_Form_Bridge
	 */
	private static function contacts_bridge() {
		return new Mautic_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/contacts/new',
				'method'   => 'POST',
			)
		);
	}

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		self::$requests       = array();
		self::$mock_responses = array();

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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Mautic_Addon' ) );
		$this->assertEquals( 'Mautic', Mautic_Addon::TITLE );
		$this->assertEquals( 'mautic', Mautic_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Mautic_Form_Bridge', Mautic_Addon::BRIDGE );
		$this->assertInstanceOf( Mautic_Addon::class, Addon::addon( 'mautic' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = self::contacts_bridge();

		$this->assertTrue( $bridge->is_valid );
		$this->assertEquals( 'mautic', $bridge->addon );
	}

	/**
	 * Test contact creation with basic auth.
	 */
	public function test_post_create_contact() {
		$response = self::contacts_bridge()->submit(
			array(
				'email'     => self::email(),
				'firstname' => 'John',
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals( 47, $response['data']['contact']['id'] );

		$this->assertCount( 1, self::$requests );
		$request = self::$requests[0];

		$this->assertEquals( self::BACKEND_URL . '/api/contacts/new', $request['url'] );
		$this->assertEquals( self::email(), $request['body']['email'] );
		$this->assertEquals(
			'Basic ' . base64_encode( 'test-user:test-password' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$request['args']['headers']['Authorization']
		);
	}

	/**
	 * Test that the contact is added to each payload segment after its creation.
	 */
	public function test_post_create_contact_with_segments() {
		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => array( 3, '5', 3 ),
			)
		);

		$this->assertFalse( is_wp_error( $response ) );

		$contact_request = self::requests_to( '/api/contacts/new' )[0];
		$this->assertArrayNotHasKey( 'segments', $contact_request['body'] );

		$this->assertEquals(
			array(
				'/api/contacts/new',
				'/api/segments/3/contact/47/add',
				'/api/segments/5/contact/47/add',
			),
			array_column( self::$requests, 'path' )
		);
	}

	/**
	 * Test that the template custom fields reach the API as contact segments.
	 */
	public function test_custom_fields_segments() {
		$bridge = new Mautic_Form_Bridge(
			array(
				'name'          => self::BRIDGE_NAME,
				'backend'       => self::BACKEND_NAME,
				'endpoint'      => '/api/contacts/new',
				'method'        => 'POST',
				'custom_fields' => array(
					array(
						'name'  => 'segments[0]',
						'value' => '3',
					),
					array(
						'name'  => 'segments[1]',
						'value' => '5',
					),
				),
				'mutations'     => array(
					array(
						array(
							'from' => 'segments[0]',
							'to'   => 'segments[0]',
							'cast' => 'integer',
						),
						array(
							'from' => 'segments[1]',
							'to'   => 'segments[1]',
							'cast' => 'integer',
						),
					),
				),
			)
		);

		$payload = $bridge->add_custom_fields( array( 'email' => self::email() ) );
		$payload = $bridge->apply_mutation( $payload );

		$this->assertSame( array( 3, 5 ), $payload['segments'] );

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals(
			array(
				'/api/contacts/new',
				'/api/segments/3/contact/47/add',
				'/api/segments/5/contact/47/add',
			),
			array_column( self::$requests, 'path' )
		);
	}

	/**
	 * Test that comma separated segments are supported.
	 */
	public function test_post_create_contact_with_csv_segments() {
		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => '3, 5',
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertCount( 1, self::requests_to( '/api/segments/3/contact/47/add' ) );
		$this->assertCount( 1, self::requests_to( '/api/segments/5/contact/47/add' ) );
	}

	/**
	 * Test that empty segments does not perform segment requests.
	 */
	public function test_post_create_contact_without_segments() {
		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => array(),
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals( array( '/api/contacts/new' ), array_column( self::$requests, 'path' ) );
		$this->assertArrayNotHasKey( 'segments', self::$requests[0]['body'] );
	}

	/**
	 * Test that segments field is not removed from other endpoints payloads.
	 */
	public function test_segments_untouched_on_other_endpoints() {
		$bridge = new Mautic_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/companies/new',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit(
			array(
				'companyname' => 'Codec',
				'segments'    => array( 3 ),
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertCount( 1, self::$requests );
		$this->assertEquals( array( 3 ), self::$requests[0]['body']['segments'] );
	}

	/**
	 * Test segment error status codes are returned as errors.
	 */
	public function test_segment_error_response() {
		self::$mock_responses['/api/segments/3/contact/47/add'] = array(
			'http'   => array(
				'code'    => 404,
				'message' => 'Not Found',
			),
			'errors' => array(
				array(
					'code'    => 404,
					'message' => 'Item was not found.',
				),
			),
		);

		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => array( 3 ),
			)
		);

		$this->assertTrue( is_wp_error( $response ) );
	}

	/**
	 * Test unsuccessful segment responses are returned as errors.
	 */
	public function test_segment_unsuccessful_response() {
		self::$mock_responses['/api/segments/3/contact/47/add'] = array( 'success' => 0 );

		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => array( 3 ),
			)
		);

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'mautic_segments_error', $response->get_error_code() );
	}

	/**
	 * Test contact creation errors skip segment requests.
	 */
	public function test_error_response_handling() {
		self::$mock_responses['/api/contacts/new'] = array(
			'http'   => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'errors' => array(
				array(
					'code'    => 401,
					'message' => 'API authorization denied.',
				),
			),
		);

		$response = self::contacts_bridge()->submit(
			array(
				'email'    => self::email(),
				'segments' => array( 3 ),
			)
		);

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertCount( 1, self::$requests );
	}

	/**
	 * Test errors reported with a success status code.
	 */
	public function test_error_body_with_success_status() {
		self::$mock_responses['/api/contacts/new'] = array(
			'errors' => array(
				array(
					'code'    => 400,
					'message' => 'email: A valid email is required.',
				),
			),
		);

		$response = self::contacts_bridge()->submit( array( 'email' => 'invalid' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'mautic_api_error', $response->get_error_code() );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Mautic_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/api/contacts/new',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array( 'segments' => array( 3 ) ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test that collections indexed by ID are returned as lists.
	 */
	public function test_get_segments_as_list() {
		$response = Addon::addon( 'mautic' )->fetch( '/api/segments', self::BACKEND_NAME );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertTrue( wp_is_numeric_array( $response['data']['lists'] ) );

		$finger = new FORMS_BRIDGE\JSON_Finger( $response['data'] );
		$this->assertEquals( array( 3, 5 ), $finger->get( 'lists[].id' ) );
		$this->assertEquals( array( 'Newsletter', 'Leads' ), $finger->get( 'lists[].name' ) );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$this->assertTrue( Addon::addon( 'mautic' )->ping( self::BACKEND_NAME ) );

		$this->assertEquals( '/api/contacts', self::$requests[0]['path'] );
		$this->assertEquals( 'GET', self::$requests[0]['method'] );
	}

	/**
	 * Test addon ping method on error.
	 */
	public function test_addon_ping_error() {
		self::$mock_responses['/api/contacts'] = array(
			'http' => array(
				'code'    => 403,
				'message' => 'Forbidden',
			),
		);

		$this->assertFalse( Addon::addon( 'mautic' )->ping( self::BACKEND_NAME ) );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon = Addon::addon( 'mautic' );

		$this->assertContains( '/api/contacts/new', $addon->get_endpoints( self::BACKEND_NAME ) );
		$this->assertContains( '/api/segments', $addon->get_endpoints( self::BACKEND_NAME ) );
		$this->assertEquals(
			array( '/api/contacts/new', '/api/companies/new' ),
			$addon->get_endpoints( self::BACKEND_NAME, 'POST' )
		);
		$this->assertNotContains( '/api/contacts/new', $addon->get_endpoints( self::BACKEND_NAME, 'get' ) );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$schema = Addon::addon( 'mautic' )->get_endpoint_schema(
			'/api/contacts/new',
			self::BACKEND_NAME,
			'POST'
		);

		$fields = array();
		foreach ( $schema as $field ) {
			$fields[ $field['name'] ] = $field;
		}

		$this->assertArrayHasKey( 'email', $fields );
		$this->assertArrayHasKey( 'firstname', $fields );
		$this->assertTrue( $fields['firstname']['required'] );
		$this->assertEquals( 'number', $fields['points']['schema']['type'] );
		$this->assertArrayNotHasKey( 'legacy', $fields );
		$this->assertArrayHasKey( 'segments', $fields );
		$this->assertArrayHasKey( 'tags', $fields );

		$this->assertEquals( '/api/fields/contact', self::$requests[0]['path'] );
	}

	/**
	 * Test addon get_endpoint_schema method for unknown endpoints.
	 */
	public function test_addon_get_endpoint_schema_unknown() {
		$addon = Addon::addon( 'mautic' );

		$this->assertEquals( array(), $addon->get_endpoint_schema( '/api/unknown', self::BACKEND_NAME, 'POST' ) );
		$this->assertEquals( array(), $addon->get_endpoint_schema( '/api/contacts/new', self::BACKEND_NAME, 'GET' ) );
		$this->assertEmpty( self::$requests );
	}

	/**
	 * Test that the template segments are expanded as integer custom fields.
	 */
	public function test_template_data_segments() {
		$data = array(
			'bridge' => array(
				'custom_fields' => array(
					array(
						'name'  => 'segments',
						'value' => array( '3', '5' ),
					),
					array(
						'name'  => 'tags',
						'value' => 'web',
					),
				),
				'mutations'     => array( array() ),
			),
		);

		$data = apply_filters( 'forms_bridge_template_data', $data, 'mautic-contacts' );

		$this->assertEquals(
			array(
				array(
					'name'  => 'tags',
					'value' => 'web',
				),
				array(
					'name'  => 'segments[0]',
					'value' => 3,
				),
				array(
					'name'  => 'segments[1]',
					'value' => 5,
				),
			),
			$data['bridge']['custom_fields']
		);

		$this->assertEquals( 'segments[1]', $data['bridge']['mutations'][0][1]['to'] );
		$this->assertEquals( 'integer', $data['bridge']['mutations'][0][1]['cast'] );
	}

	/**
	 * Test that the contacts template is registered and valid.
	 */
	public function test_contacts_template() {
		$data         = include FORMS_BRIDGE_ADDONS_DIR . '/mautic/templates/contacts.php';
		$data['name'] = 'contacts';

		$template = new FORMS_BRIDGE\Form_Bridge_Template( $data, 'mautic' );

		$this->assertTrue( $template->is_valid );
		$this->assertEquals( '/api/contacts/new', $template->bridge['endpoint'] );
		$this->assertEquals( 'Basic', $template->credential['schema'] );
	}
}
