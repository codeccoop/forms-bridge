<?php

namespace WPCT_ERP_FORMS\Options;

use WPCT_ERP_FORMS\Options\BaseSettings;

require_once 'BaseSettings.php';

class Settings extends BaseSettings
{
    public $group_name = 'wpct_erp_forms';
    private $_default_endpoint = '/api/private/crm-lead';

    public function register()
    {
        $setting_name = $this->group_name . '_general';
        $this->register_setting(
            $setting_name,
            [
                'notification_receiver' => 'admin@example.com'
            ]
        );

        $this->register_field('notification_receiver', $setting_name);

        $setting_name = $this->group_name . '_api';
        $this->register_setting(
            $setting_name,
            [
                'endpoints' => [
                    [
                        'form_id' => 0,
                        'endpoint' => $this->_default_endpoint,
                    ]
                ],
            ],
        );

        $this->register_field('endpoints', $setting_name);
    }
}
