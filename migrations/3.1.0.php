<?php

$setting_names = [
    'rest-api',
    'dolibarr',
    'odoo',
    'financoop',
    'google-sheets',
    'zoho',
];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;

    $data = get_option($option, []);

    if (!isset($data['bridges'])) {
        continue;
    }

    foreach ($data['bridges'] as &$bridge_data) {
        $workflows = $bridge_data['workflows'] ?? [];
        $bridge_data['workflows'] = $workflows;
    }

    update_option($option, $data);
}
