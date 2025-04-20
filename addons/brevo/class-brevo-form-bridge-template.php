<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Brevo_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'brevo';

    /**
     * Template default config getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return forms_bridge_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the Brevo API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'Brevo API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' => 'https://api.brevo.com',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'api-key',
                        'label' => __('API Key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://app.brevo.com/settings/keys/api" target="_blank">account</a>',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'value' => 'POST',
                    ],
                ],
                'bridge' => [
                    'method' => 'POST',
                ],
                'backend' => [
                    'base_url' => 'https://api.brevo.com',
                ],
            ],
            parent::defaults(),
            self::$schema
        );
    }
}
