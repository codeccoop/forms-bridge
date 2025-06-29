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
        return wpct_plugin_merge_object(
            [
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
            ],
            parent::defaults(),
            self::schema()
        );
    }

    /**
     * Extends the common schema and adds custom properties.
     *
     * @param array $schema Common template data schema.
     *
     * @return array
     */
    public static function schema()
    {
        $schema = parent::schema();

        $schema['properties']['bridge']['properties'] = array_merge(
            $schema['properties']['bridge']['properties'],
            [
                'endpoint' => ['type' => 'string'],
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                ],
            ]
        );

        $schema['properties']['bridge']['required'][] = 'endpoint';
        $schema['properties']['bridge']['required'][] = 'method';

        return $schema;
    }
}
