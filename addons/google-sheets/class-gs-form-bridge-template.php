<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheets_Form_Bridge_Template extends Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'google-sheets';

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
                    'ref' => '#spreadsheet',
                    'name' => 'id',
                    'label' => 'Spreadsheet',
                    'type' => 'string',
                    'required' => true,
                ],
                [
                    'ref' => '#spreadsheet',
                    'name' => 'tab',
                    'label' => 'Tab',
                    'type' => 'string',
                    'required' => true,
                ],
            ],
            'bridge' => [
                'spreadsheet' => '',
                'tab' => '',
            ],
            'spreadsheet' => [
                'id' => '',
                'tab' => '',
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
    public function __construct($file, $config)
    {
        parent::__construct($file, $config);

        add_filter(
            'forms_bridge_template_data',
            function ($data, $template_name) {
                if ($template_name === $this->name) {
                    $data['bridge']['spreadsheet'] = $data['spreadsheet']['id'];
                    $data['bridge']['tab'] = $data['spreadsheet']['tab'];
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
    protected static function extend_schema($schema)
    {
        $schema['bridge']['properties'] = array_merge(
            $schema['bridge']['properties'],
            [
                'spreadsheet' => ['type' => 'string'],
                'tab' => ['type' => 'string'],
            ]
        );

        $schema['bridge']['required'][] = 'spreadsheet';
        $schema['bridge']['required'][] = 'tab';

        $schema['spreadsheet'] = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'string'],
                'tab' => ['type' => 'string'],
            ],
            'required' => ['id', 'tab'],
            'additionalProperties' => false,
        ];

        return $schema;
    }
}
