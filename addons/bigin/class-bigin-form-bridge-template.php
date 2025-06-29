<?php

namespace FORMS_BRIDGE;

if (!defined('ABSPATH')) {
    exit();
}

class Bigin_Form_Bridge_Template extends Zoho_Form_Bridge_Template
{
    /**
     * Handles the template api name.
     *
     * @var string
     */
    protected $api = 'bigin';

    /**
     * Template default config getter.
     *
     * @return array
     */
    protected static function defaults()
    {
        return wpct_plugin_merge_object(
            [
                'fields' => [
                    [
                        'ref' => '#bridge',
                        'name' => 'scope',
                        'value' => 'ZohoBigin.modules.ALL',
                    ],
                ],
                'bridge' => [
                    'backend' => 'Zoho API',
                    'scope' => 'ZohoBigin.modules.ALL',
                ],
            ],
            parent::defaults(),
            self::schema()
        );
    }
}
