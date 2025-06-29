<?php

if (!defined('ABSPATH')) {
    exit();
}

$setting_names = ['rest-api', 'odoo', 'financoop', 'google-sheets'];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;

    $data = get_option($option, []);

    if (!isset($data['bridges'])) {
        continue;
    }

    foreach ($data['bridges'] as &$bridge_data) {
        $pipes = $bridge_data['pipes'] ?? [];
        unset($bridge_data['pipes']);

        if (
            !isset($bridge_data['mappers']) ||
            !wp_is_numeric_array($bridge_data['mappers'])
        ) {
            $bridge_data['mappers'] = $pipes;
        }
    }

    update_option($option, $data);
}
