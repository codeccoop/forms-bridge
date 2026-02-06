<?php
/**
 * Class HoldedTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Holded_Form_Bridge;
use FORMS_BRIDGE\Holded_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Holded test case.
 */
class HoldedTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-holded-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://api.holded.com';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-holded-bridge';

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
							'name'  => 'key',
							'value' => 'test-holded-api-key',
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
		switch ( $path ) {
			case '/api/invoicing/v1/contacts':
				if ( 'POST' === $method ) {
					return array(
						'status' => 1,
						'info'   => 'Created',
						'id'     => '123456789',
					);
				}

				return array(
					array(
						'id'       => '123456789',
						'name'     => 'John Doe',
						'email'    => 'john.doe@example.coop',
						'phone'    => '+1234567890',
						'isPerson' => true,
					),
				);

			case '/api/invoicing/v1/documents/invoices':
				if ( 'POST' === $method ) {
					return array(
						'status'    => 1,
						'info'      => 'Created',
						'id'        => '987654321',
						'contactId' => $body['contactId'],
					);
				}

				return array(
					array(
						'id'        => '987654321',
						'docNumber' => 'INV-001',
						'contact'   => '123456789',
						'date'      => '2024-01-01',
						'dueDate'   => '2024-01-31',
						'tax'       => 234.61,
						'subtotal'  => 1117.18,
						'discount'  => 0,
						'total'     => 1351.79,
						'products'  => array(
							array(
								'name'     => 'Product 1',
								'sku'      => 'product-1',
								'tax'      => 21,
								'subtotal' => 100,
								'discount' => 10,
							),
						),
					),
				);

			case '/api/crm/v1/leads':
				if ( 'POST' === $method ) {
					return array(
						'status' => 1,
						'info'   => 'Created',
						'id'     => '456789123',
					);
				}

				return array(
					array(
						'id'         => '456789123',
						'name'       => 'John Doe Opportunity',
						'funnelId'   => '5ab13e373697ac00e305333b',
						'stageId'    => '5ab13e373697ac00e3053336',
						'contactId'  => '123456789',
						'person'     => 1,
						'personName' => 'John Doe',
						'value'      => 4000,
						'potential'  => 100,
						'dueDate'    => 1521646788,
						'createdAt'  => 1521646788,
					),
				);

			case '/api/projects/v1/projects':
				if ( 'POST' === $method ) {
					return array(
						'id'     => '789123456',
						'info'   => 'Created',
						'status' => 1,
					);
				}

				return array(
					array(
						'id'     => 789123456,
						'name'   => 'Website Redesign',
						'desc'   => 'Redesign company website',
						'status' => 2,
						'tags'   => array( 'A', 'B' ),
					),
				);

			case '/holded/api-next/v2/branches/1.0/reference/list-contacts-1':
				return array(
					'data' => array(
						'api' => array(
							'schema' => array(
								'openapi' => '3.0.1',
								'paths'   => array(
									'/contacts'            => array(
										'post' => array(
											'requestBody' => array(
												'content' => array(
													'application/json' => array(
														'schema' => array(
															'type'       => 'object',
															'properties' => array(
																'name'     => array( 'type' => 'string' ),
																'email'    => array( 'type' => 'string' ),
																'phone'    => array( 'type' => 'string' ),
																'mobile'   => array( 'type' => 'string' ),
																'isPerson' => array( 'type' => 'boolean' ),
															),
														),
													),
												),
											),
										),
									),
									'/documents/{docType}' => array(
										'parameters' => array(
											array(
												'name'     => 'docType',
												'in'       => 'path',
												'schema'   => array( 'type' => 'string' ),
												'required' => true,
											),
										),
										'post'       => array(
											'requestBody' => array(
												'content' => array(
													'application/json' => array(
														'schema' => array(
															'type'       => 'object',
															'properties' => array(
																'notes'        => array( 'type' => 'string' ),
																'contactId'    => array( 'type' => 'string' ),
																'contactName'  => array( 'type' => 'string' ),
																'contactEmail' => array( 'type' => 'string' ),
																'date'         => array( 'type' => 'integer' ),
																'items'        => array(
																	'type' => 'array',
																	'items' => array(
																		'type'       => 'object',
																		'properties' => array(
																			'name'     => array( 'type' => 'string' ),
																			'desc'     => array( 'type' => 'string' ),
																			'sku'      => array( 'type' => 'string' ),
																			'tax'      => array( 'type' => 'integer' ),
																			'subtotal' => array( 'type' => 'number' ),
																			'discount' => array( 'type' => 'number' ),
																		),
																	),
																),
															),
															'required' => array( 'date' ),
														),
													),
												),
											),
										),
									),
								),
							),
						),
					),
				);

			case '/holded/api-next/v2/branches/1.0/reference/list-leads-1':
				return array(
					'data' => array(
						'api' => array(
							'schema' => array(
								'openapi' => '3.0.1',
								'paths'   => array(
									'/leads' => array(
										'post' => array(
											'requestBody' => array(
												'content' => array(
													'application/json' => array(
														'schema' => array(
															'type'       => 'object',
															'properties' => array(
																'name'        => array( 'type' => 'string' ),
																'value'       => array( 'type' => 'integer' ),
																'potential'   => array( 'type' => 'integer' ),
																'contactName' => array( 'type' => 'string' ),
																'contactId'   => array( 'type' => 'string' ),
															),
														),
													),
												),
											),
										),
									),
								),
							),
						),
					),
				);

			case '/holded/api-next/v2/branches/1.0/reference/list-projects':
				return array(
					'data' => array(
						'api' => array(
							'schema' => array(
								'openapi' => '3.0.1',
								'paths'   => array(
									'/projects' => array(
										'post' => array(
											'requestBody' => array(
												'content' => array(
													'application/json' => array(
														'schema' => array(
															'type'       => 'object',
															'properties' => array(
																'name'   => array( 'type' => 'string' ),
															),
															'required'   => array( 'name' ),
														),
													),
												),
											),
										),
									),
								),
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Holded_Addon' ) );
		$this->assertEquals( 'Holded', Holded_Addon::TITLE );
		$this->assertEquals( 'holded', Holded_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Holded_Form_Bridge', Holded_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Holded_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Holded_Form_Bridge(
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
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'name'     => 'John Doe',
			'email'    => 'john.doe@example.coop',
			'phone'    => '+1234567890',
			'isPerson' => true,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( '123456789', $response['data']['id'] );
		$this->assertEquals( 'Created', $response['data']['info'] );
		$this->assertEquals( 1, $response['data']['status'] );
	}

	/**
	 * Test GET request to fetch contacts.
	 */
	public function test_get_contacts() {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertNotEmpty( $response['data'] );
		$this->assertEquals( 'John Doe', $response['data'][0]['name'] );
		$this->assertEquals( 'john.doe@example.coop', $response['data'][0]['email'] );
		$this->assertEquals( '+1234567890', $response['data'][0]['phone'] );
		$this->assertTrue( $response['data'][0]['isPerson'] );
	}

	/**
	 * Test POST request to create an invoice.
	 */
	public function test_post_create_invoice() {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/documents/invoices',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'contactId' => '123456789',
			'date'      => 1769868644,
			'dueDate'   => 1770473468,
			'items'     => array(
				array(
					'name'     => 'Product 1',
					'sku'      => 'product-1',
					'tax'      => 21,
					'subtotal' => 100,
					'discount' => 10,
				),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( '987654321', $response['data']['id'] );
		$this->assertEquals( 'Created', $response['data']['info'] );
		$this->assertEquals( 1, $response['data']['status'] );
	}

	/**
	 * Test POST request to create a lead.
	 */
	public function test_post_create_lead() {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/crm/v1/leads',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'name'   => 'Jane Smith',
			'email'  => 'jane.smith@example.coop',
			'status' => 'New',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( '456789123', $response['data']['id'] );
		$this->assertEquals( 'Created', $response['data']['info'] );
		$this->assertEquals( 1, $response['data']['status'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'holded' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'holded' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertNotEmpty( $endpoints );
		$this->assertContains( '/api/invoicing/v1/contacts', $endpoints );
		$this->assertContains( '/api/invoicing/v1/documents/{docType}', $endpoints );
		$this->assertContains( '/api/crm/v1/leads', $endpoints );
		$this->assertContains( '/api/projects/v1/projects', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method for contacts.
	 */
	public function test_addon_get_endpoint_schema_contacts() {
		$addon  = Addon::addon( 'holded' );
		$schema = $addon->get_endpoint_schema( '/api/invoicing/v1/contacts', self::BACKEND_NAME, 'POST' );

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'name', $field_names );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'phone', $field_names );
		$this->assertContains( 'mobile', $field_names );
		$this->assertContains( 'isPerson', $field_names );

		// Check field types.
		$schema_map = array();
		foreach ( $schema as $field ) {
			$schema_map[ $field['name'] ] = $field['schema']['type'];
		}

		$this->assertEquals( 'string', $schema_map['name'] );
		$this->assertEquals( 'string', $schema_map['email'] );
		$this->assertEquals( 'string', $schema_map['phone'] );
		$this->assertEquals( 'string', $schema_map['mobile'] );
		$this->assertEquals( 'boolean', $schema_map['isPerson'] );
	}

	/**
	 * Test addon get_endpoint_schema method for leads.
	 */
	public function test_addon_get_endpoint_schema_leads() {
		$addon  = Addon::addon( 'holded' );
		$schema = $addon->get_endpoint_schema( '/api/crm/v1/leads', self::BACKEND_NAME, 'POST' );

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'name', $field_names );
		$this->assertContains( 'value', $field_names );
		$this->assertContains( 'potential', $field_names );
		$this->assertContains( 'contactName', $field_names );
		$this->assertContains( 'contactId', $field_names );

		// Check field types.
		$schema_map = array();
		foreach ( $schema as $field ) {
			$schema_map[ $field['name'] ] = $field['schema']['type'];
		}

		$this->assertEquals( 'string', $schema_map['name'] );
		$this->assertEquals( 'integer', $schema_map['value'] );
		$this->assertEquals( 'integer', $schema_map['potential'] );
		$this->assertEquals( 'string', $schema_map['contactName'] );
		$this->assertEquals( 'string', $schema_map['contactId'] );
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
				'code'    => 'unauthorized',
				'message' => 'Invalid API key',
			),
		);

		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
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
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test validation error handling.
	 */
	public function test_validation_error_handling() {
		self::$mock_response = array(
			'http'  => array(
				'code'    => 422,
				'message' => 'Unprocessable Entity',
			),
			'error' => array(
				'code'    => 'validation_error',
				'message' => 'Validation failed',
				'details' => array(
					'email' => 'Invalid email format',
				),
			),
		);

		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'POST',
			)
		);

		$response = $bridge->submit( array( 'email' => 'invalid-email' ) );

		$this->assertTrue( is_wp_error( $response ) );

		$response_body = json_decode( $response->get_error_data()['response']['body'], true );
		$this->assertEquals( 'validation_error', $response_body['error']['code'] );
	}

	/**
	 * Test authorization header transformation.
	 */
	public function test_authorization_header_transformation() {
		$bridge = new Holded_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/invoicing/v1/contacts',
				'method'   => 'GET',
			)
		);

		$bridge->submit();

		// Check that the request was made
		$this->assertNotNull( self::$request );

		// Verify the Authorization header was transformed
		$headers = self::$request['args']['headers'] ?? array();
		$this->assertArrayHasKey( 'Key', $headers );
		$this->assertEquals( 'test-holded-api-key', $headers['Key'] );
	}
}
