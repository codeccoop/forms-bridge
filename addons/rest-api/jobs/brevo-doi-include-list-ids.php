<?php

function forms_bridge_brevo_doi_include_list_ids($payload)
{
    if (!isset($payload['includeListIds'])) {
        return $payload;
    }

    ['listIds' => $includeListIds] = forms_bridge_brevo_list_ids([
        'listIds' => $payload['includeListIds'],
    ]);

    return array_merge($payload, ['includeListIds' => $includeListIds]);
}

return [
    'title' => __('Brevo DOI list IDs', 'forms-bridge'),
    'description' => __(
        'Formats the submission payload includeListIds field as an array of integers.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_doi_include_list_ids',
    'input' => ['includeListIds'],
    'output' => ['includeListIds'],
];
