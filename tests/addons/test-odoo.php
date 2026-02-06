<?php
/**
 * Class OdooTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Odoo_Form_Bridge;
use FORMS_BRIDGE\Odoo_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Credential;
use HTTP_BRIDGE\Backend;

/**
 * Odoo test case.
 */
class OdooTest extends WP_UnitTestCase {

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
	 * Holds the mocked RPC session id.
	 *
	 * @var string
	 */
	private const SESSION_ID = 'test-session-id-12345';

	/**
	 * Holds the mocked backend name.
	 *
	 * @var string
	 */
	private const BACKEND_NAME = 'test-odoo-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://erp.example.coop';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-odoo-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-odoo-bridge';

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
					'schema'        => 'RPC',
					'client_id'     => 'admin',
					'client_secret' => 'password123',
					'database'      => 'odoo',
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

		// Parse the body to determine the method being called.
		$body = array();
		if ( ! empty( $args['body'] ) ) {
			if ( is_string( $args['body'] ) ) {
				$body = json_decode( $args['body'], true );
			} else {
				$body = $args['body'];
			}
		}

		$params = $body['params'] ?? array();
		$method = $params['method'] ?? '';

		// Return appropriate mock response based on method.
		if ( is_wp_error( self::$mock_response ) ) {
			return self::$mock_response;
		} elseif ( self::$mock_response ) {
			$mock_response = self::$mock_response;
		} else {
			$mock_response = array( 'data' => self::get_mock_response_data( $method, $params ) );
		}

		if ( isset( $mock_response['data'] ) ) {
			$mock_response['body'] = wp_json_encode( $mock_response['data'] );
			unset( $mock_response['data'] );
		}

