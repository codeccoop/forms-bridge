<?php

namespace FORMS_BRIDGE;

use HTTP_BRIDGE\Http_Backend;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implementation for the Google Sheets service.
 */
class Google_Sheets_Form_Bridge extends Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'gsheets';

    public static function schema()
    {
        $schema = parent::schema();
        $schema['properties']['spreadsheet'] = [
            'description' => __('ID of the spreadhseet', 'forms-bridge'),
            'type' => 'string',
        ];

        $schema['required'][] = 'spreadsheet';

        $schema['properties']['tab'] = [
            'description' => __('Name of the spreadsheet tab', 'forms-bridge'),
            'type' => 'string',
        ];

        $schema['required'][] = 'tab';

        $schema['properties']['endpoint'] = [
            'description' => __(
                'Concatenation of the spreadsheet ID and the tab name by double colons',
                'forms-bridge'
            ),
            'type' => 'string',
        ];

        $schema['required'][] = 'endpoint';
        return $schema;
    }
    /**
     * Retrives the bridge's backend instance.
     *
     * @return Http_Backend|null
     */
    protected function backend()
    {
        return new Http_Backend(Google_Sheets_Addon::$static_backend);
    }

    /**
     * Bridge's spreadsheet fields schema getter.
     *
     * @return array
     */
    protected function endpoint_schema()
    {
        $response = $this->patch([
            'method' => 'schema',
        ])->submit();

        if (is_wp_error($response) || empty($response['data'])) {
            return [];
        }

        $fields = [];
        foreach ($response['data'] as $field) {
            $fields[] = [
                'name' => $field,
                'schema' => ['type' => 'string'],
            ];
        }

        return $fields;
    }

    /**
     * Performs a gRPC request to the Google Sheets API.
     *
     * @param array $payload Submission data.
     * @param array $attachments Submission's attached files. Will be ignored.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        $payload = self::flatten_payload($payload);

        $method = $this->method ?: 'write';

        if ($method === 'write') {
            $result = Google_Sheets_Service::write(
                $this->spreadsheet,
                $this->tab,
                $payload
            );
        } elseif ($method === 'read') {
            $result = Google_Sheets_Service::read(
                $this->spreadsheet,
                $this->tab
            );
        } elseif ($method === 'schema') {
            $result = Google_Sheets_Service::schema(
                $this->spreadsheet,
                $this->tab,
                0
            );
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return [
            'headers' => null,
            'body' => '',
            'response' => [
                'code' => 200,
                'message' => 'OK',
            ],
            'cookies' => [],
            'filename' => null,
            'http_response' => null,
            'data' => $result,
        ];
    }

    /**
     * Sheets are flat, if payload has nested arrays, flattens it and concatenate its keys
     * as field names.
     *
     * @param array $payload Submission payload.
     * @param string $path Prefix to prepend to the field name.
     *
     * @return array Flattened payload.
     */
    private static function flatten_payload($payload, $path = '')
    {
        $flat = [];
        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                $is_flat =
                    wp_is_numeric_array($value) &&
                    count(
                        array_filter($value, static function ($d) {
                            return !is_array($d);
                        })
                    ) === count($value);
                if ($is_flat) {
                    $flat[$path . $field] = implode(',', $value);
                } else {
                    $flat = array_merge(
                        $flat,
                        self::flatten_payload($value, $path . $field . '.')
                    );
                }
            } else {
                $flat[$path . $field] = $value;
            }
        }

        return $flat;
    }
}
