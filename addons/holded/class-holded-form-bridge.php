<?php

namespace FORMS_BRIDGE;

use TypeError;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the REST API protocol.
 */
class Holded_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'holded';

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = ['accept', 'content-type', 'key'];

    /**
     * Gets bridge's default body encoding schema.
     *
     * @return string|null
     */
    protected function content_type()
    {
        return 'application/json';
    }

    /**
     * Bridge's endpoint fields schema getter.
     *
     * @return array
     */
    protected function api_schema()
    {
        $chunks = array_values(array_filter(explode('/', $this->endpoint)));
        if (empty($chunks)) {
            return [];
        }

        $api_base = $chunks[0];
        if ($api_base !== 'api') {
            array_unshift($chunks, 'api');
        }

        [, $module, $version, $resource] = $chunks;

        if (
            !in_array($module, [
                'invoicing',
                'crm',
                'projects',
                'team',
                'accounting',
            ]) ||
            $version !== 'v1'
        ) {
            return [];
        }

        $path = plugin_dir_path(__FILE__) . "/data/swagger/{$module}.json";
        if (!is_file($path)) {
            return [];
        }

        $file_content = file_get_contents($path);
        try {
            $paths = json_decode($file_content, true);
        } catch (TypeError) {
            return [];
        }

        $path = '/' . $resource;
        if (!isset($paths[$path])) {
            return [];
        }

        $schema = $paths[$path];
        if (!isset($schema[strtolower($this->method)])) {
            return [];
        }

        $schema = $schema[strtolower($this->method)];

        $fields = [];
        if (isset($schema['parameters'])) {
            foreach ($schema['parameters'] as $param) {
                $fields[] = [
                    'name' => $param['name'],
                    'schema' => $param['schema'],
                ];
            }
        } elseif (
            isset(
                $schema['requestBody']['content']['application/json']['schema'][
                    'properties'
                ]
            )
        ) {
            $properties =
                $schema['requestBody']['content']['application/json']['schema'][
                    'properties'
                ];
            foreach ($properties as $name => $schema) {
                $fields[] = [
                    'name' => $name,
                    'schema' => $schema,
                ];
            }
        }

        return $fields;
    }

    /**
     * Filters HTTP request args just before it is sent.
     *
     * @param array $request Request arguments.
     *
     * @return array
     */
    public static function do_filter_request($request)
    {
        $headers = &$request['args']['headers'];
        foreach ($headers as $name => $value) {
            unset($headers[$name]);
            $headers[strtolower($name)] = $value;
        }

        return $request;
    }
}
