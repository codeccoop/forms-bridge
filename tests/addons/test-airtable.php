<?php
/**
 * Class AirtableTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Airtable_Form_Bridge;
use FORMS_BRIDGE\Airtable_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Airtable test case.
 */
class AirtableTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-airtable-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://api.airtable.com';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-airtable-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-airtable-bridge';

	/**
	 * Test credential provider.
	 *
	 * @return Credential[]
	 */
	public static function credentials_provider() {
		return array(
			new Credential(
				array(
					'name'         => self::CREDENTIAL_NAME,
					'schema'       => 'Bearer',
					'access_token' => 'test-api-key',
					'expires_at'   => time() + 3600,
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

		$method = $args['method'] ?? 'GET';

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
		if ( 0 === strpos( $path, '/v0/meta/bases' ) ) {
			// Meta bases endpoint.
			if ( '/v0/meta/bases' === $path ) {
				return array(
					'bases' => array(
						array(
							'id'   => 'app123456789',
							'name' => 'Test Base',
						),
						array(
							'id'   => 'app987654321',
							'name' => 'Another Base',
						),
					),
				);
			} elseif ( preg_match( '#^/v0/meta/bases/(app\d+)/tables$#', $path, $matches ) ) {
				// Tables for a specific base.
				return array(
					'tables' => array(
						array(
							'id'     => 'tbl123456789',
							'name'   => 'Contacts',
							'fields' => array(
								array(
									'id'   => 'fld123456789',
									'name' => 'Name',
									'type' => 'singleLineText',
								),
								array(
									'id'   => 'fld987654321',
									'name' => 'Email',
									'type' => 'email',
								),
								array(
									'id'   => 'fld555555555',
									'name' => 'Active',
									'type' => 'checkbox',
								),
								array(
									'id'   => 'fld777777777',
									'name' => 'Score',
									'type' => 'number',
								),
								array(
									'id'      => 'fld888888888',
									'name'    => 'Tags',
									'type'    => 'multipleSelects',
									'options' => array(
										'choices' => array(
											array(
												'name' => 'A',
												'id'   => 't1',
											),
											array(
												'name' => 'B',
												'id'   => 't2',
											),
											array(
												'name' => 'C',
												'id'   => 't3',
											),
										),
									),
								),
								array(
									'id'   => 'fld999999999',
									'name' => 'Summary',
									'type' => 'aiText',
								),
								array(
									'id'   => 'fld2222222222',
									'name' => 'Profile Picture',
									'type' => 'multipleAttachments',
								),
							),
						),
						array(
							'id'     => 'tbl987654321',
							'name'   => 'Projects',
							'fields' => array(
								array(
									'id'      => 'field1234',
									'name'    => 'Name',
									'type'    => 'singleLineText',
									'options' => array(),
								),
							),
						),
					),
				);
			}
		} elseif ( preg_match( '#^/v0/(app\d+)/([^/]+)$#', $path, $matches ) ) {
			// Table data endpoint.
			if ( 'GET' === $method ) {
				// Mock field schema for GET requests.
				return array(
					'records' => array(
						array(
							'id'          => 'rec123456789',
							'createdTime' => '2023-01-01T00:00:00.000Z',
							'fields'      => array(
								array(
									'id'   => 'fld123456789',
									'name' => 'Name',
									'type' => 'singleLineText',
								),
								array(
									'id'   => 'fld987654321',
									'name' => 'Email',
									'type' => 'email',
								),
								array(
									'id'   => 'fld555555555',
									'name' => 'Active',
									'type' => 'checkbox',
								),
								array(
									'id'   => 'fld777777777',
									'name' => 'Score',
									'type' => 'number',
								),
								array(
									'id'   => 'fld888888888',
									'name' => 'Tags',
									'type' => 'multipleSelects',
								),
							),
						),
					),
				);
			} elseif ( 'POST' === $method ) {
				// Mock successful record creation for POST requests.
				return array(
					'records' => array(
						array(
							'id'          => 'rec123456789',
							'fields'      => $body['records'][0]['fields'],
							'createdTime' => '2023-01-01T00:00:00.000Z',
						),
					),
				);
			}
		} elseif ( preg_match( '#^/v0/(app\d+)/([^/]+)/uploadAttachment$#', $path, $matches ) ) {
			if ( $method === 'POST' ) {
				return array(
					'createdTime' => '2022-02-01T21:25:05.663Z',
					'fields'      => array(
						'fld00000000000000' => array(
							array(
								'filename' => 'sample.txt',
								'id'       => 'att00000000000000',
								'size'     => 11,
								'type'     => 'text/plain',
								'url'      => 'https://v5.airtableusercontent.com/v3/u/29/29/1716940800000/ffhiecnieIwxisnIBDSAln/foDeknw_G5CdkdPW1j-U0yUCX9YSaE1EJft3wvXb85pnTY1sKZdYeFvKpsM-fqOa6Bnu5MQVPA_ApINEUXL_E3SAZn6z01VN9Pn9SluhSy4NoakZGapcvl4tuN3jktO2Dt7Ck_gh4oMdsrcV8J-t_A/53m17XmDDHsNtIqzM1PQVnRKutK6damFgNNS5WCaTbI',
							),
						),
					),
					'id'          => 'rec00000000000000',
				);
			}
		}

		// Default empty response.
		return array();
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Airtable_Addon' ) );
		$this->assertEquals( 'Airtable', Airtable_Addon::TITLE );
		$this->assertEquals( 'airtable', Airtable_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Airtable_Form_Bridge', Airtable_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Airtable_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertFalse( $bridge->is_valid );
	}

	/**
	 * Test POST request to create a record.
	 */
	public function test_post_create_record() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'Name'   => 'John Doe',
			'Email'  => 'john.doe@example.com',
			'Score'  => 99,
			'Tags'   => array( 'A', 'B' ),
			'Active' => true,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'rec123456789', $response['data']['records'][0]['id'] );
		$this->assertEquals( 'John Doe', $response['data']['records'][0]['fields']['Name'] );
	}

	/**
	 * Test POST request to create a record with uploads.
	 */
	public function test_post_create_record_with_uploads() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'Name'            => 'John Doe',
			'Email'           => 'john.doe@example.com',
			'Score'           => 99,
			'Tags'            => array( 'A', 'B' ),
			'Active'          => true,
			'Profile Picture' => 'file',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'rec123456789', $response['data']['records'][0]['id'] );
		$this->assertEquals( 'John Doe', $response['data']['records'][0]['fields']['Name'] );
	}

	/**
	 * Test GET request to fetch table schema.
	 */
	public function test_get_table_schema() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'records', $response['data'] );
		$this->assertCount( 1, $response['data']['records'] );
		$this->assertArrayHasKey( 'fields', $response['data']['records'][0] );
		$this->assertCount( 5, $response['data']['records'][0]['fields'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'airtable' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon fetch method to get tables.
	 */
	public function test_addon_fetch_tables() {
		$addon    = Addon::addon( 'airtable' );
		$response = $addon->fetch( null, self::BACKEND_NAME );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'tables', $response['data'] );
		$this->assertCount( 4, $response['data']['tables'] ); // 2 bases × 2 tables each

		// Check that tables have the expected structure.
		$table = $response['data']['tables'][0];
		$this->assertArrayHasKey( 'base_id', $table );
		$this->assertArrayHasKey( 'base_name', $table );
		$this->assertArrayHasKey( 'label', $table );
		$this->assertArrayHasKey( 'id', $table );
		$this->assertArrayHasKey( 'endpoint', $table );
		$this->assertEquals( 'Test Base/Contacts', $table['label'] );
		$this->assertEquals( '/v0/app123456789/Contacts', $table['endpoint'] );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'airtable' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertNotEmpty( $endpoints );
		$this->assertContains( '/v0/app123456789/Contacts', $endpoints );
		$this->assertContains( '/v0/app123456789/Projects', $endpoints );
		$this->assertContains( '/v0/app987654321/Contacts', $endpoints );
		$this->assertContains( '/v0/app987654321/Projects', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method for POST.
	 */
	public function test_addon_get_endpoint_schema_post() {
		$addon  = Addon::addon( 'airtable' );
		$schema = $addon->get_endpoint_schema(
			'/v0/app123456789/Contacts',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Check that schema contains expected fields.
		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'Name', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertContains( 'Active', $field_names );
		$this->assertContains( 'Score', $field_names );
		$this->assertContains( 'Tags', $field_names );
		$this->assertContains( 'Profile Picture', $field_names );

		// Check field types.
		$schema_map = array();
		foreach ( $schema as $field ) {
			$schema_map[ $field['name'] ] = $field['schema']['type'];
		}

		$this->assertEquals( 'string', $schema_map['Name'] );
		$this->assertEquals( 'string', $schema_map['Email'] );
		$this->assertEquals( 'boolean', $schema_map['Active'] );
		$this->assertEquals( 'number', $schema_map['Score'] );
		$this->assertEquals( 'array', $schema_map['Tags'] );
		$this->assertEquals( 'file', $schema_map['Profile Picture'] );
	}

	/**
	 * Test addon get_endpoint_schema method for non-POST methods.
	 */
	public function test_addon_get_endpoint_schema_non_post() {
		$addon  = Addon::addon( 'airtable' );
		$schema = $addon->get_endpoint_schema(
			'/v0/app123456789/Contacts',
			self::BACKEND_NAME,
			'GET'
		);

		// Should return empty array for non-POST methods.
		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
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
				'type'    => 'AUTHENTICATION_REQUIRED',
				'message' => 'Authentication required',
			),
		);

		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
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
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test authorization header transformation.
	 */
	public function test_authorization_header_transformation() {
		$bridge = new Airtable_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/v0/app123456789/Contacts',
				'method'   => 'GET',
			)
		);

		$bridge->submit();

		// Check that the request was made.
		$this->assertNotNull( self::$request );

		// Verify the Authorization header was transformed.
		$headers = self::$request['args']['headers'] ?? array();
		$this->assertArrayHasKey( 'Authorization', $headers );
		$this->assertStringContainsString( 'Bearer', $headers['Authorization'] );
		$this->assertStringContainsString( 'test-api-key', $headers['Authorization'] );
	}

	/**
	 * Test field filtering in schema - should exclude certain field types.
	 */
	public function test_field_filtering_in_schema() {
		$addon  = Addon::addon( 'airtable' );
		$schema = $addon->get_endpoint_schema(
			'/v0/app123456789/Contacts',
			self::BACKEND_NAME,
			'POST'
		);

		// Should only include the singleLineText field, not the aiText field.
		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'Name', $field_names );
		$this->assertNotContains( 'Summary', $field_names );
		$this->assertCount( 6, $schema );
	}
}
