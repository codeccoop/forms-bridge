<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

require_once 'vendor/autoload.php';

require_once 'class-gs-store.php';
require_once 'class-gs-client.php';
require_once 'class-gs-rest-controller.php';
require_once 'class-gs-ajax-controller.php';
require_once 'class-gs-service.php';
require_once 'class-gs-form-bridge.php';
require_once 'class-gs-form-bridge-template.php';

/**
 * Google Sheets addon class.
 */
class Google_Sheets_Addon extends Addon
{
    /**
     * Handles the addon name.
     *
     * @var string
     */
    protected static $name = 'Google Sheets';

    /**
     * Handles the addon's API name.
     *
     * @var string
     */
    protected static $api = 'gsheets';

    /**
     * Google Sheets API static data. Works as a placeholder to fit into the common bridge schema.
     *
     * @var array
     */
    public static $static_backend = [
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
    protected static $bridge_class = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge';

    /**
     * Handles the addon's custom form bridge template class.
     *
     * @var string
     */
    protected static $bridge_template_class = '\FORMS_BRIDGE\Google_Sheets_Form_Bridge_Template';

    /**
     * Addon constructor. Inherits from the abstract addon and sets up interceptors
     * and custom hooks.
     */
    protected function construct(...$args)
    {
        parent::construct(...$args);
        self::register_api();
        self::setting_hooks();

        add_filter(
            'forms_bridge_prune_empties',
            static function ($prune, $bridge) {
                if ($bridge instanceof Google_Sheets_Form_Bridge) {
                    return false;
                }

                return $prune;
            },
            5,
            2
        );
    }

    private static function register_api()
    {
        add_filter(
            'option_http-bridge_general',
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                $index = array_search(
                    self::$static_backend['name'],
                    array_column($value['backends'], 'name')
                );

                if ($index === false) {
                    $value['backends'][] = self::$static_backend;
                }

                return $value;
            },
            5,
            1
        );

        add_filter(
            'wpct_plugin_setting_default',
            static function ($data, $name) {
                if ($name !== 'http-bridge_general') {
                    return $data;
                }

                $index = array_search(
                    self::$static_backend['name'],
                    array_column($data['backends'], 'name')
                );

                if ($index === false) {
                    $data['backends'][] = self::$static_backend;
                }

                return $data;
            },
            9,
            2
        );

        add_filter(
            'wpct_plugin_validate_setting',
            static function ($data, $name) {
                if ($name !== 'http-bridge_general') {
                    return $data;
                }

                $index = array_search(
                    self::$static_backend['name'],
                    array_column($data['backends'], 'name')
                );

                if ($index !== false) {
                    array_splice($data['backends'], $index, 1);
                }

                return $data;
            },
            5,
            2
        );
    }

    /**
     * Intercept setting hooks and add authorized attribute.
     */
    private static function setting_hooks()
    {
        // Patch authorized state on the setting default value
        add_filter(
            'wpct_plugin_setting_default',
            static function ($data, $name) {
                if ($name !== self::setting_name()) {
                    return $data;
                }

                return array_merge($data, [
                    'authorized' => Google_Sheets_Service::is_authorized(),
                ]);
            },
            10,
            2
        );

        add_filter(
            'wpct_plugin_validate_setting',
            static function ($data, $setting) {
                if ($setting->full_name() !== self::setting_name()) {
                    return $data;
                }

                unset($data['authorized']);
                return $data;
            },
            9,
            2
        );

        add_filter(
            'option_' . self::setting_name(),
            static function ($value) {
                if (!is_array($value)) {
                    return $value;
                }

                $value['authorized'] = Google_Sheets_Service::is_authorized();
                return $value;
            },
            9,
            1
        );
    }

    /**
     * Registers the setting and its fields.
     *
     * @return array Addon's settings configuration.
     */
    protected static function setting_config()
    {
        return [
            self::$api,
            self::default_config(),
            [
                'bridges' => [],
            ],
        ];
    }

    /**
     * Sanitizes the setting value before updates.
     *
     * @param array $data Setting data.
     * @param Setting $setting Setting instance.
     *
     * @return array Sanitized data.
     */
    protected static function validate_setting($data, $setting)
    {
        $data['bridges'] = self::validate_bridges($data['bridges']);
        return $data;
    }

    /**
     * Validate bridge settings. Filters bridges with inconsistencies with
     * current store state.
     *
     * @param array $bridges Array with bridge configurations.
     *
     * @return array Array with valid bridge configurations.
     */
    private static function validate_bridges($bridges)
    {
        if (!wp_is_numeric_array($bridges)) {
            return [];
        }

        $uniques = [];
        $validated = [];
        foreach ($bridges as $bridge) {
            $bridge = self::validate_bridge($bridge, $uniques);

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
     * @param string $backend Target backend name.
     * @params WP_REST_Request $request Current REST request.
     *
     * @return array Ping result.
     */
    protected function do_ping($backend, $request)
    {
        return ['success' => Google_Sheets_Service::is_authorized()];
    }

    /**
     * Performs a GET request against the backend endpoint and retrive the response data.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Target endpoint name.
     * @params null $credential Credential data, ignored.
     *
     * @return array Fetched records.
     */
    protected function do_fetch($backend, $endpoint, $credential)
    {
        [$spreadsheet, $tab] = explode('::', $endpoint);

        $bridge = new Google_Sheets_Form_Bridge([
            'name' => '__gs-' . time(),
            'endpoint' => $endpoint,
            'spreadsheet' => $spreadsheet,
            'tab' => $tab,
            'method' => 'read',
        ]);

        $response = $bridge->submit();
        if (is_wp_error($response)) {
            return [];
        }

        return $response['data'];
    }

    /**
     * Performs an introspection of the backend endpoint and returns API fields
     * and accepted content type.
     *
     * @param string $backend Target backend name.
     * @param string $endpoint Concatenation of spreadsheet ID and tab name.
     * @params null $credential Credential data, ignored.
     *
     * @return array List of fields and content type of the endpoint.
     */
    protected function get_endpoint_schema($backend, $endpoint, $credential)
    {
        [$spreadsheet, $tab] = explode('::', $endpoint);

        $bridge = new Google_Sheets_Form_Bridge([
            'name' => '__gs-' . time(),
            'endpoint' => $endpoint,
            'spreadsheet' => $spreadsheet,
            'tab' => $tab,
        ]);

        return $bridge->endpoint_schema;
    }
}

Google_Sheets_Addon::setup();
