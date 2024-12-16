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

class Google_Sheets extends Addon
{
    protected static $name = 'Google Sheets';
    protected static $slug = 'google-sheets-api';
    protected static $hook_class = '\FORMS_BRIDGE\Google_Sheets_Form_Hook';

    protected function construct(...$args)
    {
        parent::construct(...$args);
        $this->interceptors();
        $this->wp_hooks();
    }

    private function interceptors()
    {
        add_filter(
            'forms_bridge_payload',
            function ($payload, $uploads, $hook) {
                return $this->payload_interceptor($payload, $hook);
            },
            9,
            3
        );

        add_filter(
            'forms_bridge_attachments',
            function ($attachments, $uploads, $hook) {
                if ($hook->api === 'google-sheets-api') {
                    return [];
                }

                return $attachments;
            },
            90,
            3
        );

        add_filter(
            'forms_bridge_form_hooks',
            function ($form_hooks, $form_id) {
                return $this->form_hooks_interceptor($form_hooks, $form_id);
            },
            9,
            2
        );
    }

    private function wp_hooks()
    {
        add_filter('option_forms-bridge_google-sheets-api', function ($value) {
            $value['authorized'] = Google_Sheets_Service::is_authorized();
            return $value;
        });
    }

    protected function register_setting($settings)
    {
        $settings->register_setting(
            'google-sheets-api',
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

    private function payload_interceptor($payload, $form_hook)
    {
        if (empty($payload)) {
            return $payload;
        }

        if ($form_hook->api !== 'google-sheets-api') {
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

    private function form_hooks_interceptor($form_hooks, $form_id)
    {
        if (!is_list($form_hooks)) {
            $form_hooks = [];
        }

        return array_merge($form_hooks, $this->form_hooks($form_id));
    }

    protected function sanitize_setting($value, $setting)
    {
        $value['form_hooks'] = $this->validate_form_hooks($value['form_hooks']);
        return $value;
    }

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
            // array_reduce(
            //     $spreadsheets,
            //     static function ($is_valid, $spreadsheet) use ($hook) {
            //         return $hook['spreadsheet'] === $spreadsheet['id'] || $is_valid;
            //     },
            //     false
            // ) && in_array($hook['form_id'], $form_ids);

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

Google_Sheets::setup();
