<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Finan_Coop_Form_Bridge_Template extends Form_Bridge_Template
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
                'type' => 'number',
                'required' => true,
            ],
            // [
            //     'ref' => '#form/fields[]',
            //     'name' => 'lang',
            //     'label' => 'Language',
            //     'type' => 'string',
            //     'required' => true,
            //     'value' => 'en_US',
            // ],
            [
                'ref' => '#bridge',
                'name' => 'name',
                'label' => 'Bridge name',
                'type' => 'string',
                'required' => true,
            ],
            // [
            //     'ref' => '#bridge',
            //     'name' => 'backend',
            //     'label' => 'Backend',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            // [
            //     'ref' => '#bridge',
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
                'default' => 'FinanCoop',
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'string',
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
        'bridge' => [
            'backend' => 'FinanCoop',
            'endpoint' => '/api/campaign/{campaign_id}',
        ],
        'backend' => [
            'name' => 'FinanCoop',
            'pipes' => [
                [
                    'from' => 'submission_id',
                    'to' => 'submission_id',
                    'cast' => 'null',
                ],
            ],
        ],
        'form' => [
            'fields' => [
                [
                    'name' => 'campaign_id',
                    'type' => 'hidden',
                    'required' => true,
                ],
                [
                    'name' => 'lang',
                    'type' => 'hidden',
                    'required' => true,
                    'value' => 'en_US',
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
     * @param string $api Bridge API name.
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

        add_filter(
            'forms_bridge_template_data',
            function ($data, $template_name) {
                if ($template_name === $this->name) {
                    if (!empty($data['backend']['name'])) {
                        $data['bridge']['backend'] = $data['backend']['name'];
                    }

                    $index = array_search(
                        'campaign_id',
                        array_column($data['fields'], 'name')
                    );

                    if ($index !== false) {
                        $campaign_id = $data['fields'][$index]['value'];
                        $data['bridge']['endpoint'] = preg_replace(
                            '/\{campaign_id\}/',
                            $campaign_id,
                            $data['bridge']['endpoint']
                        );
                    }
                }

                return $data;
            },
            9,
            2
        );
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
        $schema['bridge']['properties'] = array_merge(
            $schema['bridge']['properties'],
            [
                'backend' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
            ]
        );

        $schema['bridge']['required'][] = 'backend';
        $schema['bridge']['required'][] = 'endpoint';

        return $schema;
    }
}
