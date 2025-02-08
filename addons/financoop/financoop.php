<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-financoop-form-hook.php';
require_once 'class-financoop-form-hook-template.php';

/**
 * FinanCoop Addon class.
 */
class Finan_Coop_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'FinanCoop';

    /**
     * Handles the addon slug.
     *
     * @var string
     */
    protected static $slug = 'financoop';

    /**
     * Handles the addom's custom form hook class.
     *
     * @var string
     */
    protected static $hook_class = '\FORMS_BRIDGE\Finan_Coop_Form_Hook';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$slug,
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
        }, apply_filters('forms_bridge_templates', [], 'financoop'));

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

Finan_Coop_Addon::setup();
