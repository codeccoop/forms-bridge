<?php

namespace FORMS_BRIDGE;

use WP_Error;
use Exception;

if (!defined('ABSPATH')) {
    exit();
}

class Oauth_Credential extends Credential
{
    protected const transient = 'forms-bridge-credential-transient';

    public static function schema($addon = null)
    {
        $schema = parent::schema();

        $schema['title'] = 'oauth-credential';

        $schema['properties']['schema']['enum'] = ['Bearer'];
        $schema['properties']['schema']['value'] = 'Bearer';

        $schema['properties']['realm']['name'] = 'scope';

        $schema['properties'] = array_merge($schema['properties'], [
            'access_token' => [
                'type' => 'string',
                'default' => '',
                'public' => false,
            ],
            'expires_at' => [
                'type' => 'integer',
                'default' => 0,
                'public' => false,
            ],
            'refresh_token' => [
                'type' => 'string',
                'default' => '',
                'public' => false,
            ],
            'refresh_token_expires_at' => [
                'type' => 'integer',
                'default' => 0,
                'public' => false,
            ],
        ]);

        $schema['required'] = array_merge($schema['required'], [
            'access_token',
            'refresh_token',
            'expires_at',
        ]);

        if (!$addon) {
            return $schema;
        }

        return apply_filters('forms_bridge_credential_schema', $schema, $addon);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'access_token':
            case 'refresh_token':
                return;
            case 'authorized':
                return $this->is_valid && !empty($this->data['access_token']);
            default:
                return parent::__get($name);
        }
    }

    public function login()
    {
        return $this->get_access_token();
    }

    protected function oauth_service_url($verb)
    {
        throw new Exception('Should be overwriten');
    }

    protected function redirect_uri()
    {
        $base = get_rest_url();
        return $base . "forms-bridge/v1/{$this->addon}/oauth/redirect";
    }

    protected function token_request($query)
    {
        $url = $this->oauth_service_url('token');

        $query['client_id'] = $this->client_id;
        $query['client_secret'] = $this->client_secret;

        $response = http_bridge_post($url, $query, [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = $response['data'];

        if (isset($data['error'])) {
            return new WP_Error($data['error']);
        }

        return $data;
    }

    protected function authorization_code_request($code)
    {
        if (!$this->is_valid) {
            return;
        }

        return $this->token_request([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri(),
        ]);
    }

    protected function revoke_token()
    {
        if (!empty($this->data['refresh_token'])) {
            $url = $this->oauth_service_url('revoke');
            $query = ['token' => $this->data['refresh_token']];

            $response = http_bridge_post($url, $query, [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

            if (is_wp_error($response)) {
                return false;
            }
        }

        if (empty($this->data['access_token'])) {
            return true;
        }

        return $this->update_tokens([
            'access_token' => '',
            'refresh_token' => '',
            'expires_at' => 0,
            'refresh_token_expires_at' => 0,
        ]);
    }

    protected function refresh_token_request()
    {
        if (!$this->is_valid || empty($this->data['refresh_token'])) {
            return;
        }

        return $this->token_request([
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->data['refresh_token'],
        ]);
    }

    protected function update_tokens($tokens)
    {
        $data = $this->data;
        $data['enabled'] = true;
        $data['access_token'] = $tokens['access_token'];
        $data['expires_at'] = $tokens['expires_in'] + time() - 10;

        if (isset($tokens['refresh_token'])) {
            $data['refresh_token'] = $tokens['refresh_token'];

            if (isset($tokens['refresh_token_expires_in'])) {
                $data['refresh_token_expires_at'] =
                    $tokens['refresh_token_expires_in'] + time() - 10;
            }
        }

        $credential = new static($data, $this->addon);
        return $credential->save();
    }

    protected function refresh_access_token()
    {
        if (!$this->is_valid) {
            return;
        }

        $tokens = $this->refresh_token_request();

        if ($this->update_tokens($tokens)) {
            return $tokens['access_token'];
        }
    }

    public function get_access_token()
    {
        if (!$this->is_valid) {
            return;
        }

        $access_token = $this->data['access_token'];
        if (!$access_token) {
            return;
        }

        if ($this->expires_at <= time()) {
            $expires_at = $this->refresh_token_expires_at;
            if ($expires_at && $expires_at <= time()) {
                return;
            }

            return $this->refresh_access_token();
        }

        return $access_token;
    }

    public function oauth_grant()
    {
        if (!$this->is_valid) {
            return new WP_Error('invalid_credential');
        }

        if ($this->authorized) {
            $result = $this->revoke_token();

            if (!$result) {
                return new WP_Error('internal_server_error');
            }

            return;
        }

        $query = http_build_query([
            'client_id' => $this->client_id,
            'scope' => $this->realm,
            'response_type' => 'code',
            'redirect_uri' => $this->redirect_uri(),
            'access_type' => 'offline',
        ]);

        $url = $this->oauth_service_url('auth');

        $response = wp_remote_request($url . '?' . $query, [
            'method' => 'POST',
            'redirection' => 0,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $location = $response['headers']['Location'];

        if (!$location) {
            return new WP_Error('oauth_error');
        }

        set_transient(static::transient, $this->data, 600);

        return ['redirect' => $location];
    }

    public static function oauth_redirect_callback($request, $addon)
    {
        $data = get_transient(static::transient);

        if (!$data) {
            wp_die(__('Invalid oatuh redirect request', 'forms-bridge'));
            return;
        } else {
            delete_transient(static::transient);
        }

        $credential = new static($data, $addon);
        if (!$credential->is_valid) {
            return;
        }

        $token = $credential->authorization_code_request($request['code']);

        if (!$token || is_wp_error($token)) {
            wp_die(__('Invalid oatuh redirect request', 'forms-bridge'));
            return;
        }

        $data['enabled'] = true;
        $data['access_token'] = $token['access_token'];
        $data['refresh_token'] = $token['refresh_token'];
        $data['expires_at'] = $token['expires_in'] + time() - 10;

        if (isset($token['refresh_token_expires_in'])) {
            $data['refresh_token_expires_at'] =
                $token['refresh_token_expires_in'] + time() - 10;
        }

        $credential = new static($data, $addon);
        return $credential->save();
    }
}
