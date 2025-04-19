<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Listmonk_Form_Bridge_Template extends Rest_Form_Bridge_Template
{
    /**
     * Template default config getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return forms_bridge_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#backend',
                        'name' => 'name',
                        'description' => __(
                            'Label of the Listmonk API backend connection',
                            'forms-bridge'
                        ),
                        'default' => 'Listmonk API',
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'api_user',
                        'label' => __('API user', 'forms-bridge'),
                        'description' => __(
                            'You have to generate an API user on your listmonk instance. See the <a href="https://listmonk.app/docs/roles-and-permissions/#api-users">documentation</a> for more information',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#backend/headers[]',
                        'name' => 'token',
                        'label' => __('API user token', 'forms-bridge'),
                        'description' => __(
                            'Token of the API user. The token will be shown only once on user creation time, be sure to copy its value and store it in a save place',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                    [
                        'ref' => '#bridge',
                        'name' => 'method',
                        'default' => 'POST',
                    ],
                    [
                        'ref' => '#bridge/custom_fields[]',
                        'name' => 'lists',
                        'label' => __('Mailing lists', 'forms-bridge'),
                        'description' => __(
                            'Select, at least, one list that users will subscribe to',
                            'forms-bridge'
                        ),
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
                'bridge' => [
                    'backend' => 'Listmonk',
                    'endpoint' => '',
                    'method' => 'POST',
                ],
                'backend' => [
                    'name' => 'Listmonk',
                    'headers' => [
                        [
                            'name' => 'Content-Type',
                            'value' => 'application/json',
                        ],
                        [
                            'name' => 'Accept',
                            'value' => 'application/json',
                        ],
                    ],
                ],
            ],
            parent::defaults(),
            self::$schema
        );
    }
}
