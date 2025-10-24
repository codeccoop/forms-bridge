<?php

return [
    'title' => __('Job position', 'forms-bridge'),
    'description' => __(
        'Job application form. The resulting bridge will convert form submissions into applications to a job from the Human Resources module',
        'forms-bridge'
    ),
    'fields' => [
        [
            'ref' => '#form',
            'name' => 'title',
            'default' => __('Job position', 'forms-bridge'),
        ],
        [
            'ref' => '#bridge',
            'name' => 'endpoint',
            'value' => 'hr.applicant',
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'user_id',
            'label' => __('Owner', 'forms-bridge'),
            'description' => __(
                'Name of the owner user of the application',
                'forms-bridge'
            ),
            'type' => 'select',
            'options' => [
                'endpoint' => 'res.users',
                'finger' => [
                    'value' => 'result.[].id',
                    'label' => 'result.[].name',
                ],
            ],
        ],
        [
            'ref' => '#bridge/custom_fields[]',
            'name' => 'job_id',
            'label' => __('Job position', 'forms-bridge'),
            'type' => 'select',
            'options' => [
                'endpoint' => 'hr.job',
                'finger' => [
                    'value' => 'result.[].id',
                    'label' => 'result.[].name',
                ],
            ],
        ],
    ],
    'bridge' => [
        'endpoint' => 'hr.applicant',
        'mutations' => [
            [
                [
                    'from' => 'your-name',
                    'to' => 'name',
                    'cast' => 'string',
                ],
                [
                    'from' => 'name',
                    'to' => 'partner_name',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'your-email',
                    'to' => 'email',
                    'cast' => 'string',
                ],
                [
                    'from' => 'email',
                    'to' => 'email_from',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'your-phone',
                    'to' => 'phone',
                    'cast' => 'string',
                ],
                [
                    'from' => 'phone',
                    'to' => 'partner_phone',
                    'cast' => 'copy',
                ],
                [
                    'from' => 'user_id',
                    'to' => 'user_id',
                    'cast' => 'integer',
                ],
                [
                    'from' => 'job_id',
                    'to' => 'job_id',
                    'cast' => 'integer',
                ],
            ],
            [],
            [],
            [
                [
                    'from' => 'curriculum',
                    'to' => 'curriculum',
                    'cast' => 'null',
                ],
                [
                    'from' => 'curriculum_filename',
                    'to' => 'curriculum_filename',
                    'cast' => 'null',
                ],
            ],
        ],
        'workflow' => ['contact', 'candidate', 'attachments'],
    ],
    'form' => [
        'title' => 'Job position',
        'fields' => [
            [
                'name' => 'your-name',
                'label' => __('Your name', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'your-email',
                'label' => __('Your email', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'your-phone',
                'label' => __('Your phone', 'forms-bridge'),
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'applicant_notes',
                'label' => __('Description', 'forms-bridge'),
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'name' => 'curriculum',
                'label' => __('Curriculum', 'forms-brirdge'),
                'type' => 'file',
                'required' => true,
            ],
        ],
    ],
];
