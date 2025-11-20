<?php
/**
 * Rocket.Chat addon hooks
 *
 * @package formsbridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_filter(
	'forms_bridge_template_defaults',
	function ( $defaults, $addon, $schema ) {
		if ( 'rocketchat' !== $addon ) {
			return $defaults;
		}

		return wpct_plugin_merge_object(
			array(
				'fields' => array(
					array(
						'ref'         => '#backend',
						'name'        => 'name',
						'description' => __(
							'Label of the Rocket.Chat API backend connection',
							'forms-bridge'
						),
						'default'     => 'Rocket.Chat API',
					),
					array(
						'ref'         => '#backend/headers[]',
						'name'        => 'X-Auth-Token',
						'label'       => __( 'Personal Access Token', 'forms-bridge' ),
						'description' => __(
							'Use <a target="_blank" href="https://docs.rocket.chat/docs/manage-personal-access-tokens">Personal Access Tokens</a> to interact securely with the Rocket.Chat API',
							'forms-bridge',
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'         => '#backend/headers[]',
						'name'        => 'X-User-Id',
						'label'       => __( 'User Id', 'forms-bridge' ),
						'description' => __(
							'Displayed when the Personal Access Token is created',
							'forms-bridge',
						),
						'type'        => 'text',
						'required'    => true,
					),
					array(
						'ref'   => '#bridge',
						'name'  => 'method',
						'value' => 'POST',
					),
					array(
						'ref'         => '#bridge/custom_fields[]',
						'name'        => 'emoji',
						'label'       => __( 'Emoji', 'forms-bridge' ),
						'description' => __( 'If provided, the avatar will be displayed as an emoji', 'forms-bridge' ),
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
				'bridge' => array(
					'method' => 'POST',
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
		if ( 'rocketchat' !== $addon ) {
			return $schema;
		}

		$schema['properties']['method']['enum'] = array( 'GET', 'POST' );
		return $schema;
	},
	10,
	2
);
