<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

return array(
	'name'        => 'iso3-country-code',
	'title'       => __( 'ISO3 country code', 'forms-bridge' ),
	'description' => __(
		'Gets the ISO3 country code from country names and replace its value',
		'forms-bridge'
	),
	'method'      => 'forms_bridge_job_iso3_country_code',
	'input'       => array(
		array(
			'name'     => 'country',
			'required' => true,
			'schema'   => array( 'type' => 'string' ),
		),
	),
	'output'      => array(
		array(
			'name'   => 'country',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

function forms_bridge_job_iso3_country_code( $payload ) {
	global $forms_bridge_iso3_countries;
	$country_code = strtoupper( $payload['country'] );

	if ( ! isset( $forms_bridge_iso3_countries[ $country_code ] ) ) {
		$countries = array();
		foreach ( $forms_bridge_iso3_countries as $country_code => $country ) {
			$countries[ $country ] = $country_code;
		}

		if ( isset( $countries[ $payload['country'] ] ) ) {
			$payload['country'] = $countries[ $payload['country'] ];
		} else {
			$payload['country'] = null;
		}
	} else {
		$payload['country'] = $country_code;
	}

	return $payload;
}
