<?php

namespace FORMS_BRIDGE;

use Error;
use Exception;
use TypeError;
use WP_Error;
use WPCT_PLUGIN\Singleton;

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
            if (
                strtolower($sheet->getProperties()['title']) ===
                strtolower($tab_name)
            ) {
                return $sheet;
            }
        }
    }

    private static function prepare($spreadsheet_id, $tab_name)
    {
        self::service()->client()->use_credentials();

        try {
            $service = self::service()->client()->get_sheets_service();
            $sheets = $service->spreadsheets->get($spreadsheet_id);

            if (empty($sheets)) {
                $sheet = self::add_sheet($service, $spreadsheet_id, $tab_name);
            } else {
                foreach ($sheets as $candidate) {
                    if (
                        strtolower($candidate->getProperties()['title']) ===
                        strtolower($tab_name)
                    ) {
                        $sheet = $candidate;
                        break;
                    }
                }

                if (!isset($sheet)) {
                    $sheet = self::add_sheet(
                        $service,
                        $spreadsheet_id,
                        $tab_name
                    );
                }
            }
        } catch (Exception | Error $e) {
            return new WP_Error(
                'spreadsheets_not_found_error',
                __('Can\'t find the spreadsheets by id', 'forms-bridge'),
                ['error' => $e]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }

        return $service;
    }

    private static function write_headers(
        $service,
        $spreadsheet_id,
        $tab_name,
        $fields
    ) {
        self::service()->client()->use_credentials();

        try {
            $value_range = new \Google\Service\Sheets\ValueRange();
            $value_range->setValues(['values' => $fields]);

            $result = $service->spreadsheets_values->append(
                $spreadsheet_id,
                "{$tab_name}!A1:Z",
                $value_range,
                ['valueInputOption' => 'USER_ENTERED']
            );

            return self::headers($service, $spreadsheet_id, $tab_name);
        } catch (Exception | Error $e) {
            return new WP_Error(
                'spreadhseet_write_error',
                __('Can\'t write spreadhseet tab headers', 'forms-bridge'),
                ['status' => 500, 'error' => $e->getMessage()]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }
    }

    private static function headers($service, $spreadsheet_id, $tab_name)
    {
        self::service()->client()->use_credentials();

        try {
            $row = $service->spreadsheets_values->get(
                $spreadsheet_id,
                $tab_name . '!1:1'
            );

            if (empty($row->values[0])) {
                return [];
            }

            return array_map(static function ($value) {
                return $value;
            }, array_values($row->values[0]));
        } catch (Exception | Error $e) {
            return new WP_Error(
                'spreadhseet_read_error',
                __('Can\'t read spreadhseet headers', 'forms-bridge'),
                ['status' => 500, 'error' => $e->getMessage()]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }
    }

    public static function schema($spreadsheet_id, $tab_name)
    {
        $service = self::prepare($spreadsheet_id, $tab_name);
        if (is_wp_error($service)) {
            return $service;
        }

        $headers = self::headers($service, $spreadsheet_id, $tab_name);
        if (is_wp_error($headers)) {
            return [];
        }

        return $headers;
    }

    public static function read(
        $spreadsheet_id,
        $tab_name,
        $offset = 0,
        $limit = -1
    ) {
        $service = self::prepare($spreadsheet_id, $tab_name);
        if (is_wp_error($service)) {
            return $service;
        }

        $headers = self::headers($service, $spreadsheet_id, $tab_name);
        if (is_wp_error($headers)) {
            return $headers;
        } elseif (empty($headers)) {
            return new WP_Error(
                'spreadhseet_read_error',
                __('Spreadhseet tab without columns', 'forms-bridge'),
                ['status' => 400]
            );
        }

        self::service()->client()->use_credentials();
        try {
            $response = $service->spreadsheets_values->get(
                $spreadsheet_id,
                $tab_name . '!A1:Z'
            );
            $rows = $response->getValues();
            $data = [];

            $end = $limit === -1 ? count($rows) : 1 + $offset + $limit;
            for ($i = $offset + 1; $i < $end; $i++) {
                $row = [];
                for ($j = 0; $j < count($headers); $j++) {
                    $field = $headers[$j];
                    $row[$field] = $rows[$i][$j] ?? '';
                }

                $data[] = $row;
            }
        } catch (Exception | Error $e) {
            return new WP_Error(
                'spreadhseet_read_error',
                __('Can\'t read values from the spreadhseet', 'posts-bridge'),
                ['status' => 500, 'error' => $e->getMessage()]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }

        if (!isset($data)) {
            return [];
        }

        return $data;
    }

    public static function write($spreadsheet_id, $tab_name, $data)
    {
        if (empty($data)) {
            return;
        }

        $service = self::prepare($spreadsheet_id, $tab_name);
        if (is_wp_error($service)) {
            return $service;
        }

        $headers = self::headers($service, $spreadsheet_id, $tab_name);
        if (is_wp_error($headers)) {
            return $headers;
        }

        if (empty($headers)) {
            $headers = self::write_headers(
                $service,
                $spreadsheet_id,
                $tab_name,
                array_keys($data)
            );
            if (is_wp_error($headers)) {
                return $headers;
            }
        }

        self::service()->client()->use_credentials();
        try {
            $values = array_map(static function ($header) use ($data) {
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
        } catch (Exception | Error $e) {
            return new WP_Error(
                'spreadhseet_write_error',
                __(
                    'Can\'t write new values to the spreadhseet',
                    'forms-bridge'
                ),
                ['status' => 500, 'error' => $e]
            );
        } finally {
            self::service()->client()->flush_credentials();
        }

        return [
            'updates' => [
                'spreadsheet_id' => $result->updates->spreadsheetId,
                'rows' => $result->updates->updatedRows,
                'columns' => $result->updates->updatedColumns,
                'range' => $result->updates->updatedRange,
            ],
        ];
    }

    public static function get_spreadsheets()
    {
        if (!self::is_authorized()) {
            return [];
        }

        self::service()->client()->use_credentials();
        try {
            $service = self::get_instance()->client()->get_drive_service();
            $results = $service->files->listFiles([
                'q' => "mimeType='application/vnd.google-apps.spreadsheet'",
            ]);

            $files = $results->files ?: [];
            return array_map(
                static function ($spreadhseet) {
                    return [
                        'id' => $spreadhseet['id'],
                        'title' => $spreadhseet['name'],
                    ];
                },
                array_filter($files, static function ($file) {
                    return isset($file['kind']) &&
                        $file['kind'] === 'drive#file';
                })
            );
        } catch (Exception | Error) {
            return [];
        } finally {
            self::service()->client()->flush_credentials();
        }
    }

    public static function get_sheets($spreadsheet_id)
    {
        if (!self::is_authorized()) {
            return [];
        }

        self::service()->client()->use_credentials();
        try {
            $service = self::service()->client()->get_sheets_service();
            $sheets = $service->spreadsheets->get($spreadsheet_id);

            return array_map(static function ($sheet) {
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
