<?php
/**
 * Class CustomHooksTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Form_Bridge;

/**
 * Custom hooks test case.
 */
class CustomHooksTest extends WP_UnitTestCase {
	/**
	 * Handles the last intercepted http request data.
	 *
	 * @var array
	 */
	private static $request;

	public static function forms_provider() {
		return array(
			array(
				'_id'    => 'gf:1',
				'id'     => '1',
				'title'  => 'test-form',
				'fields' => array(),
			),
		);
	}

	/**
	 * HTTP requests interceptor. Prevent test to access the network and store the request arguments
	 * on the static $request attribute.
	 *
	 * @param mixed  $pre Initial pre hook value.
	 * @param array  $args Request arguments.
	 * @param string $url Request URL.
	 *
	 * @return array
	 */
	public function pre_http_request( $pre, $args, $url ) {
		self::$request = array(
			'args' => $args,
			'url'  => $url,
		);

		return array(
			'response'      => array(
				'code'    => 200,
				'message' => 'Success',
			),
			'headers'       => array( 'Content-Type' => 'application/json' ),
			'cookies'       => array(),
			'body'          => '{"success":true}',
			'http_response' => null,
		);
	}

	public static function set_up_before_class() {
		add_filter( 'forms_bridge_forms', array( self::class, 'forms_provider' ), 10, 0 );

		$result = FBAPI::save_credential(
			array(
				'name'          => 'test-basic',
				'schema'        => 'Basic',
				'client_id'     => 'foo',
				'client_secret' => 'bar',
			)
		);

		if ( ! $result ) {
			throw new Exception( 'Can not create the credential' );
		}

		$result = FBAPI::save_backend(
			array(
				'name'       => 'test-backend',
				'base_url'   => 'https://example.coop',
				'credential' => 'test-basic',
				'headers'    => array(
					array(
						'name'  => 'Content-Type',
						'value' => 'application/json',
					),
				),
			),
		);

		if ( ! $result ) {
			throw new Exception( 'Can not create the backend' );
		}

		$result = FBAPI::save_bridge(
			array(
				'name'          => 'test-bridge',
				'form_id'       => 'gf:1',
				'backend'       => 'test-backend',
				'endpoint'      => '/api/endpoint',
				'method'        => 'POST',
				'custom_fields' => array(
					array(
						'name'  => 'a',
						'value' => 'b',
					),
				),
				'mutations'     => array(
					array(
						array(
							'from' => 'foo',
							'to'   => 'boofoo',
							'cast' => 'string',
						),
					),
				),
			),
			'rest'
		);

		if ( ! $result ) {
			throw new Exception( 'Can not create the bridge' );
		}
	}

	public function set_up() {
		parent::set_up();
		add_filter( 'pre_http_request', array( $this, 'pre_http_request' ), 10, 3 );
	}

	public static function tear_down_after_class() {
		remove_filter( 'forms_bridge_forms', array( self::class, 'forms_provider' ), 10, 0 );
		FBAPI::delete_backend( 'test-backend' );
		FBAPI::delete_bridge( 'test-bridge', 'rest' );
		FBAPI::delete_credential( 'test-credential' );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'pre_http_request' ), 10, 3 );
		parent::tear_down();
	}

	public function test_backends() {
		/**
		 * Array of available bridges.
		 *
		 * @var HTTP_BRIDGE\Backend[] $backends
		 */
		$backends = apply_filters( 'http_bridge_backends', array() );

		$this->assertEquals( count( $backends ), 1 );

		$backend = $backends[0];
		$this->assertSame( $backend->name, 'test-backend' );
		$this->assertSame( $backend->base_url, 'https://example.coop' );
		$this->assertSame( $backend->url( '/api/endpoint' ), 'https://example.coop/api/endpoint' );

		$response = $backend->post( '/api/endpoint', array( 'foo' => 'bar' ) );

		$this->assertTrue( $response['data']['success'] );

		$this->assertSame( self::$request['url'], 'https://example.coop/api/endpoint' );
		$this->assertSame( self::$request['args']['body'], '{"foo":"bar"}' );
		$this->assertTrue( isset( self::$request['args']['headers']['Authorization'] ) );
	}

	public function test_bridges() {
		/**
		 * Array of available bridges.
		 *
		 * @var Form_Bridge[] $bridges
		 */
		$bridges = apply_filters( 'forms_bridge_bridges', array() );

		$this->assertEquals( count( $bridges ), 1 );

		$bridge = $bridges[0];
		$this->assertSame( $bridge->name, 'test-bridge' );
		$this->assertSame( $bridge->backend->name, 'test-backend' );
		$this->assertSame( $bridge->endpoint, '/api/endpoint' );

		$payload  = array( 'foo' => 'bar' );
		$payload  = $bridge->add_custom_fields( $payload );
		$payload  = $bridge->apply_mutation( $payload );
		$response = $bridge->submit( $payload );
		$this->assertTrue( $response['data']['success'] );

		$this->assertSame( self::$request['url'], 'https://example.coop/api/endpoint' );
		$this->assertTrue( isset( self::$request['args']['headers']['Authorization'] ) );

		$body = json_decode( self::$request['args']['body'], true );
		$this->assertSame( $body['boofoo'], 'bar' );
		$this->assertSame( $body['a'], 'b' );
	}
}
