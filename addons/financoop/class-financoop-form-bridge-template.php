<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Finan_Coop_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#bridge',
                'name' => 'campaign_id',
                'label' => 'Campaign ID',
                'type' => 'number',
                'required' => true,
            ],
            [
                'ref' => '#bridge',
                'name' => 'name',
                'label' => 'Bridge name',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
                'required' => true,
                'default' => 'FinanCoop',
            ],
            [
                'ref' => '#backend',
                'name' => 'base_url',
                'label' => 'Base URL',
                'type' => 'string',
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
            'endpoint' => '/api/campaign/{campaign_id}',
            'method' => 'POST',
        ],
    ];

    /**
     * Sets the template api, extends the common schema and inherits the parent's
     * constructor.
     *
     * @param string $file Source file path of the template config.
     * @param array $config Template config data.
     * @param string $api Bridge API name.
     */
    public function __construct($file, $config, $api)
    {
        parent::__construct($file, $config, $api);

        add_filter(
            'forms_bridge_template_data',
            function ($data, $template_name) {
                if ($template_name === $this->name) {
                    if (!empty($data['backend']['name'])) {
                        $data['bridge']['backend'] = $data['backend']['name'];
                    }

                    $index = array_search(
                        'campaign_id',
                        array_column($data['fields'], 'name')
                    );

                    if ($index !== false) {
                        $campaign_id = $data['fields'][$index]['value'];
                        $data['bridge']['endpoint'] = preg_replace(
                            '/\{campaign_id\}/',
                            $campaign_id,
                            $data['bridge']['endpoint']
                        );
                    }
                }

                return $data;
            },
            9,
            2
        );

        add_filter(
            'forms_bridge_payload',
            function ($payload, $bridge) {
                if ($bridge->template === $this->name) {
                    preg_match(
                        '/(?<=campaign\/)[0-9]+/',
                        $bridge->endpoint,
                        $matches
                    );

                    $payload['campaign_id'] = (int) $matches[0];
                    $payload['lang'] = apply_filters(
                        'wpct_i18n_current_language',
                        get_locale(),
                        'locale'
                    );
                }

                return $payload;
            },
            9,
            2
        );
    }

    protected function extend_schema($schema)
    {
        $schema = parent::extend_schema($schema);
        $schema['bridge']['properties']['method']['enum'] = ['POST'];
        return $schema;
    }
}
