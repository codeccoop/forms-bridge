<?php

if (!defined('ABSPATH')) {
    exit();
}

function forms_bridge_dolibarr_thirdparty_socid($payload, $bridge)
{
    $thirdparty = forms_bridge_dolibarr_create_thirdparty($payload, $bridge);

    if (is_wp_error($thirdparty)) {
        return $thirdparty;
    }

    $payload['socid'] = $thirdparty['id'];
    return $payload;
}

return [
    'title' => __('Third party', 'forms-bridge'),
    'description' => __(
        'Creates a new third party and returns its ID as the socid of the payload.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_dolibarr_thirdparty_socid',
    'input' => [
        [
            'name' => 'name',
            'schema' => ['type' => 'string'],
            'required' => true,
        ],
        [
            'name' => 'email',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'code_client',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'idprof1',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'idprof2',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'tva_intra',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'phone',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'fax',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'url',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'address',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'zip',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'town',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'country_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'region_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'state_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'typent_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'status',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'client',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'fournisseur',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'stcomm_id',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'note_public',
            'schema' => ['type' => 'string'],
        ],
        [
            'name' => 'no_email',
            'schema' => ['type' => 'integer'],
        ],
        [
            'name' => 'parent',
            'schema' => ['type' => 'integer'],
        ],
    ],
    'output' => [
        [
            'name' => 'socid',
            'schema' => ['type' => 'string'],
        ],
    ],
];
