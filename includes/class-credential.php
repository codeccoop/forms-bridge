<?php

namespace FORMS_BRIDGE;

use FBAPI;

if (!defined('ABSPATH')) {
    exit();
}

class Credential
{
    public static function schema($addon = null)
    {
        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'credential',
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
                'schema' => [
                    'type' => 'string',
                    'enum' => ['Basic', 'Digest', 'Token', 'URL'],
                    'default' => 'Basic',
                ],
                'client_id' => ['type' => 'string'],
                'client_secret' => ['type' => 'string'],
                'realm' => ['type' => 'string'],
                'is_valid' => [
                    'description' => __(
                        'Validation result of the bridge setting',
                        'forms-bridge'
                    ),
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
            'required' => [
                'name',
                'schema',
                'client_id',
                'client_secret',
                'is_valid',
            ],
            'additionalProperties' => false,
        ];

        if (!$addon) {
            return $schema;
        }

        return apply_filters('forms_bridge_credential_schema', $schema, $addon);
    }

    /**
     * Ephemeral credential registration as an interceptor to allow
     * api fetch, ping and introspection with non registered credentials.
     *
     * @param array $data Credential data.
     * @param string $addon Addon name.
     */
    public static function temp_registration($data, $addon)
    {
        if (empty($data) || !isset($data['name'])) {
            return;
        }

        add_filter(
            'forms_bridge_credentials',
            static function ($credentials, $ns) use ($data, $addon) {
                if ($ns && $ns !== $addon) {
                    return $credentials;
                }

                foreach ($credentials as $candidate) {
                    if ($candidate->name === $data['name']) {
                        $credential = $candidate;
                    }
                }

                $addon = FBAPI::get_addon($addon);

                if (!isset($credential)) {
                    $credential_class = $addon::credential_class;
                    $data['is_valid'] = true;
                    $credential = new $credential_class($data, $addon::name);

                    if ($credential->is_valid) {
                        $credentials[] = $credential;
                    }
                }

                return $credentials;
            },
            99,
            2
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
                    $this->data['is_valid'] &&
                    Addon::addon($this->addon) !== null;
            default:
                if (!$this->is_valid) {
                    return;
                }

                return $this->data[$name] ?? null;
        }
    }

    public function login()
    {
        return implode(
            ':',
            array_map(
                'trim',
                array_filter([
                    $this->client_id,
                    $this->realm,
                    $this->client_secret,
                ])
            )
        );
    }

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
