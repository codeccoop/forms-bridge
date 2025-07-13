<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_holded_search_contact($payload, $bridge)
{
    $query = [];
    $query_params = ['phone', 'mobile', 'customId'];

    foreach ($query_params as $param) {
        if (isset($payload[$param])) {
            $query[$param] = $payload[$param];
        }
    }

    if (empty($query)) {
        return;
    }

    $response = $bridge
        ->patch([
            'name' => 'holded-search-contact-query',
            'endpoint' => '/api/invoicing/v1/contacts',
            'method' => 'GET',
        ])
        ->submit($query);

    if (is_wp_error($response)) {
        return $response;
    }

    if (empty($response['data'])) {
        return;
    }

    return $response['data'][0];
}

function forms_bridge_holded_update_contact($payload, $bridge)
{
    return forms_bridge_holded_create_contact($payload, $bridge, true);
}

function forms_bridge_holded_create_contact($payload, $bridge, $update = false)
{
    if (!$update) {
        $contact = forms_bridge_holded_search_contact($payload, $bridge);

        if (!is_wp_error($contact) && isset($contact['id'])) {
            $patch['id'] = $contact['id'];
            return forms_bridge_holded_update_contact($patch, $bridge);
        }
    }

    $contact = [
        'name' => $payload['name'],
    ];

    $contact_fields = [
        'tradeName',
        'email',
        'mobile',
        'phone',
        'type',
        'code',
        'vatnumber',
        'iban',
        'swift',
        'billAddress',
        'defaults',
        'tags',
        'note',
        'isperson',
        'contactPersons',
        'shippingAddresses',
    ];

    foreach ($contact_fields as $field) {
        if (isset($payload[$field])) {
            $contact[$field] = $payload[$field];
        }
    }

    $method = 'POST';
    $endpoint = '/api/invoicing/v1/contacts';

    if ($update && isset($payload['id'])) {
        $method = 'PUT';
        $endpoint = $endpoint . '/' . $payload['id'];
    }

    $response = $bridge
        ->patch([
            'name' => 'holded-create-contact',
            'endpoint' => $endpoint,
            'method' => $method,
        ])
        ->submit($contact);

    if (is_wp_error($response)) {
        return $response;
    }

    $contact_id = $response['data']['id'];

    $response = $bridge
        ->patch([
            'name' => 'holded-get-contact-by-id',
            'endpoint' => '/api/invoicing/v1/contacts/' . $contact_id,
            'method' => 'GET',
        ])
        ->submit();

    if (is_wp_error($response)) {
        return $response;
    }

    return $response['data'];
}
