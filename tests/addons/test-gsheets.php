<?php
/**
 * Class GSheetsTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\GSheets_Form_Bridge;
use FORMS_BRIDGE\GSheets_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Google Sheets addon test case.
 */
class GSheetsTest extends WP_UnitTestCase {

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
	 * Mock spreadsheet ID.
	 *
	 * @var string
	 */
	private const SPREADSHEET_ID = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';

	/**
	 * Test credential provider.
	 *
	 * @return Credential[]
	 */
	public static function credentials_provider() {
		return array(
			new Credential(
				array(
					'name'          => 'gsheets-test-credential',
					'schema'        => 'OAuth',
					'oauth_url'     => 'https://accounts.google.com/o/oauth2/v2',
					'client_id'     => 'test-client-id',
					'client_secret' => 'test-client-secret',
					'access_token'  => 'test-access-token-12345',
					'expires_at'    => time() + 3600,
					'refresh_token' => 'test-refresh-token',
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
					'name'       => 'gsheets-test-backend',
					'base_url'   => 'https://sheets.googleapis.com/v4/spreadsheets',
					'credential' => 'gsheets-test-credential',
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

		// Parse the URL to determine the operation.
		$parsed_url = wp_parse_url( $url );
		$path       = $parsed_url['path'] ?? '';
		$query      = $parsed_url['query'] ?? '';

		// Return appropriate mock response based on path and method.
		if ( self::$mock_response ) {
			$response_body = self::$mock_response;
		} else {
			$response_body = self::get_mock_response( $path, $query, $args );
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
	 * Get mock response based on API path.
	 *
	 * @param string $path API path.
	 * @param string $query Query string.
	 * @param array  $args Request arguments.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $path, $query, $args ) {
		$method = $args['method'] ?? 'GET';

		// Get spreadsheet metadata (list of sheets).
		if ( preg_match( '/\/spreadsheets\/([^\/]+)$/', $path, $matches ) && 'GET' === $method ) {
			return array(
				'spreadsheetId' => self::SPREADSHEET_ID,
				'properties'    => array(
					'title'    => 'Test Spreadsheet',
					'locale'   => 'en_US',
					'timeZone' => 'America/New_York',
				),
				'sheets'        => array(
					array(
						'properties' => array(
							'sheetId'   => 0,
							'title'     => 'Sheet1',
							'index'     => 0,
							'sheetType' => 'GRID',
						),
					),
					array(
						'properties' => array(
							'sheetId'   => 1234567890,
							'title'     => 'Contacts',
							'index'     => 1,
							'sheetType' => 'GRID',
						),
					),
				),
			);
		}

		// Get sheet values (headers or data).
		if ( strpos( $path, '/values/' ) !== false && 'GET' === $method ) {
			// Extract sheet name from path.
			if ( preg_match( '/\/values\/([^!]+)!/', $path, $matches ) ) {
				$sheet = urldecode( $matches[1] );

				// Return headers for the first row request.
				if ( strpos( $path, '!1:1' ) !== false ) {
					return array(
						'range'          => "{$sheet}!A1:Z1",
						'majorDimension' => 'ROWS',
						'values'         => array(
							array( 'Name', 'Email', 'Phone', 'Company' ),
						),
					);
				}

				// Return data rows.
				return array(
					'range'          => "{$sheet}!A1:D10",
					'majorDimension' => 'ROWS',
					'values'         => array(
						array( 'Name', 'Email', 'Phone', 'Company' ),
						array( 'John Doe', 'john@example.coop', '555-1234', 'Acme Corp' ),
						array( 'Jane Smith', 'jane@example.coop', '555-5678', 'Test Inc' ),
					),
				);
			}

			return array(
				'range'          => 'Sheet1!A1:Z1',
				'majorDimension' => 'ROWS',
				'values'         => array(),
			);
		}

		// Append values to sheet.
		if ( strpos( $path, '/values/' ) !== false && strpos( $path, ':append' ) !== false && 'POST' === $method ) {
			$body = json_decode( $args['body'], true );
			return array(
				'spreadsheetId' => self::SPREADSHEET_ID,
				'tableRange'    => 'Contacts!A1:D1',
				'updates'       => array(
					'spreadsheetId'  => self::SPREADSHEET_ID,
					'updatedRange'   => 'Contacts!A2:D2',
					'updatedRows'    => 1,
					'updatedColumns' => 4,
					'updatedCells'   => 4,
				),
			);
		}

		// Create new sheet (batchUpdate).
		if ( strpos( $path, ':batchUpdate' ) !== false && 'POST' === $method ) {
			$body = json_decode( $args['body'], true );
			return array(
				'spreadsheetId' => self::SPREADSHEET_ID,
				'replies'       => array(
					array(
						'addSheet' => array(
							'properties' => array(
								'sheetId'   => time(),
								'title'     => $body['requests'][0]['addSheet']['properties']['title'] ?? 'New Sheet',
								'index'     => $body['requests'][0]['addSheet']['properties']['index'] ?? 0,
								'sheetType' => 'GRID',
							),
						),
					),
				),
			);
		}

		// List spreadsheets (Drive API).
		if ( strpos( $path, '/drive/v3/files' ) !== false ) {
			return array(
				'kind'  => 'drive#fileList',
				'files' => array(
					array(
						'id'       => self::SPREADSHEET_ID,
						'name'     => 'Test Spreadsheet',
						'mimeType' => 'application/vnd.google-apps.spreadsheet',
					),
					array(
						'id'       => '2CxjNWt1YSB6oGNeLvCeEZkmVVrqsumct85PhuF3vqnt',
						'name'     => 'Another Spreadsheet',
						'mimeType' => 'application/vnd.google-apps.spreadsheet',
					),
				),
			);
		}

		// Default response.
		return array(
			'success' => true,
		);
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\GSheets_Addon' ) );
		$this->assertEquals( 'Google Sheets', GSheets_Addon::TITLE );
		$this->assertEquals( 'gsheets', GSheets_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\GSheets_Form_Bridge', GSheets_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\GSheets_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-gsheets-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test appending data to existing sheet.
	 */
	public function test_append_to_existing_sheet() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-append-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'    => 'Alice Johnson',
			'Email'   => 'alice@example.coop',
			'Phone'   => '555-9999',
			'Company' => 'NewCo',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'updates', $response['data'] );
		$this->assertEquals( 1, $response['data']['updates']['updatedRows'] );
	}

	/**
	 * Test appending data to new sheet (auto-creates sheet).
	 */
	public function test_append_to_new_sheet() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-new-sheet-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'NewSheet',
			)
		);

		$payload = array(
			'Name'  => 'Bob Smith',
			'Email' => 'bob@example.coop',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test appending data with headers auto-creation.
	 */
	public function test_append_creates_headers() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-headers-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'EmptySheet',
			)
		);

		// Reset mock after getting sheets list.
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				if ( strpos( $url, '/values/' ) !== false && strpos( $url, '!1:1' ) !== false ) {
					return array(
						'response'      => array(
							'code'    => 200,
							'message' => 'Success',
						),
						'headers'       => array( 'Content-Type' => 'application/json' ),
						'cookies'       => array(),
						'body'          => wp_json_encode(
							array(
								'range'          => 'EmptySheet!A1:Z1',
								'majorDimension' => 'ROWS',
								'values'         => array(),
							)
						),
						'http_response' => null,
					);
				}
				return $pre;
			},
			11,
			3
		);

		$payload = array(
			'Field1' => 'Value1',
			'Field2' => 'Value2',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
	}

	/**
	 * Test flattening nested payload.
	 */
	public function test_flatten_nested_payload() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-flatten-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'    => 'Test User',
			'Email'   => 'test@example.coop',
			'Address' => array(
				'Street' => '123 Main St',
				'City'   => 'New York',
				'State'  => 'NY',
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test flattening array values to comma-separated string.
	 */
	public function test_flatten_array_values() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-array-values-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'     => 'Test User',
			'Email'    => 'test@example.coop',
			'Tags'     => array( 'customer', 'premium', 'active' ),
			'Products' => array( 'Product A', 'Product B' ),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test getting headers from sheet.
	 */
	public function test_get_headers() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-headers-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$headers = $bridge->get_headers();

		$this->assertFalse( is_wp_error( $headers ) );
		$this->assertIsArray( $headers );
		$this->assertContains( 'Name', $headers );
		$this->assertContains( 'Email', $headers );
		$this->assertContains( 'Phone', $headers );
		$this->assertContains( 'Company', $headers );
	}

	/**
	 * Test error when backend is invalid.
	 */
	public function test_error_invalid_backend() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$response = $bridge->submit( array( 'Name' => 'Test' ) );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'gsheets' );
		$response = $addon->ping( 'gsheets-test-backend' );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon ping with invalid backend host.
	 */
	public function test_addon_ping_invalid_host() {
		Backend::temp_registration(
			array(
				'name'       => 'gsheets-invalid-host-backend',
				'base_url'   => 'https://wrong.example.coop',
				'credential' => 'gsheets-test-credential',
				'headers'    => array(),
			)
		);

		$addon    = Addon::addon( 'gsheets' );
		$response = $addon->ping( 'gsheets-invalid-host-backend' );

		$this->assertFalse( $response );
	}

	/**
	 * Test addon ping without credential.
	 */
	public function test_addon_ping_no_credential() {
		Backend::temp_registration(
			array(
				'name'       => 'gsheets-no-cred-backend',
				'base_url'   => 'https://sheets.googleapis.com/v4/spreadsheets',
				'credential' => 'non-existent-credential',
				'headers'    => array(),
			)
		);

		$addon    = Addon::addon( 'gsheets' );
		$response = $addon->ping( 'gsheets-no-cred-backend' );

		$this->assertFalse( $response );
	}

	/**
	 * Test addon fetch method (list spreadsheets).
	 */
	public function test_addon_fetch() {
		$addon    = Addon::addon( 'gsheets' );
		$response = $addon->fetch( '', 'gsheets-test-backend' );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'files', $response['data'] );
		$this->assertNotEmpty( $response['data']['files'] );
	}

	/**
	 * Test addon get_endpoint_schema method for POST.
	 */
	public function test_addon_get_endpoint_schema_post() {
		// First create a bridge so it can be found by get_endpoint_schema.
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-schema-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		// Register the bridge temporarily.
		$bridges = array( $bridge );
		tests_add_filter(
			'forms_bridge_bridges',
			function ( $existing_bridges, $addon ) use ( $bridges ) {
				if ( 'gsheets' === $addon ) {
					return $bridges;
				}

				return $existing_bridges;
			},
			10,
			2
		);

		$addon  = Addon::addon( 'gsheets' );
		$schema = $addon->get_endpoint_schema(
			'/' . self::SPREADSHEET_ID,
			'gsheets-test-backend',
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'Name', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertContains( 'Phone', $field_names );
		$this->assertContains( 'Company', $field_names );
	}

	/**
	 * Test addon get_endpoint_schema method for GET returns empty.
	 */
	public function test_addon_get_endpoint_schema_get() {
		$addon  = Addon::addon( 'gsheets' );
		$schema = $addon->get_endpoint_schema(
			'/' . self::SPREADSHEET_ID,
			'gsheets-test-backend',
			'GET'
		);

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test appending with partial data (missing columns).
	 */
	public function test_append_partial_data() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-partial-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'  => 'Partial User',
			'Email' => 'partial@example.coop',
			// Missing Phone and Company.
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test appending with extra fields not in headers.
	 */
	public function test_append_extra_fields() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-extra-fields-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'       => 'Extra User',
			'Email'      => 'extra@example.coop',
			'Phone'      => '555-0000',
			'Company'    => 'Extra Corp',
			'ExtraField' => 'This should be ignored',
			'Another'    => 'Also ignored',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test empty payload handling.
	 */
	public function test_empty_payload() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-empty-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$response = $bridge->submit( array() );

		$this->assertFalse( is_wp_error( $response ) );
	}

	/**
	 * Test case-insensitive sheet name matching.
	 */
	public function test_case_insensitive_sheet_name() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-case-insensitive-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'CONTACTS',
			)
		);

		$payload = array(
			'Name'  => 'Case Test',
			'Email' => 'case@example.coop',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
	}

	/**
	 * Test PUT method (should work like POST).
	 */
	public function test_put_method() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-put-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'PUT',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'    => 'PUT User',
			'Email'   => 'put@example.coop',
			'Phone'   => '555-1111',
			'Company' => 'PUT Corp',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}

	/**
	 * Test complex nested structure flattening.
	 */
	public function test_complex_nested_flattening() {
		$bridge = new GSheets_Form_Bridge(
			array(
				'name'     => 'test-complex-bridge',
				'backend'  => 'gsheets-test-backend',
				'endpoint' => '/' . self::SPREADSHEET_ID,
				'method'   => 'POST',
				'tab'      => 'Contacts',
			)
		);

		$payload = array(
			'Name'     => 'Complex User',
			'Email'    => 'complex@example.coop',
			'Metadata' => array(
				'Source'   => 'Web Form',
				'Campaign' => array(
					'Name' => 'Spring 2024',
					'Type' => 'Email',
				),
				'Tags'     => array( 'lead', 'qualified' ),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
	}
}
