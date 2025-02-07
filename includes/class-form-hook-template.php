<?php

namespace FORMS_BRIDGE;

use Exception;

if (!defined('ABSPATH')) {
    exit();
}

class Form_Hook_Template_Exception extends Exception
{
    private $string_code;

    public function __construct($string_code, $message)
    {
        $this->string_code = $string_code;
        parent::__construct($message);
    }

    public function getStringCode()
    {
        return $this->string_code;
    }
}

class Form_Hook_Template
{
    private $api;
    private $file;
    private $name;
    private $title;
    private $config;

    private static $default = [
        'fields' => [
            [
                'ref' => '#form',
                'name' => 'title',
                'label' => 'Form title',
                'type' => 'string',
                'required' => true,
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
                'ref' => '#hook',
                'name' => 'method',
                'label' => 'Method',
                'type' => 'string',
                'required' => true,
                'default' => 'POST',
            ],
        ],
        'hook' => [
            'name' => '',
            'backend' => '',
            'form_id' => '',
            'endpoint' => '',
            'method' => 'POST',
        ],
    ];

    private static $schema = [
        'title' => ['type' => 'string'],
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
                    'ref' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'required' => ['type' => 'boolean'],
                    'const' => [],
                    'default' => [],
                    'options' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'uniqueItems' => true,
                    ],
                    'enum' => [
                        'type' => 'array',
                        'items' => [],
                        'uniqueItems' => true,
                    ],
                    'attributes' => ['type' => 'object'],
                ],
                'required' => ['ref', 'name', 'label', 'type'],
                'additionalProperties' => false,
            ],
        ],
        'hook' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'backend' => ['type' => 'string'],
                'form_id' => ['type' => 'string'],
                'endpoint' => ['type' => 'string'],
                'pipes' => [
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
                                    'float',
                                    'json',
                                    'csv',
                                    'concat',
                                    'null',
                                ],
                            ],
                        ],
                        'additionalProperties' => false,
                        'required' => ['from', 'to', 'cast'],
                    ],
                ],
            ],
            'required' => ['name', 'backend', 'form_id', 'endpoint', 'pipes'],
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
                            'type' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [],
                                ],
                            ],
                            'is_file' => ['type' => 'boolean'],
                            'is_multi' => ['type' => 'boolean'],
                        ],
                        'required' => ['label', 'name', 'type'],
                    ],
                    'minItems' => 1,
                ],
            ],
            'required' => ['title', 'fields'],
            'additionalProperties' => false,
        ],
    ];

    private static function validate_config($config)
    {
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => static::$schema,
            'required' => ['title', 'integrations', 'fields', 'form', 'hook'],
        ];

        $config = self::with_defaults($config, static::$default);

        $is_valid = rest_validate_value_from_schema($config, $schema);
        if (is_wp_error($is_valid)) {
            return $is_valid;
        }

        return rest_sanitize_value_from_schema($config, $schema);
    }

    /**
     * Apply defaults to the given config.
     *
     * @param array $config Input config.
     *
     * @return array Config fullfilled with defaults.
     */
    private static function with_defaults($config)
    {
        $schema = [
            'type' => 'object',
            'properties' => static::$schema,
        ];

        // merge template defaults with common defaults
        $default = self::merge_array(
            static::$default,
            [
                'fields' => [
                    [
                        'ref' => '#form',
                        'name' => 'title',
                        'label' => __('Form title', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#hook',
                        'name' => 'name',
                        'label' => __('Hook name', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'hook' => [
                    'name' => '',
                    'pipes' => [],
                ],
                'form' => [
                    'title' => '',
                    'fields' => [],
                ],
            ],
            $schema
        );

        // merge config with defaults
        $config = self::merge_array($config, $default, $schema);

        // add integrations to config
        return array_merge($config, [
            'integrations' => array_keys(Integration::integrations()),
        ]);
    }

    /**
     * Merge numeric arrays with default values and returns the union of
     * the two arrays without repetitions.
     *
     * @param array $list Numeric array with values.
     * @param array $default Default values for the list.
     *
     * @return array
     */
    private static function merge_list($list, $default)
    {
        return array_values(array_unique(array_merge($list, $default)));
    }

    /**
     * Merge collection of arrays with its defaults, apply defaults to
     * each item of the collection and return the collection without
     * repetitions.
     *
     * @param array $collection Input collection of arrays.
     * @param array $default Default values for the collection.
     * @param array $schema JSON schema of the collection.
     *
     * @return array
     */
    private static function merge_collection($collection, $default, $schema)
    {
        if (!in_array($schema['type'], ['array', 'object'])) {
            return self::merge_list($collection, $default);
        }

        if ($schema['type'] === 'object') {
            // TODO: Not harcoded column name
            foreach ($default as $item) {
                if (
                    !in_array($item['name'], array_column($collection, 'name'))
                ) {
                    $collection[] = $item;
                }
            }

            $items = [];
            foreach ($collection as $item) {
                $default_item = array_filter($default, function ($value) use (
                    $item
                ) {
                    return $value['name'] === $item['name'];
                });

                if (!empty($default_item)) {
                    $items[] = self::merge_array(
                        $item,
                        $default_item[0],
                        $schema
                    );
                } else {
                    $items[] = $item;
                }
            }

            $collection = $items;
        } elseif ($schema['type'] === 'array') {
            // TODO: Handle matrix case
        }

        return $collection;
    }

    private static function merge_array($array, $default, $schema)
    {
        foreach ($default as $key => $default_value) {
            if (empty($array[$key])) {
                $array[$key] = $default_value;
            } else {
                $value = $array[$key];
                if ($schema['properties'][$key]['type'] === 'object') {
                    if (!is_array($value) || wp_is_numeric_array($value)) {
                        $array[$key] = $default_value;
                    } else {
                        $array[$key] = self::merge_array(
                            $value,
                            $default_value,
                            $schema['properties'][$key]
                        );
                    }
                } elseif ($schema['properties'][$key]['type'] === 'array') {
                    if (!wp_is_numeric_array($value)) {
                        $array[$key] = $default_value;
                    } else {
                        $array[$key] = self::merge_collection(
                            $value,
                            $default_value,
                            $schema['properties'][$key]['items']
                        );
                    }
                }
            }
        }

        foreach ($array as $key => $value) {
            if (!isset($schema['properties'][$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function __construct($file, $config)
    {
        $this->api = 'rest-api';
        $this->file = $file;
        $this->name = pathinfo(basename($file))['filename'];
        $this->config = self::validate_config($config);

        add_filter(
            'forms_bridge_templates',
            function ($templates, $api = 'rest-api') {
                if (!wp_is_numeric_array($templates)) {
                    $templates = [];
                }

                if ($api && $api !== $this->api) {
                    return $templates;
                }

                if (is_wp_error($this->config)) {
                    return $templates;
                }

                return array_merge($templates, [
                    [
                        'name' => $this->name,
                        'title' => $this->config['title'],
                    ],
                ]);
            },
            10,
            2
        );

        add_action('forms_bridge_use_template', function ($data) {
            if ($data['name'] === $this->name) {
                $this->use_template($data['fields'], $data['integration']);
            }
        });
    }

    public function __get($name)
    {
        switch ($name) {
            case 'name':
                return $this->name;
            case 'file':
                return $this->file;
            case 'config':
                return $this->config;
        }

        return $this->config[$name] ?? null;
    }

    public function to_json()
    {
        return [
            'fields' => $this->config['fields'],
            'title' => $this->config['title'],
            'name' => $this->name,
        ];
    }

    private function use_template($fields, $integration)
    {
        $template = $this->config;

        if (is_wp_error($template)) {
            return;
        }

        $all_fields = self::merge_collection(
            $fields,
            $template['fields'],
            static::$schema['fields']['items']
        );

        if (count($all_fields) !== count($fields)) {
            throw new Form_Hook_Template_Exception(
                'invalid_fields',
                __('Invalid number of template fields', 'forms-bridge')
            );
        }

        $data = $template;
        foreach ($fields as $field) {
            $is_valid = rest_validate_value_from_schema($field, [
                'type' => 'object',
                'properties' => [
                    'ref' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'value' => [],
                ],
                'required' => ['ref', 'name', 'value'],
            ]);

            if (!$is_valid || ($field['ref'][0] ?? '') !== '#') {
                throw new Form_Hook_Template_Exception(
                    'invalid_field',
                    /* translators: %s: Field name */
                    sprintf(
                        __(
                            'Field `%s` does not match the schema',
                            'forms-bridge'
                        ),
                        $field['name'] ?? ''
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
                    throw new Form_Hook_Template_Exception(
                        'invalid_template',
                        sprintf(
                            __(
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
            }

            $keys = explode('/', substr($field['ref'], 1));
            $leaf = &$data;
            foreach ($keys as $key) {
                $clean_key = str_replace('[]', '', $key);
                if (!isset($leaf[$clean_key])) {
                    throw new Form_Hook_Template_Exception(
                        'invalid_ref',
                        /* translators: %s: ref value */
                        sprintf(
                            __(
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

        $integration_instance = Integration::integrations()[$integration];
        $form_id = $integration_instance->create_form($data['form']);

        if (!$form_id) {
            throw new Form_Hook_Template_Exception(
                'form_creation_error',
                __('Forms bridge can\'t create the form', 'forms-bridge')
            );
        }

        $result = $this->create_hook(
            array_merge($data['hook'], [
                'form_id' => $integration . ':' . $form_id,
            ])
        );

        if (!$result) {
            wp_delete_post($form_id);
            throw new Form_Hook_Template_Exception(
                'hook_creation_error',
                __('Forms bridge can\'t create the form hook', 'forms-bridge')
            );
        }
    }

    private function create_hook($data)
    {
        $setting = Forms_Bridge::setting($this->api);
        $setting_data = $setting->data();

        $name_conflict =
            array_search(
                $data['name'],
                array_column($setting_data['form_hooks'], 'name')
            ) !== false;
        if ($name_conflict) {
            return;
        }

        $setting_data['form_hooks'][] = $data;
        $setting_data = apply_filters(
            'wpct_validate_setting',
            $setting_data,
            $setting
        );

        $is_valid =
            array_search(
                $data['name'],
                array_column($setting_data['form_hooks'], 'name')
            ) !== false;
        if (!$is_valid) {
            return;
        }

        $setting->form_hooks = $setting_data['form_hooks'];
        return true;
    }
}
