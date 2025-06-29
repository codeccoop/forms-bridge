<?php

if (!defined('ABSPATH')) {
    exit();
}

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
        if (!isset($bridge_data['workflow'])) {
            if (!empty($bridge_data['template'])) {
                $template = apply_filters(
                    'forms_bridge_template',
                    null,
                    $bridge_data['template']
                );

                if ($template) {
                    $bridge_data['workflow'] =
                        $template->bridge['workflow'] ?? [];
                }
            }
        }

        $bridge_data['workflow'] = $bridge_data['workflow'] ?? [];

        if (!isset($bridge_data['mutations'])) {
            $mappers = $bridge_data['mappers'] ?? [];
            $mutations = [$mappers];
        } else {
            $mutations = $bridge_data['mutations'];
        }

        for (
            $i = count($mutations);
            $i <= count($bridge_data['workflow']);
            $i++
        ) {
            $mutations[] = [];
        }

        $bridge_data['mutations'] = $mutations;
        unset($bridge_data['mappers']);
    }

    update_option($option, $data);
}
