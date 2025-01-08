<?php

namespace FORMS_BRIDGE;

use function WPCT_ABSTRACT\is_list;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'vendor/autoload.php';

require_once 'class-gs-store.php';
require_once 'class-gs-client.php';
require_once 'class-gs-rest-controller.php';
require_once 'class-gs-ajax-controller.php';
require_once 'class-gs-service.php';
require_once 'class-gs-form-hook.php';

class Google_Sheets_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Google Sheets';

    /**
     * Handles the addon slug.
     *
     * @var string
     */
    protected static $slug = 'google-sheets-api';

    /**
     * Handles the addom's custom form hook class.
     *
     * @var string
     */
    protected static $hook_class = '\FORMS_BRIDGE\Google_Sheets_Form_Hook';

    /**
     * Addon constructor. Inherits from the abstract addon and initialize interceptos
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);

        self::interceptors();
        self::wp_hooks();
        self::custom_hooks();
    }

    /**
     * Addon interceptors
     */
    private function interceptors()
    {
        // Intercepts submission payload and catch google sheets hooks
        add_filter(
            'forms_bridge_payload',
            static function ($payload, $uploads, $hook) {
                return self::payload_interceptor($payload, $hook);
            },
            9,
            3
        );

        // Discard attachments for google sheets submissions
        add_filter(
            'forms_bridge_attachments',
            static function ($attachments, $uploads, $hook) {
                if ($hook->api === self::$slug) {
                    return [];
                }

                return $attachments;
            },
            90,
            3
        );
    }

    /**
     * Binds plugin custom hooks.
     */
    private static function custom_hooks()
    {
        // Patch authorized state on the setting default value
        add_filter(
            'wpct_setting_default',
            static function ($value, $name) {
                if ($name !== self::setting_name()) {
                    return $value;
                }

                return array_merge($value, [
                    'authorized' => Google_Sheets_Service::is_authorized(),
                ]);
            },
            10,
            2
        );
    }

    /**
     * Binds wp standard hooks.
     */
    private static function wp_hooks()
    {
        // Patch authorized state on the setting value
        add_filter('option_' . self::setting_name(), static function ($value) {
            $value['authorized'] = Google_Sheets_Service::is_authorized();
            return $value;
        });

        // Unset authorized from setting data before updates
        add_filter(
            'pre_update_option',
            static function ($value, $option) {
                if ($option === self::setting_name()) {
                    unset($value['authorized']);
                }

                return $value;
            },
            9,
            2
        );
    }

    /**
     * Registers the setting and its fields.
     *
     * @param Settings $settings Plugin settings instance.
     */
    protected static function register_setting($settings)
    {
        $settings->register_setting(
            self::$slug,
            [
                'form_hooks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'form_id' => ['type' => 'string'],
                            'spreadsheet' => ['type' => 'string'],
                            'tab' => ['type' => 'string'],
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
                                                'null',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'form_hooks' => [],
            ]
        );
    }

    /**
     * Intercepts the payload, flatten it, write to the spreadsheet and skip submission.
     *
     * @param array $payload Submission payload.
     * @param Form_Hook $form_hook Instance of the current form hook.
     */
    private static function payload_interceptor($payload, $form_hook)
    {
        if (empty($payload)) {
            return $payload;
        }

        if ($form_hook->api !== self::$slug) {
            return $payload;
        }

        $form_data = apply_filters(
            'forms_bridge_form',
            null,
            $form_hook->integration
        );
        if (!$form_data) {
            return;
        }

        $payload = self::flatten_payload($payload);
        $result = Google_Sheets_Service::write_row(
            $form_hook->spreadsheet,
            $form_hook->tab,
            $payload
        );

        if (is_wp_error($result)) {
            do_action(
                'forms_bridge_on_failure',
                $payload,
                [],
                $form_data,
                $result->get_error_data()
            );
        } else {
            do_action(
                'forms_bridge_after_submission',
                $result,
                $payload,
                [],
                $form_data
            );
        }
    }

    /**
     * Sheets are flat, if payload has nested arrays, flattens it and concatenate its keys
     * as field names.
     *
     * @param array $payload Submission payload.
     * @param string $path Prefix to prepend to the field name.
     *
     * @return array Flattened payload.
     */
    private static function flatten_payload($payload, $path = '')
    {
        $flat = [];
        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                $is_flat =
                    is_list($value) &&
                    count(
                        array_filter($value, static function ($d) {
                            return !is_array($d);
                        })
                    ) === count($value);
                if ($is_flat) {
                    $flat[$path . $field] = implode(',', $value);
                } else {
                    $flat = array_merge(
                        $flat,
                        self::flatten_payload($value, $path . $field . '.')
                    );
                }
            } else {
                $flat[$path . $field] = $value;
            }
        }

        return $flat;
    }

    /**
     * Sanitizes the setting value before updates.
     *
     * @param array $value Setting value.
     * @param Setting $setting Setting instance.
     *
     * @return array Sanitized value.
     */
    protected static function sanitize_setting($value, $setting)
    {
        $value['form_hooks'] = self::validate_form_hooks($value['form_hooks']);
        return $value;
    }

    /**
     * Validates setting form hooks data.
     *
     * @param array $form_hooks List with form hooks data.
     *
     * @return array Validated list with form hooks data.
     */
    private static function validate_form_hooks($form_hooks)
    {
        if (!is_list($form_hooks)) {
            return [];
        }

        $_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['_id']]);
            },
            []
        );

        // $spreadsheets = Google_Sheets_Service::get_spreadsheets();
        $valid_hooks = [];
        for ($i = 0; $i < count($form_hooks); $i++) {
            $hook = $form_hooks[$i];

            // Valid only if database and form id exists
            $is_valid = in_array($hook['form_id'], $_ids);

            if ($is_valid) {
                // filter empty pipes
                $hook['pipes'] = isset($hook['pipes'])
                    ? (array) $hook['pipes']
                    : [];
                $hook['pipes'] = array_filter($hook['pipes'], static function (
                    $pipe
                ) {
                    return $pipe['to'] && $pipe['from'] && $pipe['cast'];
                });

                $valid_hooks[] = $hook;
            }
        }

        return $valid_hooks;
    }
}

Google_Sheets_Addon::setup();
