<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_Form_Bridge_Template extends Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'odoo';

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
                        'ref' => '#credential',
                        'name' => 'name',
                        'label' => __('Name', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'database',
                        'label' => __('Database', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'user',
                        'label' => __('User', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#credential',
                        'name' => 'password',
                        'label' => __('Password', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'Odoo',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'endpoint',
                        'label' => __('Model', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'name' => '',
                    'form_id' => '',
                    'backend' => '',
                    'credential' => '',
                    'endpoint' => '',
                ],
                'backend' => [
                    'name' => 'Odoo',
                    'headers' => [
                        [
                            'name' => 'Accept',
                            'value' => 'application/json',
                        ],
                    ],
                ],
                'credential' => [
                    'name' => '',
                    'database' => '',
                    'user' => '',
                    'password' => '',
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

        $schema['properties']['bridge']['properties']['endpoint'] = [
            'type' => 'string',
        ];
        $schema['properties']['bridge']['required'][] = 'endpoint';

        $schema['properties']['credential']['properties']['user'] = [
            'type' => 'string',
        ];
        $schema['properties']['credential']['required'][] = 'user';

        $schema['properties']['credential']['properties']['database'] = [
            'type' => 'string',
        ];
        $schema['properties']['credential']['required'][] = 'database';

        $schema['properties']['credential']['properties']['password'] = [
            'type' => 'string',
        ];
        $schema['properties']['credential']['required'][] = 'password';

        return $schema;
    }
}
