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
     * Handles the zoho oauth service name.
     *
     * @var string
     */
    protected const zoho_oauth_service = 'ZohoCRM';

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
            $transient = get_transient(static::token_transient);

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

        $scope = $this->scope ?: static::zoho_oauth_service . '.modules.ALL';
        $service = explode('.', $scope)[0] ?? static::zoho_oauth_service;

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
            static::token_transient,
            json_encode($data),
            $response['data']['expires_in'] - 10
        );

        return $response['data']['access_token'];
    }

    /**
     * Performs an http request to the Zoho API backend.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    public function submit($payload = [], $attachments = [])
    {
        $credential = $this->credential();
        if (!$credential) {
            return new WP_Error('unauthorized');
        }

        $access_token = $credential->get_access_token();
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
}
