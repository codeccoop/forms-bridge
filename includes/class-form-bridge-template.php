<?php

namespace FORMS_BRIDGE;

use Exception;
use Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Custom exception for fine grained error handling during template implementations.
 */
class Form_Bridge_Template_Exception extends Exception
{
    /**
     * Handles the error's string code.
     *
     * @var string.
     */
    private $string_code;

    /**
     * Recives a code as string and a message and store the string code
     * as a custom attribute.
     *
     * @param string $string_code String code compatible with WP_Error codes.
     * @param string $message Error message.
     */
    public function __construct($string_code, $message)
    {
        $this->string_code = $string_code;
        parent::__construct($message);
    }

    /**
     * String code getter.
     *
     * @return string
     */
    public function getStringCode()
    {
        return $this->string_code;
    }
}

/**
 * Form Bridge template class. Handles the config data validation
 * and the use of template as form bridge creation strategy.
 */
class Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api;

    /**
     * Handles the template name.
     *
     * @var string
     */
    private $name;

    /**
     * Handles the template config data.
     *
     * @var array
     */
    private $config;

    /**
     * Handles the common template data json schema. The schema is common for all
     * Form_Bridge_Templates.
     *
     * @var array
     */
    public static $schema = [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'integrations' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'uniqueItems' => true,
        ],
        'fields' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'ref' => [
                        'type' => 'string',
                        'pattern' => '#.+',
                    ],
                    'name' => [
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'label' => [
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'description' => ['type' => 'string'],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['string', 'number', 'options', 'boolean'],
                    ],
                    'required' => ['type' => 'boolean'],
                    'value' => [
                        'type' => [
                            'integer',
                            'number',
                            'string',
                            'array',
                            'boolean',
                        ],
                    ],
                    'default' => [
                        'type' => [
                            'integer',
                            'number',
                            'string',
                            'array',
                            'boolean',
                        ],
                    ],
                    'options' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'label' => ['type' => 'string'],
                                'value' => ['type' => 'string'],
                            ],
                        ],
                        'uniqueItems' => true,
                    ],
                    'enum' => [
                        'type' => 'array',
                        'items' => [
                            'type' => ['integer', 'number', 'string'],
                        ],
                        'uniqueItems' => true,
                    ],
                    'min' => ['type' => 'integer'],
                    'max' => ['type' => 'integer'],
                    'multiple' => ['type' => 'boolean'],
                ],
                'required' => ['ref', 'name', 'label', 'type'],
                'additionalProperties' => true,
            ],
        ],
        'bridge' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'form_id' => ['type' => 'string'],
                'backend' => ['type' => 'string'],
                'credential' => ['type' => 'string'],
                'custom_fields' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                        'additionalProperties' => false,
                        'required' => ['name', 'value'],
                    ],
                ],
                'mutations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'from' => ['type' => 'string'],
                                'to' => ['type' => 'string'],
                                'cast' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'boolean',
                                        'string',
                                        'integer',
                                        'number',
                                        'json',
                                        'csv',
                                        'concat',
                                        'join',
                                        'inherit',
                                        'copy',
                                        'null',
                                    ],
                                ],
                            ],
                            'additionalProperties' => false,
                            'required' => ['from', 'to', 'cast'],
                        ],
                    ],
                ],
                'workflow' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'name',
                'form_id',
                'backend',
                'custom_fields',
                'mutations',
            ],
            'additionalProperties' => false,
        ],
        'backend' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'base_url' => ['type' => 'string'],
                'headers' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'value'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['name', 'base_url', 'headers'],
            'additionalProperties' => false,
        ],
        'form' => [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'fields' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => [
                                    'text',
                                    'textarea',
                                    'number',
                                    'url',
                                    'email',
                                    'options',
                                    'date',
                                    'hidden',
                                ],
                            ],
                            'required' => ['type' => 'boolean'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'value' => [
                                            'type' => [
                                                'integer',
                                                'number',
                                                'string',
                                                'boolean',
                                            ],
                                        ],
                                        'label' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                            'value' => [
                                'type' => [
                                    'integer',
                                    'number',
                                    'string',
                                    // 'boolean',
                                    // 'object',
                                    'array',
                                    // 'null',
                                ],
                            ],
                            'is_file' => ['type' => 'boolean'],
                            'is_multi' => ['type' => 'boolean'],
                            'filetypes' => ['type' => 'string'],
                            'min' => ['type' => 'number'],
                            'max' => ['type' => 'number'],
                            'step' => ['type' => 'number'],
                            'format' => ['type' => 'string'],
                        ],
                        'required' => ['name', 'type'],
                    ],
                    'minItems' => 1,
                ],
            ],
            'required' => ['title', 'fields'],
            'additionalProperties' => false,
        ],
        'credential' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'additionalProperties' => true,
            'required' => ['name'],
        ],
    ];

    /**
     * Template default config getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return [];
    }

    /**
     * Validates input config against the template schema.
     *
     * @param string $name Template name.
     * @param array $config Input config.
     *
     * @return array|WP_Error Validated config.
     */
    private static function validate_config($name, $config)
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => apply_filters(
                'forms_bridge_template_schema',
                static::$schema,
                $name
            ),
            'required' => ['title', 'integrations', 'fields', 'form', 'bridge'],
        ];

        $config = self::with_defaults($config, $schema);
        $config_or_error = forms_bridge_validate_with_schema($config, $schema);
        return $config_or_error;
    }

    /**
     * Apply defaults to the given config.
     *
     * @param array $config Input config.
     * @param array $schema Template data schema.
     *
     * @return array Config fullfilled with defaults.
     */
    private static function with_defaults($config, $schema)
    {
        // merge template config with addon defaults
        $config = forms_bridge_merge_object(
            $config,
            static::defaults(),
            self::$schema
        );

        // merge template defaults with common defaults
        $config = forms_bridge_merge_object(
            $config,
            [
                'description' => '',
                'fields' => [
                    [
                        'ref' => '#form',
                        'name' => 'title',
                        'label' => __('Form title', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'label' => __('Backend', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'label' => __('Base URL', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'name',
                        'label' => __('Bridge name', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'name' => '',
                    'form_id' => '',
                    'backend' => '',
                    'custom_fields' => [],
                    'mutations' => [[]],
                    'workflow' => [],
                ],
                'backend' => [
                    'name' => '',
                    'base_url' => '',
                    'headers' => [
                        [
                            'name' => 'Content-Type',
                            'value' => 'application/json',
                        ],
                    ],
                ],
                'form' => [
                    'title' => '',
                    'fields' => [],
                ],
                'credential' => [
                    'name' => '',
                ],
            ],
            $schema
        );

        // add integrations to config
        return array_merge($config, [
            'integrations' => array_keys(Integration::integrations()),
        ]);
    }

    /**
     * Store template attribute values, validates config data and binds the
     * instance to custom forms bridge template hooks.
     *
     * @param string $name Template name.
     * @param array $config Template config data.
     * @param string $api Bridge API name.
     */
    public function __construct($name, $config, $api)
    {
        $this->api = $api;
        $this->name = $api . '-' . $name;
        $this->config = self::validate_config($this->name, $config);

        add_filter(
            'forms_bridge_templates',
            function ($templates, $api = null) {
                if ($api && $api !== $this->api) {
                    return $templates;
                }

                if (!wp_is_numeric_array($templates)) {
                    $templates = [];
                }

                if (is_wp_error($this->config)) {
                    return $templates;
                }

                $templates[] = $this;
                return $templates;
            },
            10,
            2
        );

        add_filter(
            'forms_bridge_template_data',
            function ($data, $template_name) {
                if (
                    $template_name === $this->name &&
                    isset($data['bridge']['credential'])
                ) {
                    $data['bridge']['credential'] = $data['credential']['name'];
                }

                return $data;
            },
            10,
            3
        );
    }

    /**
     * Magic method to proxy private template attributes and config data.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'api':
                return $this->api;
            case 'name':
                return $this->name;
            case 'config':
                return $this->config;
            default:
                return $this->config[$name] ?? null;
        }
    }

    /**
     * Decorates the template config data for REST responses.
     *
     * @return array REST config data.
     */
    public function to_json()
    {
        return [
            'name' => $this->name,
            'title' => $this->config['title'],
            'description' => $this->config['description'] ?? '',
            'fields' => array_values(
                array_filter($this->config['fields'], function ($field) {
                    return empty($field['value']);
                })
            ),
            'bridge' => $this->config['bridge'],
            'backend' => $this->config['backend'],
            'form' => $this->config['form'],
            'credential' => $this->config['credential'],
        ];
    }

    /**
     * Applies the input fields with the template's config data to
     * create a form and bind it with a bridge.
     *
     * @param array $fields User input fields data.
     * @param string $integration Target integration.
     */
    public function use($fields, $integration)
    {
        $template = $this->config;

        if (is_wp_error($template)) {
            return;
        }

        // Add constants to the user fields
        foreach ($template['fields'] as $field) {
            if (!empty($field['value'])) {
                $fields[] = $field;
            }
        }

        $all_fields = forms_bridge_merge_collection(
            $fields,
            $template['fields'],
            static::$schema['fields']['items']
        );

        $requireds = array_filter($all_fields, function ($field) {
            return ($field['required'] ?? false) && empty($field['value']);
        });

        if (
            count($fields) > count($all_fields) ||
            count($fields) < count($requireds)
        ) {
            throw new Form_Bridge_Template_Exception(
                'invalid_fields',
                __('Invalid template fields', 'forms-bridge')
            );
        }

        $data = $template;
        foreach ($fields as $field) {
            $is_required = $field['required'] ?? false;

            $field = forms_bridge_validate_with_schema($field, [
                'type' => 'object',
                'properties' => [
                    'ref' => [
                        'type' => 'string',
                        'pattern' => '#.+',
                    ],
                    'name' => [
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'value' => [
                        'type' => [
                            'number',
                            'integer',
                            'string',
                            'boolean',
                            'array',
                            'object',
                            'null',
                        ],
                    ],
                ],
                'required' => ['ref', 'name'],
            ]);

            if (is_wp_error($field)) {
                throw new Form_Bridge_Template_Exception(
                    'invalid_field',
                    sprintf(
                        __(
                            /* translators: %s: Field name */
                            'Field `%s` does not match the schema',
                            'forms-bridge'
                        ),
                        $field['name']
                    )
                );
            }

            if (empty($field['value']) && $is_required) {
                throw new Form_Bridge_Template_Exception(
                    'required_field',
                    sprintf(
                        __(
                            /* translators: %s: Field name */
                            'Field `%s` is required',
                            'forms-bridge'
                        ),
                        $field['name']
                    )
                );
            }

            // Inherit form field structure if field ref points to form fields
            if ($field['ref'] === '#form/fields[]') {
                $index = array_search(
                    $field['name'],
                    array_column($template['form']['fields'], 'name')
                );

                if ($index === false) {
                    throw new Form_Bridge_Template_Exception(
                        'invalid_template',
                        sprintf(
                            __(
                                /* translators: %s: Field name */
                                'Template does not include form field `%s`',
                                'forms-bridge'
                            ),
                            $field['name']
                        )
                    );
                }

                $form_field = $template['form']['fields'][$index];
                $field['index'] = $index;
                $field['value'] = array_merge($form_field, [
                    'value' => $field['value'],
                ]);

                if (
                    $field['type'] === 'boolean' &&
                    isset($field['value']['value']) &&
                    is_array($field['value']['value'])
                ) {
                    $field['value']['value'] =
                        ($field['value']['value'][0] ?? false) === '1';
                }
            }

            // Format backend headers' values
            if (
                $field['ref'] === '#backend/headers[]' ||
                $field['ref'] === '#bridge/custom_fields[]'
            ) {
                $field['value'] = [
                    'name' => $field['name'],
                    'value' => $field['value'],
                ];
            }

            $keys = explode('/', substr($field['ref'], 1));
            $leaf = &$data;
            foreach ($keys as $key) {
                $clean_key = str_replace('[]', '', $key);
                if (!isset($leaf[$clean_key])) {
                    throw new Form_Bridge_Template_Exception(
                        'invalid_ref',
                        sprintf(
                            __(
                                /* translators: %s: ref value */
                                'Invalid template field ref `%s`',
                                'forms-bridge'
                            ),
                            $field['ref']
                        )
                    );
                }

                $leaf = &$leaf[$clean_key];
            }

            if (substr($key, -2) === '[]') {
                if (isset($field['index'])) {
                    $leaf[$field['index']] = $field['value'];
                } else {
                    $leaf[] = $field['value'];
                }
            } else {
                $leaf[$field['name']] = $field['value'];
            }
        }

        $data['fields'] = $fields;
        $data = apply_filters(
            'forms_bridge_template_data',
            $data,
            $this->name,
            $this
        );

        if (empty($data) || is_wp_error($data)) {
            throw new Form_Bridge_Template_Exception(
                'template_creation_error',
                __('There is a problem with the template data', 'forms-bridge')
            );
        }

        $integration_instance = Integration::integrations()[$integration];

        do_action_ref_array('forms_bridge_before_template_form', [
            $data['form'],
            $this->name,
            $this,
        ]);

        try {
            $create_form = !$this->form_exists(
                $data['form']['id'] ?? null,
                $integration
            );

            if ($create_form) {
                $form_id = $integration_instance->create_form($data['form']);

                if (!$form_id) {
                    throw new Form_Bridge_Template_Exception(
                        'form_creation_error',
                        __(
                            'Forms bridge can\'t create the form',
                            'forms-bridge'
                        ),
                        ['status' => 400, 'data' => $data['form']]
                    );
                }

                $data['bridge']['form_id'] = $integration . ':' . $form_id;
                $data['form']['id'] = $form_id;

                do_action(
                    'forms_bridge_template_form',
                    $data['form'],
                    $this->name,
                    $this
                );
            } else {
                $form_id = $data['form']['id'];
            }

            $create_backend = !$this->backend_exists($data['backend']['name']);
            if ($create_backend) {
                $result = $this->create_backend($data['backend']);

                if (!$result) {
                    $integration_instance->remove_form($form_id);
                    throw new Form_Bridge_Template_Exception(
                        'backend_creation_error',
                        __(
                            'Forms bridge can\'t create the backend',
                            'forms-bridge',
                            ['status' => 400, 'data' => $data['backend']]
                        )
                    );
                }
            }

            $create_credential =
                !empty($data['credential']['name']) &&
                !$this->credential_exists($data['credential']['name']);

            if ($create_credential) {
                $result = $this->create_credential($data['credential']);

                if (!$result) {
                    if ($create_form) {
                        $integration_instance->remove_form($form_id);
                    }

                    if ($create_backend) {
                        $this->remove_backend($data['backend']['name']);
                    }

                    throw new Form_Bridge_Template_Exception(
                        'credential_creation_error',
                        __(
                            'Forms bridge can\'t create the credential',
                            'forms-bridge',
                            ['status' => 400, 'data' => $data['credential']]
                        )
                    );
                }
            }

            $result = $this->create_bridge(
                array_merge($data['bridge'], [
                    'form_id' => $integration . ':' . $form_id,
                ])
            );

            if (!$result) {
                if ($create_form) {
                    $integration_instance->remove_form($form_id);
                }

                if ($create_backend) {
                    $this->remove_backend($data['backend']['name']);
                }

                if ($create_credential) {
                    $this->remove_credential($data['credential']['name']);
                }

                throw new Form_Bridge_Template_Exception(
                    'bridge_creation_error',
                    __(
                        'Forms bridge can\'t create the form bridge',
                        'forms-bridge',
                        ['status' => 400, 'data' => $data['bridge']]
                    )
                );
            }
        } catch (Form_Bridge_Template_Exception $e) {
            throw $e;
        } catch (Error | Exception $e) {
            if (isset($form_id)) {
                $integration_instance->remove_form($form_id);
            }

            if (isset($create_backend) && $create_backend) {
                $this->remove_backend($data['backend']['name']);
            }

            if (isset($result) && $result) {
                $this->remove_bridge($data['bridge']['name']);
            }

            throw new Form_Bridge_Template_Exception(
                'internal_server_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Checks if a form with the given id exists on the settings store.
     *
     * @param string $form_id Internal ID of the form.
     * @param string $integration Slug of the target integration.
     *
     * @return boolean
     */
    private function form_exists($form_id, $integration)
    {
        $form = apply_filters(
            'forms_bridge_form',
            null,
            $form_id,
            $integration
        );
        return !empty($form['id']);
    }

    private function create_form($data) {}

    private function remove_form($name) {}

    /**
     * Checks if a backend with the given name exists on the settings store.
     *
     * @param string $name Backend name.
     *
     * @return boolean
     */
    final protected function backend_exists($name)
    {
        $backends =
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: [];
        return array_search($name, array_column($backends, 'name')) !== false;
    }

    /**
     * Stores the backend data on the settings store.
     *
     * @param array $data Backend data.
     *
     * @return boolean Creation result.
     */
    private function create_backend($data)
    {
        $setting = \HTTP_BRIDGE\Settings_Store::setting('general');
        $backends = $setting->backends ?: [];

        do_action_ref_array('forms_bridge_before_template_backend', [
            $data,
            $this->name,
            $this,
        ]);

        $setting->backends = array_merge($backends, [$data]);
        $setting->flush();

        $is_valid = $this->backend_exists($data['name']);

        if (!$is_valid) {
            return;
        }

        do_action('forms_bridge_template_backend', $data, $this->name, $this);

        return true;
    }

    /**
     * Removes backend from the settings store by name.
     *
     * @param string $name Backend name.
     */
    private function remove_backend($name)
    {
        $setting = \HTTP_BRIDGE\Settings_Store::setting('general');
        $backends = $setting->backends ?: [];

        $setting->backends = array_filter($backends, static function (
            $backend
        ) use ($name) {
            return $backend['name'] !== $name;
        });
    }

    /**
     * Checks if a bridge with the given name exists on the settings store.
     *
     * @param string $form_id Internal ID of the form.
     * @param string $integration Slug of the target integration.
     *
     * @return boolean
     */
    private function bridge_exists($name)
    {
        $bridges = Forms_Bridge::setting($this->api)->bridges ?: [];
        return array_search($name, array_column($bridges, 'name')) !== false;
    }

    /**
     * Stores the form bridge data on the settings store.
     *
     * @param array $data Form bridge data.
     *
     * @return boolean Creation result.
     */
    private function create_bridge($data)
    {
        $name_conflict = $this->bridge_exists($data['name']);
        if ($name_conflict) {
            return;
        }

        $setting = Forms_Bridge::setting($this->api);
        $bridges = $setting->bridges ?: [];

        do_action_ref_array('forms_bridge_before_template_bridge', [
            $data,
            $this->name,
            $this,
        ]);

        $setting->bridges = array_merge($bridges, [$data]);
        $setting->flush();

        $is_valid = $this->bridge_exists($data['name']);
        if (!$is_valid) {
            return;
        }

        do_action('forms_bridge_template_bridge', $data, $this->name, $this);

        return true;
    }

    /**
     * Removes a bridge from the settings store by name.
     *
     * @param string $name Bridge name.
     */
    private function remove_bridge($name)
    {
        $setting = Forms_Bridge::setting($this->api);
        $bridges = $setting->bridges ?: [];

        $setting->bridges = array_filter($bridges, static function (
            $bridge
        ) use ($name) {
            return $bridge['name'] !== $name;
        });
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
        $credentials = Forms_Bridge::setting($this->api)->credentials ?: [];
        return array_search($name, array_column($credentials, 'name')) !==
            false;
    }

    /**
     * Stores the bridge credential data on the settings store.
     *
     * @param array $data Credential data.
     *
     * @return boolean Creation result.
     */
    private function create_credential($data)
    {
        $name_conflict = $this->credential_exists($data['name']);
        if ($name_conflict) {
            return;
        }

        $setting = Forms_Bridge::setting($this->api);
        $credentials = $setting->credentials ?: [];

        if (!is_array($credentials)) {
            return;
        }

        do_action_ref_array('forms_bridge_before_template_credential', [
            $data,
            $this->name,
            $this,
        ]);

        $setting->credentials = array_merge($credentials, [$data]);
        $setting->flush();

        $is_valid = $this->credential_exists($data['name']);
        if (!$is_valid) {
            return;
        }

        do_action(
            'forms_bridge_template_credential',
            $data,
            $this->name,
            $this
        );

        return true;
    }

    /**
     * Removes a credential from the settings store by name.
     *
     * @param string $name Credential name.
     */
    private function remove_credential($name)
    {
        $setting = Forms_Bridge::setting($this->api);
        $credentials = $setting->credentials ?: [];

        $setting->credentials = array_filter($credentials, static function (
            $credential
        ) use ($name) {
            return $credential['name'] !== $name;
        });
    }
}
