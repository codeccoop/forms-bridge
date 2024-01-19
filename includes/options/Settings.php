<?php

namespace WPCT_ERP_FORMS\Options;

use WPCT_ERP_FORMS\Options\BaseSettings;

require_once 'BaseSettings.php';

class Settings extends BaseSettings
{

    public $_name = 'wpct_erp_forms';
    private $_default_endpoint = '/api/private/crm-lead';

    public function register()
    {
        /* General group  */
        register_setting(
            $this->_name,
            'wpct_erp_forms_general',
            [
                'type' => 'array',
                'description' => __('Configuració global dels formularis', 'wpct-erp-forms'),
                'show_in_rest' => false,
                'default' => [
                    'coord_id' => 0,
                    'notification_receiver' => 'admin@example.com',
                ],
            ]
        );

        /* General section */
        add_settings_section(
            'wpct_erp_forms_general_section',
            __('General', 'wpct-erp-forms'),
            function () {
                echo '<p>' . __('General settings', 'wpct-erp-forms') . '</p>';
            },
            $this->_name,
        );

        /* General fields */
        add_settings_field(
            'notification_receiver',
            __('Error notification receiver', 'wpct-erp-forms'),
            function () {
                echo $this->field_render('wpct_erp_forms_general', 'notification_receiver');
            },
            $this->_name,
            'wpct_erp_forms_general_section'
        );

        add_settings_field(
            'coord_id',
            __('ID de la coordinadora', 'wpct-erp-forms'),
            function () {
                echo $this->field_render('wpct_erp_forms_general', 'coord_id');
            },
            $this->_name,
            'wpct_erp_forms_general_section'
        );

        /* API group */
        register_setting(
            $this->_name,
            'wpct_erp_forms_api',
            [
                'type' => 'array',
                'description' => __('Configuració de la API dels formularis', 'wpct-erp-forms'),
                'show_in_rest' => false,
                'default' => [
                    'endpoints' => [
                        [
                            'form_id' => 0,
                            'endpoint' => $this->_default_endpoint
                        ],
                    ],
                ]
            ]
        );

        /* API section */
        add_settings_section(
            'wpct_erp_forms_api_section',
            __('API', 'wpct-erp-forms'),
            function () {
                echo '<p>' . __('API settings', 'wpct-erp-forms') . '</p>';
            },
            $this->_name,
        );

        /* API fields */
        add_settings_field(
            'api_endpoints',
            __('Endpoints', 'wpct-erp-forms'),
            function () {
                echo $this->field_render('wpct_erp_forms_api', 'endpoints');
            },
            $this->_name,
            'wpct_erp_forms_api_section',
            [
                'class' => 'wpct_erp_forms_api_endpoints'
            ]
        );
    }

    public function default_values()
    {
        return [
            'form_id' => 0,
            'endpoint' => $this->_default_endpoint
        ];
    }
}
