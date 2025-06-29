<?php

if (!defined('ABSPATH')) {
    exit();
}

$setting_names = [
    'bigin',
    'brevo',
    'dolibarr',
    'financoop',
    'gsheets',
    'holded',
    'listmonk',
    'mailchimp',
    'odoo',
    'rest-api',
    'zoho',
];

foreach ($setting_names as $setting_name) {
    $option = 'forms-bridge_' . $setting_name;

    $data = get_option($option, []);

    if (!isset($data['bridges'])) {
        continue;
    }

    foreach ($data['bridges'] as &$bridge_data) {
        if (!isset($bridge_data['workflow'])) {
            continue;
        }

        $i = 0;
        while ($i < count($bridge_data['workflow'])) {
            $job_name = $bridge_data['workflow'][$i];

            if (strpos('forms-bridge-', $job_name) === 0) {
                $job_name = substr($job_name, 13);
            } elseif (strpos($job_name, $setting_name) === 0) {
                $job_name = substr($job_name, strlen($setting_name) + 1);
            }

            $bridge_data['workflow'][$i] = $job_name;
            $i++;
        }
    }

    update_option($option, $data);
}
