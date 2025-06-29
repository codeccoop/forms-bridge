<?php

if (!defined('ABSPATH')) {
    exit();
}

$setting_names = ['rest-api', 'odoo', 'financoop', 'google-sheets'];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;
    $deprecated_option = $option . '-api';

    $data = get_option($deprecated_option);
    if (is_array($data)) {
        update_option($option, $data);
        delete_option($deprecated_option);
    }

    $data = get_option($option, []);

    if (isset($data['form_hooks'])) {
        $data['bridges'] = $data['form_hooks'];
        unset($data['form_hooks']);
        update_option($option, $data);
    }
}
