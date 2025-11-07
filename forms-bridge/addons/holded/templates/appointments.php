<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

global $forms_bridge_iso2_countries;

return array(
	'title'       => __( 'Appointments', 'forms-bridge' ),
	'description' => __(
		'Appointments form template. The resulting bridge will convert form submissions into events on the calendar linked to new contacts.',
		'forms-bridge'
	),
	'fields'      => array(
		array(
			'ref'     => '#form',
			'name'    => 'title',
			'default' => __( 'Appointments', 'forms-bridge' ),
		),
		array(
			'ref'   => '#bridge',
			'name'  => 'endpoint',
			'value' => '/api/crm/v1/events',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'event_name',
			'label'    => __( 'Event name', 'forms-bridge' ),
			'type'     => 'text',
			'required' => true,
			'default'  => 'Web appointment',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'kind',
			'label'    => __( 'Event type', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'value' => 'meeting',
					'label' => __( 'Meeting', 'forms-bridge' ),
				),
				array(
					'value' => 'call',
					'label' => __( 'Call', 'forms-bridge' ),
				),
				array(
					'value' => 'lunch',
					'label' => __( 'Lunch', 'forms-bridge' ),
				),
				array(
					'value' => 'dinner',
					'label' => __( 'Dinner', 'forms-bridge' ),
				),
			),
			'required' => true,
			'default'  => 'meeting',
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'duration',
			'label'    => __( 'Duration (Hours)', 'forms-bridge' ),
			'type'     => 'number',
			'default'  => 1,
			'required' => true,
		),
		array(
			'ref'      => '#bridge/custom_fields[]',
			'name'     => 'type',
			'label'    => __( 'Contact type', 'forms-bridge' ),
			'type'     => 'select',
			'options'  => array(
				array(
					'label' => __( 'Unspecified', 'forms-bridge' ),
					'value' => '0',
				),
				array(
					'label' => __( 'Client', 'forms-bridge' ),
					'value' => 'client',
				),
				array(
					'label' => __( 'Lead', 'forms-bridge' ),
					'value' => 'lead',
				),
				array(
					'label' => __( 'Supplier', 'forms-bridge' ),
					'value' => 'supplier',
				),
				array(
					'label' => __( 'Debtor', 'forms-bridge' ),
					'value' => 'debtor',
				),
				array(
					'label' => __( 'Creditor', 'forms-bridge' ),
					'value' => 'creditor',
				),
			),
			'required' => true,
			'default'  => '0',
		),
		array(
			'ref'         => '#bridge/custom_fields[]',
			'name'        => 'tags',
			'label'       => __( 'Tags', 'forms-bridge' ),
			'description' => __( 'Tags separated by commas', 'forms-bridge' ),
			'type'        => 'text',
		),
	),
	'bridge'      => array(
		'endpoint'      => '/api/crm/v1/events',
		'custom_fields' => array(
			array(
				'name'  => 'isperson',
				'value' => '1',
			),
			array(
				'name'  => 'defaults.language',
				'value' => '$locale',
			),
		),
		'mutations'     => array(
			array(
				array(
					'from' => 'isperson',
					'to'   => 'isperson',
					'cast' => 'integer',
				),
				array(
					'from' => 'code',
					'to'   => 'vatnumber',
					'cast' => 'copy',
				),
				array(
					'from' => 'your-name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => 'address',
					'to'   => 'billAddress.address',
					'cast' => 'string',
				),
				array(
					'from' => 'postalCode',
					'to'   => 'billAddress.postalCode',
					'cast' => 'string',
				),
				array(
					'from' => 'city',
					'to'   => 'billAddress.city',
					'cast' => 'string',
				),
				array(
					'from' => '?tags',
					'to'   => 'event_tags',
					'cast' => 'inherit',
				),
			),
			array(
				array(
					'from' => 'datetime',
					'to'   => 'date',
					'cast' => 'string',
				),
			),
			array(),
			array(
				array(
					'from' => 'country',
					'to'   => 'country',
					'cast' => 'null',
				),
				array(
					'from' => 'country_code',
					'to'   => 'countryCode',
					'cast' => 'string',
				),
			),
			array(
				array(
					'from' => 'countryCode',
					'to'   => 'billAddress.countryCode',
					'cast' => 'string',
				),
			),
			array(
				array(
					'from' => 'event_name',
					'to'   => 'name',
					'cast' => 'string',
				),
				array(
					'from' => '?event_tags',
					'to'   => 'tags',
					'cast' => 'inherit',
				),
			),
		),
		'workflow'      => array(
			'date-fields-to-date',
			'appointment-dates',
			'iso2-country-code',
			'prefix-vatnumber',
			'contact-id',
		),
	),
	'form'        => array(
		'fields' => array(
			array(
				'label'    => __( 'Your name', 'forms-bridge' ),
				'name'     => 'your-name',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Tax ID', 'forms-bridge' ),
				'name'     => 'code',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label'    => __( 'Your email', 'forms-bridge' ),
				'name'     => 'email',
				'type'     => 'email',
				'required' => true,
			),
			array(
				'label'    => __( 'Your phone', 'forms-bridge' ),
				'name'     => 'phone',
				'type'     => 'text',
				'required' => true,
			),
			array(
				'label' => __( 'Address', 'forms-bridge' ),
				'name'  => 'address',
				'type'  => 'text',
			),
			array(
				'label' => __( 'Zip code', 'forms-bridge' ),
				'name'  => 'postalCode',
				'type'  => 'text',
			),
			array(
				'label' => __( 'City', 'forms-bridge' ),
				'name'  => 'city',
				'type'  => 'text',
			),
			array(
				'label'    => __( 'Country', 'forms-bridge' ),
				'name'     => 'country',
				'type'     => 'select',
				'options'  => array_map(
					function ( $country_code ) {
						global $forms_bridge_iso2_countries;
						return array(
							'value' => $country_code,
							'label' => $forms_bridge_iso2_countries[ $country_code ],
						);
					},
					array_keys( $forms_bridge_iso2_countries )
				),
				'required' => true,
			),
			array(
				'name'     => 'date',
				'label'    => __( 'Date', 'forms-bridge' ),
				'type'     => 'date',
				'required' => true,
			),
			array(
				'name'     => 'hour',
				'label'    => __( 'Hour', 'forms-bridge' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'label' => __( '1 AM', 'forms-bridge' ),
						'value' => '01',
					),
					array(
						'label' => __( '2 AM', 'forms-bridge' ),
						'value' => '02',
					),
					array(
						'label' => __( '3 AM', 'forms-bridge' ),
						'value' => '03',
					),
					array(
						'label' => __( '4 AM', 'forms-bridge' ),
						'value' => '04',
					),
					array(
						'label' => __( '5 AM', 'forms-bridge' ),
						'value' => '05',
					),
					array(
						'label' => __( '6 AM', 'forms-bridge' ),
						'value' => '06',
					),
					array(
						'label' => __( '7 AM', 'forms-bridge' ),
						'value' => '07',
					),
					array(
						'label' => __( '8 AM', 'forms-bridge' ),
						'value' => '08',
					),
					array(
						'label' => __( '9 AM', 'forms-bridge' ),
						'value' => '09',
					),
					array(
						'label' => __( '10 AM', 'forms-bridge' ),
						'value' => '10',
					),
					array(
						'label' => __( '11 AM', 'forms-bridge' ),
						'value' => '11',
					),
					array(
						'label' => __( '12 AM', 'forms-bridge' ),
						'value' => '12',
					),
					array(
						'label' => __( '1 PM', 'forms-bridge' ),
						'value' => '13',
					),
					array(
						'label' => __( '2 PM', 'forms-bridge' ),
						'value' => '14',
					),
					array(
						'label' => __( '3 PM', 'forms-bridge' ),
						'value' => '15',
					),
					array(
						'label' => __( '4 PM', 'forms-bridge' ),
						'value' => '16',
					),
					array(
						'label' => __( '5 PM', 'forms-bridge' ),
						'value' => '17',
					),
					array(
						'label' => __( '6 PM', 'forms-bridge' ),
						'value' => '18',
					),
					array(
						'label' => __( '7 PM', 'forms-bridge' ),
						'value' => '19',
					),
					array(
						'label' => __( '8 PM', 'forms-bridge' ),
						'value' => '20',
					),
					array(
						'label' => __( '9 PM', 'forms-bridge' ),
						'value' => '21',
					),
					array(
						'label' => __( '10 PM', 'forms-bridge' ),
						'value' => '22',
					),
					array(
						'label' => __( '11 PM', 'forms-bridge' ),
						'value' => '23',
					),
					array(
						'label' => __( '12 PM', 'forms-bridge' ),
						'value' => '24',
					),
				),
			),
			array(
				'name'     => 'minute',
				'label'    => __( 'Minute', 'forms-bridge' ),
				'type'     => 'select',
				'required' => true,
				'options'  => array(
					array(
						'label' => '00',
						'value' => '00.0',
					),
					array(
						'label' => '05',
						'value' => '05',
					),
					array(
						'label' => '10',
						'value' => '10',
					),
					array(
						'label' => '15',
						'value' => '15',
					),
					array(
						'label' => '20',
						'value' => '20',
					),
					array(
						'label' => '25',
						'value' => '25',
					),
					array(
						'label' => '30',
						'value' => '30',
					),
					array(
						'label' => '35',
						'value' => '35',
					),
					array(
						'label' => '40',
						'value' => '40',
					),
					array(
						'label' => '45',
						'value' => '45',
					),
					array(
						'label' => '50',
						'value' => '50',
					),
					array(
						'label' => '55',
						'value' => '55',
					),
				),
			),
		),
	),
);
