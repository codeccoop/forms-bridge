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
    protected $api = 'gsheets';

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
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'value' => Google_Sheets_Addon::$static_backend['name'],
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' =>
                            Google_Sheets_Addon::$static_backend['base_url'],
                    ],
                ],
                'backend' => Google_Sheets_Addon::$static_backend,
                'bridge' => [
                    'backend' => Google_Sheets_Addon::$static_backend['name'],
                    'endpoint' => '',
                    'spreadsheet' => '',
                    'tab' => '',
                ],
                'spreadsheet' => [
                    'id' => '',
                    'tab' => '',
                ],
            ],
            parent::defaults(),
            self::schema()
        );
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
                    $data['bridge']['endpoint'] =
                        $data['spreadsheet']['id'] .
                        '::' .
                        $data['spreadsheet']['tab'];
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
     * @return array
     */
    public static function schema()
    {
        $schema = parent::schema();

        $schema['properties']['bridge']['properties']['spreadsheet'] = [
            'type' => 'string',
        ];
        $schema['properties']['bridge']['required'][] = 'spreadsheet';

        $schema['properties']['bridge']['properties']['tab'] = [
            'type' => 'string',
        ];
        $schema['properties']['bridge']['required'][] = 'tab';

        $schema['properties']['bridge']['properties']['endpoint'] = [
            'type' => 'string',
        ];
        $schema['properties']['bridge']['required'][] = 'endpoint';

        $schema['properties']['spreadsheet'] = [
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
