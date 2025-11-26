<?php
/**
 * Class GCalendarTest
 *
 * @package formsbridge-tests
 */

use FORMS_BRIDGE\GCalendar_Form_Bridge;
use FORMS_BRIDGE\GCalendar_Addon;
use FORMS_BRIDGE\Addon;
use HTTP_BRIDGE\Backend;
use HTTP_BRIDGE\Credential;

/**
 * Google Calendar addon test case.
 */
class GCalendarTest extends WP_UnitTestCase {

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
					'name'          => 'gcalendar-test-credential',
					'schema'        => 'Bearer',
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
					'name'       => 'gcalendar-test-backend',
					'base_url'   => 'https://www.googleapis.com',
					'credential' => 'gcalendar-test-credential',
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

		// Return appropriate mock response based on path and method.
		if ( self::$mock_response ) {
			$response_body = self::$mock_response;
		} else {
			$response_body = self::get_mock_response( $path, $args );
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
	 * @param array  $args Request arguments.
	 *
	 * @return array Mock response.
	 */
	private static function get_mock_response( $path, $args ) {
		$method = $args['method'] ?? 'GET';

		if ( strpos( $path, '/calendarList' ) !== false ) {
			// Calendar list endpoint.
			return array(
				'kind'  => 'calendar#calendarList',
				'items' => array(
					array(
						'id'      => 'primary',
						'summary' => 'Primary Calendar',
					),
					array(
						'id'      => 'test@group.calendar.google.com',
						'summary' => 'Test Calendar',
					),
				),
			);
		}

		if ( strpos( $path, '/events' ) !== false && 'POST' === $method ) {
			// Create event endpoint.
			$body  = json_decode( $args['body'], true );
			$event = array_merge(
				array(
					'kind'     => 'calendar#event',
					'id'       => 'test-event-id-' . time(),
					'status'   => 'confirmed',
					'htmlLink' => 'https://www.google.com/calendar/event?eid=test',
					'created'  => gmdate( 'c' ),
					'updated'  => gmdate( 'c' ),
				),
				$body
			);
			return $event;
		}

		if ( strpos( $path, '/events' ) !== false && 'PUT' === $method ) {
			// Update event endpoint.
			$body = json_decode( $args['body'], true );
			return array_merge(
				array(
					'kind'    => 'calendar#event',
					'id'      => 'test-event-id-12345',
					'status'  => 'confirmed',
					'updated' => gmdate( 'c' ),
				),
				$body
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
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\GCalendar_Addon' ) );
		$this->assertEquals( 'Google Calendar', GCalendar_Addon::TITLE );
		$this->assertEquals( 'gcalendar', GCalendar_Addon::NAME );
		$this->assertEquals( '\FORMS_BRIDGE\GCalendar_Form_Bridge', GCalendar_Addon::BRIDGE );
	}

	/**
	 * Test that the form bridge class exists.
	 */
	public function test_form_bridge_class_exists() {
		$this->assertTrue( class_exists( 'FORMS_BRIDGE\GCalendar_Form_Bridge' ) );
	}

	/**
	 * Test bridge validation with valid data.
	 */
	public function test_bridge_validation() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-gcalendar-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test bridge validation with invalid data.
	 */
	public function test_bridge_validation_invalid() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name' => 'invalid-bridge',
				// Missing required fields.
			)
		);

		$this->assertTrue( $bridge->is_valid );
	}

