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
        add_action('wp_ajax_' . self::action, function () {
            $this->ajax_handler();
        });

        add_action('admin_enqueue_scripts', function () {
            $this->localize_script();
        });
    }

    private function localize_script()
    {
        wp_localize_script(
            'forms-bridge-google-sheets-api',
            'formsBridgeGSAjax',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::nonce),
                'action' => self::action,
            ]
        );
    }

    private function ajax_handler()
    {
        check_ajax_referer(self::nonce, 'nonce');

        $status = 200;
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'DELETE':
                $success = $this->revoke_credentials($status);
                break;
            case 'POST':
                $success = $this->add_credentials($status);
                break;
            default:
                $success = false;
        }

        wp_send_json(['success' => $success], $status);
    }

    private function add_credentials(&$status)
    {
        if (!isset($_FILES['credentials'])) {
            $status = 400;
            return false;
        }

        $credentials = $_FILES['credentials'];
        Google_Sheets_Store::set(
            'credentials',
            file_get_contents($credentials['tmp_name'])
        );
        unlink($credentials['tmp_name']);
        return true;
    }

    private function revoke_credentials(&$status)
    {
        Google_Sheets_Store::delete('credentials');
        return true;
    }
}

Google_Sheets_Ajax_Controller::setup();
