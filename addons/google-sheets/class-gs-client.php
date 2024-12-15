<?php

namespace FORMS_BRIDGE;

use Exception;

if (!defined('ABSPATH')) {
    exit();
}

putenv(
    'GOOGLE_APPLICATION_CREDENTIALS=' .
        dirname(__FILE__) .
        '/assets/credentials.json'
);

class Google_Sheets_Client
{
    private $instance;

    public function __construct()
    {
        $base_url = get_bloginfo('url');
        $settings = $this->settings();

        $client = new \Google\Client();
        $client->useApplicationDefaultCredentials();
        // $client->setApplicationName('example.coop');
        // $client->setDeveloperKey('AIzaSyDhrYn304dbfKfE-PABL_MJtwS5TOXVUgY');
        // $client->setClientId($settings['client_id']);
        // $client->setClientSecret($settings['client_secret']);
        $client->setScopes([
            \Google\Service\Sheets::SPREADSHEETS,
            \Google\Service\Drive::DRIVE_METADATA_READONLY,
        ]);
        // $client->setAccessType('offline');
        // $client->setState('abc');
        // $client->setRedirectUri($base_url . '/wp-admin/options-general.php?page=forms-bridge');

        $this->instance = $client;
    }

    private function settings()
    {
        $setting = apply_filters(
            'forms_bridge_setting',
            null,
            'google-sheets-api'
        );
        return [
            'client_id' => $setting->client_id,
            'client_secret' => $setting->client_secret,
        ];
    }

    public function auth_url()
    {
        return $this->instance->createAuthUrl();
    }

    public function set_token($token)
    {
        $this->instance->setAccessToken($token);
    }

    public function fetch_token($access_code)
    {
        return $this->instance->fetchAccessTokenWithAuthCode($access_code);
    }

    public function access_token()
    {
        return $this->instance->getAccessToken();
    }

    public function refresh_token()
    {
        if ($this->instance->isAccessTokenExpired()) {
            if ($refresh_token = $this->instance->getRefreshToken()) {
                $this->instance->fetchAccessTokenWithRefreshToken(
                    $refresh_token
                );
            } else {
                throw new Exception('Token is expired');
            }
        }
    }

    public function revoke_token($token)
    {
        $this->instance->revokeToken($token);
    }

    public function get_drive_service()
    {
        return new \Google\Service\Drive($this->instance);
    }

    public function get_sheets_service()
    {
        return new \Google\Service\Sheets($this->instance);
    }
}
