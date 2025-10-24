<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'init',
	function () {
		register_post_type(
			'fb-job',
			array(
				'labels'          => array(
					'name'          => __( 'Jobs', 'forms-bridge' ),
					'singular_name' => __( 'Job', 'forms-bridge' ),
				),
				'public'          => false,
				'supports'        => array( 'title', 'excerpt', 'custom-fields' ),
				'capability_type' => 'post',
				'can_export'      => false,
			)
		);
	}
);
