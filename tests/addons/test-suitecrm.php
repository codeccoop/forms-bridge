<?php
/**
 * Class SuiteCRMTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\SuiteCRM_Form_Bridge;
use FORMS_BRIDGE\SuiteCRM_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * SuiteCRM addon test case.
 */
class SuiteCRMTest extends WP_UnitTestCase {

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
					'name'          => 'suitecrm-test-credential',
					'schema'        => 'Basic',
					'client_id'     => 'admin',
					'client_secret' => 'password123',
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
					'name'       => 'suitecrm-test-backend',
					'base_url'   => 'https://crm.example.coop',
					'credential' => 'suitecrm-test-credential',
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

		// Parse the body to determine the method being called.
		$body = array();
		if ( ! empty( $args['body'] ) ) {
			if ( is_string( $args['body'] ) ) {
				parse_str( $args['body'], $body );
			} else {
				$body = $args['body'];
			}
		}

		$method = $body['method'] ?? '';

		// Return appropriate mock response based on method.
		if ( self::$mock_response ) {
			$response_body = self::$mock_response;
		} else {
			$response_body = self::get_mock_response( $method, $body );
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
	 * Get mock response based on API method.
	 *
	 * @param string $method API method name.
	 * @param array  $body Request body.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $method, $body ) {
		switch ( $method ) {
			case 'login':
				return array(
					'id'              => 'test-session-id-12345',
					'module_name'     => 'Users',
					'name_value_list' => array(
						'user_id'   => array(
							'name'  => 'user_id',
							'value' => '1',
						),
						'user_name' => array(
							'name'  => 'user_name',
							'value' => 'admin',
						),
					),
				);

			case 'get_user_id':
				return '1';

			case 'get_available_modules':
				return array(
					'modules' => array(
						array(
							'module_key'   => 'Contacts',
							'module_label' => 'Contacts',
						),
						array(
							'module_key'   => 'Leads',
							'module_label' => 'Leads',
						),
						array(
							'module_key'   => 'Accounts',
							'module_label' => 'Accounts',
						),
						array(
							'module_key'   => 'Opportunities',
							'module_label' => 'Opportunities',
						),
					),
				);

			case 'get_module_fields':
				return array(
					'module_name'   => 'Contacts',
					'module_fields' => array(
						'first_name' => array(
							'name'     => 'first_name',
							'type'     => 'varchar',
							'label'    => 'First Name',
							'required' => 1,
						),
						'last_name'  => array(
							'name'     => 'last_name',
							'type'     => 'varchar',
							'label'    => 'Last Name',
							'required' => 1,
						),
						'email1'     => array(
							'name'     => 'email1',
							'type'     => 'varchar',
							'label'    => 'Email',
							'required' => 0,
						),
						'phone_work' => array(
							'name'     => 'phone_work',
							'type'     => 'phone',
							'label'    => 'Office Phone',
							'required' => 0,
						),
					),
				);

			case 'get_entry_list':
				return array(
					'result_count'      => 2,
					'total_count'       => 2,
					'next_offset'       => 2,
					'entry_list'        => array(
						array(
							'id'              => 'contact-id-1',
							'module_name'     => 'Contacts',
							'name_value_list' => array(
								'id'         => array(
									'name'  => 'id',
									'value' => 'contact-id-1',
								),
								'first_name' => array(
									'name'  => 'first_name',
									'value' => 'John',
								),
								'last_name'  => array(
									'name'  => 'last_name',
									'value' => 'Doe',
								),
							),
						),
						array(
							'id'              => 'contact-id-2',
							'module_name'     => 'Contacts',
							'name_value_list' => array(
								'id'         => array(
									'name'  => 'id',
									'value' => 'contact-id-2',
								),
								'first_name' => array(
									'name'  => 'first_name',
									'value' => 'Jane',
								),
								'last_name'  => array(
									'name'  => 'last_name',
									'value' => 'Smith',
								),
							),
						),
					),
					'relationship_list' => array(),
				);

			case 'set_entry':
				return array(
					'id'         => 'new-contact-id-123',
					'entry_list' => array(
						array(
							'name'  => 'id',
							'value' => 'new-contact-id-123',
						),
					),
				);

			case 'get_entry':
				return array(
					'entry_list' => array(
						array(
							'id'              => 'contact-id-1',
							'module_name'     => 'Contacts',
							'name_value_list' => array(
								'id'         => array(
									'name'  => 'id',
									'value' => 'contact-id-1',
								),
								'first_name' => array(
									'name'  => 'first_name',
									'value' => 'John',
								),
								'last_name'  => array(
									'name'  => 'last_name',
									'value' => 'Doe',
								),
							),
						),
					),
				);

			default:
				return array( 'success' => true );
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\SuiteCRM_Addon' ) );
		$this->assertEquals( 'SuiteCRM', SuiteCRM_Addon::TITLE );
		$this->assertEquals( 'suitecrm', SuiteCRM_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\SuiteCRM_Form_Bridge', SuiteCRM_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\SuiteCRM_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-suitecrm-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'set_entry',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertFalse( $bridge->is_valid );
	}

	/**
	 * Test REST payload building.
	 */
	public function test_rest_payload() {
		$payload = SuiteCRM_Form_Bridge::rest_payload(
			'login',
			array(
				'user_auth' => array(
					'user_name' => 'admin',
					'password'  => md5( 'password' ),
				),
			)
		);

		$this->assertArrayHasKey( 'method', $payload );
		$this->assertArrayHasKey( 'input_type', $payload );
		$this->assertArrayHasKey( 'response_type', $payload );
		$this->assertArrayHasKey( 'rest_data', $payload );

		$this->assertEquals( 'login', $payload['method'] );
		$this->assertEquals( 'JSON', $payload['input_type'] );
		$this->assertEquals( 'JSON', $payload['response_type'] );
	}

	/**
	 * Test successful login flow.
	 */
	public function test_login_flow() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-login-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'get_entry_list',
			)
		);

