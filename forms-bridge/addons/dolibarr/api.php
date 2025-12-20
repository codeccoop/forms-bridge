<?php
/**
 * Dolibarr addon php API
 *
 * @package formsbridge
 */

use FORMS_BRIDGE\Dolibarr_Form_Bridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Search for a contact in Dolibarr by email, fisrtname and lastname.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array|null Contact data.
 */
function forms_bridge_dolibarr_search_contact( $payload, $bridge ) {
	$sqlfilters = array();

	$search_fields = array(
		'email'     => fn( $v ) => "(t.email:=:'{$v}')",
		'firstname' => fn( $v ) => "(t.firstname:like:'{$v}')",
		'lastname'  => fn( $v ) => "(t.lastname:like:'{$v}')",
		// 'socid' => fn ($v) => "(t.fk_soc:=:{$v})",
	);

	foreach ( $search_fields as $field => $filter ) {
		if ( isset( $payload[ $field ] ) ) {
			$sqlfilters[] = $filter( $payload[ $field ] );
		}
	}

	if ( empty( $sqlfilters ) ) {
		return;
	}

	$sqlfilters = implode( ' and ', $sqlfilters );

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-search-contact',
				'endpoint' => '/api/index.php/contacts',
				'method'   => 'GET',
			)
		)
		->submit(
			array(
				'sortfield'  => 't.rowid',
				'sortorder'  => 'DESC',
				'limit'      => '1',
				'properties' => 'id',
				'sqlfilters' => $sqlfilters,
			)
		);

	if ( is_wp_error( $response ) ) {
		$error_data    = $response->get_error_data();
		$response_code = $error_data['response']['response']['code'];

		if ( 404 !== $response_code ) {
			return $response;
		}
	}

	if ( is_wp_error( $response ) || empty( $response['data'] ) ) {
		return;
	}

	return $response['data'][0];
}

/**
 * Search for a thirdparty in Dolibarr by tva_intra, idprof1, email or code_client.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array|null Thirdparty data.
 */
function forms_bridge_dolibarr_search_thirdparty( $payload, $bridge ) {
	$sqlfilters = array( "(t.nom:like:'{$payload['name']}')" );

	$search_fields = array(
		// 'typent_id' => fn ($v) => "(t.fk_typent:=:{$v})",
		'tva_intra'   => fn( $v ) => "(t.tva_intra:=:'{$v}')",
		'idprof1'     => fn( $v ) => "(t.siren:=:'{$v}')",
		'email'       => fn( $v ) => "(t.email:=:'{$v}')",
		'code_client' => fn( $v ) => "(t.code_client:=:'{$v}')",
	);

	foreach ( $search_fields as $field => $filter ) {
		if ( isset( $payload[ $field ] ) ) {
			$sqlfilters[] = $filter( $payload[ $field ] );
		}
	}

	if ( empty( $sqlfilters ) ) {
		return;
	}

	$sqlfilters = implode( ' or ', $sqlfilters );

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-search-thirdparty',
				'endpoint' => '/api/index.php/thirdparties',
				'method'   => 'GET',
			)
		)
		->submit(
			array(
				'sortfield'  => 't.rowid',
				'sortorder'  => 'DESC',
				'limit'      => '1',
				'properties' => 'id,code_client',
				'sqlfilters' => $sqlfilters,
			)
		);

	if ( is_wp_error( $response ) ) {
		$error_data    = $response->get_error_data();
		$response_code = $error_data['response']['response']['code'] ?? null;

		if ( 404 !== $response_code ) {
			return $response;
		}
	}

	if ( is_wp_error( $response ) || empty( $response['data'][0] ) ) {
		return;
	}

	return $response['data'][0];
}

/**
 * Updates a contact on Dolibarr.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array|null Contact data.
 */
function forms_bridge_dolibarr_update_contact( $payload, $bridge ) {
	return forms_bridge_dolibarr_create_contact( $payload, $bridge, true );
}

/**
 * Creates a contact on Dolibarr.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 * @param boolean              $update True to perform an update request.
 *
 * @return array|null Contact data.
 */
