<?php

namespace FORMS_BRIDGE;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

class Zoho_Credential extends Credential
{
    protected const zoho_oauth_app = 'ZohoCRM';

    private const transient = 'forms-bridge-zoho-credential';

    private function auth_request($query)
    {
        $url = "https://accounts.{$this->region}/oauth/v2/token";

        $response = http_bridge_post($url . '?' . http_build_query($query));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = $response['data'];

        if (isset($data['error'])) {
            return new WP_Error($data['error']);
        }

        return $data;
    }

    private function self_client_auth()
    {
        if (!$this->is_valid || empty($this->data['organization_id'])) {
            return;
        }

        return $this->auth_request([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $this->scope,
            'soid' => static::zoho_oauth_app . '.' . $this->organization_id,
            'grant_type' => 'client_credentials',
        ]);
    }

    private function authorization_code_auth($code)
    {
        if (!$this->is_valid) {
            return;
        }

        $rest_url = get_rest_url();
        return $this->auth_request([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $rest_url . 'forms-bridge/v1/zoho/oauth/redirect',
        ]);
    }

    private function refresh_token_auth()
    {
        if (!$this->is_valid || empty($this->data['refresh_token'])) {
            return;
        }

        return $this->auth_request([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->data['refresh_token'],
        ]);
    }

    private function update_tokens($tokens)
    {
        $data = $this->data;
        $data['enabled'] = true;
        $data['access_token'] = $tokens['access_token'];
        $data['expires_at'] = $tokens['expires_in'] + time() - 10;
        $data['refresh_token'] =
            $tokens['refresh_token'] ?? $data['refresh_token'] ?: '';

        $credential = new static($data, $this->addon);
        return $credential->save();
    }

    protected function refresh_access_token()
    {
        if (!$this->is_valid) {
            return;
        }

        if ($this->type === 'Self Client') {
            $tokens = $this->self_client_auth();
        } else {
            $tokens = $this->refresh_token_auth();
        }

        if (!$tokens || is_wp_error($tokens)) {
            return;
        }

        if ($this->update_tokens($tokens)) {
            return $tokens['access_token'];
        }
    }

    public function oauth_grant()
    {
        if (!$this->is_valid) {
            return;
        }

        $redirect =
            site_url() .
            '/wp-admin/options-general.php?page=forms-bridge&tab=zoho';

        if ($this->authorized) {
            $result = $this->revoke_token();

            if (!$result) {
                return new WP_Error('internal_server_error');
            }

            return $redirect;
        }

        $access_token = $this->get_access_token();

        if ($access_token) {
            return $redirect;
        }

        if ($this->type === 'Self Client') {
            $tokens = $this->self_client_auth();

            if (!$tokens || is_wp_error($tokens)) {
                return;
            }

            if (!$this->update_tokens($tokens)) {
                return;
            }

            return $redirect;
        }

        $rest_url = get_rest_url();
        $query = http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $this->scope,
            'response_type' => 'code',
            'redirect_uri' => $rest_url . 'forms-bridge/v1/zoho/oauth/redirect',
            'access_type' => 'offline',
        ]);

        $url = "https://accounts.{$this->region}/oauth/v2/auth";

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

        set_transient(self::transient, $this->data, 600);

        return $location;
    }

    public static function oauth_redirect_callback($request, $addon)
    {
        $data = get_transient(self::transient);

        if (!$data) {
            return;
        } else {
            delete_transient(self::transient);
        }

        $credential = new static($data, $addon);
        if (!$credential->is_valid) {
            return;
        }

        $token = $credential->authorization_code_auth($request['code']);

        if (!$token || is_wp_error($token)) {
            return;
        }

        $data['enabled'] = true;
        $data['access_token'] = $token['access_token'];
        $data['refresh_token'] = $token['refresh_token'];
        $data['expires_at'] = $token['expires_in'] + time() - 10;

        $credential = new static($data, $addon);
        return $credential->save();
    }
}
