<?php
/**
 * Job post type
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Job;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'init',
	function () {
		register_post_type(
			Job::TYPE,
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
