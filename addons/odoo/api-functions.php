<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_odoo_search_user_by_email($payload, $bridge)
{
    $response = $bridge
        ->patch([
            'name' => 'odoo-rpc-search-user-by-email',
            'template' => null,
            'method' => 'search_read',
            'model' => 'res.users',
        ])
        ->submit([['email', '=', $payload['email']]]);

    if (is_wp_error($response)) {
        return $response;
    }

    return $response['data']['result'][0];
}
