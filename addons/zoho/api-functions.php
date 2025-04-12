<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_crm_create_lead($payload, $bridge)
{
    $lead = [
        'Last_Name' => $payload['Last_Name'],
    ];

    $lead_fields = [
        'Owner',
        'First_Name',
        'Email',
        'Phone',
        'Title',
        'Company',
        'Description',
        'Lead_Status',
        'Lead_Source',
    ];

    foreach ($lead_fields as $field) {
        if (isset($payload[$field])) {
            $lead[$field] = $payload[$field];
        }
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-crm-create-lead',
            'endpoint' => '/crm/v7/Leads/upsert',
            'template' => null,
        ])
        ->submit($lead);

    if (is_wp_error($response)) {
        return $response;
    }

    if ($response['data']['data'][0]['code'] === 'DUPLICATE_DATA') {
        return $response['data']['data'][0]['details']['duplicate_record'];
    } else {
        return $response['data']['data'][0]['details'];
    }
}

function forms_bridge_zoho_crm_create_contact($payload, $bridge)
{
    $contact = [
        'Last_Name' => $payload['Last_Name'],
    ];

    $contact_fields = [
        'First_Name',
        'Email',
        'Phone',
        'Title',
        'Account_Name',
        'Description',
    ];

    foreach ($contact_fields as $field) {
        if (isset($payload[$field])) {
            $contact[$field] = $payload[$field];
        }
    }

    if (
        isset($payload['Account_Name']) &&
        is_string($payload['Account_Name'])
    ) {
        $account = forms_bridge_zoho_crm_create_account($payload, $bridge);

        if (is_wp_error($account)) {
            return $account;
        }

        $payload['Account_Name'] = ['id' => $account['id']];
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-crm-create-contact',
            'endpoint' => '/crm/v7/Contacts/upsert',
            'template' => null,
        ])
        ->submit($contact);

    if (is_wp_error($response)) {
        return $response;
    }

    if ($response['data']['data'][0]['code'] === 'DUPLICATE_DATA') {
        return $response['data']['data'][0]['details']['duplicate_record'];
    } else {
        return $response['data']['data'][0]['details'];
    }
}

function forms_bridge_zoho_crm_create_account($payload, $bridge)
{
    $company = [
        'Account_Name' => $payload['Account_Name'],
    ];

    $company_fields = [
        'Billing_Street',
        'Billing_Code',
        'Billing_City',
        'Billing_State',
        'Billing_Country',
        'Description',
    ];

    foreach ($company_fields as $field) {
        if (isset($payload[$field])) {
            $company[$field] = $payload[$field];
        }
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-crm-create-account',
            'endpoint' => '/crm/v7/Accounts/upsert',
            'template' => null,
        ])
        ->submit($company);

    if (is_wp_error($response)) {
        return $response;
    }

    if ($response['data']['data'][0]['code'] === 'DUPLICATE_DATA') {
        return $response['data']['data'][0]['details']['duplicate_record'];
    } else {
        return $response['data']['data'][0]['details'];
    }
}
