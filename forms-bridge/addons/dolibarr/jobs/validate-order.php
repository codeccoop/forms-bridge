<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'title'       => __( 'Validate order', 'forms-bridge' ),
	'description' => __(
		'Add a callback to the bridge submission to validate the order after its creation',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_enqueue_order_validation',
	'input'       => array(),
	'output'      => array(),
);

function forms_bridge_dolibarr_validate_order(
	$bridge,
	$response,
	$payload,
	$attachments
) {
	remove_action(
		'forms_bridge_after_submission',
		'forms_bridge_dolibarr_validate_order',
		10,
		4
	);

	$order_id = intval( $response['data'] ?? null );

	if ( empty( $order_id ) ) {
		return;
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-validate-order',
				'method'   => 'POST',
				'endpoint' => "/api/index.php/orders/{$order_id}/validate",
			)
		)
		->submit();

	if ( is_wp_error( $response ) ) {
		do_action(
			'forms_bridge_on_failure',
			$response,
			$bridge,
			$payload,
			$attachments
		);
	}
}

function forms_bridge_dolibarr_enqueue_order_validation( $payload ) {
	add_action(
		'forms_bridge_after_submission',
		'forms_bridge_dolibarr_validate_order',
		10,
		4
	);

	return $payload;
}
