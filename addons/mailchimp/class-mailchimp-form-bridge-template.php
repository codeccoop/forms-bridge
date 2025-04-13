<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Mailchimp_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            // [
            //     'ref' => '#bridge',
            //     'name' => 'backend',
            //     'label' => 'Backend',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            [
                'ref' => '#bridge',
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#bridge',
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
        'bridge' => [
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

        add_filter(
            'forms_bridge_template_data',
            function ($data, $template_name) {
                if ($template_name === $this->name) {
                    if (!empty($data['backend']['name'])) {
                        $data['bridge']['backend'] = $data['backend']['name'];
                    }
                }

                return $data;
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
    protected function extend_schema($schema)
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
