<?php

namespace WPCT_ERP_FORMS\Abstract;

class Undefined
{
};

abstract class Settings extends Singleton
{
    protected $group_name;
    private $_defaults = [
        'wpct-erp-forms_general' => [
            'notification_receiver' => 'admin@example.coop'
        ],
        'wpct-erp-forms_api' => [
            'endpoints' => [
                [
                    'form_id' => 0,
                    'endpoint' => '/api/private/crm-lead',
                ]
            ]
        ]
    ];

    abstract public function register();

    public function __construct($textdomain)
    {
        $this->group_name = $textdomain;
    }

    public function get_name()
    {
        return $this->group_name;
    }

    public function register_setting($name)
    {
        $defaults = $this->get_defaults($name);
        register_setting(
            $this->group_name,
            $name,
            [
                'type' => 'array',
                'show_in_rest' => false,
                'default' => $defaults,
            ],
        );

        add_settings_section(
            $name . '_section',
            __($name . '--title', $this->group_name),
            function () use ($name) {
                $title = __($name . '--description', $this->group_name);
                echo "<p>{$title}</p>";
            },
            $this->group_name,
        );

        $this->_defaults[$name] = $defaults;

        foreach (array_keys($defaults) as $field) {
            $this->register_field($field, $name);
        }
    }

    public function register_field($field_name, $setting_name)
    {
        $field_id = $setting_name . '__' . $field_name;
        add_settings_field(
            $field_name,
            __($field_id . '--label', $this->group_name),
            function () use ($setting_name, $field_name) {
                echo $this->field_render($setting_name, $field_name);
            },
            $this->group_name,
            $setting_name . '_section',
            [
                'class' => $field_id,
            ]
        );
    }

    public function field_render()
    {
        $args = func_get_args();
        $setting = $args[0];
        $field = $args[1];
        if (count($args) >= 3) {
            $value = $args[2];
        } else {
            $value = new Undefined();
        }

        return $this->_field_render($setting, $field, $value);
    }

    private function _field_render($setting, $field, $value)
    {
        $is_root = false;
        if ($value instanceof Undefined) {
            $value = $this->option_getter($setting, $field);
            $is_root = true;
        }

        if (!is_array($value)) {
            return $this->input_render($setting, $field, $value);
        } else {
            $fieldset = $this->fieldset_render($setting, $field, $value);
            if ($is_root) {
                $fieldset = $this->control_style($setting, $field)
                    . $fieldset . $this->control_render($setting, $field);
            }

            return $fieldset;
        }
    }

    public function input_render($setting, $field, $value)
    {
        $default_value = $this->get_defaults($setting, $field);
        $keys = explode('][', $field);
        $is_list = is_list($default_value);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            if ($is_list) {
                $key = (int) $key;
            }
            $default_value = isset($default_value[$key]) ? $default_value[$key] : $default_value[0];
            $is_list = is_list($default_value);
        }
        $is_bool = is_bool($default_value);
        if ($is_bool) {
            $is_bool = true;
            $value = 'on' === $value;
        }

        if ($is_bool) {
            return "<input type='checkbox' name='{$setting}[$field]' " . ($value ? 'checked' : '') . " />";
        } else {
            return "<input type='text' name='{$setting}[{$field}]' value='{$value}' />";
        }
    }

    public function fieldset_render($setting, $field, $data)
    {
        $table_id = $setting . '__' . str_replace('][', '_', $field);
        $fieldset = "<table id='{$table_id}'>";
        $is_list = is_list($data);
        foreach (array_keys($data) as $key) {
            $fieldset .= '<tr>';
            if (!$is_list) {
                $fieldset .= "<th>{$key}</th>";
            }
            $_field = $field . '][' . $key;
            $fieldset .= "<td>{$this->field_render($setting, $_field, $data[$key])}</td>";
            $fieldset .= '</tr>';
        }
        $fieldset .= '</table>';

        return $fieldset;
    }

    public function control_render($setting, $field)
    {
        $defaults = $this->get_defaults($setting);
        ob_start();
        ?>
        <div class="<?= $setting; ?>__<?= $field ?>--controls">
            <button class="button button-primary" data-action="add">Add</button>
            <button class="button button-secondary" data-action="remove">Remove</button>
        </div>
		<?php
        $script_path = dirname(__FILE__, 2) . '/includes/fieldset-control-js.php';
        include $script_path;
        return ob_get_clean();
    }

    public function control_style($setting, $field)
    {
        return "<style>#{$setting}__{$field} td td,#{$setting}__{$field} td th{padding:0}#{$setting}__{$field} table table{margin-bottom:1rem}</style>";
    }

    public function option_getter($setting, $option)
    {
        $setting = get_option($setting) ? get_option($setting) : [];
        if (!key_exists($option, $setting)) {
            return null;
        }
        return $setting[$option];
    }

    public function get_defaults($setting_name, $field = null)
    {
        $defaults = isset($this->_defaults[$setting_name]) ? $this->_defaults[$setting_name] : [];
        $defaults = apply_filters($setting_name . '_defaults', $defaults);

        if ($field) {

        }

        return $defaults;
    }
}

function is_list($arr)
{
    if (!is_array($arr)) {
        return false;
    }
    if (sizeof($arr) === 0) {
        return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
}
