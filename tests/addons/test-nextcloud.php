<?php
/**
 * Class NextcloudTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\Nextcloud_Form_Bridge;
use FORMS_BRIDGE\Nextcloud_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Nextcloud test case.
 */
class NextcloudTest extends WP_UnitTestCase {

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
	private const BACKEND_NAME = 'test-nextcloud-backend';

	/**
	 * Holds the mocked backend base URL.
	 *
	 * @var string
	 */
	private const BACKEND_URL = 'https://nextcloud.example.com';

	/**
	 * Holds the mocked credential name.
	 *
	 * @var string
	 */
	private const CREDENTIAL_NAME = 'test-nextcloud-credential';

	/**
	 * Holds the mocked bridge name.
	 *
	 * @var string
	 */
	private const BRIDGE_NAME = 'test-nextcloud-bridge';

	/**
	 * Holds the mocked user ID.
	 *
	 * @var string
	 */
	private const USER_ID = 'testuser';

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
					'client_id'     => self::USER_ID,
					'client_secret' => 'test-user-password',
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
							'value' => 'application/octet-stream',
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

		$response = array(
			'response'      => array(
				'code'    => 201,
				'message' => 'Created',
			),
			'headers'       => array( 'Content-Type' => 'text/html' ),
			'cookies'       => array(),
			'body'          => '',
			'http_response' => null,
		);

		$method = $args['method'] ?? 'PUT';

		// Parse URL to determine the endpoint being called.
		$parsed_url = wp_parse_url( $url );
		$path       = $parsed_url['path'] ?? '';

		// Parse the body to determine the method being called.
		$body = '';
		if ( ! empty( $args['body'] ) ) {
			$body = $args['body'];
		}

		// Return appropriate mock response based on endpoint.
		if ( self::$mock_response ) {
			if ( ! empty( self::$mock_response['http'] ) ) {
				$response['response'] = self::$mock_response['http'];
			}

			$response_body = self::$mock_response['body'];

			self::$mock_response = null;
		} else {
			$response_body = self::get_mock_response( $method, $path, $body );
		}

		if ( $response_body ) {
			$response['headers'] = array( 'Content-Type' => 'text/xml' );
			$response['body']    = $response_body;
		}

		return $response;
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
		// Mock PROPFIND response for file listing
		if ( false !== strpos( $path, '/remote.php/dav/files/' ) && 'PROPFIND' === $method ) {
			return '<?xml version="1.0" encoding="utf-8" ?>
			<d:multistatus xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
				<d:response>
					<d:href>/remote.php/dav/files/' . self::USER_ID . '/</d:href>
					<d:propstat>
						<d:prop>
							<d:resourcetype><d:collection/></d:resourcetype>
						</d:prop>
						<d:status>HTTP/1.1 200 OK</d:status>
					</d:propstat>
				</d:response>
				<d:response>
					<d:href>/remote.php/dav/files/' . self::USER_ID . '/test.csv</d:href>
					<d:propstat>
						<d:prop>
							<d:resourcetype/>
							<d:getcontentlength>1024</d:getcontentlength>
							<d:getlastmodified>Mon, 01 Jan 2024 00:00:00 GMT</d:getlastmodified>
						</d:prop>
						<d:status>HTTP/1.1 200 OK</d:status>
					</d:propstat>
				</d:response>
				<d:response>
					<d:href>/remote.php/dav/files/' . self::USER_ID . '/directory/</d:href>
					<d:propstat>
						<d:prop>
							<d:resourcetype><d:collection/></d:resourcetype>
						</d:prop>
						<d:status>HTTP/1.1 200 OK</d:status>
					</d:propstat>
				</d:response>
			</d:multistatus>';
		}

		// Default empty response
		return '';
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

		$uploads_path = FORMS_BRIDGE\Forms_Bridge::upload_dir() . '/nextcloud';
		$test_file    = $uploads_path . '/test.csv';

		if ( is_file( $test_file ) ) {
			unlink( $test_file );
		}