	/**
	 * Test creating a calendar event with all fields.
	 */
	public function test_create_event() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-create-event-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary'     => 'Team Meeting',
			'description' => 'Weekly team sync meeting',
			'location'    => 'Conference Room A',
			'start'       => '2024-03-20T10:00:00',
			'end'         => '2024-03-20T11:00:00',
			'attendees'   => 'john@example.coop,jane@example.coop',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'id', $response['data'] );
		$this->assertEquals( 'Team Meeting', $response['data']['summary'] );
		$this->assertEquals( 'Weekly team sync meeting', $response['data']['description'] );
		$this->assertEquals( 'Conference Room A', $response['data']['location'] );
	}

	/**
	 * Test creating event with minimal fields.
	 */
	public function test_create_event_minimal() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-minimal-event-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Quick Meeting',
			'start'   => '2024-03-20T14:00:00',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertEquals( 'Quick Meeting', $response['data']['summary'] );
		$this->assertArrayHasKey( 'end', $response['data'] );
	}

	/**
	 * Test creating event with numeric timestamp.
	 */
	public function test_create_event_with_timestamp() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-timestamp-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$start_time = strtotime( '2024-03-20 10:00:00' );
		$end_time   = strtotime( '2024-03-20 11:00:00' );

		$payload = array(
			'summary' => 'Timestamp Event',
			'start'   => $start_time,
			'end'     => $end_time,
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'start', $response['data'] );
		$this->assertArrayHasKey( 'dateTime', $response['data']['start'] );
	}

	/**
	 * Test creating event with attendees as array.
	 */
	public function test_create_event_attendees_array() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-attendees-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary'   => 'Team Meeting',
			'start'     => '2024-03-20T10:00:00',
			'attendees' => array( 'john@example.coop', 'jane@example.coop' ),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertArrayHasKey( 'attendees', $response['data'] );
		$this->assertCount( 2, $response['data']['attendees'] );
	}

	/**
	 * Test creating event with structured attendees.
	 */
	public function test_create_event_attendees_structured() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-structured-attendees-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary'   => 'Important Meeting',
			'start'     => '2024-03-20T10:00:00',
			'attendees' => array(
				array(
					'email'    => 'john@example.coop',
					'optional' => false,
				),
				array(
					'email'    => 'jane@example.coop',
					'optional' => true,
				),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'attendees', $response['data'] );
	}

	/**
	 * Test error when summary is missing.
	 */
	public function test_error_missing_summary() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-missing-summary-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'start' => '2024-03-20T10:00:00',
			'end'   => '2024-03-20T11:00:00',
		);

		$response = $bridge->submit( $payload );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'missing_summary', $response->get_error_code() );
	}

	/**
	 * Test error when start date is missing.
	 */
	public function test_error_missing_start_date() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-missing-start-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Meeting',
		);

		$response = $bridge->submit( $payload );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'missing_event_dates', $response->get_error_code() );
	}

	/**
	 * Test automatic end time calculation.
	 */
	public function test_auto_end_time() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-auto-end-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Auto End Meeting',
			'start'   => '2024-03-20T10:00:00',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'end', $response['data'] );
		$this->assertArrayHasKey( 'dateTime', $response['data']['end'] );
	}

	/**
	 * Test creating event with color.
	 */
	public function test_create_event_with_color() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-color-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Colored Event',
			'start'   => '2024-03-20T10:00:00',
			'colorId' => '5',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'colorId', $response['data'] );
		$this->assertEquals( '5', $response['data']['colorId'] );
	}

	/**
	 * Test creating event with reminders.
	 */
	public function test_create_event_with_reminders() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-reminders-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary'   => 'Event with Reminders',
			'start'     => '2024-03-20T10:00:00',
			'reminders' => array(
				'useDefault' => false,
				'overrides'  => array(
					array(
						'method'  => 'email',
						'minutes' => 1440,
					),
					array(
						'method'  => 'popup',
						'minutes' => 10,
					),
				),
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'reminders', $response['data'] );
	}

	/**
	 * Test invalid backend handling.
	 */
	public function test_invalid_backend() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-invalid-backend-bridge',
				'backend'  => 'non-existent-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Test Event',
			'start'   => '2024-03-20T10:00:00',
		);

		$response = $bridge->submit( $payload );

		$this->assertTrue( is_wp_error( $response ) );
		$this->assertEquals( 'invalid_backend', $response->get_error_code() );
	}

	/**
	 * Test addon ping method.
	 */
	public function test_addon_ping() {
		$addon    = Addon::addon( 'gcalendar' );
		$response = $addon->ping( 'gcalendar-test-backend' );

		$this->assertTrue( $response );
	}

	/**
	 * Test addon ping with invalid backend.
	 */
	public function test_addon_ping_invalid_backend() {
		Backend::temp_registration(
			array(
				'name'       => 'gcalendar-invalid-host-backend',
				'base_url'   => 'https://wrong.example.coop',
				'credential' => 'gcalendar-test-credential',
				'headers'    => array(),
			)
		);

		$addon    = Addon::addon( 'gcalendar' );
		$response = $addon->ping( 'gcalendar-invalid-host-backend' );

		$this->assertFalse( $response );
	}

	/**
	 * Test addon get_endpoint_schema method for POST.
	 */
	public function test_addon_get_endpoint_schema_post() {
		Backend::temp_registration(
			array(
				'name'       => 'gcalendar-test-backend',
				'base_url'   => 'https://www.googleapis.com',
				'credential' => 'gcalendar-test-credential',
				'headers'    => array(),
			)
		);

		$addon  = Addon::addon( 'gcalendar' );
		$schema = $addon->get_endpoint_schema(
			'/calendar/v3/calendars/primary/events',
			'gcalendar-test-backend',
			'POST'
		);

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		$field_names = array_column( $schema, 'name' );
		$this->assertContains( 'summary', $field_names );
		$this->assertContains( 'description', $field_names );
		$this->assertContains( 'location', $field_names );
		$this->assertContains( 'start', $field_names );
		$this->assertContains( 'end', $field_names );
		$this->assertContains( 'attendees', $field_names );
	}

	/**
	 * Test addon get_endpoint_schema method for GET returns empty.
	 */
	public function test_addon_get_endpoint_schema_get() {
		Backend::temp_registration(
			array(
				'name'       => 'gcalendar-test-backend',
				'base_url'   => 'https://www.googleapis.com',
				'credential' => 'gcalendar-test-credential',
				'headers'    => array(),
			)
		);

		$addon  = Addon::addon( 'gcalendar' );
		$schema = $addon->get_endpoint_schema(
			'/calendar/v3/calendars/primary/events',
			'gcalendar-test-backend',
			'GET'
		);

		$this->assertIsArray( $schema );
		$this->assertEmpty( $schema );
	}

	/**
	 * Test invalid email in attendees is filtered out.
	 */
	public function test_invalid_email_filtered() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-invalid-email-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary'   => 'Meeting',
			'start'     => '2024-03-20T10:00:00',
			'attendees' => 'valid@example.coop,invalid-email,another@example.coop',
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'attendees', $response['data'] );
		$this->assertCount( 2, $response['data']['attendees'] );
	}

	/**
	 * Test creating event with already structured datetime.
	 */
	public function test_structured_datetime() {
		$bridge = new GCalendar_Form_Bridge(
			array(
				'name'     => 'test-structured-datetime-bridge',
				'backend'  => 'gcalendar-test-backend',
				'endpoint' => '/calendar/v3/calendars/primary/events',
				'method'   => 'POST',
			)
		);

		$payload = array(
			'summary' => 'Structured DateTime Event',
			'start'   => array(
				'dateTime' => '2024-03-20T10:00:00',
				'timeZone' => 'America/New_York',
			),
			'end'     => array(
				'dateTime' => '2024-03-20T11:00:00',
				'timeZone' => 'America/New_York',
			),
		);

		$response = $bridge->submit( $payload );

		$this->assertFalse( is_wp_error( $response ) );
		$this->assertArrayHasKey( 'start', $response['data'] );
		$this->assertEquals( '2024-03-20T10:00:00', $response['data']['start']['dateTime'] );
		$this->assertEquals( 'America/New_York', $response['data']['start']['timeZone'] );
	}
}
