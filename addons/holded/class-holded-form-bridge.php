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
     * Performs an http request to backend's REST API.
     *
     * @param array $payload Payload data.
     * @param array $attachments Submission's attached files.
     *
     * @return array|WP_Error Http request response.
     */
    protected function do_submit($payload, $attachments = [])
    {
        $response = parent::do_submit($payload, $attachments);

        if (is_wp_error($response)) {
            $error_response = $response->get_error_data()['response'];
            if (
                $error_response['response']['code'] !== 425 &&
                $error_response['response']['code'] !== 400
            ) {
                return $response;
            }

            $data = json_decode($error_response['body'], true);
            if ($data['code'] !== 'duplicate_parameter') {
                return $response;
            }

            if (
                !isset($payload['email']) ||
                strstr($this->endpoint, '/v3/contacts') === false
            ) {
                return $response;
            }

            $update_response = $this->patch([
                'name' => 'holded-update-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}?identifierType=email_id",
                'method' => 'PUT',
            ])->submit($payload);

            if (is_wp_error($update_response)) {
                return $update_response;
            }

            return $this->patch([
                'name' => 'holded-search-contact-by-email',
                'endpoint' => "/v3/contacts/{$payload['email']}",
                'method' => 'GET',
            ])->submit(['identifierType' => 'email_id']);
        }

        return $response;
    }

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
