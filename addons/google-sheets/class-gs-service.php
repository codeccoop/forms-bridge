<?php

namespace FORMS_BRIDGE;

use Exception;
use WP_Error;
use WPCT_ABSTRACT\Singleton;

class Google_Sheets_Service extends Singleton
{
    private const access_token_option = 'forms_bridge_gs_access_token';

    private $client;
    private $token;

    public static function get_token()
    {
        return self::get_instance()->token();
    }

    public static function fetch_token($access_code)
    {
        $token = self::get_instance()->client()->fetch_token($access_code);
        return self::update_token($token);
    }

    private static function update_token($token)
    {
        $expires_in = isset($token['expires_in'])
            ? (int) $token['expires_in']
            : 0;
        $token['expire'] = time() + $expires_in;
        return self::get_instance()->set_token($token);
    }

    public static function refresh_token()
    {
        $token = self::get_instance()->token();
        self::get_instance()
            ->client()
            ->refreshToken($token['access_token']);
    }

    public static function revoke_token()
    {
        $token = self::get_instance()->token();
        if ($token) {
            self::get_instance()->client()->revoke_token($token);
            self::get_instance()->set_token(null);
        }
    }

    public static function is_authorized()
    {
        return self::get_instance()->is_valid();
    }

    public static function auth_url()
    {
        return self::get_instance()->client()->auth_url();
    }

    public static function auth()
    {
    }

    public static function write_row($spreadsheet_id, $tab_name, $data)
    {
        if (empty($data)) {
            return;
        }

        try {
            $service = self::get_instance()->client()->get_sheets_service();
            $sheets = $service->spreadsheets->get($spreadsheet_id);
        } catch (Exception $e) {
            return new WP_Error(
                'spreadsheets_api_error',
                __('Can\'t connect to the spreadsheets API', 'forms-bridge')
            );
        }

        if (empty($sheets)) {
            return new WP_Error(
                'no_workbook_sheets',
                __(
                    'You have to manully create sheets on your spreadsheet',
                    'forms-bridge'
                ),
                ['spreadsheet_id' => $spreadsheet_id]
            );
        }

        $sheet = null;
        foreach ($sheets as $_sheet) {
            if ($_sheet->getProperties()['title'] === $tab_name) {
                $sheet = $_sheet;
                break;
            }
        }

        if (!$sheet) {
            return new WP_Error(
                'unkown_sheet',
                __(
                    'There is no tab on the spreadsheet that matches this name',
                    'forms-bridge'
                ),
                ['tab_name' => $tab_name, 'spreadsheet' => $spreadsheet_id]
            );
        }

        $row = $service->spreadsheets_values->get(
            $spreadsheet_id,
            $tab_name . '!1:1'
        );
        if (!isset($row->values[0])) {
            $value_range = new \Google\Service\Sheets\ValueRange();
            $value_range->setValues(['values' => array_keys($data)]);

            $result = $service->spreadsheets_values->append(
                $spreadsheet_id,
                "{$tab_name}!1:Z",
                $value_range,
                ['valueInputOption' => 'USER_ENTERED']
            );
        }

        $headers = array_map(function ($value) {
            return $value;
        }, array_values($row->values[0]));

        $values = array_map(function ($header) use ($data) {
            return isset($data[$header]) ? $data[$header] : '';
        }, $headers);

        $response = $service->spreadsheets_values->get(
            $spreadsheet_id,
            $tab_name . '!A1:Z'
        );
        $rows = $response->getValues();

        if ($rows) {
            $row = count($rows) + 1;
        } else {
            $row = 1;
        }

        $range = $tab_name . '!A' . $row . ':Z';

        $range = new \Google\Service\Sheets\ValueRange();
        $range->setValues(['values' => $values]);

        $result = $service->spreadsheets_values->append(
            $spreadsheet_id,
            "{$tab_name}!A{$row}:Z",
            $range,
            ['valueInputOption' => 'USER_ENTERED']
        );

        return $result;
    }

    public static function get_spreadsheets()
    {
        try {
            $service = self::get_instance()->client()->get_drive_service();
            $results = $service->files->listFiles([
                'q' => "mimeType='application/vnd.google-apps.spreadsheet'",
            ]);

            return array_map(
                function ($spreadhseet) {
                    return [
                        'id' => $spreadhseet['id'],
                        'title' => $spreadhseet['name'],
                    ];
                },
                array_filter($results->files, function ($file) {
                    return isset($file['kind']) &&
                        $file['kind'] === 'drive#file';
                })
            );
        } catch (Exception $e) {
            return [];
        }
    }

    public static function get_sheets($spreadsheet_id)
    {
        try {
            $service = self::get_instance()->client()->get_sheets_service();
            $sheets = $service->spreadsheets->get($spreadsheet_id);

            return array_map(function ($sheet) {
                $props = $sheet->getProperties();
                return [
                    'id' => $props->getSheetId(),
                    'title' => $props->getTitle(),
                ];
            }, $sheets);
        } catch (Exception) {
            return [];
        }
    }

    public static function setup()
    {
        return self::get_instance();
    }

    protected function construct(...$args)
    {
        $this->client = new Google_Sheets_Client();
        $this->token = get_option(self::access_token_option);
        if ($this->is_valid($this->token)) {
            $this->client->set_token($this->token);
        }
    }

    public function client()
    {
        return $this->client;
    }

    public function token()
    {
        return $this->token;
    }

    public function set_token($token)
    {
        $from = $this->token;

        if ($token && $this->is_valid($token)) {
            $success = update_option(self::access_token_option, $token);
        } elseif (empty($token)) {
            $success = delete_option(self::access_token_option);
        } else {
            $success = false;
        }

        if ($this->is_valid($from) && !$token) {
            Google_Sheets_Service::revoke_token($from);
        }

        if ($success) {
            $this->token = $token;
        } else {
            $this->token = get_option(self::access_token_option);
        }

        return $this->token;
    }

    public function is_valid($token = null)
    {
        if (!is_array($token)) {
            return false;
        }

        $token = $token ?? $this->token();
        if (!isset($token['scope'])) {
            return false;
        }

        $permissions = explode(' ', $token['scope']);
        $requirements = [
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
        ];

        foreach ($requirements as $requirement) {
            if (!in_array($requirement, $permissions)) {
                return false;
            }
        }

        return true;
    }
}

Google_Sheets_Service::setup();