		parent::tear_down();
	}

	/**
	 * Test that the addon class exists and has correct constants.
	 */
	public function test_addon_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Nextcloud_Addon' ) );
		$this->assertEquals( 'Nextcloud', Nextcloud_Addon::TITLE );
		$this->assertEquals( 'nextcloud', Nextcloud_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\Nextcloud_Form_Bridge', Nextcloud_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\Nextcloud_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
		$this->assertEquals( '/', $bridge->endpoint );
		$this->assertEquals( '', $bridge->backend );
		$this->assertEquals( '', $bridge->form_id );
		$this->assertEquals( 'PUT', $bridge->method );
	}

	/**
	 * Test PUT request to upload CSV file.
	 */
	public function test_put_upload_csv() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
			)
		);

		$payload = array(
			'Name'  => 'John Doe',
			'Email' => 'john.doe@example.com',
			'Score' => 99,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertEquals( 201, $response['response']['code'] );
		$this->assertEmpty( $response['body'] );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'nextcloud' );
		$response = $addon->ping( self::BACKEND_NAME );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon fetch method to get files.
	 */
	public function test_addon_fetch_files() {
		$addon    = Addon::addon( 'nextcloud' );
		$response = $addon->fetch( 'files', self::BACKEND_NAME );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'files', $response['data'] );
		$this->assertCount( 1, $response['data']['files'] );
		$this->assertEquals( 'test.csv', $response['data']['files'][0]['path'] );
	}

	/**
	 * Test addon get_endpoints method.
	 */
	public function test_addon_get_endpoints() {
		$addon     = Addon::addon( 'nextcloud' );
		$endpoints = $addon->get_endpoints( self::BACKEND_NAME );

		$this->assertIsArray( $endpoints );
		$this->assertNotEmpty( $endpoints );
		$this->assertCount( 2, $endpoints );
		$this->assertContains( 'test.csv', $endpoints );
		$this->assertContains( 'directory/', $endpoints );
	}

	/**
	 * Test addon get_endpoint_schema method for PUT.
	 */
	public function test_addon_get_endpoint_schema_put() {
		// Create a temporary CSV file for testing
		$uploads_path = FORMS_BRIDGE\Forms_Bridge::upload_dir() . '/nextcloud';
		$test_file    = $uploads_path . '/test.csv';

		// Create test CSV file
		if ( ! is_dir( dirname( $test_file ) ) ) {
			wp_mkdir_p( dirname( $test_file ) );
		}

		$csv_content = '"Name","Email","Score"
"John Doe","john.doe@example.com","99"';

		file_put_contents( $test_file, $csv_content );

		$addon  = Addon::addon( 'nextcloud' );
		$schema = $addon->get_endpoint_schema( '/test.csv', self::BACKEND_NAME, 'PUT' );

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Check that schema contains expected fields
		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'Name', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertContains( 'Score', $field_names );

		// Check field types
		$schema_map = array();
		foreach ( $schema as $field ) {
			$schema_map[ $field['name'] ] = $field['schema']['type'];
		}

		$this->assertEquals( 'string', $schema_map['Name'] );
		$this->assertEquals( 'string', $schema_map['Email'] );
		$this->assertEquals( 'string', $schema_map['Score'] );
	}

	/**
	 * Test addon get_endpoint_schema method for non-PUT methods.
	 */
	public function test_addon_get_endpoint_schema_non_put() {
		$addon  = Addon::addon( 'nextcloud' );
		$schema = $addon->get_endpoint_schema( '/test.csv', self::BACKEND_NAME, 'GET' );

		// Should return empty array for non-PUT methods
		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test error response handling.
	 */
	public function test_error_response_handling() {
		self::$mock_response = array(
			'http' => array(
				'code'    => 401,
				'message' => 'Unauthorized',
			),
			'body' => '<?xml version="1.0" encoding="utf-8"?>
			<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
				<s:exception>Sabre\DAV\Exception\NotAuthenticated</s:exception>
				<s:message>No public access to this resource., AppAPIAuth has not passed, This request is not for a federated calendar, Username or password was incorrect, No \'Authorization: Basic\' header found. Either the client didn\'t send one, or the server is mis-configured, Username or password was incorrect</s:message>
			</d:error>',
		);

		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertTrue( is_wp_error( $response ) );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
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
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
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

	/**
	 * Test CSV encoding and decoding.
	 */
	public function test_csv_encoding_decoding() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
			)
		);

		$payload = array(
			'Name'  => 'John Doe',
			'Email' => 'john.doe@example.coop',
			'Score' => 99,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );

		$body = '"Name","Email","Score"
"John Doe","john.doe@example.coop",99';

		$this->assertEquals( $body, self::$request['args']['body'] );
	}

	/**
	 * Test payload flattening.
	 */
	public function test_payload_flattening() {
		$bridge = new Nextcloud_Form_Bridge(
			array(
				'name'     => self::BRIDGE_NAME,
				'backend'  => self::BACKEND_NAME,
				'endpoint' => '/test.csv',
				'method'   => 'PUT',
			)
		);

		$payload = array(
			'Name'    => 'John Doe',
			'Contact' => array(
				'Email' => 'john.doe@example.coop',
				'Phone' => '1234567890',
			),
			'Score'   => 99,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );

		$body = '"Name","Contact.Email","Contact.Phone","Score"
"John Doe","john.doe@example.coop","1234567890",99';

		$this->assertEquals( $body, self::$request['args']['body'] );
	}
}
