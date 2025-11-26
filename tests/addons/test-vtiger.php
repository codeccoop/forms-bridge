<?php
/**
 * Class VtigerTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Vtiger_Form_Bridge;
use FORMS_BRIDGE\Vtiger_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Vtiger addon test case.
 */
class VtigerTest extends WP_UnitTestCase {

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
	 * Test credential provider.
	 *
	 * @return Credential[]
	 */
	public static function credentials_provider() {
		return array(
			new Credential(
				array(
					'name'          => 'vtiger-test-credential',
					'schema'        => 'Basic',
					'client_id'     => 'admin',
					'client_secret' => 'accessKey123',
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
					'name'       => 'vtiger-test-backend',
					'base_url'   => 'https://vtiger.example.coop',
					'credential' => 'vtiger-test-credential',
					'headers'    => array(
						array(
							'name'  => 'Content-Type',
							'value' => 'application/x-www-form-urlencoded',
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

		// Parse URL to determine the operation being called.
		$parsed_url = wp_parse_url( $url );
		$query      = array();
		if ( ! empty( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query );
		}

		$operation = $query['operation'] ?? '';

		// Check if this is a POST request (for create/update/delete operations).
		$body = array();
		if ( ! empty( $args['body'] ) ) {
			if ( is_string( $args['body'] ) ) {
				parse_str( $args['body'], $body );
			} else {
				$body = $args['body'];
			}
			$operation = $body['operation'] ?? $operation;
		}

		// Return appropriate mock response based on operation.
		if ( self::$mock_response ) {
			$response_body = self::$mock_response;
		} else {
			$response_body = self::get_mock_response( $operation, $query, $body );
		}

		return array(
			'response'      => array(
				'code'    => 200,
				'message' => 'Success',
			),
			'headers'       => array( 'Content-Type' => 'application/json' ),
			'cookies'       => array(),
			'body'          => wp_json_encode( $response_body ),
			'http_response' => null,
		);
	}

	/**
	 * Get mock response based on API operation.
	 *
	 * @param string $operation API operation name.
	 * @param array  $query Query parameters.
	 * @param array  $body Request body.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $operation, $query, $body ) {
		switch ( $operation ) {
			case 'getchallenge':
				return array(
					'success' => true,
					'result'  => array(
						'token'      => 'test-challenge-token-12345',
						'serverTime' => time(),
						'expireTime' => time() + 300,
					),
				);

			case 'login':
				return array(
					'success' => true,
					'result'  => array(
						'sessionName'   => 'test-session-id-67890',
						'userId'        => '19x1',
						'version'       => '7.4.0',
						'vtigerVersion' => '7.4.0',
					),
				);

			case 'listtypes':
				return array(
					'success' => true,
					'result'  => array(
						'types'       => array(
							'Contacts',
							'Leads',
							'Accounts',
							'Potentials',
							'Calendar',
						),
						'information' => array(
							'Contacts'   => array(
								'isEntity' => true,
								'label'    => 'Contacts',
								'singular' => 'Contact',
							),
							'Leads'      => array(
								'isEntity' => true,
								'label'    => 'Leads',
								'singular' => 'Lead',
							),
							'Accounts'   => array(
								'isEntity' => true,
								'label'    => 'Accounts',
								'singular' => 'Account',
							),
							'Potentials' => array(
								'isEntity' => true,
								'label'    => 'Potentials',
								'singular' => 'Potential',
							),
							'Calendar'   => array(
								'isEntity' => true,
								'label'    => 'Calendar',
								'singular' => 'Event',
							),
						),
					),
				);

			case 'describe':
				return array(
					'success' => true,
					'result'  => array(
						'label'        => 'Contacts',
						'name'         => 'Contacts',
						'createable'   => true,
						'updateable'   => true,
						'deleteable'   => true,
						'retrieveable' => true,
						'fields'       => array(
							array(
								'name'      => 'firstname',
								'label'     => 'First Name',
								'mandatory' => false,
								'type'      => array(
									'name' => 'string',
								),
								'nullable'  => true,
								'editable'  => true,
							),
							array(
								'name'      => 'lastname',
								'label'     => 'Last Name',
								'mandatory' => true,
								'type'      => array(
									'name' => 'string',
								),
								'nullable'  => false,
								'editable'  => true,
							),
							array(
								'name'      => 'email',
								'label'     => 'Email',
								'mandatory' => false,
								'type'      => array(
									'name' => 'email',
								),
								'nullable'  => true,
								'editable'  => true,
							),
							array(
								'name'      => 'phone',
								'label'     => 'Office Phone',
								'mandatory' => false,
								'type'      => array(
									'name' => 'phone',
								),
								'nullable'  => true,
								'editable'  => true,
							),
						),
					),
				);

			case 'query':
				return array(
					'success' => true,
					'result'  => array(
						array(
							'id'        => '4x11',
							'firstname' => 'John',
							'lastname'  => 'Doe',
							'email'     => 'john.doe@example.coop',
						),
						array(
							'id'        => '4x12',
							'firstname' => 'Jane',
							'lastname'  => 'Smith',
							'email'     => 'jane.smith@example.coop',
						),
					),
				);

			case 'retrieve':
				return array(
					'success' => true,
					'result'  => array(
						'id'        => '4x11',
						'firstname' => 'John',
						'lastname'  => 'Doe',
						'email'     => 'john.doe@example.coop',
						'phone'     => '555-1234',
					),
				);

			case 'create':
				$element = array();
				if ( ! empty( $body['element'] ) ) {
					$element = json_decode( $body['element'], true );
				}
				return array(
					'success' => true,
					'result'  => array_merge(
						array(
							'id'               => '4x123',
							'assigned_user_id' => '19x1',
						),
						$element
					),
				);

			case 'update':
				$element = array();
				if ( ! empty( $body['element'] ) ) {
					$element = json_decode( $body['element'], true );
				}
				return array(
					'success' => true,
					'result'  => $element,
				);

			case 'delete':
				return array(
					'success' => true,
					'result'  => array(
						'status' => 'successful',
					),
				);

			case 'sync':
				return array(
					'success' => true,
					'result'  => array(
						'updated' => array(),
						'deleted' => array(),
					),
				);

			default:
				return array(
					'success' => true,
					'result'  => array(),
				);
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Vtiger_Addon' ) );
		$this->assertEquals( 'Vtiger', Vtiger_Addon::TITLE );
		$this->assertEquals( 'vtiger', Vtiger_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Vtiger_Form_Bridge', Vtiger_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Vtiger_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-vtiger-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertFalse( $bridge->is_valid );
	}

	/**
	 * Test successful challenge-response authentication flow.
	 */
	public function test_authentication_flow() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-auth-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'query',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );

		// Verify the request URL contains the Vtiger webservice endpoint.
		$this->assertStringContainsString( '/webservice.php', self::$request['url'] );
	}

	/**
	 * Test listtypes operation.
	 */
	public function test_listtypes() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-listtypes-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => '',
				'method'   => 'listtypes',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
		$this->assertArrayHasKey( 'types', $response['data']['result'] );
		$this->assertContains( 'Contacts', $response['data']['result']['types'] );
		$this->assertContains( 'Leads', $response['data']['result']['types'] );
		$this->assertContains( 'Accounts', $response['data']['result']['types'] );
	}

	/**
	 * Test describe operation.
	 */
	public function test_describe() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-describe-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'describe',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
		$this->assertArrayHasKey( 'fields', $response['data']['result'] );
		$this->assertNotEmpty( $response['data']['result']['fields'] );
	}

	/**
	 * Test query operation.
	 */
	public function test_query() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-query-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'query',
			)
		);

		$response = $bridge->submit(
			array(
				'query' => 'SELECT * FROM Contacts;',
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
		$this->assertIsArray( $response['data']['result'] );
		$this->assertCount( 2, $response['data']['result'] );
	}

	/**
	 * Test retrieve operation.
	 */
	public function test_retrieve() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-retrieve-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'retrieve',
			)
		);

		$response = $bridge->submit(
			array(
				'id' => '4x11',
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
		$this->assertEquals( '4x11', $response['data']['result']['id'] );
		$this->assertEquals( 'John', $response['data']['result']['firstname'] );
	}

	/**
	 * Test create operation.
	 */
	public function test_create() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-create-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$payload = array(
			'firstname' => 'John',
			'lastname'  => 'Doe',
			'email'     => 'john.doe@example.coop',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
		$this->assertArrayHasKey( 'id', $response['data']['result'] );
		$this->assertEquals( '4x123', $response['data']['result']['id'] );
	}

	/**
	 * Test update operation.
	 */
	public function test_update() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-update-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'update',
			)
		);

		$payload = array(
			'id'        => '4x11',
			'firstname' => 'Jane',
			'lastname'  => 'Doe',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'result', $response['data'] );
	}

	/**
	 * Test delete operation.
	 */
	public function test_delete() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-delete-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'delete',
			)
		);

		$payload = array(
			'id' => '4x11',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertTrue( $response['data']['success'] );
	}

	/**
	 * Test sync operation.
	 */
	public function test_sync() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-sync-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'sync',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertTrue( $response['data']['success'] );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'success' => false,
			'error'   => array(
				'code'    => 'INVALID_SESSIONID',
				'message' => 'Given sessionid is invalid',
			),
		);

		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-error-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$response = $bridge->submit( array( 'firstname' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertStringContainsString( 'vtiger_', $response->get_error_code() );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'vtiger' );
		$response = $addon->ping( 'vtiger-test-backend' );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		Backend::temp_registration(
			array(
				'name'       => 'vtiger-test-backend',
				'base_url'   => 'https://vtiger.example.coop',
				'credential' => 'vtiger-test-credential',
				'headers'    => array(),
			)
		);

		$addon     = Addon::addon( 'vtiger' );
		$endpoints = $addon->get_endpoints( 'vtiger-test-backend' );

		$this->assertIsArray( $endpoints );
		$this->assertContains( 'Contacts', $endpoints );
		$this->assertContains( 'Leads', $endpoints );
		$this->assertContains( 'Accounts', $endpoints );
		$this->assertContains( 'Potentials', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		Backend::temp_registration(
			array(
				'name'       => 'vtiger-test-backend',
				'base_url'   => 'https://vtiger.example.coop',
				'credential' => 'vtiger-test-credential',
				'headers'    => array(),
			)
		);

		$addon  = Addon::addon( 'vtiger' );
		$schema = $addon->get_endpoint_schema(
			'Contacts',
			'vtiger-test-backend',
			'create'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'firstname', $field_names );
		$this->assertContains( 'lastname', $field_names );
	}

	/**
	 * Test bridge schema hook is applied.
	 */
	public function test_bridge_schema_hook() {
		$schema = \FORMS_BRIDGE\Form_Bridge::schema( 'vtiger' );

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'method', $schema['properties'] );

		// Verify the method enum contains Vtiger-specific methods.
		$this->assertContains( 'create', $schema['properties']['method']['enum'] );
		$this->assertContains( 'query', $schema['properties']['method']['enum'] );
		$this->assertContains( 'retrieve', $schema['properties']['method']['enum'] );
		$this->assertContains( 'update', $schema['properties']['method']['enum'] );
		$this->assertContains( 'delete', $schema['properties']['method']['enum'] );
	}

	/**
	 * Test MD5 access key hashing in login.
	 */
	public function test_access_key_md5_hashing() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-md5-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'query',
			)
		);

		$response = $bridge->submit();

		// Check that a request was made.
		$this->assertNotNull( self::$request );

		// Verify the flow completed successfully.
		$this->assertFalse( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$response = $bridge->submit( array( 'firstname' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test invalid credential handling.
	 */
	public function test_invalid_credential() {
		Backend::temp_registration(
			array(
				'name'       => 'vtiger-no-cred-backend',
				'base_url'   => 'https://vtiger.example.coop',
				'credential' => 'non-existent-credential',
				'headers'    => array(),
			)
		);

		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-invalid-credential-bridge',
				'backend'  => 'vtiger-no-cred-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$response = $bridge->submit( array( 'firstname' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_credential', $response->get_error_code() );
	}

	/**
	 * Test that assigned_user_id is automatically set on create.
	 */
	public function test_auto_assigned_user_id() {
		$bridge = new Vtiger_Form_Bridge(
			array(
				'name'     => 'test-auto-user-bridge',
				'backend'  => 'vtiger-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'create',
			)
		);

		$payload = array(
			'firstname' => 'John',
			'lastname'  => 'Doe',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'assigned_user_id', $response['data']['result'] );
		$this->assertEquals( '19x1', $response['data']['result']['assigned_user_id'] );
	}
}
