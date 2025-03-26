<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_zoho_bigin_contact_id($payload, $bridge)
{
    $contact = [];
    $contact_fields = [];

    foreach ($contact_fields as $field) {
        if (isset($payload[$field])) {
            $contact[$field] = $payload[$field];
        }
    }

    $response = $bridge
        ->patch([
            'name' => 'zoho-bigin-contact-id',
            'endpoint' => '/bigin/v2/Contacts',
            'template' => null,
        ])
        ->submit($contact);

    if (is_wp_error($response)) {
        $data = json_decode(
            $response->get_error_data()['response']['body'],
            true
        );

        if ($data['data'][0]['code'] !== 'DUPLICATE_DATA') {
            return $response;
        }

        $contact_id = $data['data'][0]['details']['duplicate_record']['id'];
    } else {
        $contact_id = $response['data'][0]['details']['id'];
    }

    return $contact_id;
}

function forms_bridge_zoho_date_to_time($payload)
{
    $date = $payload['date'];
    $hour = $payload['hour'] ?? '00';
    $minute = $payload['minute'] ?? '00';

    $form_data = apply_filters('forms_bridge_form', null);
    $date_index = array_search(
        'date',
        array_column($form_data['fields'], 'name')
    );
    $date_format = $form_data['fields'][$date_index]['format'] ?? '';

    if (strstr($date_format, '-')) {
        $separator = '-';
    } elseif (strstr($date_format, '.')) {
        $separator = '.';
    } elseif (strstr($date_format, '/')) {
        $separator = '/';
    }

    switch (substr($date_format, 0, 1)) {
        case 'y':
            [$year, $month, $day] = explode($separator, $date);
            break;
        case 'm':
            [$month, $day, $year] = explode($separator, $date);
            break;
        case 'd':
            [$day, $month, $year] = explode($separator, $date);
            break;
    }

    $date = "{$year}-{$month}-{$day}";

    if (preg_match('/(am|pm)/i', $hour, $matches)) {
        $hour = (int) $hour;
        if (strtolower($matches[0]) === 'pm') {
            $hour += 12;
        }
    }

    $time = strtotime("{$date} {$hour}:{$minute}");

    if ($time === false) {
        return new WP_Error('Invalid date format');
    }

    return $time;
}
