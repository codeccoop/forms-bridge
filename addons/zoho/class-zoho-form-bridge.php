<?php

namespace FORMS_BRIDGE;

use TypeError;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Zoho API protocol.
 */
class Zoho_Form_Bridge extends Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'zoho';

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = ['Authorization', 'Content-Type', 'Accept'];

    /**
     * Handles the oauth access token transient name.
     *
     * @var string
     */
    private const token_transient = 'forms-bridge-zoho-oauth-access-token';

    /**
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected static $zoho_oauth_service = 'ZohoCRM';

    /**
     * Parent getter interceptor to short circtuit credentials access.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'method':
                return $this->data['method'] ?? 'POST';
            default:
                return parent::__get($name);
        }
    }

    /**
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Bridge's credential data getter.
     *
     * @return array|null
     */
    protected function credential()
    {
        $credentials = Forms_Bridge::setting($this->api)->credentials ?: [];
        foreach ($credentials as $credential) {
            if ($credential['name'] === $this->data['credential']) {
                return $credential;
            }
        }
    }

    /**
     * Compare two scope strings and return true if the first one is compatible with the second one.
     *
     * @param string $scope Zoho OAuth scope string.
     * @param string $required Zoho OAuth scope string.
     *
     * @return boolean
     */
    private static function check_oauth_scope($scope, $required)
    {
        $scopes = array_filter(array_map('trim', explode(',', $scope)));
        $requireds = array_filter(array_map('trim', explode(',', $required)));

        $is_valid = true;
        foreach ($requireds as $required) {
            [$rapp, $rmodule, $rsubmodule, $rpermission] = explode(
                '.',
                $required
            );

            if (empty($rpermission)) {
                $rpermission = $rsubmodule;
                $rsubmodule = null;
            }

            $match = false;
            foreach ($scopes as $scope) {
                [$sapp, $smodule, $ssubmodule, $spermission] = explode(
                    '.',
                    $scope
                );

                if (empty($spermission)) {
                    $spermission = $ssubmodule;
                    $ssubmodule = null;
                }

                if ($rapp !== $sapp) {
                    continue;
                }

                if ($rmodule !== $smodule) {
                    continue;
                }

                if ($rsubmodule) {
                    if ($ssubmodule === null && $spermission === 'ALL') {
                        $match = true;
                        break;
                    } elseif ($rsubmodule !== $ssubmodule) {
                        continue;
                    }
                }

                $match =
                    $spermission === 'ALL' || $spermission === $rpermission;

                if ($match) {
                    break;
                }
            }

            $is_valid = $is_valid && $match;
        }

        return $is_valid;
    }

    /**
     * Performs an authentication request to the zoho oauth server using
     * the bridge credentials.
     *
     * @param array|null $token Token to be refreshed, optional.
     *
     * @return string|null Access token.
     */
    protected function get_access_token($token = null)
    {
        if (!$token) {
            $transient = get_transient(self::token_transient);

            if ($transient) {
                try {
                    $token = json_decode($transient, true);
                } catch (TypeError) {
                    $token = false;
                }
            }

            if (
                $token &&
                $this->check_oauth_scope($token['scope'], $this->scope)
            ) {
                if ($token['expires_at'] > time()) {
                    return $token['access_token'];
                } else {
                    $refreshed = $this->get_access_token(true);

                    if ($refreshed) {
                        return $refreshed;
                    }
                }
            }
        } else {
            $refresh = true;
        }

        $base_url = $this->backend->base_url;

        $host = parse_url($base_url)['host'] ?? null;
        if (!$host) {
            return;
        }

        $region = null;
        if (preg_match('/\.([a-z]{2,3}(\.[a-z]{2})?)$/', $host, $matches)) {
            $region = $matches[1];
        } else {
            Logger::log('Invalid Zoho API URL', Logger::ERROR);
            return;
        }

        $oauth_server = 'https://accounts.zoho.' . $region;
        $url = $oauth_server . '/oauth/v2/token';

        $credential = $this->credential();

        $scope = $this->scope ?: static::$zoho_oauth_service . '.modules.ALL';
        $service = explode('.', $scope)[0] ?? static::$zoho_oauth_service;

        if (isset($refresh)) {
            $query = http_build_query([
                'client_id' => $credential['client_id'] ?? '',
                'client_secret' => $credential['client_secret'] ?? '',
                'grant_type' => 'refresh_token',
                'refresh_token' => $token['refresh_token'],
            ]);
        } else {
            $query = http_build_query([
                'client_id' => $credential['client_id'] ?? '',
                'client_secret' => $credential['client_secret'] ?? '',
                'grant_type' => 'client_credentials',
                'scope' => $scope,
                'soid' => implode('.', [
                    $service,
                    $credential['organization_id'] ?? '',
                ]),
            ]);
        }

        $response = http_bridge_post($url . '?' . $query);

        if (is_wp_error($response)) {
            Logger::log('Oauth response error', Logger::ERROR);
            Logger::log($response, Logger::ERROR);
            return;
        }

        $data = $response['data'];
        $data['expires_at'] = $data['expires_in'] + time() - 10;

        set_transient(
            self::token_transient,
            json_encode($data),
            $response['data']['expires_in'] - 10
        );

        return $response['data']['access_token'];
    }

    /**
     * Check for credentials validity without publishing the private access token.
     *
     * @return boolean
     */
    public function check_credential()
    {
        $access_token = $this->get_access_token();
        return $access_token !== null;
    }

    /**
     * Performs an http request to the Zoho API backend.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        $access_token = $this->get_access_token();
        if (empty($access_token)) {
            return new WP_Error(
                'unauthorized',
                __('OAuth invalid response', 'forms-bridge')
            );
        }

        $method_fn = strtolower($this->method);
        if ($method_fn === 'post' || $method_fn === 'put') {
            $payload = wp_is_numeric_array($payload) ? $payload : [$payload];
            $payload = ['data' => $payload];
        }

        $response = $this->backend->$method_fn(
            $this->endpoint,
            $payload,
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
            ],
            $attachments
        );

        if (is_wp_error($response)) {
            $data = json_decode(
                $response->get_error_data()['response']['body'],
                true
            );

            $code = $data['data'][0]['code'] ?? null;
            if ($code !== 'DUPLICATE_DATA') {
                return $response;
            }

            $response = $response->get_error_data()['response'];
            $response['data'] = json_decode($response['body'], true);
        }

        return $response;
    }

    /**
     * Bridge's endpoint fields schema getter.
     *
     * @param string|null $endpoint Layout metadata endpoint.
     *
     * @return array
     */
    protected function api_schema($endpoint = '/crm/v7/settings/layouts')
    {
        if (!preg_match('/\/([A-Z].+$)/', $this->endpoint, $matches)) {
            return [];
        }

        $module = str_replace('/upsert', '', $matches[1]);

        $response = $this->patch([
            'name' => 'zoho-api-schema-introspection',
            'endpoint' => $endpoint,
            'scope' => static::$zoho_oauth_service . '.settings.layouts.READ',
            'method' => 'GET',
        ])->submit(['module' => $module]);

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['layouts'] as $layout) {
            foreach ($layout['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $type = $field['json_type'];
                    if ($type === 'jsonobject') {
                        $type = 'object';
                    } elseif ($type === 'jsonarray') {
                        $type = 'array';
                    } elseif ($type === 'double') {
                        $type = 'number';
                    }

                    $fields[] = [
                        'name' => $field['api_name'],
                        'schema' => ['type' => $type],
                    ];
                }
            }
        }

        return $fields;
    }
}
