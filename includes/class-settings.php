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
                        'type' => 'object',
                        'properties' => [
                            'form_id' => ['type' => 'string'],
                            'endpoint' => ['type' => 'string'],
                            'ref' => ['type' => 'string'],
                        ],
                    ]
                ]
            ],
            [
                'forms' => [
                    [
                        'form_id' => null,
                        'endpoint' => '/api/crm-lead',
                        'ref' => null
                    ]
                ]
            ]
        );

        $this->register_setting(
            'rpc-api',
            [
                'endpoint' => [
                    'type' => 'string',
                ],
                'user' => [
                    'type' => 'string',
                ],
                'password' => [
                    'type' => 'string',
                ],
                'database' => [
                    'type' => 'string',
                ],
                'forms' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'form_id' => ['type' => 'string'],
                            'model' => ['type' => 'string'],
                            'ref' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            [
                'endpoint' => '/jsonrpc',
                'user' => 'admin',
                'password' => 'admin',
                'database' => 'erp',
                'forms' => [
                    [
                        'form_id' => 0,
                        'model' => 'crm.lead',
                        'ref' => null,
                    ],
                ],
            ],
        );
    }

    protected function input_render($setting, $field, $value)
    {
        if (preg_match('/^forms.*form_id$/', $field)) {
            return $this->render_forms_dropdown($setting, $field, $value);
		} elseif (preg_match('/password$/', $field)) {
			return $this->password_input_render($setting, $field, $value);
		}

        return parent::input_render($setting, $field, $value);
    }

    private function render_forms_dropdown($setting, $field, $value)
    {
        $setting_name = $this->setting_name($setting);
        $forms = self::get_forms();
        $options = array_merge(['<option value=""></option>'], array_map(function ($form) use ($value) {
            $selected = $form->id == $value ? 'selected' : '';
            return "<option value='{$form->id}' {$selected}>{$form->title}</option>";
        }, $forms));
        return "<select name='{$setting_name}[{$field}]'>" . implode('', $options) . '</select>';
    }

	private function password_input_render($setting, $field, $value)
	{
		$setting_name = $this->setting_name($setting);
		return "<input type='password' name='{$setting_name}[{$field}]' value='{$value}' />";
	}
}
