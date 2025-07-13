<?php

namespace FORMS_BRIDGE;

use Exception;
use Error;
use FBAPI;
use WP_Error;
use HTTP_BRIDGE\Settings_Store as Http_Store;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form Bridge template class. Handles the data validation
 * and the use of template as form bridge creation strategy.
 */
class Form_Bridge_Template
{
    public const post_type = 'fb-bridge-template';

    /**
     * Handles the template id;
     *
     * @var string
     */
    protected $id;

    /**
     * Handles the template addon name.
     *
     * @var string
     */
    protected $addon;

    /**
     * Handles the template data.
     *
     * @var array
     */
    private $data;

    private static function child_schema_to_template($schema)
    {
        foreach ($schema['properties'] as &$prop_schema) {
            if ($prop_schema['type'] === 'string') {
                $prop_schema['default'] = '';
                unset($prop_schema['minLength']);
                unset($prop_schema['pattern']);
                unset($prop_schema['format']);
            } elseif ($prop_schema['type'] === 'array') {
                $prop_schema['default'] = [];
                unset($prop_schema['minItems']);
            }
        }

        if (!isset($schema['default'])) {
            $schema['default'] = [];
        }

        return $schema;
    }

    /**
     * Handles the common template data json schema. The schema is common for all
     * Form_Bridge_Templates.
     *
     * @param string $addon Addon name.
     *
     * @return array
     */
    public static function schema($addon = null)
    {
        $backend_schema = FBAPI::get_backend_schema();
        $bridge_schema = FBAPI::get_bridge_schema($addon);
        $credential_schema = FBAPI::get_credential_schema($addon);

        $backend_schema['properties']['authentication']['required'] = [];

        return apply_filters(
            'forms_bridge_template_schema',
            [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'form-bridge-template-schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'description' => __(
                            'Internal and unique name of the template',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'title' => [
                        'description' => __(
                            'Public title of the template',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'description' => [
                        'description' => __(
                            'Short description of the template purpose',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'default' => '',
                    ],
                    'integrations' => [
                        'description' => __(
                            'Template\'s supported integrations',
                            'forms-bridge'
                        ),
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'uniqueItems' => true,
                        'minItems' => 1,
                    ],
                    'fields' => [
                        'description' => __(
                            'Template fields to be filled by the user',
                            'forms-bridge'
                        ),
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'ref' => [
                                    'type' => 'string',
                                    'pattern' => '^#.+',
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
                                    'enum' => [
                                        'string',
                                        'number',
                                        'options',
                                        'boolean',
                                    ],
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
                                    'anyOf' => [
                                        [
                                            'description' => __(
                                                'List of field options',
                                                'forms-bridge'
                                            ),
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'label' => [
                                                        'type' => 'string',
                                                    ],
                                                    'value' => [
                                                        'type' => 'string',
                                                    ],
                                                ],
                                                'required' => [
                                                    'value',
                                                    'label',
                                                ],
                                            ],
                                            'uniqueItems' => true,
                                        ],
                                        [
                                            'description' => __(
                                                'How to get options from the addon API',
                                                'forms-bridge'
                                            ),
                                            'type' => 'object',
                                            'properties' => [
                                                'endpoint' => [
                                                    'description' => __(
                                                        'Endpoint to get values from',
                                                        'forms-bridge'
                                                    ),
                                                    'type' => 'string',
                                                ],
                                                'finger' => [
                                                    'description' => __(
                                                        'Fingers to get values from the endpoint response',
                                                        'forms-bridge'
                                                    ),
                                                    'oneOf' => [
                                                        [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'value' => [
                                                                    'type' =>
                                                                        'string',
                                                                ],
                                                                'label' => [
                                                                    'type' =>
                                                                        'string',
                                                                ],
                                                            ],
                                                            'required' => [
                                                                'value',
                                                            ],
                                                        ],
                                                        [
                                                            'type' => 'string',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'required' => [
                                                'endpoint',
                                                'finger',
                                            ],
                                        ],
                                    ],
                                ],
                                'enum' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => [
                                            'integer',
                                            'number',
                                            'string',
                                        ],
                                    ],
                                    'uniqueItems' => true,
                                ],
                                'min' => ['type' => 'integer'],
                                'max' => ['type' => 'integer'],
                                'multiple' => ['type' => 'boolean'],
                            ],
                            'required' => ['ref', 'name', 'type'],
                            'additionalProperties' => true,
                        ],
                    ],
                    'form' => [
                        'description' => __(
                            'Form title and fields settings',
                            'forms-bridge'
                        ),
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'default' => '',
                            ],
                            'fields' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'minLength' => 1,
                                        ],
                                        'label' => ['type' => 'string'],
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
                                                    'label' => [
                                                        'type' => 'string',
                                                    ],
                                                ],
                                                'required' => [
                                                    'value',
                                                    'label',
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
                    'bridge' => self::child_schema_to_template($bridge_schema),
                    'backend' => self::child_schema_to_template(
                        $backend_schema
                    ),
                    'credential' => self::child_schema_to_template(
                        $credential_schema
                    ),
                ],
                'additionalProperties' => false,
                'required' => [
                    'name',
                    'title',
                    'integrations',
                    'fields',
                    'form',
                    'backend',
                    'bridge',
                ],
            ],
            $addon
        );
    }

    /**
     * Template default data getter.
     *
     * @param string $addon Template addon namespace.
     * @param array $schema Template schema.
     *
     * @return array
     */
    protected static function defaults($addon = null, $schema = null)
    {
        if (!is_array($schema)) {
            $schema = static::schema($addon);
        }

        $integrations = [];
        foreach (Integration::integrations() as $integration) {
            if ($integration !== 'woo') {
                $integrations[] = $integration::name;
            }
        }

        return apply_filters(
            'forms_bridge_template_defaults',
            [
                'integrations' => $integrations,
                'fields' => [
                    [
                        'ref' => '#form',
                        'name' => 'id',
                        'label' => __('Form ID', 'forms-bridge'),
                        'type' => 'string',
                    ],
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
                        'label' => __('Name', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend',
                        'name' => 'base_url',
                        'label' => __('Base URL', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                        'default' => 'https://',
                        'format' => 'uri',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'name',
                        'label' => __('Bridge name', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                        'minLength' => 1,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'endpoint',
                        'label' => __('Endpoint', 'forms-bridge'),
                        'type' => 'string',
                        'required' => true,
                        'default' => '',
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'label' => __('Method', 'forms-bridge'),
                        'type' => 'options',
                        'options' => [
                            [
                                'label' => 'GET',
                                'value' => 'GET',
                            ],
                            [
                                'label' => 'POST',
                                'value' => 'POST',
                            ],
                            [
                                'label' => 'PUT',
                                'value' => 'PUT',
                            ],
                            [
                                'label' => 'PATCH',
                                'value' => 'PATCH',
                            ],
                            [
                                'label' => 'DELETE',
                                'value' => 'DELETE',
                            ],
                        ],
                        'required' => true,
                        'default' => 'POST',
                    ],
                ],
                'bridge' => [
                    'endpoint' => '',
                    'method' => 'POST',
                ],
                'backend' => [
                    'headers' => [
                        [
                            'name' => 'Content-Type',
                            'value' => 'application/json',
                        ],
                    ],
                ],
                'form' => [],
            ],
            $addon,
            $schema
        );
    }

    private static function data_from_post($post)
    {
        return [
            'name' => $post->post_name,
            'title' => $post->post_title,
            'description' => $post->post_excerpt,
            'fields' =>
                (array) (get_post_meta($post->ID, '_template-fields', true) ?:
                []),
            'form' =>
                (array) (get_post_meta($post->ID, '_template-form', true) ?:
                []),
            'bridge' =>
                (array) (get_post_meta($post->ID, '_template-bridge', true) ?:
                []),
            'backend' =>
                (array) (get_post_meta($post->ID, '_template-backend', true) ?:
                []),
            'credential' =>
                (array) (get_post_meta(
                    $post->ID,
                    '_template-credential',
                    true
                ) ?:
                []),
        ];
    }

    /**
     * Store template attribute values, validates data and binds the
     * instance to custom forms bridge template hooks.
     *
     * @param string $name Template name.
     * @param array $data Template data.
     */
    public function __construct($data, $addon)
    {
        if ($data instanceof WP_Post) {
            $data = self::data_from_post($data);
        }

        $this->addon = $addon;
        $this->data = $this->validate($data);

        if ($this->is_valid) {
            $this->id = $this->addon . '-' . $data['name'];
        }
    }

    /**
     * Magic method to proxy private template attributes and data.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->id;
            case 'addon':
                return $this->addon;
            case 'data':
                return $this->data;
            case 'is_valid':
                return !is_wp_error($this->data) &&
                    Addon::addon($this->addon) !== null;
            default:
                if (!$this->is_valid) {
                    return;
                }

                return $this->data[$name] ?? null;
        }
    }

    /**
     * Validates input data against the template schema.
     *
     * @param array $data Input data.
     *
     * @return array|WP_Error Validated data.
     */
    private function validate($data)
    {
        $schema = static::schema($this->addon);
        $defaults = static::defaults($this->addon, $schema);

        $data = wpct_plugin_merge_object($data, $defaults, $schema);
        return wpct_plugin_sanitize_with_schema($data, $schema);
    }

    /**
     * Decorates the template data for REST responses.
     *
     * @return array REST data.
     */
    public function data()
    {
        if (!$this->is_valid) {
            return;
        }

        return array_merge(
            [
                'id' => $this->id,
                'addon' => $this->addon,
            ],
            $this->data
        );
    }

    private function get_post_id()
    {
        $ids = get_posts([
            'post_type' => self::post_type,
            'name' => $this->name,
            'meta_key' => '_fb-addon',
            'meta_value' => $this->addon,
            'fields' => 'ids',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'update_menu_item_cache' => false,
        ]);

        if (count($ids)) {
            return $ids[0];
        }
    }

    public function save()
    {
        if (!$this->is_valid) {
            return $this->data;
        }

        $post_arr = [
            'post_type' => self::post_type,
            'post_name' => $this->name,
            'post_title' => $this->title,
            'post_excerpt' => $this->description,
        ];

        $post_id = $this->get_post_id();
        if ($post_id) {
            $post_arr['ID'] = $post_id;
            $post_id = wp_update_post($post_arr, true);
        } else {
            $post_id = wp_insert_post($post_arr, true);
        }

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_template-fields', $this->fields);
            update_post_meta($post_id, '_template-form', $this->form);
            update_post_meta($post_id, '_template-bridge', $this->bridge);
            update_post_meta($post_id, '_template-backend', $this->backend);
            update_post_meta(
                $post_id,
                '_template-credential',
                $this->credential
            );
        }

        return $post_id;
    }

    public function reset()
    {
        $post_id = $this->get_post_id();

        if (!$post_id) {
            return false;
        }

        return wp_delete_post($post_id, true) instanceof WP_Post;
    }

    /**
     * Applies the input fields with the template's data to
     * create a form and bind it with a bridge.
     *
     * @param array $fields User input fields data.
     * @param string $integration Target integration.
     */
    public function use($fields, $integration)
    {
        if (!$this->is_valid) {
            return new WP_Error(
                'invalid_template',
                __('The target template is invalid', 'forms-bridge')
            );
        }

        $template = $this->data;
        $schema = static::schema($this->addon);

        // Add constants to the user fields
        foreach ($template['fields'] as $field) {
            if (!empty($field['value'])) {
                $fields[] = $field;
            }
        }

        $all_fields = wpct_plugin_merge_collection(
            $fields,
            $template['fields'],
            $schema['properties']['fields']['items']
        );

        $requireds = array_filter($all_fields, static function ($field) {
            return ($field['required'] ?? false) && !isset($field['value']);
        });

        if (count($requireds) || count($fields) > count($all_fields)) {
            return new WP_Error(
                'invalid_fields',
                __('Invalid template fields', 'forms-bridge')
            );
        }

        $data = $template;
        foreach ($fields as $field) {
            $is_required = $field['required'] ?? false;

            $field_schema = $schema['properties']['fields']['items'];
            $field_schema['required'] = ['ref', 'name'];

            if ($is_required) {
                $field_schema['required'][] = 'value';
            }

            $field = wpct_plugin_sanitize_with_schema($field, $field_schema);

            if (is_wp_error($field)) {
                return new WP_Error(
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

            // Inherit form field structure if field ref points to form fields
            if ($field['ref'] === '#form/fields[]') {
                $index = array_search(
                    $field['name'],
                    array_column($template['form']['fields'], 'name')
                );

                if ($index === false) {
                    return new WP_Error(
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
                    return new WP_Error(
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
            } elseif (isset($field['value'])) {
                $leaf[$field['name']] = $field['value'];
            }
        }

        $data = apply_filters(
            'forms_bridge_template_data',
            $data,
            $this->id,
            $this
        );

        if (is_wp_error($data)) {
            return $data;
        } elseif (empty($data)) {
            return new WP_Error(
                'template_creation_error',
                __('There is a problem with the template data', 'forms-bridge')
            );
        }

        if ($integration === 'woo') {
            $data['form']['id'] = 1;
        }

        $integration_instance = Integration::integration($integration);

        try {
            $create_form = !$this->form_exists(
                $data['form']['id'] ?? null,
                $integration
            );

            if ($create_form) {
                do_action_ref_array('forms_bridge_before_template_form', [
                    $data['form'],
                    $this->name,
                    $this,
                ]);

                $form_id = $integration_instance->create_form($data['form']);

                if (!$form_id) {
                    return new WP_Error(
                        'form_creation_error',
                        __(
                            'Forms bridge can\'t create the form',
                            'forms-bridge'
                        ),
                        ['status' => 400, 'data' => $data['form']]
                    );
                }

                $data['form']['id'] = $form_id;

                do_action(
                    'forms_bridge_template_form',
                    $data['form'],
                    $this->id,
                    $this
                );
            }

            $data['bridge']['form_id'] =
                $integration . ':' . $data['form']['id'];

            $create_backend = !$this->backend_exists($data['backend']['name']);
            if ($create_backend) {
                $result = $this->create_backend($data['backend']);

                if (!$result) {
                    if ($create_form) {
                        $integration_instance->remove_form($data['form']['id']);
                    }

                    return new WP_Error(
                        'backend_creation_error',
                        __(
                            'Forms bridge can\'t create the backend',
                            'forms-bridge',
                            ['status' => 400, 'data' => $data['backend']]
                        )
                    );
                }
            }

            $data['bridge']['backend'] = $data['backend']['name'];

            $create_credential = false;
            if (!empty($data['credential']['name'])) {
                $create_credential = !$this->credential_exists(
                    $data['credential']['name']
                );

                if ($create_credential) {
                    $result = $this->create_credential($data['credential']);

                    if (!$result) {
                        if ($create_form) {
                            $integration_instance->remove_form(
                                $data['form']['id']
                            );
                        }

                        if ($create_backend) {
                            $this->remove_backend($data['backend']['name']);
                        }

                        return new WP_Error(
                            'credential_creation_error',
                            __(
                                'Forms bridge can\'t create the credential',
                                'forms-bridge',
                                ['status' => 400, 'data' => $data['credential']]
                            )
                        );
                    }
                }

                $data['bridge']['credential'] = $data['credential']['name'];
            }

            $bridge_created = $this->create_bridge($data['bridge']);

            if (!$bridge_created) {
                if ($create_form) {
                    $integration_instance->remove_form($data['form_id']);
                }

                if ($create_backend) {
                    $this->remove_backend($data['backend']['name']);
                }

                if ($create_credential) {
                    $this->remove_credential($data['credential']['name']);
                }

                return new WP_Error(
                    'bridge_creation_error',
                    __(
                        'Forms bridge can\'t create the form bridge',
                        'forms-bridge',
                        ['status' => 400, 'data' => $data['bridge']]
                    )
                );
            }
        } catch (Error | Exception $e) {
            if (isset($create_form) && $create_form) {
                $integration_instance->remove_form($data['form']['id']);
            }

            if (isset($create_backend) && $create_backend) {
                $this->remove_backend($data['backend']['name']);
            }

            if (isset($create_credential) && $create_credential) {
                $this->remove_credential($data['credential']['name']);
            }

            if (isset($bridge_created) && $bridge_created) {
                $this->remove_bridge($data['bridge']['name']);
            }

            return new WP_Error('internal_server_error', $e->getMessage(), [
                'status' => 500,
            ]);
        }

        return true;
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
        $form = FBAPI::get_form_by_id($form_id, $integration);
        return !empty($form['id']);
    }

    /**
     * Checks if a backend with the given name exists on the settings store.
     *
     * @param string $name Backend name.
     *
     * @return boolean
     */
    final protected function backend_exists($name)
    {
        $backends = Http_Store::setting('general')->backends ?: [];
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
        $setting = Http_Store::setting('general');
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

        do_action('forms_bridge_template_backend', $data, $this->id, $this);
        return true;
    }

    /**
     * Removes backend from the settings store by name.
     *
     * @param string $name Backend name.
     */
    private function remove_backend($name)
    {
        $setting = Http_Store::setting('general');
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
        $bridges = Forms_Bridge::setting($this->addon)->bridges ?: [];
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

        $setting = Forms_Bridge::setting($this->addon);
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

        do_action('forms_bridge_template_bridge', $data, $this->id, $this);
        return true;
    }

    /**
     * Removes a bridge from the settings store by name.
     *
     * @param string $name Bridge name.
     */
    private function remove_bridge($name)
    {
        $setting = Forms_Bridge::setting($this->addon);
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
        $credentials = Forms_Bridge::setting($this->addon)->credentials ?: [];
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
        // $name_conflict = $this->credential_exists($data['name']);
        // if ($name_conflict) {
        //     return;
        // }

        $setting = Forms_Bridge::setting($this->addon);
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

        do_action('forms_bridge_template_credential', $data, $this->id, $this);
        return true;
    }

    /**
     * Removes a credential from the settings store by name.
     *
     * @param string $name Credential name.
     */
    private function remove_credential($name)
    {
        $setting = Forms_Bridge::setting($this->addon);
        $credentials = $setting->credentials ?: [];

        $setting->credentials = array_filter($credentials, static function (
            $credential
        ) use ($name) {
            return $credential['name'] !== $name;
        });
    }
}
