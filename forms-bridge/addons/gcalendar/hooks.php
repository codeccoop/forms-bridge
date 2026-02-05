<?php
/**
 * Google Calendar addon hooks
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'gcalendar' !== $addon ) {
			return $schema;
		}

		$schema['properties']['endpoint']['default'] = '/calendars/v3/calendar/{$calendarId}/events';

		$schema['properties']['backend']['default'] = 'Google API';

		$schema['properties']['method']['enum']    = array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' );
		$schema['properties']['method']['default'] = 'POST';

		return $schema;
	},
	10,
	2
);

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'gcalendar' !== $addon ) {
			return $defaults;
		}

		$defaults = wpct_plugin_merge_object(
			array(
				'fields'     => array(
					array(
						'ref'      => '#credential',
						'name'     => 'name',
						'label'    => __( 'Name', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'   => '#credential',
						'name'  => 'schema',
						'type'  => 'text',
						'value' => 'OAuth',
					),
					array(
						'ref'   => '#credential',
						'name'  => 'oauth_url',
						'label' => __( 'Authorization URL', 'forms-bridge' ),
						'type'  => 'text',
						'value' => 'https://accounts.google.com/o/oauth2/v2',
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_id',
						'label'    => __( 'Client ID', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'client_secret',
						'label'    => __( 'Client secret', 'forms-bridge' ),
						'type'     => 'text',
						'required' => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'scope',
						'label'    => __( 'Scope', 'forms-bridge' ),
						'type'     => 'text',
						'value'    => 'https://www.googleapis.com/auth/calendar.readonly https://www.googleapis.com/auth/calendar.events',
						'required' => true,
					),
					array(
						'ref'      => '#bridge',
						'name'     => 'endpoint',
						'label'    => __( 'Calendar', 'forms-bridge' ),
						'type'     => 'select',
						'options'  => array(
							'endpoint' => '/calendar/v3/users/me/calendarList',
							'finger'   => array(
								'value' => 'items[].id',
								'label' => 'items[].summary',
							),
						),
						'required' => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
					array(
						'ref'      => '#bridge/custom_fields[]',
						'name'     => 'duration_hours',
						'label'    => __( 'Duration (Hours)', 'forms-bridge' ),
						'type'     => 'number',
						'required' => true,
						'default'  => 1,
					),
					array(
						'ref'     => '#bridge/custom_fields[]',
						'name'    => 'duration_minutes',
						'label'   => __( 'Duration (Minutes)', 'forms-bridge' ),
						'type'    => 'number',
						'default' => 0,
					),
					array(
						'ref'         => '#bridge/custom_fields[]',
						'name'        => 'location',
						'label'       => __( 'Location', 'forms-bridge' ),
						'description' => __(
							'Geographic location of the event as free-form text',
							'forms-bridge',
						),
						'type'        => 'text',
					),
					array(
						'ref'     => '#bridge/custom_fields[]',
						'name'    => 'sendUpdates',
						'label'   => __( 'Send email notification', 'forms-bridge' ),
						'type'    => 'boolean',
						'default' => true,
					),
					array(
						'ref'     => '#backend',
						'name'    => 'name',
						'default' => 'Google API',
					),
					array(
						'ref'   => '#backend',
						'name'  => 'base_url',
						'value' => 'https://www.googleapis.com',
					),
				),
				'backend'    => array(
					'name'     => 'Google API',
					'base_url' => 'https://www.googleapis.com',
					'headers'  => array(
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
					),
				),
				'bridge'     => array(
					'backend'  => 'Google API',
					'endpoint' => '/calendar/v3/calendars/{$calendarId}/events',
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'OAuth',
					'oauth_url'     => 'https://accounts.google.com/o/oauth2/v2',
					'scope'         => 'https://www.googleapis.com/auth/calendar.readonly https://www.googleapis.com/auth/calendar.events',
					'client_id'     => '',
					'client_secret' => '',
					'access_token'  => '',
					'expires_at'    => 0,
					'refresh_token' => '',
				),
			),
			$defaults,
			$schema
		);

		return $defaults;
	},
	10,
	3
);

add_filter(
	'forms_bridge_template_data',
	function ( $data, $template_id ) {
		if ( strpos( $template_id, 'gcalendar-' ) !== 0 ) {
			return $data;
		}

		$data['bridge']['endpoint'] = '/calendar/v3/calendars/' . $data['bridge']['endpoint'] . '/events';
		return $data;
	},
	10,
	2
);

add_filter(
	'http_bridge_oauth_url',
	function ( $url, $verb ) {
		if ( false === strpos( $url, 'accounts.google.com' ) ) {
			return $url;
		}

		if ( 'auth' === $verb ) {
			return $url;
		}

		return "https://oauth2.googleapis.com/{$verb}";
	},
	10,
	2
);
