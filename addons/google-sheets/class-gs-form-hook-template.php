<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheets_Form_Hook_Template extends Form_Hook_Template
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
                'name' => 'spreadsheet',
                'label' => 'Spreadsheet',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#hook',
                'name' => 'tab',
                'label' => 'Tab',
                'type' => 'string',
                'required' => true,
            ],
        ],
        'hook' => [
            'spreadsheet' => '',
            'tab' => '',
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
                'spreadsheet' => ['type' => 'string'],
                'tab' => ['type' => 'string'],
            ]
        );

        $schema['hook']['required'][] = 'spreadsheet';
        $schema['hook']['required'][] = 'tab';

        return $schema;
    }
}
