<?php

namespace FORMS_BRIDGE;

use FORMS_BRIDGE\Forms_Bridge as Forms_Bridge;
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
     * Parent getter interceptor to short circtuit credentials access.
     *
     * @param string $name Attribute name.
     *
     * @return mixed Attribute value or null.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'credential':
                return $this->credential();
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
     * Bridge's API key private getter.
     *
     * @return Zoho_Credentials|null
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

    private function check_oauth_scope($scope, $required)
    {
        $scopes = array_map('trim', explode(',', $scope));
        $requireds = array_map('trim', explode(',', $scope));

        $is_valid = true;
        foreach ($requireds as $required) {
            $is_valid = $is_valid && in_array($required, $scopes);
        }

        return $is_valid;
    }

    /**
     * Performs an authentication request to the zoho oauth server using
     * the bridge credentials.
     */
    protected function get_access_token()
    {
        $token = get_transient(self::token_transient);

        if ($token) {
            try {
                $token = json_decode($token, true);
            } catch (TypeError) {
                $token = false;
            }
        }

        if (
            is_array($token) &&
            isset($token['access_token'], $token['expires_at'])
        ) {
            if (
                $token['expires_at'] < time() - 10 &&
                $this->check_oauth_scope($token['scope'], $this->scope)
            ) {
                return $token['access_token'];
            }
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

        $scope = $this->scope ?: 'ZohoCRM.';
        $service = explode('.', $scope)[0] ?? 'ZohoCRM';

        $query = http_build_query([
            'client_id' => $credential->client_id ?? '',
            'client_secret' => $credential->client_secret ?? '',
            'grant_type' => 'client_credentials',
            'scope' => $scope,
            'soid' => implode('.', [
                $service,
                $credential->organization_id ?? '',
            ]),
        ]);

        $response = http_bridge_post($url . '?' . $query);

        if (is_wp_error($response)) {
            Logger::log('Oauth response error', Logger::ERROR);
            Logger::log($response, Logger::ERROR);
            return;
        }

        set_transient(
            self::token_transient,
            $response['body'],
            $response['data']['expires_in'] - 30
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

        $payload = wp_is_numeric_array($payload) ? $payload : [$payload];

        $method_fn = strtolower($this->method);
        $response = $this->backend->$method_fn(
            $this->endpoint,
            ['data' => $payload],
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

            if ($data['data'][0]['code'] !== 'DUPLICATE_DATA') {
                return $response;
            }

            $response = $response->get_error_data()['response'];
            $response['data'] = json_decode($response['body'], true);
        }

        return $response;
    }

    protected function api_schema()
    {
        if (!preg_match('/\/([A-Z].+$)/', $this->endpoint, $matches)) {
            return [];
        }

        $module = str_replace('/upsert', '', $matches[1]);

        $response = $this->patch([
            'name' => 'zoho-api-schema-introspection',
            'endpoint' => '/crm/v7/settings/layouts',
            'scope' => 'ZohoCRM.settings.layouts.READ',
            'method' => 'GET',
        ])->submit(['module' => $module]);

        if (is_wp_error($response)) {
            return [];
        }

        $fields = [];
        foreach ($response['data']['layouts'] as $layout) {
            foreach ($layout['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $fields[] = $field['api_name'];
                }
            }
        }

        return $fields;
    }
}
