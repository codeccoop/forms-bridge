<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Zoho_Form_Bridge_Template extends Form_Bridge_Template
{
    /**
     * Template default config getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return [
            'fields' => [
                [
                    'ref' => '#credential',
                    'name' => 'name',
                    'label' => __('Credential name', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#credential',
                    'name' => 'organization_id',
                    'label' => __('Organization ID', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#credential',
                    'name' => 'client_id',
                    'label' => __('Client ID', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#credential',
                    'name' => 'client_secret',
                    'label' => __('Client secret', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#bridge',
                    'name' => 'endpoint',
                    'label' => __('Endpoint', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#bridge',
                    'name' => 'scope',
                    'label' => __('Scope', 'forms-bridge'),
                    'description' => __(
                        'See <a href="https://www.zoho.com/accounts/protocol/oauth/scope.html">the documentation</a> for more information',
                        'forms-bridge'
                    ),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#backend',
                    'name' => 'base_url',
                    'type' => 'options',
                    'options' => [
                        [
                            'label' => 'www.zohoapis.com',
                            'value' => 'https://www.zohoapis.com',
                        ],
                        [
                            'label' => 'www.zohoapis.eu',
                            'value' => 'https://www.zohoapis.eu',
                        ],
                        [
                            'label' => 'www.zohoapis.com.au',
                            'value' => 'https://www.zohoapis.com.au',
                        ],
                        [
                            'label' => 'www.zohoapis.in',
                            'value' => 'https://www.zohoapis.in',
                        ],
                        [
                            'label' => 'www.zohoapis.cn',
                            'value' => 'https://www.zohoapis.cn',
                        ],
                        [
                            'label' => 'www.zohoapis.jp',
                            'value' => 'https://www.zohoapis.jp',
                        ],
                        [
                            'label' => 'www.zohoapis.sa',
                            'value' => 'https://www.zohoapis.sa',
                        ],
                        [
                            'label' => 'www.zohoapis.ca',
                            'value' => 'https://www.zohoapis.ca',
                        ],
                    ],
                    'default' => 'https://www.zohoapis.com',
                ],
            ],
            'bridge' => [
                'form_id' => '',
                'backend' => 'Zoho API',
                'endpoint' => '',
                'scope' => '',
                'credential' => '',
                'custom_fields' => [],
                'mutations' => [[]],
            ],
            'credential' => [
                'name' => '',
                'organization_id' => '',
                'client_id' => '',
                'client_secret' => '',
            ],
        ];
    }

    /**
     * Sets the template api, extends the common schema and inherits the parent's
     * constructor.
     *
     * @param string $file Source file path of the template config.
     * @param array $config Template config data.
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
        $schema['credential'] = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'organization_id' => ['type' => 'string'],
                'client_id' => ['type' => 'string'],
                'client_secret' => ['type' => 'string'],
            ],
            'required' => [
                'name',
                'organization_id',
                'client_id',
                'client_secret',
            ],
            'additionalProperties' => false,
        ];

        $schema['bridge']['properties'] = array_merge(
            $schema['bridge']['properties'],
            [
                'credential' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
                'scope' => ['type' => 'string'],
            ]
        );

        $schema['bridge']['required'][] = 'credential';
        $schema['bridge']['required'][] = 'endpoint';
        $schema['bridge']['required'][] = 'scope';

        return $schema;
    }
}
