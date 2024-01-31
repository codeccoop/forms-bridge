<?php

namespace WPCT_ERP_FORMS;

class Undefined
{
};

abstract class BaseSettings extends Singleton
{

    protected $group_name;
    private $_defaults = [];

    abstract public function register();

    public function __construct($textdomain)
    {
        $this->group_name = $textdomain;
    }

    public function get_name()
    {
        return $this->group_name;
    }

    public function register_setting($name, $default = [])
    {
        $default = $this->get_default($name, $default);
        register_setting(
            $this->group_name,
            $name,
            [
                'type' => 'array',
                'show_in_rest' => false,
                'default' => $default,
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

        $this->_defaults[$name] = $default;
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

    public function field_render($setting, $field, $value = new Undefined())
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
        $default_value = $this->get_default($setting);
        $keys = explode('][', $field);
        for ($i = 0; $i < count($keys); $i++) {
            $default_value = isset($default_value[$keys[$i]]) ? $default_value[$keys[$i]] : $default_value[0];
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
            if (!$is_list) $fieldset .= "<th>{$key}</th>";
            $_field = $field . '][' . $key;
            $fieldset .= "<td>{$this->field_render($setting,$_field,$data[$key])}</td>";
            $fieldset .= '</tr>';
        }
        $fieldset .= '</table>';

        return $fieldset;
    }

    public function control_render($setting, $field)
    {
        $defaults = $this->get_default($setting);
        ob_start();
?>
        <div class="<?= $setting; ?>__<?= $field ?>--controls">
            <button class="button button-primary" data-action="add">Add</button>
            <button class="button button-secondary" data-action="remove">Remove</button>
        </div>
        <?php include 'fieldset-control-js.php' ?>
<?php
        return ob_get_clean();
    }

    public function control_style($setting, $field)
    {
        return "<style>#{$setting}__{$field} td td,#{$setting}__{$field} td th{padding:0}#{$setting}__{$field} table table{margin-bottom:1rem}</style>";
    }

    public function option_getter($setting, $option)
    {
        $setting = get_option($setting) ? get_option($setting) : [];
        if (!key_exists($option, $setting)) return null;
        return $setting[$option];
    }

    public function get_default($setting_name, $default = [])
    {
        $default = isset($this->_defaults[$setting_name]) ? $this->_defaults[$setting_name] : $default;
        return apply_filters($setting_name . '_default', $default);
    }
}

function is_list($arr)
{
    if (!is_array($arr)) return false;
    if (sizeof($arr) === 0) return true;
    return array_keys($arr) === range(0, count($arr) - 1);
}
