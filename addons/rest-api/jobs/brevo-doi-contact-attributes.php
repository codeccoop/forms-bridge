<?php

function forms_bridge_brevo_doi_contact_attributes($payload, $bridge)
{
    return forms_bridge_brevo_contact_attributes($payload, $bridge, [
        'email',
        'includeListIds',
        'redirectionUrl',
        'templateId',
        'attributes',
    ]);
}

return [
    'title' => __('Brevo DOI contact attributes', 'forms-bridge'),
    'description' => __(
        'Formats the submission payload and place all non well known fields as uppercased attributes.',
        'forms-bridge'
    ),
    'method' => 'forms_bridge_brevo_doi_contact_attributes',
    'input' => [],
    'output' => ['attributes'],
];
