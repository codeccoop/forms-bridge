<?php

if (!defined('ABSPATH')) {
    exit();
}

return [
    'title' => __('Attachments', 'forms-bridge'),
    'description' => __(
        'Gets submission uploads and sets them as attached files to the newly created model',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_odoo_bridge_attachments',
    'input' => [],
    'output' => [],
];

function forms_bridge_odoo_bridge_attachments($payload, $bridge)
{
    if (!in_array($bridge->method, ['write', 'create'], true)) {
        return $payload;
    }

    $uploads = FBAPI::get_uploads();

    if (empty($uploads)) {
        return $payload;
    }

    $attachments = [];

    foreach ($uploads as $name => $upload) {
        if ($upload['is_multi']) {
            $len = count($uploads[$name]['path']);
            for ($i = 1; $i <= $len; ++$i) {
                $attachments[$name . '_' . $i] = $upload['path'][$i - 1];
            }
        } else {
            $attachments[$name] = $upload['path'];
        }
    }

    add_action(
        'forms_bridge_after_submission',
        function ($bridge, $response) use ($payload, $attachments) {
            $res_id = $response['data']['result'];
            $res_model = $bridge->endpoint;

            foreach ($attachments as $filename => $path) {
                $content_field = $filename;
                $name_field = $filename . '_filename';
                $mimetype = mime_content_type($path);

                if (!isset($payload[$content_field], $payload[$name_field])) {
                    continue;
                }

                $response = $bridge
                    ->patch([
                        'name' => '__odoo-ir-attachments',
                        'endpoint' => 'ir.attachment',
                        'method' => 'create',
                    ])
                    ->submit([
                        'name' => $payload[$name_field],
                        'datas' => $payload[$content_field],
                        'res_id' => $res_id,
                        'res_model' => $res_model,
                        'mimetype' => $mimetype,
                    ]);

                if (is_wp_error($response)) {
                    do_action(
                        'forms_bridge_on_failure',
                        $response,
                        $bridge,
                        $payload,
                        $attachments
                    );

                    return;
                }
            }
        },
        20,
        2
    );

    return $payload;
}
