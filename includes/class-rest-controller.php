<?php

namespace WPCT_ERP_FORMS;

use WP_Error;
use WP_REST_Server;

class REST_Controller
{
    private $namespace = 'wpct';
    private $version = 1;

    private static $settings = ['wpct-erp-forms_general', 'wpct-erp-forms_api'];

    private static function error($code, $message, $status)
    {
        return new WP_Error(
            $code,
            __($message, 'wpct-erp-forms'),
            [
                'status' => $status,
            ],
        );
    }

    public function __construct()
    {
        add_action('rest_api_init', function () {
            $this->init();
        });
    }

    private function init()
    {
        register_rest_route("{$this->namespace}/v{$this->version}", '/erp-forms/forms', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => function () {
                return $this->forms();
            },
            'permission_callback' => function () {
                return $this->permission_callback();
            }
        ]);

        register_rest_route("{$this->namespace}/v{$this->version}", '/erp-forms/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function () {
                    return $this->get_settings();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                }
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => function () {
                    return $this->set_settings();
                },
                'permission_callback' => function () {
                    return $this->permission_callback();
                }
            ]
        ]);
    }

    private function forms()
    {
        $forms = Settings::get_forms();
        $response = [];
        foreach ($forms as $form) {
            $response[] = $form;
        }

        return $response;
    }

    private function get_settings()
    {
		$settings = [];
        foreach (self::$settings as $setting_name) {
            $settings[$setting_name] = Settings::get_setting($setting_name);
        }
        return $settings;
    }

    private function set_settings()
    {
        $data = (array) json_decode(file_get_contents('php://input'), true);
		$response = [];
		foreach (self::$settings as $setting_name) {
			if (!isset($data[$setting_name])) continue;
			$from = Settings::get_setting($setting_name);
			$to = $data[$setting_name];
			foreach (array_keys($from) as $key) {
				$to[$key] = isset($to[$key]) ? $to[$key] : $from[$key];
			}
			update_option($setting_name, $to);
			$response[$setting_name] = $to;
		}

		return $response;
    }

    private function permission_callback()
    {
        if (!current_user_can('manage_options')) {
            return self::error('rest_unauthorized', 'You can\'t manage wp options', 403);
        }

        return true;
    }
}
