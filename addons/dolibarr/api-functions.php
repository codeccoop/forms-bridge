<?php

function forms_bridge_dolibarr_search_contact($payload, $bridge)
{
    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $sqlfilters = [];
    $search_fields = [
        'email' => fn($v) => "(t.email:=:'{$v}')",
        'firstname' => fn($v) => "(t.firstname:like:'{$v}')",
        'lastname' => fn($v) => "(t.lastname:like:'{$v}')",
        'socid' => fn($v) => "(t.fk_soc:=:{$v})",
    ];

    foreach ($search_fields as $field => $filter) {
        if (isset($payload[$field])) {
            $sqlfilters[] = $filter($payload[$field]);
        }
    }

    if (empty($sqlfilters)) {
        return $payload;
    }

    $sqlfilters = implode(' and ', $sqlfilters);

    $response = $backend->get(
        '/api/index.php/contacts',
        [
            'sortfield' => 't.rowid',
            'sortorder' => 'DESC',
            'limit' => '1',
            'sqlfilters' => $sqlfilters,
        ],
        ['DOLAPIKEY' => $dolapikey]
    );

    if (is_wp_error($response)) {
        $error_data = $response->get_error_data();
        $response_code = $error_data['response']['response']['code'];

        if ($response_code !== 404) {
            return $response;
        }
    }

    if (is_wp_error($response)) {
        return;
    }

    return $response['data'][0];
}

function forms_bridge_dolibarr_search_thirdparty($payload, $bridge)
{
    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $sqlfilters = [];
    $search_fields = [
        'typent_id' => fn($v) => "(t.fk_typent:=:{$v})",
        'idprof1' => fn($v) => "(t.siren:='{$v}')",
        'name' => fn($v) => "(t.nom:like:'{$v}')",
        'email' => fn($v) => "(t.email:=:'{$v}')",
    ];

    foreach ($search_fields as $field => $filter) {
        if (isset($payload[$field])) {
            $sqlfilters[] = $filter($payload[$field]);
        }
    }

    if (empty($sqlfilters)) {
        return $payload;
    }

    $sqlfilters = implode(' and ', $sqlfilters);

    $response = $backend->get(
        '/api/index.php/thirdparties',
        [
            'sortfield' => 't.rowid',
            'sortorder' => 'DESC',
            'limit' => '1',
            'sqlfilters' => $sqlfilters,
        ],
        ['DOLAPIKEY' => $dolapikey]
    );

    if (is_wp_error($response)) {
        $error_data = $response->get_error_data();
        $response_code = $error_data['response']['response']['code'];

        if ($response_code !== 404) {
            return $response;
        }
    }

    if (is_wp_error($response)) {
        return;
    }

    return $response['data'][0];
}

function forms_bridge_dolibarr_get_next_code_client($payload, $bridge)
{
    $backend = $bridge->backend;
    $dolapikey = $bridge->api_key->key;

    $response = $backend->get(
        '/api/index.php/thirdparties',
        [
            'sortfield' => 't.rowid',
            'sortorder' => 'DESC',
            'limit' => 1,
        ],
        ['DOLAPIKEY' => $dolapikey]
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $previus_code_client = $response['data'][0]['code_client'];

    [$prefix, $number] = explode('-', $previus_code_client);

    $next = strval($number + 1);
    while (strlen($next) < strlen($number)) {
        $next = '0' . $next;
    }

    return $prefix . '-' . $next;
}

function forms_bridge_dolibarr_date_to_time($payload)
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
