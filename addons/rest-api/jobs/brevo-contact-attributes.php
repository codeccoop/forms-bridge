<?php

function forms_bridge_brevo_contact_attributes(
    $payload,
    $bridge,
    $schema = ['listIds', 'email', 'attributes']
) {
    $payload['attributes'] = $payload['attributes'] ?? [];

    foreach ($payload as $field => $value) {
        if (!in_array($field, $schema)) {
            $payload['attributes'][strtoupper($field)] = $value;
            unset($payload[$field]);
        }
    }

    return $payload;
}

return [
    'title' => __('Brevo list IDs', 'forms-bridge'),
    'description' => __(
        'Formats the submission payload and place all non well known fields as uppercased attributes.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_contact_attributes',
    'input' => [],
    'output' => ['attributes'],
];
