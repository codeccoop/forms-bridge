<?php

namespace WPCT_ERP_FORMS\GF\Fields\Iban;

use GF_Field;

class GFField extends GF_Field
{
    /**
     * @var array @type Country codes
     */
    private $_countries = [
        'al' => 28,
        'ad' => 24,
        'at' => 20,
        'az' => 28,
        'bh' => 22,
        'be' => 16,
        'ba' => 20,
        'br' => 29,
        'bg' => 22,
        'cr' => 21,
        'hr' => 21,
        'cy' => 28,
        'cz' => 24,
        'dk' => 18,
        'do' => 28,
        'ee' => 20,
        'fo' => 18,
        'fi' => 18,
        'fr' => 27,
        'ge' => 22,
        'de' => 22,
        'gi' => 23,
        'gr' => 27,
        'gl' => 18,
        'gt' => 28,
        'hu' => 28,
        'is' => 26,
        'ie' => 22,
        'il' => 23,
        'it' => 27,
        'jo' => 30,
        'kz' => 20,
        'kw' => 30,
        'lv' => 21,
        'lb' => 28,
        'li' => 21,
        'lt' => 20,
        'lu' => 20,
        'mk' => 19,
        'mt' => 31,
        'mr' => 27,
        'mu' => 30,
        'mc' => 27,
        'md' => 24,
        'me' => 22,
        'nl' => 18,
        'no' => 15,
        'pk' => 24,
        'ps' => 29,
        'pl' => 28,
        'pt' => 25,
        'qa' => 29,
        'ro' => 24,
        'sm' => 27,
        'sa' => 24,
        'rs' => 22,
        'sk' => 24,
        'si' => 19,
        'es' => 24,
        'se' => 24,
        'ch' => 21,
        'tn' => 24,
        'tr' => 26,
        'ae' => 23,
        'gb' => 22,
        'vg' => 24,
    ];

    /**
     * @var array $type Char codes
     */
    private $_chars = [
        'a' => 10,
        'b' => 11,
        'c' => 12,
        'd' => 13,
        'e' => 14,
        'f' => 15,
        'g' => 16,
        'h' => 17,
        'i' => 18,
        'j' => 19,
        'k' => 20,
        'l' => 21,
        'm' => 22,
        'n' => 23,
        'o' => 24,
        'p' => 25,
        'q' => 26,
        'r' => 27,
        's' => 28,
        't' => 29,
        'u' => 30,
        'v' => 31,
        'w' => 32,
        'x' => 33,
        'y' => 34,
        'z' => 35
    ];
    /**
     * @var string $type The field type.
     */
    public $type = 'iban-field';

    /**
     * Return the field title, for use in the form editor.
     *
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('IBAN');
    }

    /**
     * Assign the field button to the Advanced Fields group.
     *
     * @return array
     */
    public function get_form_editor_button()
    {
        return [
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title(),
        ];
    }

    /**
     * The settings which should be available on the field in the form editor.
     *
     * @return array
     */
    function get_form_editor_field_settings()
    {
        return [
            'conditional_logic_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'password_field_setting',
            'rules_setting',
            'visibility_setting',
            'duplicate_setting',
            'default_value_setting',
            'placeholder_setting',
            'description_setting',
            'css_class_setting',
        ];
    }

    /**
     * Enable this field for use with conditional logic.
     *
     * @return bool
     */
    public function is_conditional_logic_supported()
    {
        return true;
    }

    /**
     * Define the fields inner markup.
     *
     * @param array        $form The Form Object currently being processed.
     * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
     * @param null|array   $entry Null or the Entry Object currently being edited.
     *
     * @return string
     */
    public function get_field_input($form, $value = '', $entry = null)
    {
        $form_id = absint($form['id']);
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor  = $this->is_form_editor();

        $html_input_type = 'text';

        if ($this->enablePasswordInput && !$is_entry_detail) {
            $html_input_type = 'password';
        }

        $logic_event = !$is_form_editor && !$is_entry_detail ? $this->get_conditional_logic_event('keyup') : '';
        $id = (int)$this->id;
        $field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

        $value = esc_attr($value);
        $size = $this->size;
        $class_suffix = $is_entry_detail ? '_admin' : '';
        $class = $size . $class_suffix;

        $tabindex = $this->get_tabindex();
        $disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $required_attribute = $this->isRequired ? 'aria-required="true"' : '';
        $invalid_attribute = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

        $input = "<input name='input_{$id}' id='{$field_id}' type='{$html_input_type}' value='{$value}' class='{$class}' {$tabindex} {$logic_event} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text}/>";

        return sprintf("<div class='ginput_container ginput_container_text'>%s</div>", $input);
    }

    /**
     * Validate Field
     */
    public function validate($value, $form)
    {
        if (strlen($value) < 5) return false;
        $value = strtolower(str_replace(' ', '', $value));


        $country_exists = array_key_exists(substr($value, 0, 2), $this->_countries);
        $country_conform = strlen($value) == $this->_countries[substr($value, 0, 2)];

        try {
            if (!($country_exists && $country_conform)) throw new Exception();

            $moved_char = substr($value, 4) . substr($value, 0, 4);
            $move_char_array = str_split($moved_char);
            $new_string = '';

            foreach ($move_char_array as $key => $val) {
                if (!is_numeric($move_char_array[$key])) {
                    if (!isset($this->_chars[$val])) throw new Exception();
                    $move_char_array[$key] = $this->_chars[$val];
                }

                $new_string .= $move_char_array[$key];
            }

            if (bcmod($new_string, '97') != 1) {
                throw new Exception();
            }
        } catch (Exception) {
            $this->failed_validation = true;
            if (!empty($this->errorMessage)) {
                $this->validation_message = $this->errorMessage;
            } else {
                $this->validation_message = __('The IABN you inserted is not valid.');
            }
        }
    }
}
