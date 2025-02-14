<?php

namespace FORMS_BRIDGE;

use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheets_Ajax_Controller extends Singleton
{
    private const nonce = 'forms-bridge-gs-credentials';
    private const action = 'forms_bridge_google_access_grant';

    public static function setup()
    {
        return self::get_instance();
    }

    protected function construct(...$args)
    {
        add_action('wp_ajax_' . self::action, static function () {
            self::ajax_handler();
        });

        add_action('admin_enqueue_scripts', static function () {
            self::localize_script();
        });
    }

    private static function localize_script()
    {
        wp_localize_script('forms-bridge-google-sheets', 'formsBridgeGSAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::nonce),
            'action' => self::action,
        ]);
    }

    private static function ajax_handler()
    {
        check_ajax_referer(self::nonce, 'nonce');

        $status = 200;
        $method = isset($_SERVER['REQUEST_METHOD'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
            : 'GET';
        switch ($method) {
            case 'DELETE':
                $success = self::revoke_credentials($status);
                break;
            case 'POST':
                $success = self::add_credentials($status);
                break;
            default:
                $success = false;
        }

        wp_send_json(['success' => $success], $status);
    }

    private static function add_credentials(&$status)
    {
        if (!isset($_FILES['credentials']['tmp_name'])) {
            $status = 400;
            return false;
        }

        $credentials = sanitize_text_field(
            wp_unslash($_FILES['credentials']['tmp_name'])
        );
        if (!is_file($credentials)) {
            $status = 400;
            return false;
        }

        Google_Sheets_Store::set(
            'credentials',
            file_get_contents($credentials)
        );

        wp_delete_file($credentials);
        return true;
    }

    private static function revoke_credentials(&$status)
    {
        Google_Sheets_Store::delete('credentials');
        return true;
    }
}

Google_Sheets_Ajax_Controller::setup();