function forms_bridge_dolibarr_create_contact(
	$payload,
	$bridge,
	$update = false
) {
	if ( ! $update ) {
		$contact = forms_bridge_dolibarr_search_contact( $payload, $bridge );

		if ( ! is_wp_error( $contact ) && isset( $contact['id'] ) ) {
			$payload['id'] = $contact['id'];
			return forms_bridge_dolibarr_update_contact( $payload, $bridge );
		}
	}

	$contact = array(
		'lastname' => $payload['lastname'],
	);

	$contact_fields = array(
		'email',
		'firstname',
		'civility_code',
		'socid',
		'poste',
		'status',
		'note_public',
		'note_private',
		'address',
		'zip',
		'town',
		'country_id',
		'state_id',
		'region_id',
		'url',
		'no_email',
		'phone_pro',
		'phone_perso',
		'phone_mobile',
		'fax',
		'stcomm_id',
		'default_lang',
	);

	foreach ( $contact_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$contact[ $field ] = $payload[ $field ];
		}
	}

	$method   = 'POST';
	$endpoint = '/api/index.php/contacts';
	if ( $update && isset( $payload['id'] ) ) {
		$endpoint .= '/' . $payload['id'];
		$method    = 'PUT';
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-create-contact',
				'endpoint' => $endpoint,
				'method'   => $method,
			)
		)
		->submit( $contact );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 'POST' === $method ) {
		$response = $bridge
			->patch(
				array(
					'name'     => 'dolibarr-get-new-contact-data',
					'endpoint' => '/api/index.php/contacts/' . $response['data'],
					'method'   => 'GET',
				)
			)
			->submit( array() );
	}

	return $response['data'];
}

/**
 * Updates a thirdparty on Dolibarr.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 *
 * @return array|null Thirdparty data.
 */
function forms_bridge_dolibarr_update_thirdparty( $payload, $bridge ) {
	return forms_bridge_dolibarr_create_thirdparty( $payload, $bridge, true );
}

/**
 * Creates a thirdparty on Dolibarr.
 *
 * @param array                $payload Bridge payload.
 * @param Dolibarr_Form_Bridge $bridge Bridge object.
 * @param boolean              $update True to perform an update request.
 *
 * @return array|null Thirdparty data.
 */
function forms_bridge_dolibarr_create_thirdparty(
	$payload,
	$bridge,
	$update = false
) {
	if ( ! $update ) {
		$thirdparty = forms_bridge_dolibarr_search_thirdparty(
			$payload,
			$bridge
		);

		if ( ! is_wp_error( $thirdparty ) && isset( $thirdparty['id'] ) ) {
			$payload['id'] = $thirdparty['id'];

			if ( ! empty( $thirdparty['code_client'] ) ) {
				$payload['code_client'] = $thirdparty['code_client'];
			}

			return forms_bridge_dolibarr_update_thirdparty( $payload, $bridge );
		}
	}

	$thirdparty = array(
		'name' => $payload['name'],
	);

	$thirdparty_fields = array(
		'email',
		'idprof1',
		'idprof2',
		'tva_intra',
		'phone',
		'fax',
		'url',
		'zip',
		'town',
		'address',
		'region_id',
		'state_id',
		'country_id',
		'no_email',
		'typent_id',
		'stcomm_id',
		'parent',
		'client',
		'fournisseur',
		'code_client',
	);

	foreach ( $thirdparty_fields as $field ) {
		if ( isset( $payload[ $field ] ) ) {
			$thirdparty[ $field ] = $payload[ $field ];
		}
	}

	if ( ! isset( $thirdparty['code_client'] ) && ! $update ) {
		$thirdparty['code_client'] = -1;
	}

	$endpoint = '/api/index.php/thirdparties';
	$method   = 'POST';

	if ( $update && isset( $payload['id'] ) ) {
		$endpoint .= '/' . $payload['id'];
		$method    = 'PUT';
	}

	$response = $bridge
		->patch(
			array(
				'name'     => 'dolibarr-create-thirdparty',
				'endpoint' => $endpoint,
				'method'   => $method,
			)
		)
		->submit( $thirdparty );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 'POST' === $method ) {
		$response = $bridge
			->patch(
				array(
					'name'     => 'dolibarr-get-new-thirdparty-data',
					'endpoint' =>
						'/api/index.php/thirdparties/' . $response['data'],
					'method'   => 'GET',
				)
			)
			->submit();
	}

	return $response['data'];
}