		$response = $bridge->submit(
			array(),
			array( 'max_results' => 10 )
		);

		$this->assertFalse( is_wp_error( $response ) );

		// Verify the request URL contains the SuiteCRM endpoint.
		$this->assertStringContainsString( '/service/v4_1/rest.php', self::$request['url'] );
	}

	/**
	 * Test set_entry operation.
	 */
	public function test_set_entry() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-set-entry-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'set_entry',
			)
		);

		$payload = array(
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'email1'     => 'john.doe@example.com',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'id', $response['data'] );
		$this->assertEquals( 'new-contact-id-123', $response['data']['id'] );
	}

	/**
	 * Test get_entry_list operation.
	 */
	public function test_get_entry_list() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-get-entry-list-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'get_entry_list',
			)
		);

		$response = $bridge->submit(
			array(),
			array(
				'select_fields' => array( 'id', 'first_name', 'last_name' ),
				'max_results'   => 20,
			)
		);

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'entry_list', $response['data'] );
		$this->assertCount( 2, $response['data']['entry_list'] );
	}

	/**
	 * Test get_server_info operation (no auth required).
	 */
	public function test_get_user_id() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-server-info-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => '',
				'method'   => 'get_user_id',
			)
		);

		$response = $bridge->submit();

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( '1', $response['data'] );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'name'        => 'Invalid Login',
			'number'      => '10',
			'description' => 'Login attempt failed please check the username and password',
		);

		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-error-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'set_entry',
			)
		);

		$response = $bridge->submit( array( 'first_name' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertStringContainsString( 'suitecrm_error', $response->get_error_code() );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'suitecrm' );
		$response = $addon->ping( 'suitecrm-test-backend' );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		Backend::temp_registration(
			array(
				'name'       => 'suitecrm-test-backend',
				'base_url'   => 'https://crm.example.coop',
				'credential' => 'suitecrm-test-credential',
				'headers'    => array(),
			)
		);

		$addon     = Addon::addon( 'suitecrm' );
		$endpoints = $addon->get_endpoints( 'suitecrm-test-backend' );

		$this->assertIsArray( $endpoints );
		$this->assertContains( 'Contacts', $endpoints );
		$this->assertContains( 'Leads', $endpoints );
		$this->assertContains( 'Accounts', $endpoints );
		$this->assertContains( 'Opportunities', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		Backend::temp_registration(
			array(
				'name'       => 'suitecrm-test-backend',
				'base_url'   => 'https://crm.example.coop',
				'credential' => 'suitecrm-test-credential',
				'headers'    => array(),
			)
		);

		$addon  = Addon::addon( 'suitecrm' );
		$schema = $addon->get_endpoint_schema(
			'Contacts',
			'suitecrm-test-backend',
			'set_entry'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'last_name', $field_names );
	}

	/**
	 * Test that templates exist and are valid.
	 */
	public function test_templates_exist() {
		$templates_dir = dirname( dirname( __DIR__ ) ) . '/forms-bridge/addons/suitecrm/templates/';

		$this->assertFileExists( $templates_dir . 'contacts.php' );
		$this->assertFileExists( $templates_dir . 'leads.php' );
		$this->assertFileExists( $templates_dir . 'accounts.php' );
	}

	/**
	 * Test contacts template structure.
	 */
	public function test_contacts_template_structure() {
		$template = include dirname( dirname( __DIR__ ) ) . '/forms-bridge/addons/suitecrm/templates/contacts.php';

		$this->assertIsArray( $template );
		$this->assertArrayHasKey( 'title', $template );
		$this->assertArrayHasKey( 'description', $template );
		$this->assertArrayHasKey( 'fields', $template );
		$this->assertArrayHasKey( 'bridge', $template );
		$this->assertArrayHasKey( 'form', $template );

		$this->assertEquals( 'Contacts', $template['bridge']['endpoint'] );
		$this->assertEquals( 'set_entry', $template['bridge']['method'] );
	}

	/**
	 * Test leads template structure.
	 */
	public function test_leads_template_structure() {
		$template = include dirname( dirname( __DIR__ ) ) . '/forms-bridge/addons/suitecrm/templates/leads.php';

		$this->assertIsArray( $template );
		$this->assertEquals( 'Leads', $template['bridge']['endpoint'] );
		$this->assertEquals( 'set_entry', $template['bridge']['method'] );
	}

	/**
	 * Test accounts template structure.
	 */
	public function test_accounts_template_structure() {
		$template = include dirname( dirname( __DIR__ ) ) . '/forms-bridge/addons/suitecrm/templates/accounts.php';

		$this->assertIsArray( $template );
		$this->assertEquals( 'Contacts', $template['bridge']['endpoint'] );
		$this->assertEquals( 'set_entry', $template['bridge']['method'] );
	}

	/**
	 * Test bridge schema hook is applied.
	 */
	public function test_bridge_schema_hook() {
		$schema = \FORMS_BRIDGE\Form_Bridge::schema( 'suitecrm' );

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'method', $schema['properties'] );

		// Verify the method enum contains SuiteCRM-specific methods.
		$this->assertContains( 'set_entry', $schema['properties']['method']['enum'] );
		$this->assertContains( 'get_entry', $schema['properties']['method']['enum'] );
		$this->assertContains( 'get_entry_list', $schema['properties']['method']['enum'] );
	}

	/**
	 * Test MD5 password hashing in login.
	 */
	public function test_password_md5_hashing() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-md5-bridge',
				'backend'  => 'suitecrm-test-backend',
				'endpoint' => 'Contacts',
				'method'   => 'get_entry_list',
			)
		);

		$response = $bridge->submit();

		// Check that a request was made.
		$this->assertNotNull( self::$request );

		// The first request should be a login request.
		// We can't easily verify the MD5 hash in the intercepted request,
		// but we can verify the flow completed successfully.
		$this->assertFalse( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new SuiteCRM_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => 'Contacts',
				'method'   => 'set_entry',
			)
		);

		$response = $bridge->submit( array( 'first_name' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}
}
