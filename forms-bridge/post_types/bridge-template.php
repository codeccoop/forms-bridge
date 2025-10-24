<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'init',
	function () {
		register_post_type(
			'fb-bridge-template',
			array(
				'labels'          => array(
					'name'          => __( 'Bridge templates', 'forms-bridge' ),
					'singular_name' => __( 'Bridge template', 'forms-bridge' ),
				),
				'public'          => false,
				'supports'        => array( 'title', 'excerpt', 'custom-fields' ),
				'capability_type' => 'post',
				'can_export'      => false,
			)
		);
	}
);
