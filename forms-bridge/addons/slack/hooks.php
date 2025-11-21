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
						'ref'         => '#credential',
						'name'        => 'client_id',
						'label'       => __( 'Client ID', 'forms-bridge' ),
						'description' => __( 'Register Forms Bridge as an app on <a target="_blank" href="https://api.slack.com/apps">Slack API</a> and get its Client ID', 'forms-bridge' ),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'         => '#credential',
						'name'        => 'client_secret',
						'label'       => __( 'Client Secret', 'forms-bridge' ),
						'description' => __( 'Register Forms Bridge as an app on <a target="_blank" href="https://api.slack.com/apps">Slack API</a> and get its Client Secret', 'forms-bridge' ),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'      => '#credential',
						'name'     => 'scope',
						'label'    => __( 'Scope', 'forms-bridge' ),
						'type'     => 'text',
						'value'    => 'chat:write,chat:write.public,channels:read,users:read,files:write',
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
					array(
						'ref'         => '#bridge/custom_fields[]',
						'name'        => 'channel',
						'label'       => __( 'Channel', 'forms-bridge' ),
						'description' => __(
							'Name of the channel where messages will be sent',
							'forms-bridge'
						),
						'type'        => 'select',
						'options'     => array(
							'endpoint' => '/api/conversations.list',
							'finger'   => array(
								'value' => 'channels[].id',
								'label' => 'channels[].name',
							),
						),
						'required'    => true,
					),
					array(
						'ref'         => '#bridge/custom_fields[]',
						'name'        => 'icon_emoji',
						'label'       => __( 'Emoji', 'forms-bridge' ),
						'description' => __( 'Emoji to use as the icon for this message', 'forms-bridge' ),
						'type'        => 'select',
						'options'     => array(
							array(
								'value' => ':smile:',
								'label' => '😄',
							),
							array(
								'value' => ':grinning:',
								'label' => '😀',
							),
							array(
								'value' => ':laughing:',
								'label' => '😂',
							),
							array(
								'value' => ':wink:',
								'label' => '😉',
							),
							array(
								'value' => ':blush:',
								'label' => '😊',
							),
							array(
								'value' => ':heart_eyes:',
								'label' => '😍',
							),
							array(
								'value' => ':sunglasses:',
								'label' => '😎',
							),
							array(
								'value' => ':rocket:',
								'label' => '🚀',
							),
							array(
								'value' => ':alien:',
								'label' => '👽',
							),
							array(
								'value' => ':robot:',
								'label' => '🤖',
							),
							array(
								'value' => ':ghost:',
								'label' => '👻',
							),
							array(
								'value' => ':cat:',
								'label' => '🐱',
							),
							array(
								'value' => ':dog:',
								'label' => '🐶',
							),
							array(
								'value' => ':panda_face:',
								'label' => '🐼',
							),
							array(
								'value' => ':owl:',
								'label' => '🦉',
							),
							array(
								'value' => ':fox_face:',
								'label' => '🦊',
							),
							array(
								'value' => ':fire:',
								'label' => '🔥',
							),
							array(
								'value' => ':sparkles:',
								'label' => '✨',
							),
							array(
								'value' => ':star:',
								'label' => '⭐',
							),
							array(
								'value' => ':crescent_moon:',
								'label' => '🌙',
							),
							array(
								'value' => ':rainbow:',
								'label' => '🌈',
							),
							array(
								'value' => ':tada:',
								'label' => '🎉',
							),
							array(
								'value' => ':confetti_ball:',
								'label' => '🎊',
							),
							array(
								'value' => ':bulb:',
								'label' => '💡',
							),
							array(
								'value' => ':gift:',
								'label' => '🎁',
							),
							array(
								'value' => ':trophy:',
								'label' => '🏆',
							),
							array(
								'value' => ':microphone:',
								'label' => '🎤',
							),
							array(
								'value' => ':headphones:',
								'label' => '🎧',
							),
							array(
								'value' => ':camera:',
								'label' => '📷',
							),
							array(
								'value' => ':video_game:',
								'label' => '🎮',
							),
							array(
								'value' => ':book:',
								'label' => '📖',
							),
							array(
								'value' => ':coffee:',
								'label' => '☕',
							),
							array(
								'value' => ':pizza:',
								'label' => '🍕',
							),
							array(
								'value' => ':hamburger:',
								'label' => '🍔',
							),
							array(
								'value' => ':fries:',
								'label' => '🍟',
							),
							array(
								'value' => ':cookie:',
								'label' => '🍪',
							),
							array(
								'value' => ':cake:',
								'label' => '🍰',
							),
							array(
								'value' => ':icecream:',
								'label' => '🍦',
							),
							array(
								'value' => ':beer:',
								'label' => '🍺',
							),
							array(
								'value' => ':wine_glass:',
								'label' => '🍷',
							),
							array(
								'value' => ':earth_americas:',
								'label' => '🌎',
							),
							array(
								'value' => ':milky_way:',
								'label' => '🌌',
							),
						),
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
	'forms_bridge_bridge_schema',
	function ( $schema, $addon ) {
		if ( 'slack' !== $addon ) {
			return $schema;
		}

		$schema['properties']['method']['const'] = 'POST';
		return $schema;
	},
	10,
	2,
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
