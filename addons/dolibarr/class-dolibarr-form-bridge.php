<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Dolibarr REST API.
 */
class Dolibarr_Form_Bridge extends Rest_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'dolibarr';

    /**
     * Handles the array of accepted HTTP header names of the bridge API.
     *
     * @var array<string>
     */
    protected static $api_headers = ['DOLAPIKEY', 'Accept', 'Content-Type'];

    /**
     * Returns json as static bridge content type.
     *
     * @return string.
     */
    protected function content_type()
    {
        return 'application/json';
    }

    protected function api_schema()
    {
        $backend = $this->backend();

        $res = $backend->get($this->endpoint, ['limit' => 1]);

        if (is_wp_error($res)) {
            return [];
        }

        $entry = $res['data'][0] ?? null;
        if (empty($entry)) {
            return [];
        }

        $fields = [];
        foreach ($entry as $field => $value) {
            if (wp_is_numeric_array($value)) {
                $type = 'array';
            } elseif (is_array($value)) {
                $type = 'object';
            } elseif (is_double($value)) {
                $type = 'number';
            } elseif (is_int($value)) {
                $type = 'integer';
            } else {
                $type = 'string';
            }

            $fields[] = [
                'name' => $field,
                'schema' => ['type' => $type],
            ];
        }

        return $fields;
    }
}
