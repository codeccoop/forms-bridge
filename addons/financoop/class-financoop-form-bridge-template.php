<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Finan_Coop_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'financoop';

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
                        'name' => 'method',
                        'value' => 'POST',
                    ],
                    [
                        'ref' => '#bridge/custom_fields[]',
                        'name' => 'campaign_id',
                        'label' => __('Campaign ID', 'forms-bridge'),
                        'type' => 'number',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'default' => 'FinanCoop',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Db',
                        'label' => 'Database',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Username',
                        'label' => 'Username',
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'X-Odoo-Api-Key',
                        'label' => 'API Key',
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'backend' => 'FinanCoop',
                    'method' => 'POST',
                ],
                'backend' => [
                    'headers' => [
                        [
                            'name' => 'Accept',
                            'value' => 'application/json',
                        ],
                    ],
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
                    $index = array_search(
                        'campaign_id',
                        array_column($data['bridge']['custom_fields'], 'name')
                    );

                    if ($index !== false) {
                        $campaign_id =
                            $data['bridge']['custom_fields'][$index]['value'];

                        $data['bridge']['endpoint'] = preg_replace(
                            '/\{campaign_id\}/',
                            $campaign_id,
                            $data['bridge']['endpoint']
                        );

                        array_splice(
                            $data['bridge']['custom_fields'],
                            $index,
                            1
                        );

                        $data['bridge']['custom_fields'] = array_values(
                            $data['bridge']['custom_fields']
                        );
                    }
                }

                return $data;
            },
            5,
            2
        );
    }
}
