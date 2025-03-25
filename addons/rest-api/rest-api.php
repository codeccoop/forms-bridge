<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'class-rest-form-bridge.php';
require_once 'class-rest-form-bridge-template.php';

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
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    protected static $bridge_class = '\FORMS_BRIDGE\Rest_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Rest_Form_Bridge_Template';

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            static::$api,
            self::merge_setting_config([
                'bridges' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'backend' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'method' => [
                                'type' => 'string',
                                'enum' => ['GET', 'POST', 'PUT', 'DELETE'],
                            ],
                        ],
                        'required' => ['backend', 'endpoint', 'method'],
                    ],
                ],
            ]),
            [
                'bridges' => [],
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
        $data['bridges'] = self::validate_bridges(
            $data['bridges'],
            \HTTP_BRIDGE\Settings_Store::setting('general')->backends ?: []
        );

        return $data;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     * @param array $backends Array with backends data.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges, $backends)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $form_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        $backend_names = array_map(function ($backend) {
            return $backend['name'];
        }, $backends);

        $http_methods = static::$bridge_class::allowed_methods;

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            if (empty($bridge['name'])) {
                continue;
            }

            if (in_array($bridge['name'], $uniques)) {
                continue;
            } else {
                $uniques[] = $bridge['name'];
            }

            if (!in_array($bridge['backend'], $backend_names)) {
                $bridge['backend'] = '';
            }

            if (!in_array($bridge['form_id'], $form_ids)) {
                $bridge['form_id'] = '';
            }

            if (!in_array($bridge['method'], $http_methods)) {
                $bridge['method'] = 'POST';
            }

            $bridge['endpoint'] = $bridge['endpoint'] ?? '';

            $bridge['mappers'] = array_values(
                array_filter((array) $bridge['mappers'], function ($pipe) {
                    return !(
                        empty($pipe['from']) ||
                        empty($pipe['to']) ||
                        empty($pipe['cast'])
                    );
                })
            );

            $bridge['workflow'] = array_map(
                'sanitize_text_field',
                (array) $bridge['workflow']
            );

            $is_valid = true;
            unset($bridge['is_valid']);
            foreach ($bridge as $field => $value) {
                if ($field === 'mappers' || $field === 'workflow') {
                    continue;
                }

                $is_valid = $is_valid && !empty($value);
            }

            $bridge['is_valid'] = $is_valid;
            $validated[] = $bridge;
        }

        return $validated;
    }
}

Rest_Addon::setup();
