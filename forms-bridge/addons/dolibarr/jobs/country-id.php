<?php
/**
 * Country ID Dolibarr job.
 *
 * @package forms-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Gets the country_id value from the ISO-2 country code.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array
 */
function forms_bridge_dolibarr_country_id_from_code( $payload, $bridge ) {
	global $forms_bridge_iso2_countries;

	if ( ! isset( $forms_bridge_iso2_countries[ $payload['country'] ] ) ) {
		if ( ! isset( $forms_bridge_iso2_countries[ $payload['country_id'] ] ) ) {
			return new WP_Error( 'Invalid ISO-2 country code', 'forms-bridge' );
		}

		// backward compatibility.
		$payload['country'] = $payload['country_id'];
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-get-country-id',
				'method'   => 'GET',
				'endpoint' =>
					'/api/index.php/setup/dictionary/countries/byCode/' .
					$payload['country'],
			)
		)
		->submit();

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload['country_id'] = $response['data']['id'];
	return $payload;
}

return array(
	'title'       => __( 'Country ID', 'forms-bridge' ),
	'description' => __(
		'Gets country_id value from ISO-2 country code and replace it on the payload',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_dolibarr_country_id_from_code',
	'input'       => array(
		array(
			'name'     => 'country',
			'schema'   => array( 'type' => 'string' ),
			'required' => true,
		),
		array(
			'name'   => 'country_id',
			'schema' => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'country_id',
			'schema' => array( 'type' => 'integer' ),
		),
	),
);
