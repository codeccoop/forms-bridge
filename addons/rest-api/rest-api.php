<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-rest-form-hook.php';
require_once 'class-rest-form-hook-template.php';

/**
 * REST API Addon class.
 */
class Rest_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'REST API';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'rest-api';

    /**
     * Handles the addom's custom form hook class.
     *
     * @var string
     */
    protected static $hook_class = '\FORMS_BRIDGE\Rest_Form_Hook';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            [
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'backend' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'method' => [
                                'type' => 'string',
                                'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                            ],
                            'pipes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
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
                                    'required' => ['from', 'to', 'cast'],
                                ],
                            ],
                            'template' => ['type' => 'string'],
                        ],
                        'required' => [
                            'name',
                            'backend',
                            'form_id',
                            'endpoint',
                            'method',
                            'pipes',
                        ],
                    ],
                ],
            ],
            [
                'form_hooks' => [],
            ],
        ];
    }

    /**
     * Apply settings' data validations before db updates.
     *
     * @param array $data Setting data.
     *
     * @return array Validated setting data.
     */
    protected static function validate_setting($data, $setting)
    {
        $data['form_hooks'] = self::validate_form_hooks(
            $data['form_hooks'],
            Forms_Bridge::setting('general')->backends ?: []
        );

        return $data;
    }

    /**
     * Validate form hooks settings. Filters form hooks with inconsistencies with
     * the existing backends.
     *
     * @param array $form_hooks Array with form hooks configurations.
     * @param array $backends Array with backends data.
     *
     * @return array Array with valid form hook configurations.
     */
    private static function validate_form_hooks($form_hooks, $backends)
    {
        if (!wp_is_numeric_array($form_hooks)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $tempaltes = array_map(function ($template) {
            return $template['name'];
        }, apply_filters('forms_bridge_templates', [], 'rest-api'));

        $valid_hooks = [];
        for ($i = 0; $i < count($form_hooks); $i++) {
            $hook = $form_hooks[$i];

            // Valid only if backend, form id and template exists
            $is_valid =
                array_reduce(
                    $backends,
                    static function ($is_valid, $backend) use ($hook) {
                        return $hook['backend'] === $backend['name'] ||
                            $is_valid;
                    },
                    false
                ) &&
                in_array($hook['form_id'], $_ids) &&
                (empty($hook['template']) ||
                    empty($tempaltes) ||
                    in_array($hook['template'], $tempaltes));

            if ($is_valid) {
                $valid_hooks[] = $hook;
            }
        }

        return $valid_hooks;
    }
}

Rest_Addon::setup();
