<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Rest_Form_Hook_Template extends Form_Hook_Template
{
    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#hook',
                'name' => 'backend',
                'label' => 'Backend',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#hook',
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#hook',
                'name' => 'method',
                'label' => 'Method',
                'type' => 'string',
                'required' => true,
                'default' => 'POST',
            ],
            [
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'string',
            ],
        ],
        'hook' => [
            'backend' => '',
            'endpoint' => '',
            'method' => 'POST',
        ],
    ];

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
        $schema['hook']['properties'] = array_merge(
            $schema['hook']['properties'],
            [
                'backend' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
                'method' => [
                    'type' => 'string',
                    'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                ],
            ]
        );

        $schema['hook']['required'][] = 'backend';
        $schema['hook']['required'][] = 'endpoint';
        $schema['hook']['required'][] = 'method';

        return $schema;
    }
}
