<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Odoo_Form_Hook_Template extends Form_Hook_Template
{
    private $database_data = null;

    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#database',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
            ],
            // [
            //     'ref' => '#database',
            //     'name' => 'backend',
            //     'label' => 'Backend',
            //     'type' => 'string',
            // ],
            [
                'ref' => '#database',
                'name' => 'user',
                'label' => 'User',
                'type' => 'string',
            ],
            [
                'ref' => '#database',
                'name' => 'password',
                'label' => 'Password',
                'type' => 'string',
            ],
            // [
            //     'ref' => '#hook',
            //     'name' => 'database',
            //     'label' => 'Database',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            // [
            //     'ref' => '#hook',
            //     'name' => 'model',
            //     'label' => 'Model',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            [
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
                'default' => 'Odoo',
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'string',
            ],
        ],
        'hook' => [
            'name' => '',
            'form_id' => '',
            'database' => '',
            'model' => '',
        ],
        'backend' => [
            'name' => 'Odoo',
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
        'database' => [
            'name' => '',
            'backend' => 'Odoo',
            'user' => '',
            'password' => '',
        ],
    ];

    /**
     * Store template attribute values, validates config data and binds the
     * instance to custom forms bridge template hooks.
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

        add_filter(
            'forms_bridge_template_data',
            function ($data, $name) {
                if ($name === $this->name) {
                    $this->database_data = array_merge($data['database'], [
                        'backend' => $data['backend']['name'],
                    ]);

                    $data['hook']['database'] = $data['database']['name'];
                }

                return $data;
            },
            10,
            2
        );

        add_action(
            'forms_bridge_before_template_hook',
            function ($data, $template_name) {
                if ($template_name === $this->name && $this->database_data) {
                    $database_exists = $this->database_exists(
                        $this->database_data['name']
                    );

                    if ($database_exists) {
                        return;
                    }

                    $result = $this->create_database($this->database_data);

                    if (!$result) {
                        throw new Form_Hook_Template_Exception(
                            'database_creation_error',
                            __(
                                'Forms bridge can\'t create the database',
                                'forms-bridge'
                            )
                        );
                    }
                }
            },
            10,
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
        $schema['database'] = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'user' => ['type' => 'string'],
                'password' => ['type' => 'string'],
                'backend' => ['type' => 'string'],
            ],
            'required' => ['name', 'user', 'password', 'backend'],
            'additionalProperties' => false,
        ];

        $schema['hook']['properties'] = array_merge(
            $schema['hook']['properties'],
            [
                'database' => ['type' => 'string'],
                'model' => ['type' => 'string'],
            ]
        );

        $schema['hook']['required'][] = 'database';
        $schema['hook']['required'][] = 'model';

        return $schema;
    }

    /**
     * Checks if a backend with the given name exists on the settings store.
     *
     * @param string $name Backend name.
     *
     * @return boolean
     */
    private function database_exists($name)
    {
        $setting = Forms_Bridge::setting($this->api);
        $databases = $setting->databases;

        return array_search($name, array_column($databases, 'name')) !== false;
    }

    /**
     * Stores the database data on the settings store.
     *
     * @param array $data Database data.
     *
     * @return boolean Creation result.
     */
    private function create_database($data)
    {
        $setting = Forms_Bridge::setting($this->api);
        $databases = $setting->databases;

        do_action('forms_bridge_before_template_database', $data, $this->name);

        $setting->databases = array_merge($databases, [$data]);
        $setting->refresh();

        $is_valid = $this->database_exists($data['name']);

        if (!$is_valid) {
            return;
        }

        do_action('forms_bridge_template_database', $data, $this->name);

        return true;
    }
}
