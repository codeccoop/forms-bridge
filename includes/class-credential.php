<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Credential
{
    public static function schema($addon = null)
    {
        return apply_filters(
            'forms_bridge_credential_schema',
            [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'credential-schema',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'name' => __('Name', 'forms-bridge'),
                        'description' => __(
                            'Unique name of the credential',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'enabled' => [
                        'description' => __(
                            'Boolean flag to enable/disable a bridge',
                            'forms-bridge'
                        ),
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'is_valid' => [
                        'description' => __(
                            'Validation result of the bridge setting',
                            'forms-bridge'
                        ),
                        'type' => 'boolean',
                        'default' => false,
                    ],
                ],
                'required' => ['name', 'enabled', 'is_valid'],
                'additionalProperties' => false,
            ],
            $addon
        );
    }

    protected $data;

    protected $id;

    protected $addon;

    public function __construct($data, $addon)
    {
        $this->addon = $addon;
        $this->data = wpct_plugin_sanitize_with_schema(
            $data,
            static::schema($addon)
        );

        if ($this->is_valid) {
            $this->id = $addon . '-' . $data['name'];
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->id;
            case 'addon':
                return $this->addon;
            case 'is_valid':
                return !is_wp_error($this->data) &&
                    Addon::addon($this->addon) !== null;
            default:
                if (!$this->is_valid) {
                    return;
                }

                return $this->data[$name] ?? null;
        }
    }

    protected function refresh_access_token()
    {
        $refresh_token = $this->refresh_token;
        if (!$refresh_token) {
            return;
        }
    }

    public function get_access_token()
    {
        if (!$this->is_valid) {
            return;
        }

        $access_token = $this->access_token;
        if (!$access_token) {
            return;
        }

        if ($this->expires_at > time()) {
            $access_token = $this->refresh_access_token();
        }

        return $access_token;
    }

    public function oauth_grant() {}

    public function data()
    {
        if (!$this->is_valid) {
            return;
        }

        return array_merge(
            [
                'id' => $this->id,
                'name' => $this->name,
                'addon' => $this->addon,
            ],
            $this->data
        );
    }

    public function save()
    {
        if (!$this->is_valid) {
            return false;
        }

        $setting = Forms_Bridge::setting($this->addon);
        if (!$setting) {
            return false;
        }

        $credentials = $setting->credentials;
        if (!wp_is_numeric_array($credentials)) {
            return false;
        }

        $index = array_search($this->name, array_column($credentials, 'name'));

        if ($index === false) {
            $credentials[] = $this->data;
        } else {
            $credentials[$index] = $this->data;
        }

        $setting->credentials = $credentials;

        return true;
    }

    public function delete()
    {
        if ($this->is_valid) {
            return false;
        }

        $setting = Forms_Bridge::setting($this->addon);
        if (!$setting) {
            return false;
        }

        $credentials = $setting->credentials;
        if (!wp_is_numeric_array($credentials)) {
            return false;
        }

        $index = array_search($this->name, array_column($credentials, 'name'));

        if ($index === false) {
            return false;
        }

        array_splice($credentials, $index, 1);
        $setting->credentials = $credentials;

        return true;
    }
}
