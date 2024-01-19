<?php

namespace WPCT_ERP_FORMS\Settings;

class Setting
{

    private $_name = 'wpct_erp_forms';
    private $_default_endpoint = '/api/private/crm-lead';

    public function getName()
    {
        return $this->_name;
    }

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
                echo $this->control_render('wpct_erp_forms_api', 'endpoints');
            },
            $this->_name,
            'wpct_erp_forms_api_section',
            [
                'class' => 'wpct_erp_forms_api_endpoints'
            ]
        );
    }

    private function field_render($setting, $field, $value = null)
    {
        if ($value === null) $value = $this->option_getter($setting, $field);

        if (!is_array($value)) {
            return $this->input_render($setting, $field, $value);
        } else {
            return $this->fieldset_render($setting, $field, $value);
        }
    }

    private function input_render($setting, $field, $value)
    {
        return "<input type='text' name='{$setting}[{$field}]' value='{$value}' />";
    }

    private function fieldset_render($setting, $field, $data)
    {
        $fieldset = "<table id='{$setting}[{$field}]'>";
        $is_list = is_list($data);
        foreach (array_keys($data) as $key) {
            $fieldset .= '<tr>';
            if (!$is_list) $fieldset .= "<th>{$key}</th>";
            $_field = $field . '][' . $key;
            $fieldset .= "<td>{$this->field_render($setting,$_field,$data[$key])}</td>";
            $fieldset .= '</tr>';
        }
        $fieldset .= '</table>';

        return $fieldset;
    }

    private function control_render($setting, $field)
    {
        ob_start();
?>
        <div class="<?= $setting; ?>[<?= $field ?>]--controls">
            <button class="button button-primary" data-action="add">Add</button>
            <button class="button button-secondary" data-action="remove">Remove</button>
        </div>
        <script>
            <?php include 'fieldsetControl.js' ?>
        </script>
<?php
        return ob_get_clean();
    }

    private function option_getter($setting, $option)
    {
        $setting = get_option($setting) ? get_option($setting) : [];
        if (!key_exists($option, $setting)) return null;
        return $setting[$option];
    }
}

function is_list($arr)
{
    if (!is_array($arr)) return false;
    if (sizeof($arr) === 0) return true;
    return array_keys($arr) === range(0, count($arr) - 1);
}
