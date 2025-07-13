<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'vendor/autoload.php';

require_once 'class-gsheets-store.php';
require_once 'class-gsheets-client.php';
require_once 'class-gsheets-rest-controller.php';
require_once 'class-gsheets-ajax-controller.php';
require_once 'class-gsheets-service.php';
require_once 'class-gsheets-form-bridge.php';
require_once 'hooks.php';

/**
 * Google Sheets addon class.
 */
class Google_Sheets_Addon extends Addon
{
    /**
     * Handles the addon's title.
     *
     * @var string
     */
    public const title = 'Google Sheets';

    /**
     * Handles the addon's name.
     *
     * @var string
     */
    public const name = 'gsheets';

    /**
     * Google Sheets API static data. Works as a placeholder to fit into the common bridge schema.
     *
     * @var array
     */
    public const static_backend = [
        'name' => 'Sheets API',
        'base_url' => 'https://sheets.googleapis.com/v4/spreadsheets',
        'headers' => [
            [
                'name' => 'Content-Type',
                'value' => 'application/grpc+proto',
            ],
        ],
    ];
    /**
     * Handles the addom's custom bridge class.
     *
     * @var string
     */
    public const bridge_class = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge';

    public function load()
    {
        parent::load();

        Settings_Store::ready(static function ($store) {
            self::register_setting_proxy($store);
        });

        Http_Store::ready(static function ($store) {
            self::register_backend_proxy($store);
        });

        add_filter(
            'forms_bridge_prune_empties',
            static function ($prune, $bridge) {
                if ($bridge->addon === 'gsheets') {
                    return false;
                }

                return $prune;
            },
            5,
            2
        );
    }

    private static function register_backend_proxy($store)
    {
        $store::use_getter(
            'general',
            function ($data) {
                if (!isset($data['backends']) || !is_array($data['backends'])) {
                    return $data;
                }

                $index = array_search(
                    self::static_backend['name'],
                    array_column($data['backends'], 'name')
                );

                if ($index === false) {
                    $data['backends'][] = self::static_backend;
                }

                return $data;
            },
            20
        );

        $store::use_setter(
            'general',
            function ($data) {
                if (!isset($data['backends']) || !is_array($data['backends'])) {
                    return $data;
                }

                $index = array_search(
                    self::static_backend['name'],
                    array_column($data['backends'], 'name')
                );

                if ($index !== false) {
                    array_splice($data['backends'], $index, 1);
                }

                return $data;
            },
            8
        );
    }

    /**
     * Intercept setting hooks and add authorized attribute.
     */
    private static function register_setting_proxy($store)
    {
        $store::use_getter('gsheets', function ($data) {
            $data['authorized'] = Google_Sheets_Service::is_authorized();
            return $data;
        });

        $store::use_setter(
            'gsheets',
            function ($data) {
                unset($data['authorized']);
                return $data;
            },
            9
        );
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function sanitize_bridges($bridges)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::sanitize_bridge($bridge, $uniques);

            if (!$bridge) {
                continue;
            }

            $bridge['spreadsheet'] = $bridge['spreadsheet'] ?? '';
            $bridge['tab'] = $bridge['tab'] ?? '';
            $bridge['endpoint'] =
                $bridge['spreadsheet'] . '::' . $bridge['tab'];

            $bridge['is_valid'] =
                $bridge['is_valid'] &&
                !empty($bridge['spreadsheet']) &&
                !empty($bridge['tab']);

            $validated[] = $bridge;
        }

        return $validated;
    }

    /**
     * Performs a request against the backend to check the connexion status.
     *
     * @param string $backend Backend name.
     * @params string $credential Credential name.
     *
     * @return boolean
     */
    public function ping($backend, $credential = null)
    {
        return Google_Sheets_Service::is_authorized();
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $endpoint Concatenation of spreadsheet ID and tab name.
     * @param string $backend Backend name.
     * @param string $credential Credential name.
     *
     * @return array|WP_Error
     */
    public function fetch($endpoint, $backend, $credential = null)
    {
        [$spreadsheet, $tab] = explode('::', $endpoint);

        $bridge = new Google_Sheets_Form_Bridge(
            [
                'name' => '__gs-' . time(),
                'endpoint' => $endpoint,
                'spreadsheet' => $spreadsheet,
                'tab' => $tab,
                'method' => 'read',
            ],
            self::name
        );

        return $bridge->submit();
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $endpoint Concatenation of spreadsheet ID and tab name.
     * @param string $backend Backend name.
     * @params null $credential Credential name.
     *
     * @return array List of fields and content type of the endpoint.
     */
    public function get_endpoint_schema($endpoint, $backend, $credential = null)
    {
        [$spreadsheet, $tab] = explode('::', $endpoint);

        $bridge = new Google_Sheets_Form_Bridge(
            [
                'name' => '__gs-' . time(),
                'endpoint' => $endpoint,
                'spreadsheet' => $spreadsheet,
                'tab' => $tab,
                'method' => 'schema',
            ],
            self::name
        );

        $response = $bridge->submit();

        if (is_wp_error($response)) {
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
}

Google_Sheets_Addon::setup();
