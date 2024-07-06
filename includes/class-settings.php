<?php

namespace WPCT_ERP_FORMS;

use WPCT_ABSTRACT\Settings as BaseSettings;

class Settings extends BaseSettings
{
    public static function get_forms()
    {
        global $wpdb;
        if (apply_filters('wpct_is_plugin_active', false, 'contact-form-7/wp-contact-form-7.php')) {
            return $wpdb->get_results("SELECT id, post_title title FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND post_status = 'publish'");
        } elseif (apply_filters('wpct_is_plugin_active', false, 'gravityforms/gravityforms.php')) {
            return $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}gf_form WHERE is_active = 1 AND is_trash = 0");
        }
    }

    public function register()
    {
        $host = parse_url(get_bloginfo('url'))['host'];
        $this->register_setting(
            'general',
            [
                'notification_receiver' => [
                    'type' => 'string',
                ],
                'base_url' => [
                    'type' => 'string',
                ],
                'api_key' => [
                    'type' => 'string',
                ],
            ],
            [
                'notification_receiver' => 'admin@' . $host,
                'base_url' => 'https://erp.' . $host,
                'api_key' => '',
            ],
        );

        $this->register_setting(
            'rest-api',
            [
                'forms' => [
                    'type' => 'array',
                    'items' => [
                        'form_id' => 'number',
                        'endpoint' => 'string',
                        'ref' => 'string',
                    ]
                ]
            ],
            [
                'forms' => [
                    [
                        'endpoint' => '/api/crm-lead',
                        'form_id' => null,
                        'ref' => null
                    ]
                ]
            ]
        );

        $this->register_setting(
            'rpc-api',
            [
                'endpoint' => [
                    'type' => 'string'
                ],
                'user' => [
                    'type' => 'string'
                ],
                'password' => [
                    'type' => 'string'
                ],
                'database' => [
                    'type' => 'string'
                ],
                'model' => [
                    'type' => 'string'
                ],
                'forms' => [
                    'type' => 'array',
                    'items' => [
                        'form_id' => 'string',
                        'ref' => 'string'
                    ]
                ]
            ],
            [
                'endpoint' => '/jsonrpc',
                'user' => 'admin',
                'password' => 'admin',
                'database' => 'default',
                'model' => 'crm.lead',
                'forms' => [
                    [
                        'form_id' => 0,
                        'ref' => null
                    ]
                ]
            ]
        );
    }

    protected function input_render($setting, $field, $value)
    {
        if (preg_match('/^forms.*form_id$/', $field)) {
            return $this->render_forms_dropdown($setting, $field, $value);
        }

        return parent::input_render($setting, $field, $value);
    }

    private function render_forms_dropdown($setting, $field, $value)
    {
        $forms = self::get_forms();
        $options = array_merge(['<option value=""></option>'], array_map(function ($form) use ($value) {
            $selected = $form->id == $value ? 'selected' : '';
            return "<option value='{$form->id}' {$selected}>{$form->title}</option>";
        }, $forms));
        return "<select name='{$setting}[{$field}]'>" . implode('', $options) . '</select>';
    }
}
