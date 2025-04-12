<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Dolibarr_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#bridge',
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
                'default' => 'Dolibarr',
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend/headers[]',
                'name' => 'DOLAPIKEY',
                'label' => 'API key',
                'type' => 'string',
                'required' => true,
            ],
        ],
        'bridge' => [
            'backend' => '',
            'form_id' => '',
            'endpoint' => '',
            'api_key' => '',
        ],
        'backend' => [
            'name' => 'Dolibarr',
            'headers' => [
                [
                    'name' => 'Content-Type',
                    'value' => 'application/json',
                ],
                [
                    'name' => 'Accept',
                    'value' => 'application/json',
                ],
            ],
        ],
    ];

    /**
     * Extends the common schema and adds custom properties.
     *
     * @param array $schema Common template data schema.
     *
     * @return array
     */
    protected function extend_schema($schema)
    {
        $schema = parent::extend_schema($schema);
        $schema['bridge']['properties']['method']['enum'] = ['POST'];
        return $schema;
    }
}
