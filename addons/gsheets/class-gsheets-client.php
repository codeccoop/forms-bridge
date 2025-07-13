<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

putenv(
    'GOOGLE_APPLICATION_CREDENTIALS=' .
        get_temp_dir() .
        'google-sheets-credentials.json'
);

class Google_Sheets_Client
{
    private $instance;

    public function __construct()
    {
        $client = new \Google\Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes([
            \Google\Service\Sheets::SPREADSHEETS,
            \Google\Service\Drive::DRIVE_METADATA_READONLY,
        ]);

        $this->instance = $client;
    }

    public function use_credentials()
    {
        $credentials = Google_Sheets_Store::get('credentials');
        $path = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        file_put_contents($path, $credentials);
    }

    public function flush_credentials()
    {
        $path = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        wp_delete_file($path);
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
