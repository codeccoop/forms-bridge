<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Finan_Coop_Form_Hook_Template extends Form_Hook_Template
{
    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#form/fields[]',
                'name' => 'campaign_id',
                'label' => 'Campaign ID',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#form/fields[]',
                'name' => 'lang',
                'label' => 'Language',
                'type' => 'string',
                'required' => true,
                'value' => 'en_US',
            ],
            [
                'ref' => '#hook',
                'name' => 'name',
                'label' => 'Hook name',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#hook',
                'name' => 'backend',
                'label' => 'Backend',
                'type' => 'string',
                'required' => true,
            ],
            // [
            //     'ref' => '#hook',
            //     'name' => 'endpoint',
            //     'label' => 'Endpoint',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            [
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
                'value' => 'FinanCoop',
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'url',
            ],
            [
                'ref' => '#backend/headers[]',
                'name' => 'X-Odoo-Db',
                'label' => 'Database',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend/headers[]',
                'name' => 'X-Odoo-Username',
                'label' => 'Username',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend/headers[]',
                'name' => 'X-Odoo-Api-Key',
                'label' => 'API Key',
                'type' => 'string',
                'required' => true,
            ],
        ],
        'hook' => [
            'backend' => 'FinanCoop',
            'endpoint' => '',
        ],
        'backend' => [
            'name' => 'FinanCoop',
        ],
        'form' => [
            'fields' => [
                [
                    'name' => 'campaign_id',
                    'type' => 'hidden',
                    'required' => true,
                ],
            ],
        ],
    ];

    /**
     * Sets the template api, extends the common schema and inherits the parent's
     * constructor.
     *
     * @param string $file Source file path of the template config.
     * @param array $config Template config data.
     * @param string $api Form hook API name.
     */
    public function __construct($file, $config, $api)
    {
        add_filter(
            'forms_bridge_template_schema',
            function ($schema, $template_name) {
                if ($template_name === $this->name) {
                    $schema = $this->extend_schema($schema);
                }

                return $schema;
            },
            10,
            2
        );

        parent::__construct($file, $config, $api);
    }

    /**
     * Extends the common schema and adds custom properties.
     *
     * @param array $schema Common template data schema.
     *
     * @return array
     */
    private function extend_schema($schema)
    {
        $schema['hook']['properties'] = array_merge(
            $schema['hook']['properties'],
            [
                'backend' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
            ]
        );

        $schema['hook']['required'][] = 'backend';
        $schema['hook']['required'][] = 'endpoint';

        return $schema;
    }
}
