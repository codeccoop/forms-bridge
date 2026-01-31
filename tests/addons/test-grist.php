<?php
/**
 * Class GristTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Grist_Form_Bridge;
use FORMS_BRIDGE\Grist_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Grist addon test case.
 */
class GristTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-grist-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://test.getgrist.com';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-grist-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-grist-bridge';

	/**
	 * Holds the mocked doc ID.
	 *
	 * @var string
	 */
	private const DOC_ID = 'doc123456789';

	/**
	 * Holds the mocked table ID.
	 *
	 * @var string
	 */
	private const TABLE_ID = 'TestTable';

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
							'name'  => 'orgId',
							'value' => 'test',
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
		// Organizations endpoint.
		if ( '/api/orgs' === $path && 'GET' === $method ) {
			return array(
				'orgs' => array(
					array(
						'id'   => 'org123456789',
						'name' => 'Test Organization',
					),
				),
			);
		}

		// Workspaces endpoint.
		if ( preg_match( '/^\/api\/orgs\/([^\/]+)\/workspaces$/', $path ) && 'GET' === $method ) {
			return array(
				array(
					'id'   => 'ws123456789',
					'name' => 'Test Workspace',
					'docs' => array(
						array(
							'id'     => self::DOC_ID,
							'urlId'  => self::DOC_ID,
							'name'   => 'Test Document',
							'access' => 'owners',
						),
					),
				),
			);
		}

		// Tables endpoint.
		if ( preg_match( '/^\/api\/docs\/doc([^\/]+)\/tables$/', $path ) && 'GET' === $method ) {
			return array(
				'tables' => array(
					array(
						'id'   => self::TABLE_ID,
						'name' => 'Test Table',
					),
					array(
						'id'   => 'another-table',
						'name' => 'Another Table',
					),
				),
			);
		}

		// Columns endpoint.
		if ( preg_match( '/^\/api\/docs\/doc([^\/]+)\/tables\/([^\/]+)\/columns$/', $path ) && 'GET' === $method ) {
			return array(
				'columns' => array(
					array(
						'id'     => 'name',
						'fields' => array(
							'label'         => 'Name',
							'type'          => 'Text',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'email',
						'fields' => array(
							'label'         => 'Email',
							'type'          => 'Text',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'age',
						'fields' => array(
							'label'         => 'Age',
							'type'          => 'Int',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'active',
						'fields' => array(
							'label'         => 'Active',
							'type'          => 'Bool',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'tags',
						'fields' => array(
							'label'         => 'Tags',
							'type'          => 'ChoiceList',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => json_encode(
								array(
									'choices' => array( 'A', 'B', 'C' ),
								)
							),
						),
					),
					array(
						'id'     => 'attachment',
						'fields' => array(
							'label'         => 'Attachment',
							'type'          => 'Attachments',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'formula_field',
						'fields' => array(
							'label'         => 'Formula Field',
							'type'          => 'Text',
							'isFormula'     => true,
							'formula'       => '=1+1',
							'widgetOptions' => '{}',
						),
					),
					array(
						'id'     => 'ref_field',
						'fields' => array(
							'label'         => 'Reference Field',
							'type'          => 'Ref:test-table-456',
							'isFormula'     => false,
							'formula'       => '',
							'widgetOptions' => '{}',
						),
					),
				),
			);
		}

		// Records endpoint (POST).
		if ( preg_match( '/^\/api\/docs\/doc([^\/]+)\/tables\/([^\/]+)\/records$/', $path ) ) {
			if ( 'POST' === $method ) {
				return array(
					'records' => array( array( 'id' => 'rec123456789' ) ),
				);
			} else {
				return array(
					'records' => array(
						array(
							'id'     => 'rec123456789',
							'fields' => array(
								'email'       => 'john.doe@example.coop',
								'name'        => 'John Doe',
								'age'         => 43,
								'tags'        => array( 'L', 'A', 'B' ),
								'active'      => true,
								'attachments' => array( 'L', 1 ),
							),
						),
					),
				);
			}
		}

		// Attachments endpoint (POST).
		if ( preg_match( '/^\/api\/docs\/doc([^\/]+)\/attachments$/', $path ) && 'POST' === $method ) {
			return array( 1 );
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
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Grist_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables',
				'method'   => 'GET',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Grist_Form_Bridge(
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
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'name'   => 'John Doe',
			'email'  => 'john.doe@example.com',
			'tags'   => array( 'A', 'B' ),
			'active' => true,
			'age'    => 42,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'rec123456789', $response['data']['records'][0]['id'] );
	}

	/**
	 * Test POST request to create a record with uploads.
	 */
	public function test_post_create_record_with_upload() {
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'name'       => 'John Doe',
			'email'      => 'john.doe@example.com',
			'attachment' => 'file',
			'tags'       => array( 'A', 'B' ),
			'active'     => true,
			'age'        => 42,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'rec123456789', $response['data']['records'][0]['id'] );
	}

	/**
	 * Test GET request to fetch table schema.
	 */
	public function test_get_table_schema() {
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
				'method'   => 'GET',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'records', $response['data'] );
		$this->assertCount( 1, $response['data']['records'] );
		$this->assertArrayHasKey( 'fields', $response['data']['records'][0] );
		$this->assertCount( 6, $response['data']['records'][0]['fields'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'grist' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon fetch method to get tables.
	 */
	public function test_addon_fetch_tables() {
		$addon    = Addon::addon( 'grist' );
		$response = $addon->fetch( '/api/orgs/{orgId}/tables', self::BACKEND_NAME );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'tables', $response['data'] );
		$this->assertCount( 2, $response['data']['tables'] );

		// Check that tables have the expected structure.
		$table = $response['data']['tables'][0];
		$this->assertArrayHasKey( 'org_id', $table );
		$this->assertArrayHasKey( 'doc_id', $table );
		$this->assertArrayHasKey( 'doc_name', $table );
		$this->assertArrayHasKey( 'label', $table );
		$this->assertArrayHasKey( 'id', $table );
		$this->assertArrayHasKey( 'endpoint', $table );
		$this->assertEquals( 'Test Document/' . self::TABLE_ID, $table['label'] );
		$this->assertEquals( '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records', $table['endpoint'] );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'grist' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertCount( 2, $endpoints );
		$this->assertContains( '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method for POST.
	 */
	public function test_addon_get_endpoint_schema_post() {
		$addon  = Addon::addon( 'grist' );
		$schema = $addon->get_endpoint_schema(
			'/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
			self::BACKEND_NAME,
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Check that schema contains expected fields.
		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'name', $field_names );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'active', $field_names );
		$this->assertContains( 'age', $field_names );
		$this->assertContains( 'tags', $field_names );
		$this->assertContains( 'attachment', $field_names );

		// Check field types.
		$schema_map = array();
		foreach ( $schema as $field ) {
			$schema_map[ $field['name'] ] = $field['schema']['type'];
		}

		$this->assertEquals( 'string', $schema_map['name'] );
		$this->assertEquals( 'string', $schema_map['email'] );
		$this->assertEquals( 'boolean', $schema_map['active'] );
		$this->assertEquals( 'array', $schema_map['tags'] );
		$this->assertEquals( 'file', $schema_map['attachment'] );
		$this->assertEquals( 'number', $schema_map['age'] );
	}

	/**
	 * Test addon get_endpoint_schema method for non-POST methods.
	 */
	public function test_addon_get_endpoint_schema_non_post() {
		$addon  = Addon::addon( 'grist' );
		$schema = $addon->get_endpoint_schema(
			'/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
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
			'error' => 'Bad request: invalid API key',
		);

		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
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
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
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
		$bridge = new Grist_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
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
		$addon  = Addon::addon( 'grist' );
		$schema = $addon->get_endpoint_schema(
			'/api/docs/' . self::DOC_ID . '/tables/' . self::TABLE_ID . '/records',
			self::BACKEND_NAME,
			'POST'
		);

		// Should only include the singleLineText field, not the aiText field.
		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'name', $field_names );
		$this->assertNotContains( 'formula_field', $field_names );
		$this->assertNotContains( 'ref_field', $field_names );
		$this->assertCount( 6, $schema );
	}
}
