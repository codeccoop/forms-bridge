<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Form bridge implamentation for the Bigin API protocol.
 */
class Bigin_Form_Bridge extends Zoho_Form_Bridge
{
    /**
     * Handles bridge class API name.
     *
     * @var string
     */
    protected $api = 'bigin';

    protected function api_schema()
    {
        $original_scope = $this->scope;
        $this->data['scope'] = 'ZohoBigin.settings.layouts.READ';
        $access_token = $this->get_access_token();
        $this->data['scope'] = $original_scope;

        if (empty($access_token)) {
            return [];
        }

        if (!preg_match('/\/([A-Z].+$)/', $this->endpoint, $matches)) {
            return [];
        }

        $module = str_replace('/upsert', '', $matches[1]);

        $response = $this->backend->get(
            '/bigin/v2/settings/layouts',
            [
                'module' => $module,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
            ]
        );

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
