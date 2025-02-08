<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Finan_Coop_Form_Hook_Template extends Form_Hook_Template
{
    protected static $default = [
        'fields' => [
            // [
            //     'ref' => '#form',
            //     'name' => 'title',
            //     'label' => 'Form title',
            //     'type' => 'string',
            //     'required' => true,
            // ],
            [
                'ref' => '#form/fields[]',
                'name' => 'campaign_id',
                'label' => 'Campaign ID',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#form/fields[]',
                'name' => 'lang',
                'label' => 'Language',
                'type' => 'string',
                'required' => true,
                'value' => 'en_US',
            ],
            [
                'ref' => '#hook',
                'name' => 'name',
                'label' => 'Hook name',
                'type' => 'string',
                'required' => true,
            ],
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
                'ref' => '#backend',
                'name' => 'name',
                'label' => 'Nmae',
                'type' => 'string',
                'required' => true,
                'value' => 'FinanCoop',
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
        'hook' => [
            'name' => '',
            'backend' => 'FinanCoop',
            'form_id' => '',
            'endpoint' => '',
        ],
        'backend' => [
            'name' => 'FinanCoop',
        ],
        'form' => [
            'fields' => [
                [
                    'name' => 'campaign_id',
                    'type' => 'string',
                    'required' => true,
                ],
            ],
        ],
    ];

    /**
     * Store template attribute values, validates config data and binds the
     * instance to custom forms bridge template hooks.
     *
     * @param string $file Source file path of the template config.
     * @param array $config Template config data.
     */
    public function __construct($file, $config)
    {
        $this->api = 'financoop';
        parent::__construct($file, $config);
    }
}
