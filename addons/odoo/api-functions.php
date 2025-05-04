<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_create_partner($payload, $bridge)
{
    $partner = [
        'is_company' => false,
        'name' => $payload['name'],
    ];

    $partner_fields = [
        'title',
        'parent_id',
        'lang',
        'vat',
        'website',
        'employee',
        'function',
        'street',
        'street2',
        'zip',
        'city',
        'country_code',
        'country_id',
        'email',
        'phone',
        'mobile',
        'is_public',
        'additional_info',
    ];

    foreach ($partner_fields as $field) {
        if (isset($payload[$field])) {
            $partner[$field] = $payload[$field];
        }
    }

    $query = [['name', '=', $partner['name']]];

    if (isset($partner['email'])) {
        $query[] = ['email', '=', $partner['email']];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-search-partner',
            'method' => 'search_read',
            'endpoint' => 'res.partner',
        ])
        ->submit($query);

    if (!is_wp_error($response)) {
        return $response['data']['result'][0];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-create-partner',
            'method' => 'create',
            'endpoint' => 'res.partner',
        ])
        ->submit($partner);

    if (is_wp_error($response)) {
        return $response;
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-get-partner-data',
            'method' => 'read',
            'endpoint' => 'res.partner',
        ])
        ->submit([$response['data']['result']]);

    if (is_wp_error($response)) {
        return $response;
    }

    return $response['data']['result'][0];
}

function forms_bridge_odoo_create_company($payload, $bridge)
{
    $company = [
        'is_company' => true,
        'name' => $payload['name'],
    ];

    $company_fields = [
        'lang',
        'vat',
        'website',
        'street',
        'street2',
        'zip',
        'city',
        'country_code',
        'country_id',
        'email',
        'phone',
        'mobile',
        'is_public',
        'additional_info',
    ];

    foreach ($company_fields as $field) {
        if (isset($payload[$field])) {
            $company[$field] = $payload[$field];
        }
    }

    $query = [['name', '=', $company['name']]];

    if (isset($company['email'])) {
        $query[] = ['email', '=', $company['email']];
    }

    if (isset($company['vat'])) {
        $query[] = ['vat', '=', $company['vat']];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-search-company',
            'template' => null,
            'method' => 'search_read',
            'endpoint' => 'res.partner',
        ])
        ->submit($query);

    if (!is_wp_error($response)) {
        return $response['data']['result'][0];
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-create-company',
            'method' => 'create',
            'endpoint' => 'res.partner',
        ])
        ->submit($company);

    if (is_wp_error($response)) {
        return $response;
    }

    $response = $bridge
        ->patch([
            'name' => 'odoo-get-company-data',
            'method' => 'read',
            'endpoint' => 'res.partner',
        ])
        ->submit([$response['data']['result']]);

    if (is_wp_error($response)) {
        return $response;
    }

    return $response['data']['result'][0];
}
