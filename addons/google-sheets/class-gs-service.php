<?php

namespace FORMS_BRIDGE;

use Exception;
use TypeError;
use WP_Error;
use WPCT_ABSTRACT\Singleton;

if (!defined('ABSPATH')) {
    exit();
}

class Google_Sheets_Service extends Singleton
{
    private $client;

    public static function service()
    {
        return self::get_instance();
    }

    public static function is_authorized()
    {
        $credentials = Google_Sheets_Store::get('credentials');
        if (!$credentials) {
            return false;
        }

        try {
            $secrets = json_decode($credentials, true);
            return isset($secrets['type']) &&
                $secrets['type'] === 'service_account';
        } catch (TypeError) {
            return false;
        }
    }

    private static function add_sheet($service, $spreadsheet_id, $tab_name)
    {
        $requests = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
        $requests->setRequests([
            [
                'addSheet' => [
                    'properties' => [
                        'title' => $tab_name,
                    ],
                ],
            ],
        ]);
        $service->spreadsheets->batchUpdate($spreadsheet_id, $requests);
        $sheets = $service->spreadsheets->get($spreadsheet_id);

        foreach ($sheets as $sheet) {
            if ($sheet->getProperties()['title'] === $tab_name) {
                return $sheet;
            }
        }
    }

    public static function write_row($spreadsheet_id, $tab_name, $data)
    {
        if (empty($data)) {
            return;
        }

        self::service()->client()->use_credentials();
        try {
            $service = self::service()->client()->get_sheets_service();
            $sheets = $service->spreadsheets->get($spreadsheet_id);
        } catch (Exception $e) {
            return new WP_Error(
                'spreadsheets_not_found_error',
                __('Can\'t find the spreadsheets by id', 'forms-bridge'),
                ['error' => $e]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }

        self::service()->client()->use_credentials();
        try {
            if (empty($sheets)) {
                $sheet = self::add_sheet($service, $spreadsheet_id, $tab_name);
            } else {
                $sheet = null;
                foreach ($sheets as $_sheet) {
                    if ($_sheet->getProperties()['title'] === $tab_name) {
                        $sheet = $_sheet;
                        break;
                    }
                }

                if (!$sheet) {
                    $sheet = self::add_sheet(
                        $service,
                        $spreadsheet_id,
                        $tab_name
                    );
                }
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
                    "{$tab_name}!A1:Z",
                    $value_range,
                    ['valueInputOption' => 'USER_ENTERED']
                );

                $row = $service->spreadsheets_values->get(
                    $spreadsheet_id,
                    $tab_name . '!1:1'
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
        } catch (Exception $e) {
            return new WP_Error(
                'spreadhseet_write_error',
                __(
                    'Can\'t write new values to the spreadhseet',
                    'forms-bridge'
                ),
                ['error' => $e]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }

        return $result;
    }

    public static function get_spreadsheets()
    {
        self::service()->client()->use_credentials();
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
        } finally {
            self::service()->client()->flush_credentials();
        }
    }

    public static function get_sheets($spreadsheet_id)
    {
        self::service()->client()->use_credentials();
        try {
            $service = self::service()->client()->get_sheets_service();
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
        } finally {
            self::service()->client()->flush_credentials();
        }
    }

    public static function setup()
    {
        return self::get_instance();
    }

    protected function construct(...$args)
    {
        $this->client = new Google_Sheets_Client();
    }

    public function client()
    {
        return $this->client;
    }
}

Google_Sheets_Service::setup();