		return array_merge(
			array(
				'response'      => array(
					'code'    => 200,
					'message' => 'Success',
				),
				'headers'       => array( 'Content-Type' => 'application/json' ),
				'cookies'       => array(),
				'body'          => '',
				'http_response' => null,
			),
			$mock_response,
		);
	}

	/**
	 * Get mock response data based on API method.
	 *
	 * @param string $method API method name.
	 * @param array  $params RPC call params.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response_data( $method, $params ) {
		$response = array(
			'jsonrpc' => '2.0',
			'id'      => self::SESSION_ID,
		);

		if ( 'login' !== $method ) {
			$method = $params['args'][4];
		}

		switch ( $method ) {
			case 'login':
				return array_merge(
					array( 'result' => 1 ),
					$response,
				);

			case 'search':
				return array_merge(
					array( 'result' => array( 1, 2, 3, 4, 5 ) ),
					$response,
				);

			case 'search_read':
				return array_merge(
					array(
						'result' => array(
							array(
								'id'    => 1,
								'name'  => 'Test Partner 1',
								'email' => 'partner1@example.coop',
							),
							array(
								'id'    => 2,
								'name'  => 'Test Partner 2',
								'email' => 'partner2@example.coop',
							),
							array(
								'id'    => 3,
								'name'  => 'Test Partner 3',
								'email' => 'partner3@example.coop',
							),
							array(
								'id'    => 4,
								'name'  => 'Test Partner 4',
								'email' => 'partner4@example.coop',
							),
							array(
								'id'    => 5,
								'name'  => 'Test Partner 5',
								'email' => 'partner5@example.coop',
							),
						),
					),
					$response,
				);

			case 'read':
				return array_merge(
					array(
						'result' => array(
							'id'    => 1,
							'name'  => 'Test Partner 1',
							'email' => 'partner1@example.coop',
						),
					),
					$response,
				);

			case 'fields_get':
				return array_merge(
					array(
						'result' => array(
							'id'    => array(
								'name'     => 'id',
								'string'   => 'ID',
								'type'     => 'integer',
								'required' => false,
								'readonly' => false,
							),
							'name'  => array(
								'name'     => 'name',
								'string'   => 'Name',
								'type'     => 'char',
								'required' => true,
								'readonly' => false,
							),
							'email' => array(
								'name'     => 'email',
								'string'   => 'Email',
								'type'     => 'char',
								'required' => false,
								'readonly' => false,
							),
						),
					),
					$response,
				);

			case 'create':
				return array_merge(
					array( 'result' => 1 ),
					$response,
				);

			case 'write':
				return array_merge(
					array( 'result' => true ),
					$response,
				);

			default:
				return array_merge(
					array(
						'result' => null,
						$response,
					)
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Odoo_Addon' ) );
		$this->assertEquals( 'Odoo', Odoo_Addon::TITLE );
		$this->assertEquals( 'odoo', Odoo_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Odoo_Form_Bridge', Odoo_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Odoo_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => 'res.partner',
				'method'   => 'create',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Odoo_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
		$this->assertEquals( 'res.partner', $bridge->endpoint );
		$this->assertEquals( '', $bridge->form_id );
		$this->assertEquals( '', $bridge->backend );
		$this->assertEquals( 'create', $bridge->method );
	}

	/**
	 * Test rcp create call.
	 */
	public function test_rpc_create() {
		$model  = 'res.partner';
		$method = 'create';

		$payload = array(
			'name'  => 'Test Partner',
			'email' => 'partner@example.coop',
		);

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'method'   => $method,
				'endpoint' => $model,
			)
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals( 1, $response['data']['result'] );
		$this->assertEquals( '2.0', $response['data']['jsonrpc'] );
		$this->assertEquals( self::SESSION_ID, $response['data']['id'] );

		$body = json_decode( self::$request['args']['body'], true );

		$this->assertArrayHasKey( 'params', $body );
		$this->assertEquals( 'object', $body['params']['service'] );
		$this->assertEquals( 'execute', $body['params']['method'] );
		$this->assertEquals(
			array(
				'odoo',
				1,
				'password123',
				$model,
				$method,
				$payload,
			),
			$body['params']['args']
		);
	}

	/**
	 * Test rcp search call.
	 */
	public function test_rpc_search() {
		$model  = 'res.partner';
		$method = 'search';

		$payload = array( array( 'email', '=', 'partner@example.coop' ) );

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'method'   => $method,
				'endpoint' => $model,
			)
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals(
			array( 1, 2, 3, 4, 5 ),
			$response['data']['result'],
		);

		$body = json_decode( self::$request['args']['body'], true );

		// More args should be appended to the args array.
		$expected_args = array(
			'odoo',
			1,
			'password123',
			$model,
			$method,
			$payload,
		);
		$this->assertEquals( $expected_args, $body['params']['args'] );
	}

	/**
	 * Test rpc search_read call with additional arguments.
	 */
	public function test_rpc_search_read() {
		$model  = 'res.partner';
		$method = 'search_read';

		$payload = array(
			array( 'email', '=', 'partner@example.coop' ),
		);

		$more_args = array( 'id', 'name', 'email' );

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => $model,
				'method'   => $method,
			)
		);

		$response = $bridge->submit( $payload, $more_args );

		$this->assertFalse( is_wp_error( $response ) );

		$expected_result = array(
			'id'    => 1,
			'name'  => 'Test Partner 1',
			'email' => 'partner1@example.coop',
		);
		$this->assertEquals( $expected_result, $response['data']['result'][0] );

		$body = json_decode( self::$request['args']['body'], true );

		// More args should be appended to the args array.
		$expected_args = array(
			'odoo',
			1,
			'password123',
			$model,
			$method,
			$payload,
			$more_args,
		);
		$this->assertEquals( $expected_args, $body['params']['args'] );
	}

	/**
	 * Test rpc write call.
	 */
	public function test_rpc_write() {
		$model  = 'res.partner';
		$method = 'write';

		$payload   = array( 1 );
		$more_args = array( 'email' => 'partner@example.coop' );

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => $model,
				'method'   => $method,
			)
		);

		$response = $bridge->submit( $payload, $more_args );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertTrue( $response['data']['result'] );

		$body = json_decode( self::$request['args']['body'], true );

		// More args should be appended to the args array.
		$expected_args = array(
			'odoo',
			1,
			'password123',
			$model,
			$method,
			$payload,
			$more_args,
		);
		$this->assertEquals( $expected_args, $body['params']['args'] );
	}

	/**
	 * Test rpc_response returns WP_Error when input is WP_Error.
	 */
	public function test_rpc_response_returns_wp_error_passthrough() {
		self::$mock_response = new WP_Error( 'http_error', 'Connection failed' );

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => 'res.partner',
				'method'   => 'search',
			)
		);

		$response = $bridge->submit();

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'http_error', $response->get_error_code() );
		$this->assertEquals( 'Connection failed', $response->get_error_message() );
	}

	/**
	 * Test rpc_response handles empty data response.
	 */
	public function test_rpc_response_handles_empty_data() {
		self::$mock_response = array(
			'headers' => array( 'Content-Type' => 'text/html' ),
			'data'    => null,
		);

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => 'res.partner',
				'method'   => 'search',
			)
		);

		$response = $bridge->submit();

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'unkown_content_type', $response->get_error_code() );
	}

	/**
	 * Test rpc_response handles RPC error responses.
	 */
	public function test_rpc_response_handles_rpc_error() {
		self::$mock_response = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'data'    => array(
				'jsonrpc' => '2.0',
				'id'      => 'test-session',
				'error'   => array(
					'code'    => 100,
					'message' => 'Odoo Server Error',
					'data'    => array(
						'name'    => 'odoo.exceptions.AccessError',
						'debug'   => 'Access denied',
						'message' => 'You do not have access to this resource',
					),
				),
			),
		);

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => 'res.partner',
				'method'   => 'search',
			)
		);

		$response = $bridge->submit();

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'response_code_100', $response->get_error_code() );
		$this->assertEquals( 'Odoo Server Error', $response->get_error_message() );
	}

	/**
	 * Test rpc_response handles empty result.
	 */
	public function test_rpc_response_handles_empty_result() {
		self::$mock_response = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'data'    => array(
				'jsonrpc' => '2.0',
				'id'      => 'test-session',
				'result'  => null,
			),
		);

		$bridge = new Odoo_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => 'res.partner',
				'method'   => 'search',
			)
		);

		$response = $bridge->submit();

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'not_found', $response->get_error_code() );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'odoo' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		self::$mock_response = array(
			'data' => array(
				'jsonrpc' => '2.0',
				'id'      => self::SESSION_ID,
				'result'  => array(
					array(
						'name'  => 'Partner',
						'model' => 'res.partner',
					),
					array(
						'name'  => 'Product Template',
						'model' => 'product.product',
					),
					array(
						'name'  => 'Lead/Opportunity',
						'model' => 'crm.lead',
					),
				),
			),
		);

		$addon     = Addon::addon( 'odoo' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertContains( 'res.partner', $endpoints );
		$this->assertContains( 'product.product', $endpoints );
		$this->assertContains( 'crm.lead', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method.
	 */
	public function test_addon_get_endpoint_schema() {
		$addon  = Addon::addon( 'odoo' );
		$schema = $addon->get_endpoint_schema(
			'res.partner',
			self::BACKEND_NAME,
			'create'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'id', $field_names );
		$this->assertContains( 'name', $field_names );
		$this->assertContains( 'email', $field_names );
	}
}
