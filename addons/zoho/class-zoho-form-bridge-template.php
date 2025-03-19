<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Zoho_Form_Bridge_Template extends Form_Bridge_Template
{
    private $credential_data = null;

    /**
     * Handles the template default values.
     *
     * @var array
     */
    protected static $default = [
        'fields' => [
            [
                'ref' => '#credential',
                'name' => 'name',
                'label' => 'Credentials name',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#credential',
                'name' => 'organization_id',
                'label' => 'Organization ID',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#credential',
                'name' => 'client_id',
                'label' => 'Client ID',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#credential',
                'name' => 'client_secret',
                'label' => 'Client secret',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#bridge',
                'name' => 'endpoint',
                'label' => 'Endpoint',
                'type' => 'string',
                'required' => true,
            ],
            [
                'ref' => '#bridge',
                'name' => 'scope',
                'label' => 'Scope',
                'description' =>
                    'See <a href="https://www.zoho.com/accounts/protocol/oauth/scope.html">the documentation</a> for more information',
                'type' => 'string',
                'required' => true,
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
                'type' => 'options',
                'options' => [
                    [
                        'label' => 'www.zohoapis.com',
                        'value' => 'https://www.zohoapis.com',
                    ],
                    [
                        'label' => 'www.zohoapis.eu',
                        'value' => 'https://www.zohoapis.eu',
                    ],
                    [
                        'label' => 'www.zohoapis.com.au',
                        'value' => 'https://www.zohoapis.com.au',
                    ],
                    [
                        'label' => 'www.zohoapis.in',
                        'value' => 'https://www.zohoapis.in',
                    ],
                    [
                        'label' => 'www.zohoapis.cn',
                        'value' => 'https://www.zohoapis.cn',
                    ],
                    [
                        'label' => 'www.zohoapis.jp',
                        'value' => 'https://www.zohoapis.jp',
                    ],
                    [
                        'label' => 'www.zohoapis.sa',
                        'value' => 'https://www.zohoapis.sa',
                    ],
                    [
                        'label' => 'www.zohoapis.ca',
                        'value' => 'https://www.zohoapis.ca',
                    ],
                ],
                'default' => 'https://www.zohoapis.com',
            ],
        ],
        'bridge' => [
            'credential' => '',
            'form_id' => '',
            'endpoint' => '',
            'scope' => '',
        ],
        'credential' => [
            'name' => '',
            'backend' => '',
            'organization_id' => '',
            'client_id' => '',
            'client_secret' => '',
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
                    $this->credential_data = array_merge($data['credential'], [
                        'backend' => $data['backend']['name'],
                    ]);

                    $data['bridge']['credential'] = $data['credential']['name'];
                }

                return $data;
            },
            10,
            2
        );

        add_action(
            'forms_bridge_before_template_bridge',
            function ($data, $template_name) {
                if ($template_name === $this->name && $this->credential_data) {
                    $credential_exists = $this->credential_exists(
                        $this->credential_data['name']
                    );

                    if ($credential_exists) {
                        return;
                    }

                    $result = $this->create_credential($this->credential_data);

                    if (!$result) {
                        throw new Form_Bridge_Template_Exception(
                            'credential_creation_error',
                            __(
                                'Forms Bridge can\'t create the credentials',
                                'forms-bridge'
                            )
                        );
                    }
                }
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
    private function extend_schema($schema)
    {
        $schema['credential'] = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'backend' => ['type' => 'string'],
                'organization_id' => ['type' => 'string'],
                'client_id' => ['type' => 'string'],
                'client_secret' => ['type' => 'string'],
            ],
            'required' => [
                'name',
                'backend',
                'organization_id',
                'client_id',
                'client_secret',
            ],
            'additionalProperties' => false,
        ];

        $schema['bridge']['properties'] = array_merge(
            $schema['bridge']['properties'],
            [
                'credential' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
                'scope' => ['type' => 'string'],
            ]
        );

        $schema['bridge']['required'][] = 'credential';
        $schema['bridge']['required'][] = 'endpoint';
        $schema['bridge']['required'][] = 'scope';

        return $schema;
    }

    /**
     * Checks if a credential with the given name exists on the settings store.
     *
     * @param string $name Credential name.
     *
     * @return boolean
     */
    private function credential_exists($name)
    {
        $setting = Forms_Bridge::setting($this->api);
        $credentials = $setting->credentials ?: [];

        return array_search($name, array_column($credentials, 'name')) !==
            false;
    }

    /**
     * Stores the credential data on the settings store.
     *
     * @param array $data Credential data.
     *
     * @return boolean Creation result.
     */
    private function create_credential($data)
    {
        $setting = Forms_Bridge::setting($this->api);
        $credentials = $setting->credentials;

        do_action(
            'forms_bridge_before_template_credential',
            $data,
            $this->name
        );

        $setting->credentials = array_merge($credentials, [$data]);
        $setting->flush();

        $is_valid = $this->credential_exists($data['name']);

        if (!$is_valid) {
            return false;
        }

        do_action('forms_bridge_template_credential', $data, $this->name);

        return true;
    }
}
