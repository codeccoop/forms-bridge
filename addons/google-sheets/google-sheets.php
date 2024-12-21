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
    protected static $name = 'Google Sheets';
    protected static $slug = 'google-sheets-api';
    protected static $hook_class = '\FORMS_BRIDGE\Google_Sheets_Form_Hook';

    protected function construct(...$args)
    {
        parent::construct(...$args);
        $this->interceptors();
        $this->wp_hooks();
        $this->custom_hooks();
    }

    private function interceptors()
    {
        // Intercepts submission payload and catch google sheets hooks
        add_filter(
            'forms_bridge_payload',
            function ($payload, $uploads, $hook) {
                return $this->payload_interceptor($payload, $hook);
            },
            9,
            3
        );

        // Discard attachments for google sheets submissions
        add_filter(
            'forms_bridge_attachments',
            function ($attachments, $uploads, $hook) {
                if ($hook->api === self::$slug) {
                    return [];
                }

                return $attachments;
            },
            90,
            3
        );

        // Add google sheets hooks to plugin hooks
        add_filter(
            'forms_bridge_form_hooks',
            function ($form_hooks, $form_id) {
                return $this->form_hooks_interceptor($form_hooks, $form_id);
            },
            9,
            2
        );
    }

    /**
     * Binds plugin custom hooks.
     */
    private function custom_hooks()
    {
        // Patch authorized state on the setting default value
        add_filter(
            'wpct_setting_default',
            function ($value, $name) {
                if ($name !== Forms_Bridge::slug() . '_' . self::$slug) {
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
    private function wp_hooks()
    {
        // Patch authorized state on the setting value
        $plugin_slug = Forms_Bridge::slug();
        $addon_slug = self::$slug;
        add_filter("option_{$plugin_slug}_{$addon_slug}", function ($value) {
            $value['authorized'] = Google_Sheets_Service::is_authorized();
            return $value;
        });
    }

    /**
     * Registers the setting and its fields.
     *
     * @param Settings $settings Plugin settings instance.
     */
    protected function register_setting($settings)
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
    private function payload_interceptor($payload, $form_hook)
    {
        if (empty($payload)) {
            return $payload;
        }

        if ($form_hook->api !== self::$slug) {
            return $payload;
        }

        $payload = $this->flatten_payload($payload);
        $result = Google_Sheets_Service::write_row(
            $form_hook->spreadsheet,
            $form_hook->tab,
            $payload
        );

        if (is_wp_error($result)) {
            $form_data = apply_filters('forms_bridge_form', null);
            do_action(
                'forms_bridge_on_failure',
                $form_data,
                $payload,
                print_r($result->get_error_data(), true)
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
    private function flatten_payload($payload, $path = '')
    {
        $flat = [];
        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                $payload = array_merge(
                    $payload,
                    $this->flatten_payload($value, $field . '.')
                );
            } else {
                $flat[$path . $field] = $value;
            }
        }

        return $flat;
    }

    /**
     * Adds google sheets hooks to the available hooks.
     *
     * @param array $form_hooks List with available form hooks.
     * @param int|null $form_id Target form ID.
     *
     * @return array List with available form hooks.
     */
    private function form_hooks_interceptor($form_hooks, $form_id)
    {
        if (!is_list($form_hooks)) {
            $form_hooks = [];
        }

        return array_merge($form_hooks, $this->form_hooks($form_id));
    }

    /**
     * Sanitizes the setting value before updates.
     *
     * @param array $value Setting value.
     * @param Setting $setting Setting instance.
     *
     * @return array Sanitized value.
     */
    protected function sanitize_setting($value, $setting)
    {
        $value['form_hooks'] = $this->validate_form_hooks($value['form_hooks']);
        return $value;
    }

    /**
     * Validates setting form hooks data.
     *
     * @param array $form_hooks List with form hooks data.
     *
     * @return array Validated list with form hooks data.
     */
    private function validate_form_hooks($form_hooks)
    {
        if (!is_list($form_hooks)) {
            return [];
        }

        $form_ids = array_reduce(
            apply_filters('forms_bridge_forms', []),
            static function ($form_ids, $form) {
                return array_merge($form_ids, [$form['id']]);
            },
            []
        );

        // $spreadsheets = Google_Sheets_Service::get_spreadsheets();
        $valid_hooks = [];
        for ($i = 0; $i < count($form_hooks); $i++) {
            $hook = $form_hooks[$i];

            // Valid only if database and form id exists
            $is_valid = in_array($hook['form_id'], $form_ids);

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

                $hook['name'] = sanitize_text_field($hook['name']);
                $hook['form_id'] = (int) $hook['form_id'];
                $hook['spreadsheet'] = sanitize_text_field(
                    $hook['spreadsheet']
                );
                $hook['tab'] = sanitize_text_field($hook['tab']);

                $pipes = [];
                foreach ($hook['pipes'] as $pipe) {
                    $pipe['to'] = sanitize_text_field($pipe['to']);
                    $pipe['from'] = sanitize_text_field($pipe['from']);
                    $pipe['cast'] = in_array($pipe['cast'], [
                        'boolean',
                        'string',
                        'integer',
                        'float',
                        'json',
                        'null',
                    ])
                        ? $pipe['cast']
                        : 'string';
                    $pipes[] = $pipe;
                }
                $hook['pipes'] = $pipes;

                $valid_hooks[] = $hook;
            }
        }

        return $valid_hooks;
    }
}

Google_Sheets_Addon::setup();
