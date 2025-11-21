<?php
/**
 * Slack fields text summary job
 *
 * @package formsbridge
 */

return array(
	'title'       => __( 'Fields summary', 'forms-bridge' ),
	'description' => __( 'Format the payload fields field as a text list', 'forms-bridge' ),
	'method'      => 'forms_bridge_slack_fields_summary_txt',
	'input'       => array(
		array(
			'name'   => 'fields',
			'schema' => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => true,
			),
		),
	),
	'output'      => array(
		array(
			'name'   => 'text',
			'schema' => array( 'type' => 'string' ),
		),
	),
);

/**
 * Content summary job method
 *
 * @param array $payload Bridge payload.
 *
 * @return array
 */
function forms_bridge_slack_fields_summary_txt( $payload ) {
	$payload['text'] = forms_bridge_slack_content_txt( $payload['fields'] );
	return $payload;
}

/**
 * Format the content data as a markdown list.
 *
 * @param array  $data Content data.
 * @param string $leading Leading spaces of the list.
 *
 * @return string
 */
function forms_bridge_slack_content_txt( $data, $leading = '' ) {
	$content = '';

	if ( ! strlen( $leading ) ) {
		$content = __( 'Fields', 'forms-bridge' ) . ":\n";
	}

	if ( wp_is_numeric_array( $data ) ) {
		$l = count( $data );
		for ( $i = 1; $i <= $l; $i++ ) {
			$content .= forms_bridge_slack_field_txt( $i, $data[ $i - 1 ], $leading );
		}
	} else {
		foreach ( $data as $name => $value ) {
			$content .= forms_bridge_slack_field_txt( $name, $value, $leading );
		}
	}

	return $content;
}

/**
 * Format a payload field as a markdown list item.
 *
 * @param string $name Field name.
 * @param mixed  $value Field value.
 * @param string $leading Leading spaces of the list.
 *
 * @return string
 */
function forms_bridge_slack_field_txt( $name, $value, $leading = '' ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		$value = "\n" . forms_bridge_slack_content_txt( (array) $value, $leading . '  ' );
	} elseif ( is_string( $value ) ) {
		$value = preg_replace( '/\n+/', "\n{$leading}  ", $value ) . "\n";
	}

	return "{$leading}- {$name}: {$value}";
}
