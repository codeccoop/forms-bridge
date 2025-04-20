<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Rest_Form_Bridge_Template extends Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'rest-api';

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
                    'ref' => '#bridge',
                    'name' => 'endpoint',
                    'label' => __('Endpoint', 'forms-bridge'),
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#bridge',
                    'name' => 'method',
                    'label' => __('Method', 'forms-bridge'),
                    'type' => 'options',
                    'options' => [
                        [
                            'value' => 'GET',
                            'label' => 'GET',
                        ],
                        [
                            'value' => 'POST',
                            'label' => 'POST',
                        ],
                        [
                            'value' => 'PUT',
                            'label' => 'PUT',
                        ],
                        [
                            'value' => 'DELETE',
                            'label' => 'DELETE',
                        ],
                    ],
                    'required' => true,
                    'default' => 'POST',
                ],
            ],
            'bridge' => [
                'endpoint' => '',
                'method' => 'POST',
            ],
        ];
    }

    /**
     * Extends the common schema and adds custom properties.
     *
     * @param array $schema Common template data schema.
     *
     * @return array
     */
    protected static function extend_schema($schema)
    {
        $schema['bridge']['properties'] = array_merge(
            $schema['bridge']['properties'],
            [
                'backend' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                ],
            ]
        );

        $schema['bridge']['required'][] = 'backend';
        $schema['bridge']['required'][] = 'endpoint';
        $schema['bridge']['required'][] = 'method';

        return $schema;
    }
}
