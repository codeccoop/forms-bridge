<?php

$setting_names = ['rest-api', 'odoo', 'financoop', 'google-sheets'];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;

    $data = get_option($option, []);

    if (!isset($data['bridges'])) {
        continue;
    }

    foreach ($data['bridges'] as &$bridge_data) {
        $bridge_data['mappers'] = $bridge_data['pipes'];
        unset($bridge_data['pipes']);
    }

    update_option($option, $data);
}
