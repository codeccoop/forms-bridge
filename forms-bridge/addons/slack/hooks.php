<?php
/**
 * Slack addon hooks.
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'slack' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
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
						'value' => 'Bearer',
					),
					array(
						'ref'   => '#credential',
						'name'  => 'oauth_url',
						'label' => __( 'Authorization URL', 'forms-bridge' ),
						'type'  => 'text',
						'value' => 'https://slack.com/oauth/v2',
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
						'value'    => 'chat:write,channels:read,users:read,files:write',
						'required' => true,
					),
					array(
						'ref'         => '#backend',
						'name'        => 'name',
						'description' => __(
							'Label of the Slack API backend connection',
							'forms-bridge'
						),
						'default'     => 'Slack API',
					),
					array(
						'ref'   => '#backend',
						'name'  => 'base_url',
						'type'  => 'url',
						'value' => 'https://slack.com',
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
				),
				'credential' => array(
					'name'          => '',
					'schema'        => 'Bearer',
					'oauth_url'     => 'https://slack.com/oauth/v2',
					'scope'         => 'chat:write,channels:read,users:read',
					'client_id'     => '',
					'client_secret' => '',
					'access_token'  => '',
					'expires_at'    => 0,
					'refresh_token' => '',
				),
				'backend'    => array(
					'base_url' => 'https://slack.com',
					'headers'  => array(
						array(
							'name'  => 'Accept',
							'value' => 'application/json',
						),
					),
				),
			),
			$defaults,
			$schema,
		);
	},
	10,
	3
);

add_filter(
	'http_bridge_oauth_update_tokens',
	function ( $tokens, $credential ) {
		if ( false !== strstr( $credential->oauth_url, 'slack.com' ) ) {
			$tokens['expires_at']               = time() + 60 * 60 * 24 * 365 * 10;
			$tokens['refresh_token']            = $tokens['access_token'];
			$tokens['refresh_token_expires_at'] = time() + 60 * 60 * 24 * 365 * 10;
		}

		return $tokens;
	},
	10,
	2
);

add_filter(
	'http_bridge_oauth_url',
	function ( $url, $verb ) {
		if ( false === strstr( $url, 'slack.com' ) ) {
			return $url;
		}

		if ( 'auth' === $verb ) {
			return $url .= 'orize';
		}

		if ( 'token/revoke' === $verb ) {
			return 'https://slack.com/api/auth.revoke';
		}

		return 'https://slack.com/api/oauth.v2.access';
	},
	10,
	2
);
