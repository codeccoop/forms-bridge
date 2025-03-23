<?php

function forms_bridge_brevo_list_ids($payload)
{
    if (!isset($payload['listIds'])) {
        return $payload;
    }

    $list_ids = is_string($payload['listIds'])
        ? explode(',', $payload['listIds'])
        : (array) $payload['listIds'];

    $payload['listIds'] = array_filter(array_map('intval', $list_ids));

    return $payload;
}

return [
    'title' => __('Brevo list IDs', 'forms-bridge'),
    'description' => __(
        'Formats the submission payload listIds field as an array of integers.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_list_ids',
    'input' => ['listIds'],
    'output' => ['listIds'],
];
