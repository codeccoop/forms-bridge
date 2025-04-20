<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Mailchimp_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'mailchimp';

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
                            'Label of the MailChimp API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'MailChimp API',
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'value' => 'https://{dc}.api.mailchimp.com',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'datacenter',
                        'label' => __('Datacenter', 'forms-bridge'),
                        'description' => __(
                            'First part of the URL of your mailchimp account or last part of your API key',
                            'forms-bridge'
                        ),
                        'required' => true,
                        'type' => 'options',
                        'options' => [
                            [
                                'label' => 'us1',
                                'value' => 'us1',
                            ],
                            [
                                'label' => 'us2',
                                'value' => 'us2',
                            ],
                            [
                                'label' => 'us3',
                                'value' => 'us3',
                            ],
                            [
                                'label' => 'us4',
                                'value' => 'us4',
                            ],
                            [
                                'label' => 'us5',
                                'value' => 'us5',
                            ],
                            [
                                'label' => 'us6',
                                'value' => 'us6',
                            ],
                            [
                                'label' => 'us7',
                                'value' => 'us7',
                            ],
                            [
                                'label' => 'us8',
                                'value' => 'us8',
                            ],
                            [
                                'label' => 'us9',
                                'value' => 'us9',
                            ],
                            [
                                'label' => 'us10',
                                'value' => 'us10',
                            ],
                            [
                                'label' => 'us11',
                                'value' => 'us11',
                            ],
                            [
                                'label' => 'us12',
                                'value' => 'us12',
                            ],
                            [
                                'label' => 'us13',
                                'value' => 'us13',
                            ],
                        ],
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'api-key',
                        'label' => __('API key', 'forms-bridge'),
                        'description' => __(
                            'Get it from your <a href="https://us1.admin.mailchimp.com/account/api/" target="_blank">account</a>',
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
                    'backend' => '',
                    'endpoint' => '',
                    'method' => 'POST',
                ],
            ],
            parent::defaults(),
            self::$schema
        );
    }

    /**
     * Sets the template api, extends the common schema and inherits the parent's
     * constructor.
     *
     * @param string $file Source file path of the template config.
     * @param array $config Template config data.
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
                }

                return $data;
            },
            10,
            3
        );
    }
}
