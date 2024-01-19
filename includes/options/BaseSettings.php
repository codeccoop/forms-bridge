<?php

namespace WPCT_ERP_FORMS\Options;

use Exception;

class Undefined
{
};

class BaseSettings
{

    public $_name;

    public function getName()
    {
        return $this->_name;
    }

    public function register()
    {
        throw new Exception('You have to overwrite this method');
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
        return "<input type='text' name='{$setting}[{$field}]' value='{$value}' />";
    }

    public function fieldset_render($setting, $field, $data)
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

    public function default_values()
    {
        throw new Exception('You have to overwrite this method');
    }

    public function control_render($setting, $field)
    {
        $values = $this->default_values();
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

    public function control_style($setting, $field)
    {
        return "<style>.{$setting}_{$field} td td, .{$setting}_{$field} td th{padding:0}.{$setting}_{$field} table table{margin-bottom:1rem}</style>";
    }

    public function option_getter($setting, $option)
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
